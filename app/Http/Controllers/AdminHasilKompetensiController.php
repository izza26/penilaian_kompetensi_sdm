<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHasilKompetensiController extends Controller
{
    // --- 1. TAMPILAN INDEX HASIL KOMPETENSI ---
    public function index()
    {
        // Statistik Admin
        $total_pegawai = DB::table('pegawai')->where('role', 'pegawai')->count();
        $jml_kompeten = DB::table('penilaian_header')->where('status', 'Selesai')->where('nilai_akhir', '>=', 70)->count();
        $jml_belum = DB::table('penilaian_header')->where('status', 'Selesai')->where('nilai_akhir', '<', 70)->count();
        $rata_db = DB::table('penilaian_header')->where('status', 'Selesai')->avg('nilai_akhir');
        $rata_rata = $rata_db ? round($rata_db, 1) : 0;

        // Tarik Data Tabel
        $list_hasil = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('ph.penilaian_id', 'p.pegawai_id', 'p.pegawai_nama', 'p.jabatan', 'uk.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori')
            ->orderBy('ph.waktu_submit', 'desc')
            ->paginate(10);

        // --- TAMBAHAN LOGIKA ALASAN (OPSI 1) ---
        foreach ($list_hasil as $row) {
            $kategori = trim($row->kategori);

            // Hanya buat alasan jika statusnya Belum/Cukup Kompeten
            if (!in_array($kategori, ['Sangat Kompeten', 'Kompeten'])) {

                // 1. Cari semua unit kompetensi yang WAJIB untuk jabatannya
                $required_units = DB::table('unit_kompetensi')
                    ->where('posisi_target', 'ILIKE', "%{$row->jabatan}%")
                    ->where('aktif', 'Y')
                    ->pluck('kode_unit')
                    ->toArray();

                // 2. Cari unit kompetensi apa saja yang SUDAH diupload evidence-nya oleh pegawai ini
                $uploaded_units = DB::table('bukti_pegawai as bp')
                    ->join('aktivitas_kompeten as ak', 'bp.aktivitas_id', '=', 'ak.aktivitas_id')
                    ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
                    ->where('bp.pegawai_id', $row->pegawai_id)
                    ->distinct()
                    ->pluck('ek.kode_unit')
                    ->toArray();

                // 3. Cari selisihnya (Unit yang wajib dikurangi unit yang sudah diupload)
                $missing_units = array_diff($required_units, $uploaded_units);
                $jumlah_upload = count($uploaded_units);
                $jumlah_kurang = count($missing_units);

                if ($jumlah_kurang > 0) {
                    $row->alasan_sistem = "Hanya mengupload evidence untuk {$jumlah_upload} unit kompetensi. Terdapat {$jumlah_kurang} unit kompetensi lain yang belum di-upload.";
                } else {
                    $row->alasan_sistem = "Seluruh unit kompetensi telah di-upload, namun skor penilaian belum memenuhi standar minimal kompeten.";
                }
            } else {
                $row->alasan_sistem = "-";
            }
        }
        // ----------------------------------------

        return view('admin.hasil_kompetensi.index', compact('total_pegawai', 'jml_kompeten', 'jml_belum', 'rata_rata', 'list_hasil'));
    }

    // --- 2. TAMPILAN DETAIL HASIL KOMPETENSI ---
    public function show($id)
    {
        $data = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.penilaian_id', $id)
            ->select(
                'p.pegawai_id',
                'p.pegawai_nama',
                'p.jabatan',
                'p.unit_kerja',
                'uk.kode_unit',
                'ph.nilai_akhir as skor_akhir_360',
                'ph.kategori as status_kompeten',
                'ph.waktu_submit',
                'ph.catatan_umum'
            )
            ->first();

        if (!$data) {
            return redirect()->route('admin.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');
        }

        $data->nama_elemen = DB::table('elemen_kompetensi')->where('kode_unit', $data->kode_unit)->value('elemen_kompetensi');

        // --- TAMBAHAN LOGIKA CATATAN SISTEM (KHUSUS DETAIL INI) ---
        $kategori = trim($data->status_kompeten);
        if (!in_array($kategori, ['Sangat Kompeten', 'Kompeten'])) {
            $required_units = DB::table('unit_kompetensi')
                ->where('posisi_target', 'ILIKE', "%{$data->jabatan}%")
                ->where('aktif', 'Y')
                ->pluck('kode_unit')->toArray();

            $uploaded_units = DB::table('bukti_pegawai as bp')
                ->join('aktivitas_kompeten as ak', 'bp.aktivitas_id', '=', 'ak.aktivitas_id')
                ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
                ->where('bp.pegawai_id', $data->pegawai_id)
                ->distinct()
                ->pluck('ek.kode_unit')->toArray();

            $missing_units = array_diff($required_units, $uploaded_units);
            $jumlah_upload = count($uploaded_units);
            $jumlah_kurang = count($missing_units);

            if ($jumlah_kurang > 0) {
                $data->alasan_sistem = "Hanya mengupload evidence untuk {$jumlah_upload} unit kompetensi. Terdapat {$jumlah_kurang} unit kompetensi lain yang belum di-upload.";
            } else {
                $data->alasan_sistem = "Seluruh unit kompetensi telah di-upload, namun skor penilaian belum memenuhi standar minimal kompeten.";
            }
        } else {
            $data->alasan_sistem = "-";
        }
        // ------------------------------------------------------------

        $list_nilai = DB::table('penilaian_detail as pd')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('pd.penilaian_id', $id)
            ->select('ak.detail_aktivitas', 'pd.skor_final')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        return view('admin.hasil_kompetensi.show', compact('data', 'list_nilai'));
    }

    // --- 3. EXPORT EXCEL ---
    public function exportExcel()
    {
        $list_data = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('p.pegawai_nama', 'p.jabatan', 'uk.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.waktu_submit', 'ph.rekomendasi')
            ->orderBy('p.jabatan', 'asc')
            ->orderBy('p.pegawai_nama', 'asc')
            ->get();

        return response(view('pimpinan.hasil_kompetensi.excel', ['list_data' => $list_data, 'periode' => 'Semua Pegawai']))
            ->header('Content-Type', 'application/vnd-ms-excel')
            ->header('Content-Disposition', 'attachment; filename="Rekap_Penilaian_Museum_Geologi.xls"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // FUNGSI KHUSUS SUPERADMIN: HAPUS HASIL PENILAIAN
    public function destroy($id)
    {
        // Pastikan hanya superadmin yang bisa nge-hit fungsi ini (lapis keamanan ganda)
        if (Auth::user()->role !== 'superadmin') {
            return redirect()->back()->with('error', 'Akses Ditolak! Hanya Super Administrator yang diizinkan menghapus data.');
        }

        DB::beginTransaction();
        try {
            // Hapus detail nilainya dulu (karena ada foreign key / relasi)
            DB::table('geotrax_v3.penilaian_detail')->where('penilaian_id', $id)->delete();

            // Baru hapus header hasil akhirnya
            DB::table('geotrax_v3.penilaian_header')->where('penilaian_id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Riwayat hasil penilaian berhasil dihapus permanen.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
