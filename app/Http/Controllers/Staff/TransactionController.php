<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\MedicalRecord;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Service;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['patient', 'user', 'items'])
            ->where('user_id', auth()->id());

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('no_transaksi', 'like', '%' . $request->search . '%')
                  ->orWhereHas('patient', function ($p) use ($request) {
                      $p->where('nama', 'like', '%' . $request->search . '%')
                        ->orWhere('no_rm', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->tanggal);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(15);

        return view('staff.transactions.index', compact('transactions'));
    }

    public function create(Request $request)
    {
        $patient       = null;
        $medicalRecord = null;

        if ($request->filled('patient_id')) {
            $patient = Patient::findOrFail($request->patient_id);
        }

        if ($request->filled('medical_record_id')) {
            $medicalRecord = MedicalRecord::with('patient')
                ->findOrFail($request->medical_record_id);
            $patient = $medicalRecord->patient;
        }

        $services = Service::where('is_active', true)->orderBy('kode')->get();

        $packages = Package::where('is_active', true)
            ->with('services')
            ->orderBy('harga')->get();

        $products = Product::where('is_active', true)
            ->where('harga_by_order', false)
            ->where('stok', '>', 0)
            ->orderBy('kategori')->orderBy('nama')->get();

        return view('staff.transactions.create', compact(
            'patient', 'medicalRecord', 'services', 'packages', 'products'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'patient_id'   => 'required|exists:patients,id',
            'items'        => 'required|array|min:1',
            'items.*.type' => 'required|in:service,package,product',
            'items.*.id'   => 'required|integer',
            'items.*.qty'  => 'required|integer|min:1',
            'items.*.harga'=> 'nullable|numeric|min:0', // diabaikan: harga selalu diambil dari database
            'diskon'       => 'nullable|numeric|min:0',
        ], [
            'patient_id.required' => 'Pasien wajib dipilih.',
            'items.required'      => 'Pilih minimal satu layanan atau produk.',
            'items.min'           => 'Pilih minimal satu layanan atau produk.',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $itemsData = [];

            // Jumlah per produk digabung dulu (produk yang sama di dua baris tetap dicek satu kali),
            // lalu barisnya dikunci supaya dua transaksi serentak tidak bisa memakai stok yang sama.
            $qtyProduk = [];
            foreach ($request->items as $item) {
                if ($item['type'] === 'product') {
                    $qtyProduk[$item['id']] = ($qtyProduk[$item['id']] ?? 0) + (int) $item['qty'];
                }
            }

            $produk = [];
            foreach ($qtyProduk as $id => $qtyTotal) {
                $prod = Product::lockForUpdate()->find($id);

                if (!$prod || !$prod->is_active) {
                    throw new \DomainException('Produk tidak ditemukan atau sudah tidak aktif.');
                }
                if ($prod->harga_by_order) {
                    throw new \DomainException("Produk {$prod->nama} berharga by order dan tidak bisa dijual lewat transaksi ini.");
                }
                if ($qtyTotal > $prod->stok) {
                    throw new \DomainException(
                        "Stok {$prod->nama} tidak cukup (tersisa {$prod->stok} {$prod->satuan}, diminta {$qtyTotal})."
                    );
                }
                $produk[$id] = $prod;
            }

            foreach ($request->items as $item) {
                $qty = (int) $item['qty'];

                // Harga SELALU dari database, bukan dari kiriman browser
                if ($item['type'] === 'service') {
                    $svc = Service::find($item['id']);
                    if (!$svc || !$svc->is_active) {
                        throw new \DomainException('Layanan tidak ditemukan atau sudah tidak aktif.');
                    }
                    $nama     = $svc->nama;
                    $hargaSat = (float) $svc->harga;
                } elseif ($item['type'] === 'package') {
                    $pkg = Package::find($item['id']);
                    if (!$pkg || !$pkg->is_active) {
                        throw new \DomainException('Paket tidak ditemukan atau sudah tidak aktif.');
                    }
                    $nama     = $pkg->nama;
                    $hargaSat = (float) $pkg->harga;
                } else {
                    $prod     = $produk[$item['id']];
                    $nama     = $prod->nama;
                    $hargaSat = (float) $prod->harga;
                    $prod->decrement('stok', $qty);
                }

                $sub       = $hargaSat * $qty;
                $subtotal += $sub;

                $itemsData[] = [
                    'item_type'    => $item['type'],
                    'item_id'      => $item['id'],
                    'nama_item'    => $nama,
                    'harga_satuan' => $hargaSat,
                    'qty'          => $qty,
                    'subtotal'     => $sub,
                ];
            }

            $diskon = (float) ($request->diskon ?? 0);
            $total  = max(0, $subtotal - $diskon);

            $transaction = Transaction::create([
                'patient_id'        => $request->patient_id,
                'user_id'           => auth()->id(),
                'medical_record_id' => $request->medical_record_id ?: null,
                'subtotal'          => $subtotal,
                'diskon'            => $diskon,
                'total'             => $total,
                'metode_bayar'      => 'cash',
                'status'            => 'selesai',
            ]);

            foreach ($itemsData as $item) {
                $transaction->items()->create($item);
            }

            ActivityLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'create_transaction',
                'description' => 'Transaksi ' . $transaction->no_transaksi . ' untuk pasien ' . $transaction->patient->nama,
                'ip_address'  => $request->ip(),
            ]);

            DB::commit();

            // Load relasi items secara segar agar deteksi custom fields & built-in fields akurat
            $transaction->load('items');

            // Cek kebutuhan rekam medis (Baik built-in maupun custom fields dari admin)
            if ($transaction->needsMedicalRecord()) {
                return redirect()->route('staff.medical-records.create', [
                    'patient_id'     => $transaction->patient_id,
                    'transaction_id' => $transaction->id,
                    'fields'         => $transaction->getMedicalFieldsString(),
                ])->with('success', 'Transaksi ' . $transaction->no_transaksi . ' berhasil! Silakan input hasil pemeriksaan.');
            }

            // Jika hanya membeli produk biasa, langsung arahkan ke halaman detail transaksi
            return redirect()->route('staff.transactions.show', $transaction->id)
                ->with('success', 'Transaksi ' . $transaction->no_transaksi . ' berhasil disimpan!');

        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function show(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id()) abort(403);

        $transaction->load(['patient', 'user', 'items', 'medicalRecord']);
        return view('staff.transactions.show', compact('transaction'));
    }

    public function destroy(Transaction $transaction)
    {
        abort(403, 'Transaksi tidak dapat dihapus.');
    }

    public function nota(Transaction $transaction)
    {
        if ($transaction->user_id !== auth()->id() && auth()->user()->role !== 'admin') {
            abort(403);
        }

        $transaction->load(['patient', 'user', 'items', 'medicalRecord']);

        if ($transaction->needsMedicalRecord() && !$transaction->medical_record_id) {
            return redirect()
                ->route('staff.medical-records.create', [
                    'patient_id'     => $transaction->patient_id,
                    'transaction_id' => $transaction->id,
                    'fields'         => $transaction->getMedicalFieldsString(),
                ])
                ->with('warning', 'Hasil pemeriksaan harus diisi dulu sebelum cetak nota!');
        }

        $qrUrl  = url('/portal?rm=' . $transaction->patient->no_rm . '&kode=' . $transaction->patient->kode_unik);
        $qrCode = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                     ->size(120)->generate($qrUrl);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.nota', [
            'transaction' => $transaction,
            'qrCode'      => base64_encode($qrCode),
            'qrUrl'       => $qrUrl,
        ]);

        $pdf->setPaper([0, 0, 226.77, 700], 'portrait');

        return $pdf->stream('nota-' . $transaction->no_transaksi . '.pdf');
    }
}