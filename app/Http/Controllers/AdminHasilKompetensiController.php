<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHasilKompetensiController extends Controller
{
    // --- 1. TAMPILAN INDEX HASIL KOMPETENSI ---
    public function index(Request $request)
    {
        $filter_periode = $request->periode ?? 'ALL';

        // Ambil daftar jabatan yang tersedia
        $list_periode = DB::table('geotrax_v4.pegawai_skkni')
            ->select('jabatan as nama_periode')
            ->distinct()
            ->orderBy('jabatan', 'asc')
            ->get();
        
        // Ambil data dan kelompokkan berdasarkan Pegawai (Mirip Pimpinan)
        $query = DB::table('penilaian_header as ph')
            ->join('geotrax_v4.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->where('ph.status', 'Selesai')
            ->select(
                'p.pegawai_id', 
                'p.pegawai_nama', 
                'p.jabatan', 
                DB::raw('AVG(ph.nilai_akhir) as rata_nilai'), 
                DB::raw('MAX(ph.waktu_submit) as waktu_terakhir')
            )
            ->groupBy('p.pegawai_id', 'p.pegawai_nama', 'p.jabatan');

        if ($filter_periode !== 'ALL') {
            $query->where('p.jabatan', $filter_periode);
        }

        // Gunakan pagination agar rapi, misal 10 data per halaman
        $list_riwayat = $query->orderBy('waktu_terakhir', 'desc')->paginate(10);

        // Hitung Widget Keseluruhan (tanpa pagination)
        $all_riwayat = $query->get();
        $stat_sangat_tinggi = 0; $stat_tinggi = 0; $stat_sedang = 0; $stat_rendah = 0; $stat_sangat_rendah = 0;
        foreach($all_riwayat as $r) {
            if($r->rata_nilai >= 85) $stat_sangat_tinggi++;
            elseif($r->rata_nilai >= 70) $stat_tinggi++;
            elseif($r->rata_nilai >= 55) $stat_sedang++;
            elseif($r->rata_nilai >= 40) $stat_rendah++;
            else $stat_sangat_rendah++;
        }

        return view('admin.hasil_kompetensi.index', compact(
            'list_riwayat', 'list_periode', 'filter_periode',
            'stat_sangat_tinggi', 'stat_tinggi', 'stat_sedang', 'stat_rendah', 'stat_sangat_rendah'
        ));
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
            DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $id)->delete();

            // Baru hapus header hasil akhirnya
            DB::table('geotrax_v4.penilaian_header')->where('penilaian_id', $id)->delete();

            DB::commit();
            return redirect()->back()->with('success', 'Riwayat hasil penilaian berhasil dihapus permanen.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
