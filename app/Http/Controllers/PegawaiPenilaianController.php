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
        $pegawai = Auth::user();
        $jabatanPegawai = $pegawai->jabatan ?? 'Belum Ada Jabatan';
        
        // 1. Ambil list periode dan urutkan dari yang terbaru
        $listPeriode = DB::table('periode_penilaian')
            ->where('nama_periode', 'ILIKE', "%{$jabatanPegawai}%")
            ->orderBy('tanggal_selesai', 'desc')
            ->get();

        // 2. Auto-select periode terbaru jika user belum memilih dari dropdown
        $periode_terpilih = $request->periode_id;
        if (empty($periode_terpilih) && $listPeriode->isNotEmpty()) {
            $periode_terpilih = $listPeriode->first()->periode_id;
        }

        $hasilRingkasan = null;
        $hasilDetail = collect([]);
        $match_scores = [];
        $best_match_role = '-';
        $is_match = false;

        if ($periode_terpilih) {
            // Ambil nama periode (Karena di sistem ini nama periode identik dengan nama jabatan/posisi)
            $nama_periode = DB::table('periode_penilaian')->where('periode_id', $periode_terpilih)->value('nama_periode');

            // 3. Kalkulasi Rata-rata Nilai Akhir (Keseluruhan Unit Kompetensi)
            $query_header = DB::table('penilaian_header as ph')
                ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
                ->where('ph.pegawai_id', $pegawai->pegawai_id)
                ->where('uk.posisi_target', 'ILIKE', "%{$nama_periode}%");

            if ($query_header->count() > 0) {
                // Rata-rata dari seluruh nilai unit yang sudah disahkan
                $avg_nilai = $query_header->avg('ph.nilai_akhir');
                
                if ($avg_nilai >= 85) $kat = "Sangat Kompeten";
                elseif ($avg_nilai >= 70) $kat = "Kompeten";
                elseif ($avg_nilai >= 55) $kat = "Cukup Kompeten";
                else $kat = "Belum Kompeten";

                $hasilRingkasan = (object)[
                    'nilai_akhir' => $avg_nilai,
                    'kategori' => $kat
                ];
            }

            // 4. Detail Tabel Skor Historis per Aktivitas
            $hasilDetail = DB::table('penilaian_header as ph')
                ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
                ->join('penilaian_detail as pd', 'ph.penilaian_id', '=', 'pd.penilaian_id')
                ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
                ->where('ph.pegawai_id', $pegawai->pegawai_id)
                ->where('uk.posisi_target', 'ILIKE', "%{$nama_periode}%")
                ->select(
                    'pd.aktivitas_id', 'ak.detail_aktivitas', 'pd.skor_final', 
                    'ph.nilai_akhir', 'ph.kategori', 'ph.rekomendasi'
                )
                ->orderBy('ak.aktivitas_id', 'asc')
                ->paginate(5);

            // 5. DUMMY LOGIC: REKOMENDASI PENEMPATAN PROFILE MATCHING
            if ($hasilRingkasan) {
                $roles = ['Kurator', 'Edukator', 'Konservator', 'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'];
                $nilai_aktual = $hasilRingkasan->nilai_akhir; // Memakai rata-rata skor

                // Gunakan ID pegawai sebagai seed agar random generator konsisten (angka tidak berubah-ubah saat di-refresh)
                srand($pegawai->pegawai_id); 
                
                foreach($roles as $r) {
                    if ($r == $jabatanPegawai) {
                        $match_scores[$r] = $nilai_aktual; 
                    } else {
                        // Generate nilai dummy (40.00 - 85.00) untuk bidang lain
                        $match_scores[$r] = rand(4000, 8500) / 100;
                    }
                }
                srand(); // Reset seed kembali ke mode acak

                // Urutkan nilai dari tertinggi ke terendah (arsort)
                arsort($match_scores);
                
                // Ambil jabatan yang berada di urutan teratas (paling cocok/GAP terkecil)
                $best_match_role = array_key_first($match_scores);
                $is_match = ($best_match_role == $jabatanPegawai);
            }
        }

        return view('pegawai.penilaian.hasil', compact(
            'listPeriode', 'periode_terpilih', 'hasilRingkasan', 'hasilDetail', 
            'jabatanPegawai', 'match_scores', 'best_match_role', 'is_match'
        ));
    }
}