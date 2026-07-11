<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpMail;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminPasswordController extends Controller
{
    // Helper: sensor email
    private function maskEmail(string $email): string
    {
        $parts  = explode('@', $email);
        $name   = $parts[0];
        $domain = $parts[1] ?? '';
        return substr($name, 0, 2)
            . str_repeat('*', max(strlen($name) - 2, 3))
            . '@' . $domain;
    }

    // ========================
    // STEP 1: Form password lama
    // ========================
    public function showStep1()
    {
        $emailOtp = config('app.admin_otp_email');

        if (!$emailOtp) {
            return view('admin.password.step1', ['masked' => 'belum dikonfigurasi']);
        }

        return view('admin.password.step1', [
            'masked' => $this->maskEmail($emailOtp),
        ]);
    }

    // ========================
    // STEP 1 POST: Verifikasi password lama → kirim OTP
    // ========================
    public function verifyOldPassword(Request $request)
    {
        $request->validate([
            'password_lama' => 'required|string',
        ], [
            'password_lama.required' => 'Password lama wajib diisi.',
        ]);

        $admin    = auth()->user();
        $emailOtp = config('app.admin_otp_email');

        if (!$emailOtp) {
            return back()->with('error',
                'Email OTP belum dikonfigurasi. Hubungi pengembang untuk mengatur ADMIN_OTP_EMAIL di file .env');
        }

        // Cek password lama
        if (!Hash::check($request->password_lama, $admin->password)) {
            return back()->with('error', 'Password lama tidak sesuai!');
        }

        // Generate OTP 6 digit
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan OTP ke database (di-hash untuk keamanan)
        $admin->update([
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        // Kirim OTP via email
        try {
            Mail::to($emailOtp)->send(new AdminOtpMail($otp, $admin->name));
        } catch (\Exception $e) {
            return back()->with('error',
                'Gagal mengirim OTP: ' . $e->getMessage() .
                ' — Periksa konfigurasi SMTP di file .env');
        }

        // Tandai step 1 selesai
        session(['otp_step1_done' => true]);

        return redirect()->route('admin.password.step2')
            ->with('success', 'Kode OTP berhasil dikirim ke ' . $this->maskEmail($emailOtp));
    }

    // ========================
    // STEP 2: Form input OTP
    // ========================
    public function showStep2()
    {
        if (!session('otp_step1_done')) {
            return redirect()->route('admin.password.step1')
                ->with('error', 'Sesi tidak valid. Mulai dari awal.');
        }

        $emailOtp = config('app.admin_otp_email');

        return view('admin.password.step2', [
            'masked' => $this->maskEmail($emailOtp),
        ]);
    }

    // ========================
    // STEP 2 POST: Verifikasi OTP
    // ========================
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.size'     => 'Kode OTP harus 6 digit.',
        ]);

        $admin = auth()->user();

        // Cek apakah OTP expired
        if (!$admin->otp_expires_at || now()->isAfter($admin->otp_expires_at)) {
            $admin->update(['otp_code' => null, 'otp_expires_at' => null]);
            session()->forget('otp_step1_done');
            return redirect()->route('admin.password.step1')
                ->with('error', 'Kode OTP sudah expired! Silakan mulai ulang.');
        }

        // Cek OTP cocok
        if (!Hash::check($request->otp, $admin->otp_code)) {
            return back()->with('error', 'Kode OTP tidak sesuai! Periksa kembali email Anda.');
        }

        // OTP valid → tandai session
        session(['otp_step2_done' => true]);
        session()->forget('otp_step1_done');

        return redirect()->route('admin.password.step3')
            ->with('success', 'Verifikasi berhasil! Silakan buat password baru.');
    }

    // ========================
    // STEP 3: Form password baru
    // ========================
    public function showStep3()
    {
        if (!session('otp_step2_done')) {
            return redirect()->route('admin.password.step1')
                ->with('error', 'Sesi tidak valid. Mulai dari awal.');
        }

        return view('admin.password.step3');
    }

    // ========================
    // STEP 3 POST: Simpan password baru
    // ========================
    public function updatePassword(Request $request)
    {
        if (!session('otp_step2_done')) {
            return redirect()->route('admin.password.step1')
                ->with('error', 'Sesi tidak valid. Mulai dari awal.');
        }

        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.required'  => 'Password baru wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $admin = auth()->user();

        // Pastikan tidak sama dengan password lama
        if (Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Password baru tidak boleh sama dengan password lama!');
        }

        // Simpan password baru + bersihkan OTP
        $admin->update([
            'password'       => Hash::make($request->password),
            'otp_code'       => null,
            'otp_expires_at' => null,
        ]);

        // Hapus semua session OTP
        session()->forget(['otp_step1_done', 'otp_step2_done']);

        // Catat activity log
        ActivityLog::create([
            'user_id'     => $admin->id,
            'action'      => 'change_password_admin',
            'description' => 'Admin mengganti password via OTP email',
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', '✅ Password berhasil diganti!');
    }

    // ========================
    // Kirim ulang OTP
    // ========================
    public function resendOtp()
    {
        if (!session('otp_step1_done')) {
            return redirect()->route('admin.password.step1');
        }

        $admin    = auth()->user();
        $emailOtp = config('app.admin_otp_email');

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $admin->update([
            'otp_code'       => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        try {
            Mail::to($emailOtp)->send(new AdminOtpMail($otp, $admin->name));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim ulang OTP: ' . $e->getMessage());
        }

        return back()->with('success', 'Kode OTP baru berhasil dikirim!');
    }
}