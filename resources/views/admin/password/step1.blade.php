@extends('layouts.app')
@section('title', 'Ganti Password Admin')
@section('page-title', 'Ganti Password Admin')

@section('content')
<div class="row justify-content-center">
<div class="col-md-6">

    {{-- Progress Bar --}}
    <div class="d-flex align-items-center gap-2 mb-4">
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width:36px;height:36px;background:var(--biru-tua);flex-shrink:0;">1</div>
        <div style="flex:1;height:3px;background:#e0e0e0;"></div>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
             style="width:36px;height:36px;background:#e0e0e0;color:#888;flex-shrink:0;">2</div>
        <div style="flex:1;height:3px;background:#e0e0e0;"></div>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
             style="width:36px;height:36px;background:#e0e0e0;color:#888;flex-shrink:0;">3</div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-header py-3 text-white fw-bold"
             style="background:var(--biru-tua);border-radius:16px 16px 0 0;">
            <i class="bi bi-shield-lock-fill me-2"></i>
            Langkah 1 — Verifikasi Password Lama
        </div>
        <div class="card-body p-4">

            <div class="alert py-2 mb-4"
                 style="background:#e3f2fd;border-left:3px solid var(--biru-muda);
                        border-radius:8px;font-size:13px;">
                <i class="bi bi-info-circle-fill me-2"></i>
                Masukkan <strong>password saat ini</strong> untuk memulai proses
                ganti password. Kode OTP akan dikirim ke email yang sudah
                dikonfigurasi dalam sistem.
            </div>

            @if(session('error'))
            <div class="alert alert-danger py-2 mb-3"
                 style="border-radius:8px;font-size:13px;">
                <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            </div>
            @endif

            <form action="{{ route('admin.password.verify-old') }}" method="POST">
                @csrf

                {{-- Password Lama --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Password Saat Ini <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password_lama" id="pwd-lama"
                               class="form-control @error('password_lama') is-invalid @enderror"
                               placeholder="Masukkan password saat ini"
                               autofocus>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePwd('pwd-lama', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password_lama')
                        <div class="text-danger mt-1" style="font-size:12px;">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                {{-- Info email tujuan OTP --}}
                <div class="p-3 rounded mb-4"
                     style="background:#f0f7ff;border:1px solid #bbdefb;font-size:13px;">
                    <i class="bi bi-envelope-fill me-2"
                       style="color:var(--biru-muda);"></i>
                    OTP akan dikirim ke:
                    <strong>{{ $masked }}</strong>
                    <div class="text-muted mt-1" style="font-size:11px;">
                        Email ini dikonfigurasi dalam sistem.
                        Hubungi pengembang jika ingin mengubahnya.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-semibold py-2">
                    <i class="bi bi-send me-2"></i>Kirim Kode OTP
                </button>
            </form>
        </div>
    </div>

    <div class="text-center mt-3">
        <a href="{{ route('admin.dashboard') }}"
           class="text-muted" style="font-size:13px;">
            <i class="bi bi-arrow-left me-1"></i>Batal, kembali ke dashboard
        </a>
    </div>
</div>
</div>

@push('scripts')
<script>
function togglePwd(id, btn) {
    const el = document.getElementById(id);
    const isText = el.type === 'text';
    el.type = isText ? 'password' : 'text';
    btn.innerHTML = isText
        ? '<i class="bi bi-eye"></i>'
        : '<i class="bi bi-eye-slash"></i>';
}
</script>
@endpush
@endsection