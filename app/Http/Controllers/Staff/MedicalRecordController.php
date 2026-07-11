<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MedicalRecord;
use App\Models\Patient;
use Illuminate\Http\Request;

class MedicalRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = MedicalRecord::with(['patient', 'user'])
            ->where('user_id', auth()->id());

        if ($request->filled('search')) {
            $query->whereHas('patient', function ($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                  ->orWhere('no_rm', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal_periksa', $request->tanggal);
        }

        $records = $query->orderByDesc('tanggal_periksa')
                         ->orderByDesc('created_at')
                         ->paginate(15);

        return view('staff.medical.index', compact('records'));
    }

    public function create(Request $request)
    {
        $patient      = Patient::findOrFail($request->patient_id);
        $transaction  = null;
        $activeFields = [];
        $customFields = collect();

        if ($request->filled('transaction_id')) {
            $transaction = \App\Models\Transaction::with('items')
                ->find($request->transaction_id);

            if ($transaction) {
                $itemNames = $transaction->items
                    ->pluck('nama_item')
                    ->toArray();

                $activeFields = \App\Services\MedicalFieldResolver::getBuiltInFields($itemNames);
                $customFields = \App\Services\MedicalFieldResolver::getCustomFields($transaction->items);
            }
        } elseif ($request->filled('fields')) {
            $activeFields = explode(',', $request->fields);
        }

        return view('staff.medical.create', compact(
            'patient',
            'transaction',
            'activeFields',
            'customFields'
        ));
    }

    public function store(Request $request)
    {
        // 1. Validasi mutlak untuk Patient ID & Tanggal Periksa bawaan Laravel Laravel
        $request->validate([
            'patient_id'      => 'required|exists:patients,id',
            'tanggal_periksa' => 'required|date',
        ], [
            'patient_id.required'      => 'Pasien wajib dipilih.',
            'tanggal_periksa.required' => 'Tanggal periksa wajib diisi.',
        ]);

        // Wadah pengumpul nama field yang kosong untuk dijadikan alert merah
        $semuaFieldKosong = [];

        // 2. Deteksi field statis bawaan yang aktif dari transaksi atau parameter URL
        $activeFields = [];
        if ($request->filled('transaction_id')) {
            $transaction = \App\Models\Transaction::with('items')->find($request->transaction_id);
            if ($transaction) {
                $itemNames = $transaction->items->pluck('nama_item')->toArray();
                $activeFields = \App\Services\MedicalFieldResolver::getBuiltInFields($itemNames);
            }
        } elseif ($request->filled('fields')) {
            $activeFields = explode(',', $request->fields);
        }

        // Mapping label human-readable untuk field statis bawaan agar alert informatif
        $labelStatis = [
            'gula_darah'      => 'Gula Darah',
            'kolesterol'      => 'Kolesterol',
            'asam_urat'       => 'Asam Urat',
            'suhu'            => 'Suhu Tubuh',
            'nadi'            => 'Nadi',
            'respirasi'       => 'Respirasi',
            'catatan_gizi'    => 'Catatan Konsultasi Gizi',
            'catatan'         => 'Catatan/Keterangan',
        ];

        // 3. Cek manual field statis bawaan yang AKTIF, jika kosong masukkan ke list alert merah
        foreach ($activeFields as $field) {
            if ($field === 'tensi') {
                if (!$request->filled('tensi_sistolik')) $semuaFieldKosong[] = 'Tensi Sistolik';
                if (!$request->filled('tensi_diastolik')) $semuaFieldKosong[] = 'Tensi Diastolik';
            } elseif ($field === 'bmi' || $field === 'antropometri') {
                if (!$request->filled('berat_badan')) $semuaFieldKosong[] = 'Berat Badan';
                if (!$request->filled('tinggi_badan')) $semuaFieldKosong[] = 'Tinggi Badan';
            } elseif (array_key_exists($field, $labelStatis)) {
                if (!$request->filled($field)) {
                    $semuaFieldKosong[] = $labelStatis[$field];
                }
            }
        }

        // 4. Cek manual field kustom tambahan dari Admin, jika kosong masukkan ke list alert merah
        $customFieldIds = $request->input('custom_field_ids', []);
        $customData   = [];

        if (!empty($customFieldIds)) {
            $customFieldDefs = \App\Models\ServiceField::whereIn('id', $customFieldIds)->get();

            foreach ($customFieldDefs as $def) {
                $inputName = 'custom_' . $def->id;

                if ($def->is_required && !$request->filled($inputName)) {
                    $semuaFieldKosong[] = $def->label;
                    continue;
                }

                if ($request->filled($inputName)) {
                    $customData[$def->field_key] = [
                        'label' => $def->label,
                        'unit'  => $def->unit,
                        'value' => $request->input($inputName),
                    ];
                }
            }
        }

        // =========================================================================
        // JIKA ADA FIELD YANG KOSONG (STATIS MAUPUN KUSTOM), TENDANG BALIK DENGAN ALERT MERAH
        // =========================================================================
        if (!empty($semuaFieldKosong)) {
            return back()
                ->withInput()
                ->with('error_kosong', true)
                ->with('error', 'Field berikut wajib diisi: ' . implode(', ', $semuaFieldKosong) . '. Jika hasilnya nol, masukkan angka 0.');
        }

        // 5. Validasi Tipe Data & Range Nilai (Hanya dieksekusi jika data terisi untuk menjaga validitas DB)
        $rulesTipeData = [
            'gula_darah'      => 'nullable|numeric|min:0|max:9999',
            'kolesterol'      => 'nullable|numeric|min:0|max:9999',
            'asam_urat'       => 'nullable|numeric|min:0|max:99',
            'tensi_sistolik'  => 'nullable|integer|min:0|max:999',
            'tensi_diastolik' => 'nullable|integer|min:0|max:999',
            'suhu'            => 'nullable|numeric|min:30|max:45',
            'nadi'            => 'nullable|integer|min:0|max:300',
            'respirasi'       => 'nullable|integer|min:0|max:100',
            'berat_badan'     => 'nullable|numeric|min:1|max:500',
            'tinggi_badan'    => 'nullable|numeric|min:1|max:300',
        ];
        $request->validate($rulesTipeData);

        // Hitung BMI otomatis jika berat & tinggi diisi
        $bmi = null;
        if ($request->filled('berat_badan') && $request->filled('tinggi_badan')) {
            $tinggiM = $request->tinggi_badan / 100;
            $bmi     = round($request->berat_badan / ($tinggiM * $tinggiM), 2);
        }

        // 6. Simpan Rekam Medis Utama ke Database
        $record = MedicalRecord::create([
            'patient_id'      => $request->patient_id,
            'user_id'         => auth()->id(),
            'tanggal_periksa' => $request->tanggal_periksa,
            'gula_darah'      => $request->gula_darah,
            'kolesterol'      => $request->kolesterol,
            'asam_urat'       => $request->asam_urat,
            'tensi_sistolik'  => $request->tensi_sistolik,
            'tensi_diastolik' => $request->tensi_diastolik,
            'suhu'            => $request->suhu,
            'nadi'            => $request->nadi,
            'respirasi'       => $request->respirasi,
            'berat_badan'     => $request->berat_badan,
            'tinggi_badan'    => $request->tinggi_badan,
            'bmi'             => $bmi,
            'catatan_gizi'    => $request->catatan_gizi,
            'catatan'         => $request->catatan,
            'custom_data'     => !empty($customData) ? $customData : null,
        ]);

        // Catat Log Aktivitas
        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'create_medical_record',
            'description' => 'Input rekam medis pasien: ' . $record->patient->nama,
            'ip_address'  => $request->ip(),
        ]);

        // Hubungkan rekam medis ke transaksi jika ada
        if ($request->filled('transaction_id')) {
            \App\Models\Transaction::where('id', $request->transaction_id)
                ->update(['medical_record_id' => $record->id]);
            session()->forget('pending_medical');

            return redirect()
                ->route('staff.transactions.show', $request->transaction_id)
                ->with('success', 'Hasil pemeriksaan berhasil disimpan! Silakan cetak nota.');
        }

        return redirect()->route('staff.patients.show', $record->patient_id)
            ->with('success', 'Hasil pemeriksaan berhasil disimpan!');
    }

    public function show(MedicalRecord $medicalRecord)
    {
        $medicalRecord->load(['patient', 'user']);
        return view('staff.medical.show', compact('medicalRecord'));
    }

    public function destroy(MedicalRecord $medicalRecord)
    {
        abort(403, 'Rekam medis tidak dapat dihapus.');
    }
}