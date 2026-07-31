<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitKompetensi;
use App\Models\ElemenKompetensi;
use App\Models\AktivitasKompeten;
use App\Models\PeriodePenilaian;

class ManajemenPenilaianController extends Controller
{
    // --- 1. HALAMAN MANAJEMEN PERIODE ---
    public function periodeIndex()
    {
        $all_uks = UnitKompetensi::orderBy('kode_unit', 'asc')->get();
        $periods = PeriodePenilaian::orderBy('periode_id', 'asc')->get();
        $posisi_list = ['Kurator', 'Edukator', 'Konservator', 'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'];

        return view('pimpinan.manajemen_penilaian.periode', compact('all_uks', 'periods', 'posisi_list'));
    }

    // --- 2. PROSES CRUD PERIODE (Modal) ---
    public function periodeAction(Request $request)
    {
        $action = $request->action;
        
        if ($action == 'tambah_jabatan_baru') {
            $jabatan_input = trim($request->jabatan);
            $tgl_mulai = $request->tgl_mulai_serentak ?: '1970-01-01';
            $tgl_selesai = $request->tgl_selesai_serentak ?: '1970-01-01';
            
            if ($jabatan_input === 'Semua Jabatan') {
                $posisi_list = ['Kurator', 'Edukator', 'Konservator', 'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'];
                foreach ($posisi_list as $jab) {
                    $cek = PeriodePenilaian::where('nama_periode', $jab)->first();
                    if (!$cek) {
                        $new_id = PeriodePenilaian::max('periode_id') + 1;
                        PeriodePenilaian::create([
                            'periode_id' => $new_id, 'peserta_id' => 0, 'nama_periode' => $jab,
                            'tanggal_mulai' => $tgl_mulai, 'tanggal_selesai' => $tgl_selesai, 'status_aktif' => 'Y'
                        ]);
                        UnitKompetensi::where('posisi_target', 'ILIKE', "%{$jab}%")->update(['aktif' => 'Y']);
                    }
                }
                return back()->with('success', 'Semua jabatan dan periode berhasil diatur!');
            } else {
                $cek = PeriodePenilaian::where('nama_periode', $jabatan_input)->first();
                if (!$cek) {
                    $new_id = PeriodePenilaian::max('periode_id') + 1;
                    PeriodePenilaian::create([
                        'periode_id' => $new_id, 'peserta_id' => null, 'nama_periode' => $jabatan_input,
                        'tanggal_mulai' => '1970-01-01', 'tanggal_selesai' => '1970-01-01', 'status_aktif' => 'Y'
                    ]);
                    UnitKompetensi::where('posisi_target', 'ILIKE', "%{$jabatan_input}%")->update(['aktif' => 'Y']);
                }
                return back()->with('success', 'Jabatan berhasil dipilih. Silakan lanjut atur tanggalnya!');
            }
        }
        elseif ($action == 'update_tanggal') {
            PeriodePenilaian::where('nama_periode', $request->jabatan)->update([
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai
            ]);
            return back()->with('success', 'Tanggal berhasil disimpan!');
        }
        elseif ($action == 'hapus_periode') {
            PeriodePenilaian::where('nama_periode', $request->jabatan)->delete();
            UnitKompetensi::where('posisi_target', 'ILIKE', "%{$request->jabatan}%")->update(['aktif' => 'N']);
            return back()->with('success', 'Data jabatan berhasil dihapus!');
        }
    }

    // --- 3. HALAMAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasIndex(Request $request)
    {
        $jabatan = $request->jabatan;
        if (!$jabatan) return redirect()->route('pimpinan.manajemen_penilaian.periode');

        $display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;

        // Ambil data hierarki otomatis
        $data = UnitKompetensi::with(['elemen' => function($q) {
                $q->orderBy('kode_elemen_excel', 'asc');
            }, 'elemen.aktivitas' => function($q) {
                $q->orderBy('aktivitas_id', 'asc');
            }])
            ->where('posisi_target', 'ILIKE', "%{$jabatan}%")
            ->where('aktif', 'Y')
            ->orderBy('kode_unit', 'asc')
            ->get();

        return view('pimpinan.manajemen_penilaian.aktivitas', compact('data', 'jabatan', 'display_jabatan'));
    }

    // --- 4. PROSES SIMPAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasStore(Request $request)
    {
        $jabatan = $request->jabatan;
        $aktivitas_terpilih = $request->aktivitas_aktif ?? [];

        // Reset semua aktivitas menjadi 'N' untuk jabatan ini
        $unit_codes = UnitKompetensi::where('posisi_target', 'ILIKE', "%{$jabatan}%")->pluck('kode_unit');
        $elemen_ids = ElemenKompetensi::whereIn('kode_unit', $unit_codes)->pluck('elemen_id');
        AktivitasKompeten::whereIn('elemen_id', $elemen_ids)->update(['aktif' => 'N']);

        // Hidupkan (Set 'Y') hanya yang dicentang
        if (!empty($aktivitas_terpilih)) {
            AktivitasKompeten::whereIn('aktivitas_id', $aktivitas_terpilih)->update(['aktif' => 'Y']);
        }

        return back()->with('success', 'Kustomisasi aktivitas untuk jabatan ini telah diperbarui.');
    }
}