<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HasilKompetensiController extends Controller
{
    // --- 1. TAMPILAN UTAMA (hasil_kompetensi.php) ---
    public function index(Request $request)
    {
        $filter_periode = $request->periode ?? 'ALL';
        $filter_bulan = $request->bulan ?? 'ALL';
        $filter_tahun = $request->tahun ?? 'ALL';

        $list_periode = DB::table('periode_penilaian')->select('nama_periode')->distinct()->orderBy('nama_periode', 'asc')->get();
        
        $arr_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        $tahun_sekarang = date('Y');
        $arr_tahun = range(2024, $tahun_sekarang + 1);

        // Query Builder dengan Filter Dinamis[cite: 31]
        $query = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('ph.penilaian_id', 'ph.pegawai_id', 'p.pegawai_nama', 'p.jabatan', 'ph.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.waktu_submit', 'ph.rekomendasi');

        if ($filter_periode !== 'ALL') {
            $query->where('p.jabatan', $filter_periode);
        }
        if ($filter_bulan !== 'ALL') {
            $query->whereMonth('ph.waktu_submit', $filter_bulan);
        }
        if ($filter_tahun !== 'ALL') {
            $query->whereYear('ph.waktu_submit', $filter_tahun);
        }

        $list_riwayat = $query->orderBy('ph.waktu_submit', 'desc')->get();

        // Kalkulasi Widget[cite: 31]
        $stat_sangat_kompeten = $list_riwayat->where('kategori', 'Sangat Kompeten')->count();
        $stat_kompeten = $list_riwayat->where('kategori', 'Kompeten')->count();
        $stat_cukup_kompeten = $list_riwayat->where('kategori', 'Cukup Kompeten')->count();
        $stat_belum_kompeten = $list_riwayat->where('kategori', 'Belum Kompeten')->count();

        return view('pimpinan.hasil_kompetensi.index', compact(
            'list_riwayat', 'list_periode', 'arr_bulan', 'arr_tahun', 
            'filter_periode', 'filter_bulan', 'filter_tahun',
            'stat_sangat_kompeten', 'stat_kompeten', 'stat_cukup_kompeten', 'stat_belum_kompeten'
        ));
    }

    // --- 2. HALAMAN DETAIL (detail_hasil_kompetensi.php) ---
    public function show($id)
    {
        $header = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.penilaian_id', $id)
            ->select('ph.*', 'p.pegawai_nama', 'p.jabatan', 'uk.judul_unit')
            ->first();

        if (!$header) return redirect()->route('pimpinan.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');

        $details = DB::table('penilaian_detail as pd')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('pd.penilaian_id', $id)
            ->select('pd.*', 'ak.detail_aktivitas')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        // DUMMY LOGIC REKOMENDASI UNTUK PIMPINAN
        $roles = ['Kurator', 'Edukator', 'Konservator', 'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'];
        $match_scores = [];
        
        srand($header->pegawai_id); 
        foreach($roles as $r) {
            if ($r == $header->jabatan) {
                $match_scores[$r] = $header->nilai_akhir; 
            } else {
                $match_scores[$r] = rand(4000, 8500) / 100;
            }
        }
        srand(); 
        arsort($match_scores);
        $best_match_role = array_key_first($match_scores);
        $is_match = ($best_match_role == $header->jabatan);

        return view('pimpinan.hasil_kompetensi.show', compact('header', 'details', 'match_scores', 'best_match_role', 'is_match'));
    }

    // --- 3. HAPUS PENILAIAN ---
    public function destroy($id)
    {
        DB::table('penilaian_detail')->where('penilaian_id', $id)->delete();
        DB::table('penilaian_header')->where('penilaian_id', $id)->delete();
        return redirect()->route('pimpinan.hasil_kompetensi.index')->with('success', 'Riwayat penilaian berhasil dihapus permanen.');
    }

    // --- 4. CETAK EXCEL (cetak_excel.php) ---
    public function exportExcel(Request $request)
    {
        $periode = $request->periode ?? 'ALL';
        $nama_file = ($periode !== 'ALL') ? "Rekap_Penilaian_" . str_replace(" ", "_", $periode) . ".xls" : "Rekap_Penilaian_Semua_Pegawai.xls";

        $query = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('ph.penilaian_id', 'p.pegawai_nama', 'p.jabatan', 'ph.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.rekomendasi', 'ph.waktu_submit');

        if ($periode !== 'ALL') {
            $query->where('p.jabatan', $periode);
        }
        if ($request->bulan && $request->bulan !== 'ALL') {
            $query->whereMonth('ph.waktu_submit', $request->bulan);
        }
        if ($request->tahun && $request->tahun !== 'ALL') {
            $query->whereYear('ph.waktu_submit', $request->tahun);
        }

        $list_data = $query->orderBy('p.jabatan', 'asc')->orderBy('p.pegawai_nama', 'asc')->orderBy('ph.waktu_submit', 'desc')->get();

        // Pakai response bawaan Laravel untuk memaksa download
        return response(view('pimpinan.hasil_kompetensi.excel', compact('list_data', 'periode')))
            ->header('Content-Type', 'application/vnd-ms-excel')
            ->header('Content-Disposition', 'attachment; filename="'.$nama_file.'"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}