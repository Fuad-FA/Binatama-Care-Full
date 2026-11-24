<?php

namespace Tests\Feature;

use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RekamMedisPortalTest extends TestCase
{
    use RefreshDatabase;

    private $guru; private $adi; private $budi; private $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->guru = User::create(['name' => 'g', 'username' => 'g', 'password' => Hash::make('x'), 'role' => 'guru',
            'is_active' => true, 'must_change_password' => false]);
        $this->adi  = Patient::create(['nama' => 'Adi',  'created_by' => $this->guru->id]);
        $this->budi = Patient::create(['nama' => 'Budi', 'created_by' => $this->guru->id]);
        $this->svc  = Service::where('harga', '>', 0)->first();
    }

    private function rekam(Patient $p): MedicalRecord
    {
        return MedicalRecord::create(['patient_id' => $p->id, 'user_id' => $this->guru->id,
            'tanggal_periksa' => now()->toDateString()]);
    }

    private function kirim(Patient $p, $medicalRecordId)
    {
        $this->actingAs($this->guru);
        return $this->post('/staff/transactions', [
            'patient_id' => $p->id, 'medical_record_id' => $medicalRecordId, 'diskon' => 0,
            'items' => [['type' => 'service', 'id' => $this->svc->id, 'qty' => 1]],
        ]);
    }

    public function test_rekam_medis_pasien_lain_ditolak(): void
    {
        $rekamBudi = $this->rekam($this->budi);
        $r = $this->kirim($this->adi, $rekamBudi->id);
        $r->assertSessionHasErrors('medical_record_id');
        $this->assertEquals(0, Transaction::count());
    }

    public function test_rekam_medis_pasien_sama_diterima(): void
    {
        $rekamAdi = $this->rekam($this->adi);
        $this->kirim($this->adi, $rekamAdi->id)->assertSessionHasNoErrors();
        $this->assertEquals($rekamAdi->id, Transaction::first()->medical_record_id);
    }

    public function test_tanpa_rekam_medis_tetap_diterima(): void
    {
        $this->kirim($this->adi, null)->assertSessionHasNoErrors();
        $this->assertEquals(1, Transaction::count());
    }

    public function test_portal_dibatasi_setelah_5_percobaan_salah(): void
    {
        RateLimiter::clear('portal-login:' . strtolower($this->adi->no_rm) . '|127.0.0.1');
        for ($i = 0; $i < 5; $i++) {
            $this->post('/portal/login', ['no_rm' => $this->adi->no_rm, 'kode_unik' => 'SALAH000'])
                ->assertSessionHas('error');
        }
        // Percobaan ke-6 ditolak walaupun kodenya benar
        $r = $this->post('/portal/login', ['no_rm' => $this->adi->no_rm, 'kode_unik' => $this->adi->kode_unik]);
        $r->assertSessionHas('error');
        $this->assertStringContainsString('Terlalu banyak percobaan', session('error'));
        $this->assertFalse(session()->has('patient_id'));
    }

    public function test_portal_login_benar_berhasil_dan_tidak_terkunci_permanen(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->post('/portal/login', ['no_rm' => $this->adi->no_rm, 'kode_unik' => 'SALAH000']);
        }
        $this->post('/portal/login', ['no_rm' => $this->adi->no_rm, 'kode_unik' => $this->adi->kode_unik])
            ->assertRedirect(route('patient.dashboard'));
        $this->assertEquals($this->adi->id, session('patient_id'));
    }

    public function test_hanya_admin_boleh_hapus_pasien(): void
    {
        $this->actingAs($this->guru)->delete('/staff/patients/' . $this->adi->id)->assertForbidden();
        $this->assertNotNull(Patient::find($this->adi->id));
    }
}
