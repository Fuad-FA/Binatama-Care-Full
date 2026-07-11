@extends('layouts.app')
@section('title', 'Verifikasi OTP')
@section('page-title', 'Ganti Password Admin')

@section('content')
<div class="row justify-content-center">
<div class="col-md-6">

    {{-- Progress Bar --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width:36px;height:36px;background:var(--hijau);flex-shrink:0;">
            <i class="bi bi-check-lg"></i>
        </div>
        <div style="flex:1;height:3px;background:var(--hijau);"></div>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width:36px;height:36px;background:var(--biru-tua);flex-shrink:0;">2</div>
        <div style="flex:1;height:3px;background:#e0e0e0;"></div>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
             style="width:36px;height:36px;background:#e0e0e0;color:#888;flex-shrink:0;">3</div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-header py-3 text-white fw-bold"
             style="background:var(--biru-tua);border-radius:16px 16px 0 0;">
            <i class="bi bi-envelope-check-fill me-2"></i>
            Langkah 2 — Masukkan Kode OTP
        </div>
        <div class="card-body p-4">

            @if(session('success'))
            <div class="alert alert-success py-2 mb-3"
                 style="border-radius:8px;font-size:13px;">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger py-2 mb-3"
                 style="border-radius:8px;font-size:13px;">
                <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            </div>
            @endif

            {{-- Icon & info --}}
            <div class="text-center mb-4">
                <div style="width:64px;height:64px;background:#e3f2fd;border-radius:50%;
                            display:flex;align-items:center;justify-content:center;
                            margin:0 auto 12px;">
                    <i class="bi bi-envelope-open-fill fs-3"
                       style="color:var(--biru-muda);"></i>
                </div>
                <div class="fw-semibold mb-1">Cek email Anda!</div>
                <div class="text-muted" style="font-size:13px;">
                    Kode OTP 6 digit telah dikirim ke<br>
                    <strong>{{ $masked }}</strong>
                </div>
            </div>

            <form action="{{ route('admin.password.verify-otp') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="form-label fw-semibold text-center d-block"
                           style="font-size:13px;">
                        Kode OTP
                    </label>
                    <input type="text" name="otp" id="otp-input"
                           class="form-control text-center fw-bold
                                  @error('otp') is-invalid @enderror"
                           style="font-size:32px;letter-spacing:12px;height:70px;"
                           maxlength="6"
                           placeholder="------"
                           autocomplete="off"
                           inputmode="numeric"
                           oninput="this.value=this.value.replace(/[^0-9]/g,'')">
                    @error('otp')
                        <div class="invalid-feedback text-center">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2 mb-3">
                    <i class="bi bi-check-circle me-2"></i>Verifikasi OTP
                </button>
            </form>

            <div class="text-center">
                <form action="{{ route('admin.password.resend') }}"
                      method="POST" class="d-inline">
                    @csrf
                    <button type="submit"
                            class="btn btn-link text-muted p-0"
                            style="font-size:13px;">
                        <i class="bi bi-arrow-clockwise me-1"></i>Kirim ulang OTP
                    </button>
                </form>
                <span class="text-muted mx-2">·</span>
                <a href="{{ route('admin.password.step1') }}"
                   class="text-muted" style="font-size:13px;">
                    Mulai dari awal
                </a>
            </div>

            {{-- Countdown --}}
            <div class="text-center mt-3">
                <span class="text-muted" style="font-size:12px;">
                    OTP berlaku:
                    <strong id="countdown"
                            style="color:var(--orange);">10:00</strong>
                </span>
            </div>
        </div>
    </div>
</div>
</div>

@push('scripts')
<script>
// Auto-focus
document.getElementById('otp-input').focus();

// Auto-submit saat 6 digit terisi
document.getElementById('otp-input').addEventListener('input', function() {
    if (this.value.length === 6) {
        this.closest('form').submit();
    }
});

// Countdown 10 menit
let total = 10 * 60;
const el  = document.getElementById('countdown');
const tmr = setInterval(() => {
    total--;
    const m = String(Math.floor(total / 60)).padStart(2, '0');
    const s = String(total % 60).padStart(2, '0');
    el.textContent = `${m}:${s}`;
    if (total <= 60) el.style.color = '#c62828';
    if (total <= 0) {
        clearInterval(tmr);
        el.textContent = 'EXPIRED';
    }
}, 1000);
</script>
@endpush
@endsection