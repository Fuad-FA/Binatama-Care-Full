<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;

class PortalController extends Controller
{
    // Halaman login pasien
    public function showLogin()
    {
        if (Session::has('patient_id')) {
            return redirect()->route('patient.dashboard');
        }
        return view('patient.login');
    }

    // Proses login pasien
    public function login(Request $request)
    {
        $request->validate([
            'no_rm'     => 'required|string',
            'kode_unik' => 'required|string',
        ], [
            'no_rm.required'     => 'No. Rekam Medis wajib diisi.',
            'kode_unik.required' => 'Kode unik wajib diisi.',
        ]);

        // Throttle: dibatasi per kombinasi No. RM + IP dan per IP, tanpa mengunci akun pasien
        $keyRm = 'portal-login:' . strtolower($request->no_rm) . '|' . $request->ip();
        $keyIp = 'portal-login-ip:' . $request->ip();
        foreach ([[$keyRm, 5], [$keyIp, 20]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $detik = RateLimiter::availableIn($key);
                return back()->withInput($request->only('no_rm'))
                    ->with('error', "Terlalu banyak percobaan. Coba lagi dalam {$detik} detik.");
            }
        }

        $patient = Patient::where('no_rm', strtoupper($request->no_rm))
            ->where('kode_unik', strtoupper($request->kode_unik))
            ->first();

        if (!$patient) {
            RateLimiter::hit($keyRm, 60);
            RateLimiter::hit($keyIp, 60);
            return back()->withInput($request->only('no_rm'))
                ->with('error', 'No. RM atau Kode Unik salah. Cek kembali nota pemeriksaan Anda.');
        }

        RateLimiter::clear($keyRm);

        Session::put('patient_id', $patient->id);
        Session::put('patient_name', $patient->nama);

        return redirect()->route('patient.dashboard');
    }

    // Logout pasien
    public function logout()
    {
        Session::forget(['patient_id', 'patient_name']);
        return redirect()->route('patient.login')
            ->with('success', 'Anda berhasil keluar.');
    }

    // Dashboard pasien

public function dashboard(Request $request)
{
    $patient = Patient::findOrFail(session('patient_id'));

    $latestRecord = $patient->medicalRecords()
        ->orderByDesc('tanggal_periksa')
        ->orderByDesc('created_at')
        ->first();

    $totalVisit = $patient->medicalRecords()->count();

    return view('patient.dashboard', compact('patient', 'latestRecord', 'totalVisit'));
}
    // Semua riwayat pemeriksaan

    public function records(Request $request)
{
    $patient = Patient::findOrFail(session('patient_id'));

    $records = $patient->medicalRecords()
        ->with('user')
        ->orderByDesc('tanggal_periksa')
        ->orderByDesc('created_at')
        ->get();

    return view('patient.records', compact('patient', 'records'));
}
}