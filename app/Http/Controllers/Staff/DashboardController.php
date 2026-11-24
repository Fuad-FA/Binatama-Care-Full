<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MedicalRecord;
use App\Models\Patient;
use App\Models\Transaction;

class DashboardController extends Controller
{

    public function index()
    {
        $userId = auth()->id();
        $today  = now()->toDateString();
        $bulan  = now()->month;
        $tahun  = now()->year;

        $stats = [
            'input_pasien'       => Patient::where('created_by', $userId)
                ->whereMonth('created_at', $bulan)->whereYear('created_at', $tahun)->count(),
            'periksa_hari_ini'   => MedicalRecord::where('user_id', $userId)
                ->whereDate('created_at', $today)->count(),
            'transaksi_hari_ini' => Transaction::where('user_id', $userId)
                ->whereDate('created_at', $today)->count(),
            'rekam_bulan_ini'    => MedicalRecord::where('user_id', $userId)
                ->whereMonth('created_at', $bulan)->whereYear('created_at', $tahun)->count(),
            'transaksi_bulan_ini'=> Transaction::where('user_id', $userId)
                ->whereMonth('created_at', $bulan)->whereYear('created_at', $tahun)->count(),
        ];

        // Cek transaksi yang belum ada rekam medisnya milik staf ini
        $pendingTransactions = Transaction::where('user_id', $userId)
            ->whereNull('medical_record_id')
            ->where('status', 'selesai')
            ->with('patient')
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($trx) {
    $trx->loadMissing('items');

    return $trx->needsMedicalRecord();
});

        $recentRecords = MedicalRecord::with('patient')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')->limit(5)->get();

        $recentTransactions = Transaction::with('patient')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')->limit(5)->get();

        return view('staff.dashboard', compact(
            'stats', 'recentRecords', 'recentTransactions', 'pendingTransactions'
        ));
    }

private function getFieldsFromItems(array $itemNames): array
{
    $fields = [];

    $mapping = [
        'gula_darah'    => ['cek gula darah'],
        'kolesterol'    => ['cek kolesterol'],
        'asam_urat'     => ['cek asam urat'],
        'tensi'         => ['cek tekanan darah'],
        'suhu'          => ['cek suhu'],
        'nadi'          => ['cek nadi'],
        'respirasi'     => ['cek respirasi'],
        'bmi'           => ['cek bmi', 'cek antropometri'],
        'catatan_gizi'  => ['konsultasi gizi'],
    ];

    $paketMapping = [
        'paket sehat 1' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 2' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 3' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 4' => ['gula_darah', 'kolesterol', 'asam_urat',
                            'tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 5' => ['tensi', 'suhu', 'nadi', 'respirasi'],
    ];

    foreach ($itemNames as $nama) {
        $namaLower = strtolower(trim($nama));

        foreach ($paketMapping as $paket => $paketFields) {
            if (str_contains($namaLower, $paket)) {
                $fields = array_merge($fields, $paketFields);
                continue 2;
            }
        }

        foreach ($mapping as $field => $keywords) {
            foreach ($keywords as $kw) {
                if ($namaLower === $kw) {
                    $fields[] = $field;
                    break;
                }
            }
        }
    }

    return array_unique($fields);
}
}