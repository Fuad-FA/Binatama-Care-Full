@extends('layouts.app')
@section('title', 'Tambah Layanan')
@section('page-title', 'Tambah Layanan')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-6">
<div class="card border-0 shadow-sm" style="border-radius:12px;">
    <div class="card-header fw-bold text-white py-3"
         style="background:var(--biru-tua);border-radius:12px 12px 0 0;">
        <i class="bi bi-plus-circle-fill me-2"></i>Form Tambah Layanan
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.services.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label class="form-label fw-semibold">Kode <span class="text-danger">*</span></label>
                <input type="text" name="kode"
                       class="form-control @error('kode') is-invalid @enderror"
                       value="{{ old('kode') }}" placeholder="SVC-019"
                       style="text-transform:uppercase;">
                @error('kode')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Nama Layanan <span class="text-danger">*</span></label>
                <input type="text" name="nama"
                       class="form-control @error('nama') is-invalid @enderror"
                       value="{{ old('nama') }}" placeholder="Nama layanan">
                @error('nama')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Harga <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text fw-semibold">Rp</span>
                    <input type="number" name="harga"
                           class="form-control @error('harga') is-invalid @enderror"
                           value="{{ old('harga', 0) }}" min="0" step="500">
                </div>
                <div class="form-text">Isi 0 jika layanan ini gratis atau sudah termasuk paket.</div>
                @error('harga')<div class="text-danger mt-1" style="font-size:12px;">{{ $message }}</div>@enderror
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Durasi (menit)</label>
                <input type="number" name="durasi_menit" class="form-control"
                       value="{{ old('durasi_menit') }}" min="1"
                       placeholder="Kosongkan jika tidak ada durasi pasti">
            </div>



            {{-- Builder Field Pemeriksaan --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-header py-3 fw-bold" style="background:#f8f9fa;border-radius:12px 12px 0 0;">
        <i class="bi bi-clipboard-data me-2" style="color:var(--biru-muda);"></i>
        Field Pemeriksaan (Opsional)
    </div>
    <div class="card-body p-3">
        <div class="alert py-2 mb-3" style="background:#e3f2fd;border-radius:8px;font-size:12px;">
            <i class="bi bi-info-circle-fill me-2"></i>
            Jika layanan ini butuh diisi hasil pemeriksaan (misal: Lingkar Pinggang, Saturasi Oksigen, dll),
            tambahkan field di bawah. Field ini akan otomatis muncul di form rekam medis dan wajib diisi
            saat layanan ini dipilih dalam transaksi.
        </div>

        <div id="field-rows-container"></div>

        <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="tambahFieldRow()">
            <i class="bi bi-plus-circle me-1"></i>Tambah Field
        </button>
    </div>
</div>

<script>
let fieldIndex = 0;

function tambahFieldRow(data = null) {
    const container = document.getElementById('field-rows-container');
    const idx = fieldIndex++;
    const row = document.createElement('div');
    row.className = 'field-row p-3 mb-2 rounded border';
    row.style.background = '#f8f9fa';
    row.innerHTML = `
        <div class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;">Label Field</label>
                <input type="text" name="fields[${idx}][label]" class="form-control form-control-sm"
                       placeholder="mis: Lingkar Pinggang" value="${data?.label ?? ''}" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:11px;">Satuan</label>
                <input type="text" name="fields[${idx}][unit]" class="form-control form-control-sm"
                       placeholder="cm" value="${data?.unit ?? ''}">
            </div>
            <div class="col-md-2">
                <label class="form-label" style="font-size:11px;">Tipe Input</label>
                <select name="fields[${idx}][input_type]" class="form-select form-select-sm">
                    <option value="number" ${data?.input_type === 'number' ? 'selected' : ''}>Angka</option>
                    <option value="text" ${data?.input_type === 'text' ? 'selected' : ''}>Teks Singkat</option>
                    <option value="textarea" ${data?.input_type === 'textarea' ? 'selected' : ''}>Catatan Panjang</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" style="font-size:11px;">Info Normal (opsional)</label>
                <input type="text" name="fields[${idx}][normal_text]" class="form-control form-control-sm"
                       placeholder="mis: Normal 60-100" value="${data?.normal_text ?? ''}">
            </div>
            <div class="col-md-1">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="fields[${idx}][is_required]"
                           value="1" ${(data?.is_required ?? true) ? 'checked' : ''} id="req-${idx}">
                    <label class="form-check-label" for="req-${idx}" style="font-size:10px;">Wajib</label>
                </div>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-outline-danger"
                        onclick="this.closest('.field-row').remove()">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(row);
}

// Prefill saat edit (kosong jika create baru)
const existingFields = {!! json_encode($service->fields ?? []) !!};
existingFields.forEach(f => tambahFieldRow(f));
</script>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle me-2"></i>Simpan
                </button>
                <a href="{{ route('admin.services.index') }}"
                   class="btn btn-outline-secondary px-4">
                    <i class="bi bi-arrow-left me-2"></i>Batal
                </a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection