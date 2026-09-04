<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Pegawai;

class HasilKompetensiController extends Controller
{
    /**
     * Dapatkan ID Pegawai yang sah menjadi bawahan dari Pimpinan yang sedang login
     */
    private function getBawahanIds()
    {
        // Ambil ID Pimpinan yang sedang login
        $userLogin = Auth::user();
        
        // Pengecekan aman, karena di tabel users kita pakai 'id', tapi di pegawai_skkni 'pegawai_id'
        // Kita cocokan username ke pegawai_skkni untuk dapat pegawai_id yang valid
        $pimpinan = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        $idPimpinan = $pimpinan ? $pimpinan->pegawai_id : 0;

        // Ambil ID bawahan langsung dari tabel relasi penilai yang sah
        $bawahanIds = DB::table('geotrax_v3.p_pegawai_penilai_sah')
            ->where('id_penilai', $idPimpinan)
            ->pluck('id_pegawai')
            ->toArray();

        return $bawahanIds;
    }

    public function index(Request $request)
    {
        $filter_periode = $request->periode ?? 'ALL';
        $filter_bulan = $request->bulan ?? 'ALL';
        $filter_tahun = $request->tahun ?? 'ALL';

        // 1. Ambil daftar ID bawahan langsung
        $bawahanIds = $this->getBawahanIds();

        $list_periode = DB::table('geotrax_v3.pegawai_skkni')
            ->select('jabatan as nama_periode')
            ->whereIn('pegawai_id', $bawahanIds) // FILTER BAWAHAN LANGSUNG
            ->distinct()
            ->orderBy('jabatan', 'asc')
            ->get();
        
        $arr_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        $tahun_sekarang = date('Y');
        $arr_tahun = range(2024, $tahun_sekarang + 1);

        $query = DB::table('penilaian_header as ph')
            ->join('geotrax_v3.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->whereIn('p.pegawai_id', $bawahanIds) // FILTER BAWAHAN LANGSUNG
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

    public function show($id)
    {
        // 1. Data Penilaian SKKNI Header
        $header = DB::table('penilaian_header as ph')
            ->join('geotrax_v3.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.penilaian_id', $id)
            ->select('ph.*', 'p.pegawai_nama', 'p.jabatan', 'uk.judul_unit')
            ->first();

        if (!$header) return redirect()->route('pimpinan.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');

        // 2. Data Penilaian SKKNI Detail
        $details = DB::table('penilaian_detail as pd')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('pd.penilaian_id', $id)
            ->select('pd.*', 'ak.detail_aktivitas')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        // 3. AMBIL DATA PROFILE MATCHING ASLI DARI DATABASE (Bukan Rand() lagi)
        $hasil_pm = DB::table('geotrax_v3.p_hasil_profile_matching as pm')
            ->join('geotrax_v3.p_master_jabatan as mj', 'pm.id_jabatan', '=', 'mj.id_jabatan')
            ->where('pm.id_pegawai', $header->pegawai_id)
            ->orderBy('pm.peringkat', 'asc') // Urutkan dari Peringkat 1
            ->get();

        // Tentukan Best Match (Peringkat 1)
        $best_match = $hasil_pm->first();
        $best_match_role = $best_match ? $best_match->nama_jabatan : 'Belum Ada Hasil PM';
        
        // Cek apakah Best Match sesuai dengan Jabatan Saat Ini
        $is_match = false;
        if ($best_match) {
            $is_match = stripos($header->jabatan, $best_match->nama_jabatan) !== false || 
                        stripos($best_match->nama_jabatan, $header->jabatan) !== false;
        }

        return view('pimpinan.hasil_kompetensi.show', compact('header', 'details', 'hasil_pm', 'best_match_role', 'is_match'));
    }

    public function destroy($id)
    {
        DB::table('penilaian_detail')->where('penilaian_id', $id)->delete();
        DB::table('penilaian_header')->where('penilaian_id', $id)->delete();
        return redirect()->route('pimpinan.hasil_kompetensi.index')->with('success', 'Riwayat penilaian berhasil dihapus permanen.');
    }

    public function exportExcel(Request $request)
    {
        $periode = $request->periode ?? 'ALL';
        $nama_file = ($periode !== 'ALL') ? "Rekap_Penilaian_" . str_replace(" ", "_", $periode) . ".xls" : "Rekap_Penilaian_Semua_Pegawai.xls";

        $bawahanIds = $this->getBawahanIds();

        $query = DB::table('penilaian_header as ph')
            ->join('geotrax_v3.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->whereIn('p.pegawai_id', $bawahanIds)
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

        return response(view('pimpinan.hasil_kompetensi.excel', compact('list_data', 'periode')))
            ->header('Content-Type', 'application/vnd-ms-excel')
            ->header('Content-Disposition', 'attachment; filename="'.$nama_file.'"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}