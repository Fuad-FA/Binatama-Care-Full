<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TampilanTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_404_menampilkan_tombol_pasien_guru_siswa_tanpa_admin(): void
    {
        $r = $this->get('/');
        $r->assertStatus(404);
        $r->assertSee(route('landing.pasien'), false);
        $r->assertSee(route('landing.guru'), false);
        $r->assertSee(route('landing.siswa'), false);
        $r->assertDontSee(route('landing.admin'), false);
        $r->assertDontSee(route('login.admin'), false);
    }

    public function test_pagination_produk_memakai_bootstrap_dan_bahasa_indonesia(): void
    {
        $this->seed();
        $admin = User::where('role', 'admin')->first();
        $r = $this->actingAs($admin)->get('/admin/products');
        $r->assertOk();
        $r->assertSee('class="pagination"', false);
        $r->assertSee('Menampilkan');
        $r->assertDontSee('Showing');
    }
}
