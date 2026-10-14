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
        $pimpinan = DB::table('geotrax_v4.pegawai_skkni')->where('username', $userLogin->username)->first();
        $idPimpinan = $pimpinan ? $pimpinan->pegawai_id : 0;

        // Ambil ID bawahan langsung dari tabel relasi penilai yang sah
        $bawahanIds = DB::table('geotrax_v4.p_pegawai_penilai_sah')
            ->where('id_penilai', $idPimpinan)
            ->pluck('id_pegawai')
            ->toArray();

        return $bawahanIds;
    }

    public function index(Request $request)
    {
        $filter_periode = $request->periode ?? 'ALL';
        $bawahanIds = $this->getBawahanIds();

        $list_periode = DB::table('geotrax_v4.pegawai_skkni')
            ->select('jabatan as nama_periode')
            ->whereIn('pegawai_id', $bawahanIds)
            ->distinct()
            ->orderBy('jabatan', 'asc')
            ->get();
        
        // Ambil data dasar
        $query = DB::table('penilaian_header as ph')
            ->join('geotrax_v4.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->where('ph.status', 'Selesai')
            ->whereIn('p.pegawai_id', $bawahanIds)
            ->select('p.pegawai_id', 'p.pegawai_nama', 'p.jabatan', DB::raw('MAX(ph.waktu_submit) as waktu_terakhir'))
            ->groupBy('p.pegawai_id', 'p.pegawai_nama', 'p.jabatan');

        if ($filter_periode !== 'ALL') {
            $query->where('p.jabatan', $filter_periode);
        }

        $list_riwayat = $query->orderBy('waktu_terakhir', 'desc')->get();

        $stat_sangat_tinggi = 0; $stat_tinggi = 0; $stat_sedang = 0; $stat_rendah = 0; $stat_sangat_rendah = 0;

        foreach($list_riwayat as $r) {
            // HITUNG RAW SKOR PER PEGAWAI
            $list_uk = DB::table('penilaian_header')->where('pegawai_id', $r->pegawai_id)->where('status', 'Selesai')->get();
            $total_kuk = 0; $total_skor = 0;
            
            foreach($list_uk as $uk) {
                $details = DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $uk->penilaian_id)->get();
                $total_kuk += $details->count();
                $total_skor += $details->sum('skor_final');
            }

            if ($total_kuk > 0) {
                // RUMUS DINAMIS PROFESSOR (CEIL INTERVAL)
                $min = $total_kuk * 1;
                $max = $total_kuk * 5;
                $interval_bulat = ceil(($max - $min) / 5);

                $b_sangat_tinggi = $max - $interval_bulat + 1;
                $b_tinggi = $b_sangat_tinggi - $interval_bulat;
                $b_sedang = $b_tinggi - $interval_bulat;
                $b_rendah = $b_sedang - $interval_bulat;

                if ($total_skor >= $b_sangat_tinggi) { $r->kategori_baru = "Sangat Tinggi"; $stat_sangat_tinggi++; }
                elseif ($total_skor >= $b_tinggi) { $r->kategori_baru = "Tinggi"; $stat_tinggi++; }
                elseif ($total_skor >= $b_sedang) { $r->kategori_baru = "Sedang"; $stat_sedang++; }
                elseif ($total_skor >= $b_rendah) { $r->kategori_baru = "Rendah"; $stat_rendah++; }
                else { $r->kategori_baru = "Sangat Rendah"; $stat_sangat_rendah++; }
            } else {
                $r->kategori_baru = "Sangat Rendah"; $stat_sangat_rendah++;
            }
            
            $r->skor_aktual_raw = $total_skor;
            $r->total_kuk_raw = $total_kuk;
        }

        // =========================================================
        // URUTKAN BERDASARKAN SKOR AKTUAL TERTINGGI (RANKING 1 DI ATAS)
        // =========================================================
        $list_riwayat = $list_riwayat->sortByDesc('skor_aktual_raw')->values();

        return view('pimpinan.hasil_kompetensi.index', compact(
            'list_riwayat', 'list_periode', 'filter_periode',
            'stat_sangat_tinggi', 'stat_tinggi', 'stat_sedang', 'stat_rendah', 'stat_sangat_rendah'
        ));
    }

    public function show($pegawai_id)
    {
        $pegawai = DB::table('geotrax_v4.pegawai_skkni')->where('pegawai_id', $pegawai_id)->first();
        if (!$pegawai) return redirect()->route('pimpinan.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');

        $list_uk = DB::table('penilaian_header as ph')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.pegawai_id', $pegawai_id)->where('ph.status', 'Selesai')
            ->select('ph.*', 'uk.judul_unit')->orderBy('ph.waktu_submit', 'desc')->get();

        // 1. SIAPKAN KAMUS BOBOT GAP (Berdasarkan Jurnal Profile Matching)
        $bobot_db = DB::table('geotrax_v4.pm_bobot_gap')->get();
        if ($bobot_db->isEmpty()) $bobot_db = DB::table('geotrax_v4.p_master_bobot_gap')->get();
        $tabel_bobot = [];
        foreach ($bobot_db as $row) {
            $gap_val = $row->selisih_gap ?? $row->gap;
            $bobot_val = $row->bobot_nilai ?? $row->bobot;
            $tabel_bobot[(string)$gap_val] = (float)$bobot_val;
        }

        $total_kuk_jabatan = 0; $total_skor_raw_jabatan = 0;
        $total_ncf = 0; $total_nsf = 0;

        foreach ($list_uk as $uk) {
            // A. Menghitung Raw Skor
            $details = DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $uk->penilaian_id)->get();
            $uk->jumlah_kuk = $details->count();
            $uk->skor_aktual_raw = $details->sum('skor_final');
            $uk->min_skor = $uk->jumlah_kuk * 1;
            $uk->max_skor = $uk->jumlah_kuk * 5;

            $total_kuk_jabatan += $uk->jumlah_kuk;
            $total_skor_raw_jabatan += $uk->skor_aktual_raw;

            // B. NCF (Core Factor) per UK
            $tot_b_ncf = 0; $c_ncf = 0;
            foreach($details as $hd) {
                $gap = $hd->skor_final - 5; // Target standar SKKNI adalah 5
                $tot_b_ncf += $tabel_bobot[(string)$gap] ?? 1.0;
                $c_ncf++;
            }
            $total_ncf += ($c_ncf > 0) ? ($tot_b_ncf / $c_ncf) : 0;

            // C. NSF (Secondary Factor) per UK
            $sfs = DB::table('penilaian_softskill')->where('penilaian_id', $uk->penilaian_id)->get();
            $tot_b_nsf = 0; $c_nsf = 0;
            foreach($sfs as $sf) {
                $tot_b_nsf += $sf->bobot_gap;
                $c_nsf++;
            }
            $total_nsf += ($c_nsf > 0) ? ($tot_b_nsf / $c_nsf) : 0;
        }

        $min_skor_jabatan = $total_kuk_jabatan * 1;
        $max_skor_jabatan = $total_kuk_jabatan * 5;
        $jumlah_kompetensi = $list_uk->count();

        // 2. KALKULASI NILAI AKHIR (RUMUS MUTLAK DARI PROFESSOR)
        $kategori_akhir = "Sangat Rendah";
        if ($total_kuk_jabatan > 0) {
            $interval_bulat = ceil(($max_skor_jabatan - $min_skor_jabatan) / 5);
            $b_sangat_tinggi = $max_skor_jabatan - $interval_bulat + 1;
            $b_tinggi        = $b_sangat_tinggi - $interval_bulat;
            $b_sedang        = $b_tinggi - $interval_bulat;
            $b_rendah        = $b_sedang - $interval_bulat;

            if ($total_skor_raw_jabatan >= $b_sangat_tinggi) $kategori_akhir = "Sangat Tinggi";
            elseif ($total_skor_raw_jabatan >= $b_tinggi) $kategori_akhir = "Tinggi";
            elseif ($total_skor_raw_jabatan >= $b_sedang) $kategori_akhir = "Sedang";
            elseif ($total_skor_raw_jabatan >= $b_rendah) $kategori_akhir = "Rendah";
        }
        $rata_nilai = $total_skor_raw_jabatan;

        // 3. KALKULASI HASIL PROFILE MATCHING (RUMUS JURNAL) SECARA DINAMIS
        $ncf = ($jumlah_kompetensi > 0) ? ($total_ncf / $jumlah_kompetensi) : 0;
        $nsf = ($jumlah_kompetensi > 0) ? ($total_nsf / $jumlah_kompetensi) : 0;

        $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')->where('jabatan', 'ILIKE', "%{$pegawai->jabatan}%")->first();
        $persen_ncf = $master_jabatan ? $master_jabatan->persentase_cf : 60;
        $persen_nsf = $master_jabatan ? $master_jabatan->persentase_sf : 40;
        $ni = (($persen_ncf / 100) * $ncf) + (($persen_nsf / 100) * $nsf);

        // Memasukkan hasil hitungan ke dalam format array agar bisa dibaca oleh tabel view
        $hasil_pm = collect([
            (object)[
                'peringkat' => 1,
                'nama_jabatan' => $pegawai->jabatan,
                'nilai_core_factor' => $ncf,
                'nilai_secondary_factor' => $nsf,
                'nilai_total' => $ni
            ]
        ]);

        // Data Softskill (Mengambil dari penilaian terakhir)
        $latest_penilaian = $list_uk->first();
        $list_softskill = collect();
        if($latest_penilaian) {
            $list_softskill = DB::table('penilaian_softskill as ps')
                ->join('standar_softskill as ss', 'ps.softskill_id', '=', 'ss.softskill_id')
                ->where('ps.penilaian_id', $latest_penilaian->penilaian_id)
                ->select('ss.nama_softskill', 'ps.nilai_aktual', 'ps.gap', 'ps.bobot_gap')->get();
        }
        $is_match = true;

        return view('pimpinan.hasil_kompetensi.show', compact(
            'pegawai', 'list_uk', 'rata_nilai', 'kategori_akhir', 
            'list_softskill', 'hasil_pm', 'is_match', 'latest_penilaian',
            'total_kuk_jabatan', 'total_skor_raw_jabatan', 'min_skor_jabatan', 
            'max_skor_jabatan', 'jumlah_kompetensi'
        ));
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
            ->join('geotrax_v4.pegawai_skkni as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
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