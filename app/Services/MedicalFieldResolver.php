<?php

namespace App\Services;

use App\Models\Package;
use App\Models\ServiceField;

class MedicalFieldResolver
{
    private static array $builtInMapping = [
        'gula_darah'    => ['cek gula darah'],
        'kolesterol'    => ['cek kolesterol'],
        'asam_urat'     => ['cek asam urat'],
        'tensi'         => ['cek tekanan darah'],
        'suhu'          => ['cek suhu'],
        'nadi'          => ['cek nadi'],
        'respirasi'     => ['cek respirasi'],
        'bmi'           => ['cek bmi'],
        'antropometri'  => ['cek antropometri'],
        'catatan_gizi'  => ['konsultasi gizi'],
    ];

    private static array $paketMapping = [
        'paket sehat 1' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 2' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 3' => ['tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 4' => ['gula_darah', 'kolesterol', 'asam_urat',
                            'tensi', 'suhu', 'nadi', 'respirasi'],
        'paket sehat 5' => ['tensi', 'suhu', 'nadi', 'respirasi'],
    ];

    public static function getBuiltInFields(array $itemNames): array
    {
        $fields = [];

        foreach ($itemNames as $nama) {
            $namaLower = strtolower(trim($nama));

            foreach (self::$paketMapping as $paket => $paketFields) {
                if (str_contains($namaLower, $paket)) {
                    $fields = array_merge($fields, $paketFields);
                    continue 2;
                }
            }

            foreach (self::$builtInMapping as $field => $keywords) {
                foreach ($keywords as $kw) {
                    if ($namaLower === $kw || str_contains($namaLower, $kw)) {
                        $fields[] = $field;
                        break;
                    }
                }
            }
        }

        return array_unique($fields);
    }

    /**
     * Memproses data item transaksi untuk mengambil custom fields
     * $transactionItems = collection dari model TransactionItem
     */
    public static function getCustomFields($transactionItems)
    {
        $serviceIds = [];

        foreach ($transactionItems as $item) {
            // PERBAIKAN: Menggunakan properti database yang benar 'item_type'
            if ($item->item_type === 'service') {
                $serviceIds[] = $item->item_id;
            } elseif ($item->item_type === 'package') {
                $package = Package::with('services')->find($item->item_id);
                if ($package) {
                    foreach ($package->services as $svc) {
                        $serviceIds[] = $svc->id;
                    }
                }
            }
        }

        $serviceIds = array_unique($serviceIds);

        if (empty($serviceIds)) {
            return collect();
        }

        return ServiceField::whereIn('service_id', $serviceIds)
            ->with('service')
            ->orderBy('sort_order')
            ->get();
    }
}