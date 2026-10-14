<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PegawaiPenilaianController extends Controller
{
    private function konversiBobotGap($gap) {
        static $tabel_bobot = null;
        if ($tabel_bobot === null) {
            $bobot_db = DB::table('geotrax_v4.pm_bobot_gap')->get();
            if ($bobot_db->isEmpty()) $bobot_db = DB::table('geotrax_v4.p_master_bobot_gap')->get();
            $tabel_bobot = [];
            foreach ($bobot_db as $row) {
                $gap_val = isset($row->selisih_gap) ? $row->selisih_gap : $row->gap;
                $bobot_val = isset($row->bobot_nilai) ? $row->bobot_nilai : $row->bobot;
                $tabel_bobot[(string)$gap_val] = (float)$bobot_val;
            }
        }
        return $tabel_bobot[(string)$gap] ?? 1.0;
    }

    public function index()
    {
        $pegawai = Auth::user();
        $idPegawai = $pegawai->pegawai_id;

        $periodeAktif = DB::table('geotrax_v4.periode_penilaian')->where('status_aktif', 'Y')->first();
        if ($periodeAktif) {
            $periode = $periodeAktif->nama_periode;
            $semester = date('d M Y', strtotime($periodeAktif->tanggal_mulai)) . " - " . date('d M Y', strtotime($periodeAktif->tanggal_selesai));
            $statusPeriode = "Sedang Berlangsung";
        } else {
            $periode = "Tidak ada periode aktif"; $semester = "-"; $statusPeriode = "Ditutup";
        }

        $queryStat = "
            WITH Jabatan_Pegawai AS (SELECT jabatan FROM geotrax_v4.pegawai WHERE pegawai_id = ?),
            Target_Aktivitas AS (
                SELECT COUNT(DISTINCT ak.aktivitas_id) as total_target
                FROM geotrax_v4.aktivitas_kompeten ak
                JOIN geotrax_v4.elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                JOIN geotrax_v4.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
                WHERE uk.posisi_target ILIKE '%' || (SELECT jabatan FROM Jabatan_Pegawai) || '%' AND ak.aktif = 'Y'
            )
            SELECT
                (SELECT total_target FROM Target_Aktivitas) as total_target_akt,
                (SELECT COUNT(DISTINCT pd.aktivitas_id) FROM geotrax_v4.penilaian_detail pd JOIN geotrax_v4.penilaian_header ph ON pd.penilaian_id = ph.penilaian_id WHERE ph.pegawai_id = ? AND pd.skor_final > 0) as total_sistem
        ";
        $progData = DB::selectOne($queryStat, [$idPegawai, $idPegawai]);

        $formatProgress = function($assessed, $total) {
            if ($total == 0) return ["progress" => 0, "status" => "Belum Ada Target", "warna" => "waiting"];
            $pct = round(($assessed / $total) * 100);
            if ($pct >= 100) return ["progress" => 100, "status" => "Selesai Dianalisis", "warna" => "success"];
            if ($pct > 0) return ["progress" => $pct, "status" => "Proses Analisis", "warna" => "running"];
            return ["progress" => 0, "status" => "Belum Dianalisis", "warna" => "waiting"];
        };

        $penilaian = ["sistem" => $formatProgress((int)($progData->total_sistem ?? 0), (int)($progData->total_target_akt ?? 0))];
        return view('pegawai.penilaian.index', compact('periode', 'semester', 'statusPeriode', 'penilaian'));
    }

    public function list(Request $request)
    {
        $pegawai = Auth::user();
        $jabatan = $pegawai->jabatan ?? '';
        $kode_unit_selected = $request->kode_unit ?? '';

        $units = DB::table('geotrax_v4.aktivitas_kompeten as ak')
            ->join('geotrax_v4.elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('geotrax_v4.unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('uk.posisi_target', 'ILIKE', "%$jabatan%")
            ->select('uk.kode_unit', 'uk.judul_unit')
            ->distinct()->orderBy('uk.kode_unit', 'asc')->get();

        $query = DB::table('geotrax_v4.aktivitas_kompeten as ak')
            ->join('geotrax_v4.elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('geotrax_v4.unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('uk.posisi_target', 'ILIKE', "%$jabatan%")->where('ak.aktif', 'Y')
            ->select('ak.aktivitas_id', 'ak.detail_aktivitas', 'ek.elemen_kompetensi', 'uk.kode_unit', 'uk.judul_unit',
                DB::raw("(SELECT COUNT(*) FROM geotrax_v4.bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = {$pegawai->pegawai_id}) as is_uploaded"),
                DB::raw("(SELECT tanggal_upload FROM geotrax_v4.bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = {$pegawai->pegawai_id} ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload")
            );

        if (!empty($kode_unit_selected)) $query->where('uk.kode_unit', $kode_unit_selected);
        $paginatedEvidence = $query->orderBy('uk.kode_unit', 'asc')->orderBy('ak.aktivitas_id', 'asc')->paginate(10);

        return view('pegawai.penilaian.list', compact('units', 'kode_unit_selected', 'paginatedEvidence'));
    }

    public function show(Request $request, $aktivitas_id)
    {
        $pegawai = Auth::user();
        $sqlDetail = "
            SELECT ak.aktivitas_id, ak.detail_aktivitas, uk.kode_unit, uk.judul_unit,
                (SELECT file_path FROM geotrax_v4.bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) as file_path,
                (SELECT tanggal_upload FROM geotrax_v4.bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload,
                pd.skor_final as skor_tampil, ph.status as status_penilaian, ph.rekomendasi
            FROM geotrax_v4.aktivitas_kompeten ak JOIN geotrax_v4.elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id JOIN geotrax_v4.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            LEFT JOIN geotrax_v4.penilaian_header ph ON uk.kode_unit = ph.kode_unit AND ph.pegawai_id = ?
            LEFT JOIN geotrax_v4.penilaian_detail pd ON ph.penilaian_id = pd.penilaian_id AND pd.aktivitas_id = ak.aktivitas_id
            WHERE ak.aktivitas_id = ? LIMIT 1
        ";
        $data = DB::selectOne($sqlDetail, [$pegawai->pegawai_id, $pegawai->pegawai_id, $pegawai->pegawai_id, $aktivitas_id]);

        if (!$data || empty($data->file_path)) return redirect()->route('pegawai.penilaian.list')->with('error', 'Aktivitas tidak ditemukan/belum diunggah!');
        return view('pegawai.penilaian.show', compact('data', 'aktivitas_id'));
    }

    public function hasil(Request $request)
    {
        $userLogin = Auth::user();
        $pegawai = DB::table('geotrax_v4.pegawai_skkni')->where('username', $userLogin->username)->first();

        if (!$pegawai) return redirect()->route('login');

        $jabatanPegawai = $pegawai->jabatan ?? 'Belum Ada Jabatan';

        // 1. FILTER CERDAS PERIODE
        $listPeriode = DB::table('geotrax_v4.periode_penilaian as pp')
            ->where('pp.nama_periode', $jabatanPegawai) // <--- TAMBAHKAN BARIS INI
            ->whereExists(function ($query) use ($pegawai) {
                $query->select(DB::raw(1))
                      ->from('geotrax_v4.penilaian_header as ph')
                      ->where('ph.pegawai_id', $pegawai->pegawai_id)
                      ->where('ph.status', 'Selesai')
                      ->whereRaw('DATE(ph.waktu_submit) >= CAST(pp.tanggal_mulai AS DATE)')
                      ->whereRaw('DATE(ph.waktu_submit) <= CAST(pp.tanggal_selesai AS DATE)');
            })
            ->orderBy('pp.tanggal_selesai', 'desc')
            ->get();

        $periode_terpilih = $request->periode_id;
        if (empty($periode_terpilih) && $listPeriode->isNotEmpty()) {
            $periode_terpilih = $listPeriode->first()->periode_id;
        }

        $list_uk = collect(); $detail_per_uk = collect(); $hasilSoftskill = collect();
        $skor_aktual_total = 0; $kategori_raw = "Belum Ada Nilai"; $nilai_minimum = 0; $nilai_maksimum = 0;
        $sudah_dinilai = false; 
        $ncf = 0; $nsf = 0; $nilai_pm_100 = 0; $persen_ncf = 60; $persen_nsf = 40;
        $jumlah_kompetensi = 0; $total_kuk_jabatan = 0;

        if ($periode_terpilih) {
            $periodeDetail = DB::table('geotrax_v4.periode_penilaian')->where('periode_id', $periode_terpilih)->first();

            if ($periodeDetail) {
                // 2. AMBIL SEMUA PENILAIAN UNIT DI PERIODE TERSEBUT (Persis seperti Pimpinan)
                $list_uk = DB::table('geotrax_v4.penilaian_header as ph')
                    ->join('geotrax_v4.unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
                    ->where('ph.pegawai_id', $pegawai->pegawai_id)
                    ->where('ph.status', 'Selesai')
                    ->whereDate('ph.waktu_submit', '>=', $periodeDetail->tanggal_mulai)
                    ->whereDate('ph.waktu_submit', '<=', $periodeDetail->tanggal_selesai)
                    ->orderBy('ph.waktu_submit', 'desc')
                    ->select('ph.*', 'uk.judul_unit')
                    ->get();

                if ($list_uk->isNotEmpty()) {
                    $sudah_dinilai = true;
                    $total_skor_raw_jabatan = 0; $total_ncf = 0; $total_nsf = 0;

                    foreach ($list_uk as $uk) {
                        // A. RAW SCORE (Murni SKKNI seperti Excel)
                        $details = DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $uk->penilaian_id)->get();
                        $jumlah_kuk = $details->count();
                        $skor_aktual_raw = $details->sum('skor_final');
                        
                        $uk->jumlah_kuk = $jumlah_kuk;
                        $uk->skor_aktual_raw = $skor_aktual_raw;
                        $uk->min_skor = $jumlah_kuk * 1;
                        $uk->max_skor = $jumlah_kuk * 5;

                        $total_kuk_jabatan += $jumlah_kuk;
                        $total_skor_raw_jabatan += $skor_aktual_raw;

                        // B. NCF (Core Factor) PER UNIT
                        $tot_b_ncf = 0; $c_ncf = 0;
                        foreach($details as $hd) {
                            $tot_b_ncf += $this->konversiBobotGap($hd->skor_final - 5);
                            $c_ncf++;
                        }
                        $uk->ncf_unit = ($c_ncf > 0) ? ($tot_b_ncf / $c_ncf) : 0;
                        $total_ncf += $uk->ncf_unit;
                        
                        // C. NSF (Secondary Factor) PER UNIT
                        $sfs = DB::table('geotrax_v4.penilaian_softskill')->where('penilaian_id', $uk->penilaian_id)->get();
                        $tot_b_nsf = 0; $c_nsf = 0;
                        foreach($sfs as $sf) {
                            $tot_b_nsf += $sf->bobot_gap;
                            $c_nsf++;
                        }
                        $uk->nsf_unit = ($c_nsf > 0) ? ($tot_b_nsf / $c_nsf) : 0;
                        $total_nsf += $uk->nsf_unit;
                    }

                    // 3. KALKULASI HASIL AKHIR
                    $jumlah_kompetensi = $list_uk->count();
                    $nilai_minimum = $total_kuk_jabatan * 1;
                    $nilai_maksimum = $total_kuk_jabatan * 5;
                    $skor_aktual_total = $total_skor_raw_jabatan;

                    $ncf = ($jumlah_kompetensi > 0) ? ($total_ncf / $jumlah_kompetensi) : 0;
                    $nsf = ($jumlah_kompetensi > 0) ? ($total_nsf / $jumlah_kompetensi) : 0;

                    $nilai_pm_100 = $list_uk->avg('nilai_akhir') ?? 0;

                    // =========================================================
                    // RUMUS DINAMIS PROFESSOR (CEIL INTERVAL) UNTUK PEGAWAI
                    // =========================================================
                    if ($total_kuk_jabatan > 0) {
                        $interval_bulat = ceil(($nilai_maksimum - $nilai_minimum) / 5);
                        $b_sangat_tinggi = $nilai_maksimum - $interval_bulat + 1;
                        $b_tinggi        = $b_sangat_tinggi - $interval_bulat;
                        $b_sedang        = $b_tinggi - $interval_bulat;
                        $b_rendah        = $b_sedang - $interval_bulat;

                        if ($skor_aktual_total >= $b_sangat_tinggi) $kategori_raw = "Sangat Tinggi";
                        elseif ($skor_aktual_total >= $b_tinggi) $kategori_raw = "Tinggi";
                        elseif ($skor_aktual_total >= $b_sedang) $kategori_raw = "Sedang";
                        elseif ($skor_aktual_total >= $b_rendah) $kategori_raw = "Rendah";
                        else $kategori_raw = "Sangat Rendah";
                    } else {
                        $kategori_raw = "Sangat Rendah";
                    }
                    // =========================================================

                    // Persentase Jabatan
                    $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')->where('jabatan', 'ILIKE', "%{$jabatanPegawai}%")->first();
                    if($master_jabatan) { $persen_ncf = $master_jabatan->persentase_cf; $persen_nsf = $master_jabatan->persentase_sf; }

                    // 7. TARIK DETAIL SKKNI (SEMUA UNIT DI PERIODE TERSEBUT UNTUK ACCORDION)
                    $penilaian_ids = $list_uk->pluck('penilaian_id')->toArray();
                    $hasilDetail = DB::table('geotrax_v4.penilaian_detail as pd')
                        ->join('geotrax_v4.aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
                        ->join('geotrax_v4.penilaian_header as ph', 'pd.penilaian_id', '=', 'ph.penilaian_id')
                        ->whereIn('pd.penilaian_id', $penilaian_ids)
                        ->select('pd.*', 'ak.detail_aktivitas', 'ph.kode_unit')
                        ->orderBy('pd.aktivitas_id', 'asc')
                        ->get();
                    
                    // Kalkulasi Gap & Bobot manual untuk view
                    foreach($hasilDetail as $hd) {
                        $hd->target = 5;
                        $hd->gap = $hd->skor_final - 5;
                        $hd->bobot = $this->konversiBobotGap($hd->gap);
                    }
                    $detail_per_uk = $hasilDetail->groupBy('kode_unit');

                    // 8. TARIK DETAIL SOFTSKILL DARI PENILAIAN TERBARU
                    $latest_penilaian_id = $list_uk->first()->penilaian_id;
                    $hasilSoftskill = DB::table('geotrax_v4.penilaian_softskill as ps')
                        ->join('geotrax_v4.standar_softskill as ss', 'ps.softskill_id', '=', 'ss.softskill_id')
                        ->where('ps.penilaian_id', $latest_penilaian_id)
                        ->select('ps.*', 'ss.nama_softskill', 'ss.deskripsi', 'ss.target_skor')->orderBy('ss.softskill_id', 'asc')->get();
                }
            }
        }

        return view('pegawai.penilaian.hasil', compact(
            'listPeriode', 'periode_terpilih', 'jabatanPegawai',
            'skor_aktual_total', 'kategori_raw', 'nilai_minimum', 'nilai_maksimum', 'sudah_dinilai',
            'list_uk', 'detail_per_uk', 'hasilSoftskill', 'ncf', 'nsf', 'nilai_pm_100', 'persen_ncf', 'persen_nsf',
            'jumlah_kompetensi', 'total_kuk_jabatan'
        ));
    }
}