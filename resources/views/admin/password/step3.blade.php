@extends('layouts.app')
@section('title', 'Password Baru')
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
             style="width:36px;height:36px;background:var(--hijau);flex-shrink:0;">
            <i class="bi bi-check-lg"></i>
        </div>
        <div style="flex:1;height:3px;background:var(--hijau);"></div>
        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
             style="width:36px;height:36px;background:var(--biru-tua);flex-shrink:0;">3</div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-header py-3 text-white fw-bold"
             style="background:var(--biru-tua);border-radius:16px 16px 0 0;">
            <i class="bi bi-key-fill me-2"></i>
            Langkah 3 — Buat Password Baru
        </div>
        <div class="card-body p-4">

            <div class="alert alert-success py-2 mb-4"
                 style="border-radius:8px;font-size:13px;">
                <i class="bi bi-shield-check-fill me-2"></i>
                Identitas terverifikasi. Silakan buat password baru Anda.
            </div>

            @if(session('error'))
            <div class="alert alert-danger py-2 mb-3"
                 style="border-radius:8px;font-size:13px;">
                <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
            </div>
            @endif

            <form action="{{ route('admin.password.update') }}" method="POST">
                @csrf

                {{-- Password Baru --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Password Baru <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password" id="new-pwd"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Minimal 8 karakter"
                               oninput="checkStrength(this.value)"
                               autofocus>
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePwd('new-pwd', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-danger mt-1" style="font-size:12px;">
                            {{ $message }}
                        </div>
                    @enderror
                    {{-- Strength bar --}}
                    <div style="height:4px;background:#e0e0e0;border-radius:2px;margin-top:6px;">
                        <div id="strength-bar"
                             style="height:100%;width:0%;border-radius:2px;
                                    transition:all 0.3s;background:#e0e0e0;">
                        </div>
                    </div>
                    <div id="strength-text" class="mt-1" style="font-size:11px;"></div>
                </div>

                {{-- Konfirmasi Password --}}
                <div class="mb-4">
                    <label class="form-label fw-semibold" style="font-size:13px;">
                        Konfirmasi Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input type="password" name="password_confirmation" id="conf-pwd"
                               class="form-control"
                               placeholder="Ulangi password baru"
                               oninput="checkMatch()">
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="togglePwd('conf-pwd', this)">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div id="match-status" class="mt-1" style="font-size:12px;"></div>
                </div>

                <button type="submit" class="btn btn-success w-100 fw-semibold py-2">
                    <i class="bi bi-check-circle me-2"></i>Simpan Password Baru
                </button>
            </form>
        </div>
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

function checkStrength(val) {
    const bar  = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    let score  = 0;
    if (val.length >= 8)  score++;
    if (val.length >= 12) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const levels = [
        { w:'20%', c:'#c62828', l:'Sangat Lemah' },
        { w:'40%', c:'#e53935', l:'Lemah' },
        { w:'60%', c:'#F57C00', l:'Cukup' },
        { w:'80%', c:'#43A047', l:'Kuat' },
        { w:'100%', c:'#2E7D32', l:'Sangat Kuat' },
    ];
    if (!val.length) {
        bar.style.width = '0'; text.textContent = ''; return;
    }
    const lvl = levels[Math.min(score - 1, 4)] || levels[0];
    bar.style.width      = lvl.w;
    bar.style.background = lvl.c;
    text.textContent     = lvl.l;
    text.style.color     = lvl.c;
}

function checkMatch() {
    const pwd    = document.getElementById('new-pwd').value;
    const conf   = document.getElementById('conf-pwd').value;
    const status = document.getElementById('match-status');
    if (!conf) { status.textContent = ''; return; }
    if (pwd === conf) {
        status.textContent = '✓ Password cocok';
        status.style.color = '#2E7D32';
    } else {
        status.textContent = '✗ Password tidak cocok';
        status.style.color = '#c62828';
    }
}
</script>
@endpush
@endsection