<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PegawaiPenilaianController extends Controller
{
    // --- 1. HALAMAN MENU UTAMA (PENILAIAN SISTEM) ---
    public function index()
    {
        $pegawai = Auth::user();
        $idPegawai = $pegawai->pegawai_id;

        // Cek Periode
        $periodeAktif = DB::table('periode_penilaian')->where('status_aktif', 'Y')->first();
        if ($periodeAktif) {
            $periode = $periodeAktif->nama_periode;
            $semester = date('d M Y', strtotime($periodeAktif->tanggal_mulai)) . " - " . date('d M Y', strtotime($periodeAktif->tanggal_selesai));
            $statusPeriode = "Sedang Berlangsung";
        } else {
            $periode = "Tidak ada periode aktif"; $semester = "-"; $statusPeriode = "Ditutup";
        }

        // Statistik Penilaian (Hanya Analisis Sistem Profile Matching)
        $queryStat = "
            WITH Jabatan_Pegawai AS (SELECT jabatan FROM pegawai WHERE pegawai_id = ?),
            Target_Aktivitas AS (
                SELECT COUNT(DISTINCT ak.aktivitas_id) as total_target
                FROM aktivitas_kompeten ak
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
                WHERE uk.posisi_target ILIKE '%' || (SELECT jabatan FROM Jabatan_Pegawai) || '%' AND ak.aktif = 'Y'
            )
            SELECT 
                (SELECT total_target FROM Target_Aktivitas) as total_target_akt,
                (SELECT COUNT(DISTINCT pd.aktivitas_id) FROM penilaian_detail pd JOIN penilaian_header ph ON pd.penilaian_id = ph.penilaian_id WHERE ph.pegawai_id = ? AND pd.skor_final > 0) as total_sistem
        ";
        $progData = DB::selectOne($queryStat, [$idPegawai, $idPegawai]);

        $totalTargetAkt = (int)($progData->total_target_akt ?? 0);
        $totSistem      = (int)($progData->total_sistem ?? 0);

        // Helper function
        $formatProgress = function($assessed, $total) {
            if ($total == 0) return ["progress" => 0, "status" => "Belum Ada Target", "warna" => "waiting"];
            $pct = round(($assessed / $total) * 100);
            if ($pct >= 100) return ["progress" => 100, "status" => "Selesai Dianalisis", "warna" => "success"];
            if ($pct > 0) return ["progress" => $pct, "status" => "Proses Analisis", "warna" => "running"];
            return ["progress" => 0, "status" => "Belum Dianalisis", "warna" => "waiting"];
        };

        $penilaian = [
            "sistem" => $formatProgress($totSistem, $totalTargetAkt)
        ];

        return view('pegawai.penilaian.index', compact('periode', 'semester', 'statusPeriode', 'penilaian'));
    }

    // --- 2. HALAMAN DAFTAR EVIDENCE ---
    public function list(Request $request)
    {
        $pegawai = Auth::user();
        $jabatan = $pegawai->jabatan ?? '';
        $kode_unit_selected = $request->kode_unit ?? '';

        $units = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('uk.posisi_target', 'ILIKE', "%$jabatan%")
            ->select('uk.kode_unit', 'uk.judul_unit')
            ->distinct()
            ->orderBy('uk.kode_unit', 'asc')
            ->get();

        $query = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('uk.posisi_target', 'ILIKE', "%$jabatan%")
            ->where('ak.aktif', 'Y')
            ->select('ak.aktivitas_id', 'ak.detail_aktivitas', 'ek.elemen_kompetensi', 'uk.kode_unit', 'uk.judul_unit',
                DB::raw("(SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = {$pegawai->pegawai_id}) as is_uploaded"),
                DB::raw("(SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = {$pegawai->pegawai_id} ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload")
            );

        if (!empty($kode_unit_selected)) {
            $query->where('uk.kode_unit', $kode_unit_selected);
        }

        $paginatedEvidence = $query->orderBy('uk.kode_unit', 'asc')->orderBy('ak.aktivitas_id', 'asc')->paginate(10);

        return view('pegawai.penilaian.list', compact('units', 'kode_unit_selected', 'paginatedEvidence'));
    }

    // --- 3. HALAMAN RINCIAN NILAI SISTEM ---
    public function show(Request $request, $aktivitas_id)
    {
        $pegawai = Auth::user();

        $sqlDetail = "
            SELECT 
                ak.aktivitas_id, ak.detail_aktivitas, uk.kode_unit, uk.judul_unit,
                (SELECT file_path FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) as file_path,
                (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload,
                pd.skor_final as skor_tampil, ph.status as status_penilaian, ph.rekomendasi
            FROM aktivitas_kompeten ak
            JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
            JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            LEFT JOIN penilaian_header ph ON uk.kode_unit = ph.kode_unit AND ph.pegawai_id = ?
            LEFT JOIN penilaian_detail pd ON ph.penilaian_id = pd.penilaian_id AND pd.aktivitas_id = ak.aktivitas_id
            WHERE ak.aktivitas_id = ? LIMIT 1
        ";
        $data = DB::selectOne($sqlDetail, [$pegawai->pegawai_id, $pegawai->pegawai_id, $pegawai->pegawai_id, $aktivitas_id]);

        if (!$data || empty($data->file_path)) {
            return redirect()->route('pegawai.penilaian.list')->with('error', 'Aktivitas tidak ditemukan atau Anda belum mengunggah file!');
        }

        return view('pegawai.penilaian.show', compact('data', 'aktivitas_id'));
    }

    // --- 4. HASIL KOMPETENSI PROFILE MATCHING ---
    public function hasil(Request $request)
    {
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        $pegawai = \Illuminate\Support\Facades\DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        
        if (!$pegawai) return redirect()->route('login');

        $jabatanPegawai = $pegawai->jabatan ?? 'Belum Ada Jabatan';
        
        // 1. Ambil list periode
        $listPeriode = \Illuminate\Support\Facades\DB::table('geotrax_v3.periode_penilaian')
            ->where('nama_periode', $jabatanPegawai)
            ->orderBy('tanggal_selesai', 'desc')
            ->get();

        $periode_terpilih = $request->periode_id;
        if (empty($periode_terpilih) && $listPeriode->isNotEmpty()) {
            $periode_terpilih = $listPeriode->first()->periode_id;
        }

        $hasilRingkasan = null;
        $match_scores = [];
        $best_match_role = '-';
        $is_match = false;

        if ($periode_terpilih) {
            // AMBIL HASIL PROFILE MATCHING ASLI DARI DATABASE
            $hasil_pm = \Illuminate\Support\Facades\DB::table('geotrax_v3.p_hasil_profile_matching as pm')
                ->join('geotrax_v3.p_master_jabatan as mj', 'pm.id_jabatan', '=', 'mj.id_jabatan')
                ->where('pm.id_pegawai', $pegawai->pegawai_id)
                ->orderBy('pm.peringkat', 'asc') // Peringkat 1 di atas
                ->get();

            if ($hasil_pm->isNotEmpty()) {
                $best_match = $hasil_pm->first(); // Peringkat 1
                
                $kategori = "Sangat Sesuai";
                if ($best_match->nilai_total < 3) $kategori = "Cukup Sesuai";
                if ($best_match->nilai_total < 2) $kategori = "Kurang Sesuai";

                $hasilRingkasan = (object)[
                    'nilai_akhir' => $best_match->nilai_total,
                    'kategori' => $kategori
                ];

                $best_match_role = $best_match->nama_jabatan;
                
                // Cek apakah jabatan sekarang = rekomendasi sistem
                $is_match = (stripos($jabatanPegawai, $best_match_role) !== false || stripos($best_match_role, $jabatanPegawai) !== false);

                // Siapkan data untuk grafik bar
                foreach($hasil_pm as $pm) {
                    // Skala max PM adalah 5.0, kita ubah ke persen untuk lebar grafik
                    $persen = ($pm->nilai_total / 5.0) * 100; 
                    
                    $match_scores[$pm->nama_jabatan] = [
                        'skor_asli' => $pm->nilai_total,
                        'persen' => min($persen, 100)
                    ];
                }
            }
        }

        return view('pegawai.penilaian.hasil', compact(
            'listPeriode', 'periode_terpilih', 'hasilRingkasan', 
            'jabatanPegawai', 'match_scores', 'best_match_role', 'is_match'
        ));
    }
}