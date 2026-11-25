<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HargaStokTest extends TestCase
{
    use RefreshDatabase;

    private $guru; private $pas; private $svc; private $prod;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->guru = User::create(['name' => 'g', 'username' => 'g', 'password' => Hash::make('x'), 'role' => 'guru',
            'is_active' => true, 'must_change_password' => false]);
        $this->pas = Patient::create(['nama' => 'Uji', 'created_by' => $this->guru->id]);
        $this->svc = Service::where('harga', '>', 0)->first();
        $this->prod = Product::where('harga_by_order', false)->where('harga', '>', 0)->first();
        $this->prod->update(['stok' => 5, 'is_active' => true]);
        $this->actingAs($this->guru);
    }

    private function kirim(array $items)
    {
        return $this->post('/staff/transactions', ['patient_id' => $this->pas->id, 'diskon' => 0, 'items' => $items]);
    }

    public function test_harga_palsu_diabaikan_layanan_dan_produk(): void
    {
        $this->kirim([
            ['type' => 'service', 'id' => $this->svc->id, 'qty' => 1, 'harga' => 1],
            ['type' => 'product', 'id' => $this->prod->id, 'qty' => 2, 'harga' => 1],
        ]);
        $t = Transaction::latest('id')->first();
        $this->assertNotNull($t);
        $this->assertEquals((float)$this->svc->harga + 2 * (float)$this->prod->harga, (float)$t->total);
        $this->assertEquals(3, Product::find($this->prod->id)->stok);
    }

    public function test_stok_kurang_ditolak_dan_stok_tidak_berubah(): void
    {
        $r = $this->kirim([['type' => 'product', 'id' => $this->prod->id, 'qty' => 6, 'harga' => 1]]);
        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(5, Product::find($this->prod->id)->stok);
        $r->assertSessionHas('error');
        
    }

    public function test_produk_sama_di_dua_baris_dijumlahkan(): void
    {
        $this->kirim([
            ['type' => 'product', 'id' => $this->prod->id, 'qty' => 3, 'harga' => 1],
            ['type' => 'product', 'id' => $this->prod->id, 'qty' => 3, 'harga' => 1],
        ]);
        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(5, Product::find($this->prod->id)->stok);
    }

    public function test_stok_pas_diterima_dan_jadi_nol(): void
    {
        $this->kirim([['type' => 'product', 'id' => $this->prod->id, 'qty' => 5, 'harga' => 1]]);
        $this->assertEquals(1, Transaction::count());
        $this->assertEquals(0, Product::find($this->prod->id)->stok);
    }

    public function test_layanan_nonaktif_dan_produk_by_order_ditolak(): void
    {
        $this->svc->update(['is_active' => false]);
        $this->kirim([['type' => 'service', 'id' => $this->svc->id, 'qty' => 1, 'harga' => 1]]);
        $this->assertEquals(0, Transaction::count());

        $this->svc->update(['is_active' => true]);
        $this->prod->update(['harga_by_order' => true]);
        $this->kirim([['type' => 'product', 'id' => $this->prod->id, 'qty' => 1, 'harga' => 1]]);
        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(5, Product::find($this->prod->id)->stok);
    }

    public function test_gagal_di_item_kedua_membatalkan_pengurangan_stok_item_pertama(): void
    {
        $this->kirim([
            ['type' => 'product', 'id' => $this->prod->id, 'qty' => 2, 'harga' => 1],
            ['type' => 'service', 'id' => 99999, 'qty' => 1, 'harga' => 1],
        ]);
        $this->assertEquals(0, Transaction::count());
        $this->assertEquals(5, Product::find($this->prod->id)->stok);
    }
}
