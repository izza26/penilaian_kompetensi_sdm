<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;

class SkoringController extends Controller
{
    public function timSaya(\Illuminate\Http\Request $request)
    {
        // 1. Ambil data pimpinan yang login
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
        }

        if (!$pimpinan) {
            return redirect()->route('pimpinan.dashboard')->with('error', 'Data pegawai Anda tidak ditemukan!');
        }

        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
        $filterJabatan = [];

        // 2. Tentukan Bawahan Sesuai Jabatan Pimpinan
        if (str_contains($jabatanPimpinan, 'Kepala Museum')) {
            $filterJabatan = ['Koordinator', 'Manajer', 'Kurator'];
        } elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) {
            $filterJabatan = ['Konservator', 'Register'];
        } elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) {
            $filterJabatan = ['Edukator', 'Penata Pameran'];
        } elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) {
            $filterJabatan = ['Humas'];
            
        // --- TAMBAHKAN BARIS INI ---
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $filterJabatan = ['Kurator'];
        }

        // 3. Query Super: Ambil Data Bawahan
        $bawahanRaw = \Illuminate\Support\Facades\DB::table('pegawai')
            ->where('pegawai_id', '!=', $pimpinan->pegawai_id) // KUNCI 1: Hapus diri sendiri (Kasus Pak Hendra)
            ->where(function($query) use ($filterJabatan) {
                if (empty($filterJabatan)) {
                    // KUNCI 2: Jika tidak punya bawahan (Kasus Bu Siti Aminah), KOSONGKAN TABEL!
                    $query->whereRaw('1=0'); 
                } else {
                    foreach ($filterJabatan as $jab) {
                        $query->orWhere('jabatan', 'LIKE', '%' . $jab . '%');
                    }
                }
            })->get();

        $data_pegawai = [];

        // Peta Distribusi 34 UK Berdasarkan Jabatan Fungsional
        $mapUK = [
            'Kurator' => ['001', '002', '003', '004', '005', '006', '007'],
            'Register' => ['008', '009', '010', '011', '012', '013'],
            'Konservator' => ['014', '015', '016', '017', '018'],
            'Edukator' => ['019', '020', '021', '022', '023'],
            'Penata Pameran' => ['024', '025', '026', '027', '028'],
            'Humas' => ['029', '030', '031', '032', '033', '034']
        ];

        foreach ($bawahanRaw as $row) {
            $pid = $row->pegawai_id;
            $jabatanBawahan = $row->jabatan;
            
            $data_pegawai[$pid] = [
                'pegawai_id' => $row->pegawai_id,
                'pegawai_nama' => $row->pegawai_nama,
                'nip_nik' => $row->nip_nik,
                'jabatan' => $jabatanBawahan,
                'total_target' => 0,
                'total_terkumpul' => 0,
                'total_uk' => 0,
                'uk_dinilai' => 0,
                'uks' => [],
                'first_uk_to_score' => 'UK-DEFAULT'
            ];

            $allowed_codes = [];
            if (str_contains($jabatanBawahan, 'Kurator')) $allowed_codes = $mapUK['Kurator'];
            elseif (str_contains($jabatanBawahan, 'Register')) $allowed_codes = $mapUK['Register'];
            elseif (str_contains($jabatanBawahan, 'Konservator')) $allowed_codes = $mapUK['Konservator'];
            elseif (str_contains($jabatanBawahan, 'Edukator')) $allowed_codes = $mapUK['Edukator'];
            elseif (str_contains($jabatanBawahan, 'Penata Pameran')) $allowed_codes = $mapUK['Penata Pameran'];
            elseif (str_contains($jabatanBawahan, 'Humas') || str_contains($jabatanBawahan, 'Pemasaran')) $allowed_codes = $mapUK['Humas'];

            $ukQuery = "
                SELECT DISTINCT uk.kode_unit, uk.judul_unit, 
                    COALESCE(SUM(ak.jumlah_evidence_wa), 0) AS target_dokumen
                FROM unit_kompetensi uk
                JOIN elemen_kompetensi ek ON uk.kode_unit = ek.kode_unit
                JOIN aktivitas_kompeten ak ON ek.elemen_id = ak.elemen_id
                WHERE ak.aktif = 'Y' 
            ";

            if (!empty($allowed_codes)) {
                $likeConditions = [];
                foreach ($allowed_codes as $code) {
                    $likeConditions[] = "uk.kode_unit LIKE '%$code%'";
                }
                $ukQuery .= " AND (" . implode(' OR ', $likeConditions) . ")";
            } else {
                $ukQuery .= " AND 1=0"; 
            }

            $ukQuery .= " GROUP BY uk.kode_unit, uk.judul_unit ORDER BY uk.kode_unit ASC";
            $ukList = \Illuminate\Support\Facades\DB::select($ukQuery);

            foreach ($ukList as $uk) {
                $uploadData = \Illuminate\Support\Facades\DB::selectOne("
                    SELECT 
                        COUNT(bp.bukti_id) AS total_upload,
                        MAX(bp.tanggal_upload) AS waktu_terakhir
                    FROM bukti_pegawai bp
                    JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
                    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                    WHERE bp.pegawai_id = ? AND ek.kode_unit = ?
                ", [$pid, $uk->kode_unit]);

                $is_dinilai = \Illuminate\Support\Facades\DB::table('penilaian_header')
                                ->where('pegawai_id', $pid)
                                ->where('kode_unit', $uk->kode_unit)
                                ->where('status', 'Selesai')
                                ->exists();

                $data_pegawai[$pid]['uks'][] = [
                    'kode_unit' => $uk->kode_unit,
                    'judul_unit' => $uk->judul_unit,
                    'target' => $uk->target_dokumen,
                    'terkumpul' => $uploadData->total_upload ?? 0,
                    'waktu_terakhir' => $uploadData->waktu_terakhir,
                    'is_dinilai' => $is_dinilai
                ];

                $data_pegawai[$pid]['total_target'] += $uk->target_dokumen;
                $data_pegawai[$pid]['total_terkumpul'] += ($uploadData->total_upload ?? 0);
                $data_pegawai[$pid]['total_uk'] += 1;
                
                if ($is_dinilai) {
                    $data_pegawai[$pid]['uk_dinilai'] += 1;
                } elseif ($data_pegawai[$pid]['first_uk_to_score'] === 'UK-DEFAULT') {
                    $data_pegawai[$pid]['first_uk_to_score'] = $uk->kode_unit;
                }
            }
        }

        return view('pimpinan.tim_saya.index', compact('data_pegawai', 'pimpinan'));
    }
    // --- 1. HALAMAN LIST PEGAWAI & UNIT KOMPETENSI ---
    public function index(Request $request)
    {
        $filter_jabatan = $request->jabatan ?? 'ALL';
        $filter_status = $request->status ?? 'ALL';

        $list_jabatan = DB::table('periode_penilaian')->distinct()->orderBy('nama_periode', 'asc')->pluck('nama_periode');

        $params = [];
        $jabatanCond = "";

        if ($filter_jabatan !== 'ALL') {
            $jabatanCond = " AND p.jabatan = ? ";
            $params[] = $filter_jabatan;
        }

        $sql = "
            WITH Target_UK AS (
                SELECT ek.kode_unit, SUM(ak.jumlah_evidence_wa) AS total_target
                FROM aktivitas_kompeten ak
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                WHERE ak.aktif = 'Y'
                GROUP BY ek.kode_unit
            ),
            Upload_UK AS (
                SELECT 
                    bp.pegawai_id, ek.kode_unit, 
                    COUNT(bp.bukti_id) AS total_upload,
                    MAX(bp.tanggal_upload) AS waktu_terakhir_upload
                FROM bukti_pegawai bp
                JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                GROUP BY bp.pegawai_id, ek.kode_unit
            ),
            UK_Jabatan AS (
                SELECT DISTINCT p.jabatan, uk.kode_unit, uk.judul_unit
                FROM bukti_pegawai bp
                JOIN pegawai p ON bp.pegawai_id = p.pegawai_id
                JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
                UNION
                SELECT DISTINCT p.jabatan, uk.kode_unit, uk.judul_unit
                FROM penilaian_header ph
                JOIN pegawai p ON p.pegawai_id = ph.pegawai_id
                JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
            ),
            Pegawai_Aktif AS (
                SELECT p.pegawai_id, p.pegawai_nama, p.jabatan
                FROM pegawai p
                WHERE (p.pegawai_id IN (SELECT DISTINCT pegawai_id FROM bukti_pegawai)
                   OR p.pegawai_id IN (SELECT DISTINCT pegawai_id FROM penilaian_header))
                   $jabatanCond
            )
            SELECT 
                pa.pegawai_id, pa.pegawai_nama, pa.jabatan,
                uj.kode_unit, uj.judul_unit,
                COALESCE(t.total_target, 0) AS target_dokumen,
                COALESCE(u.total_upload, 0) AS terkumpul_dokumen,
                u.waktu_terakhir_upload,
                (SELECT COUNT(*) FROM penilaian_header ph WHERE ph.pegawai_id = pa.pegawai_id AND ph.kode_unit = uj.kode_unit AND ph.status = 'Selesai') AS is_dinilai
            FROM Pegawai_Aktif pa
            JOIN UK_Jabatan uj ON pa.jabatan = uj.jabatan
            LEFT JOIN Target_UK t ON uj.kode_unit = t.kode_unit
            LEFT JOIN Upload_UK u ON u.pegawai_id = pa.pegawai_id AND u.kode_unit = uj.kode_unit
            ORDER BY pa.pegawai_nama ASC, uj.kode_unit ASC
        ";

        $q_data = DB::select($sql, $params);

        $data_pegawai = [];
        foreach ($q_data as $row) {
            $pid = $row->pegawai_id;
            
            if (!isset($data_pegawai[$pid])) {
                $data_pegawai[$pid] = [
                    'pegawai_id' => $row->pegawai_id, 'nama' => $row->pegawai_nama, 'jabatan' => $row->jabatan,
                    'total_target' => 0, 'total_terkumpul' => 0, 'total_uk' => 0, 'uk_dinilai' => 0,
                    'uks' => [], 'first_uk_to_score' => null
                ];
            }
            
            $data_pegawai[$pid]['uks'][] = [
                'kode_unit' => $row->kode_unit, 'judul_unit' => $row->judul_unit,
                'target' => $row->target_dokumen, 'terkumpul' => $row->terkumpul_dokumen,
                'waktu_terakhir' => $row->waktu_terakhir_upload, 'is_dinilai' => $row->is_dinilai > 0
            ];
            
            $data_pegawai[$pid]['total_target'] += $row->target_dokumen;
            $data_pegawai[$pid]['total_terkumpul'] += $row->terkumpul_dokumen;
            $data_pegawai[$pid]['total_uk'] += 1;
            
            if ($row->is_dinilai > 0) $data_pegawai[$pid]['uk_dinilai'] += 1;
            else if (is_null($data_pegawai[$pid]['first_uk_to_score'])) $data_pegawai[$pid]['first_uk_to_score'] = $row->kode_unit;
        }

        if ($filter_status !== 'ALL') {
            foreach ($data_pegawai as $pid => $peg) {
                $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk'] && $peg['total_uk'] > 0);
                if ($filter_status === 'belum' && $semua_selesai) unset($data_pegawai[$pid]); 
                elseif ($filter_status === 'selesai' && !$semua_selesai) unset($data_pegawai[$pid]); 
            }
        }

        return view('pimpinan.skoring.index', compact('data_pegawai', 'list_jabatan', 'filter_jabatan', 'filter_status'));
    }

    // --- 2. DUMMY PHP PARSER ---
    private function dummyPhpParser($kode_unit, $file_path) {
        if (empty($file_path)) return 0; 
        if ($kode_unit === 'UK 1-01') return 5; 
        return rand(3, 5); 
    }

    // --- 3. KONVERSI GAP KE BOBOT ---
    private function konversiBobotGap($gap) {
        $tabel_bobot = [
             0 => 5.0,  1 => 4.5, -1 => 4.0,
             2 => 3.5, -2 => 3.0,  3 => 2.5,
            -3 => 2.0,  4 => 1.5, -4 => 1.0,
             5 => 1.0, -5 => 1.0
        ];
        return $tabel_bobot[(string)$gap] ?? 1.0;
    }

    // --- 4. HALAMAN BERI NILAI (PROFILE MATCHING EDITABLE) ---
    public function beriNilai($pegawai_id, $kode_unit)
    {
        $pegawai = DB::table('pegawai')->where('pegawai_id', $pegawai_id)->first();
        $unit = DB::table('unit_kompetensi')->where('kode_unit', $kode_unit)->first();

        if (!$pegawai || !$unit) return redirect()->route('pimpinan.skoring.index');

        $penilaian_lama = DB::table('penilaian_header')->where('pegawai_id', $pegawai_id)->where('kode_unit', $kode_unit)->first();
        $is_update = $penilaian_lama ? true : false;
        $rekomendasi_lama = $penilaian_lama ? $penilaian_lama->rekomendasi : '';

        // Ambil histori skor lama jika sudah pernah dinilai
        $skor_lama_array = [];
        if ($is_update) {
            $detail_lama = DB::table('penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->get();
            foreach ($detail_lama as $d) {
                $skor_lama_array[$d->aktivitas_id] = $d->skor_final;
            }
        }

        $sql_aktivitas = "
            SELECT 
                ak.aktivitas_id, ak.detail_aktivitas, ak.jumlah_evidence_wa, ak.kriteria_kompetens,
                (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ?) AS jml_upload,
                (SELECT file_path FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) AS file_path,
                (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) AS tanggal_upload
            FROM aktivitas_kompeten ak
            JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
            WHERE ek.kode_unit = ? AND ak.aktif = 'Y'
            ORDER BY ek.elemen_id ASC, ak.aktivitas_id ASC
        ";
        
        $list_aktivitas_raw = DB::select($sql_aktivitas, [$pegawai_id, $pegawai_id, $pegawai_id, $kode_unit]);

        $hasil_pm = [];
        $total_bobot_cf = 0;
        $total_bobot_sf = 0;
        $item_cf = 0;
        $item_sf = 0;

        foreach ($list_aktivitas_raw as $akt) {
            // -- CORE FACTOR --
            $target_cf = ($kode_unit === 'UK 1-01') ? 5 : 4; 
            
            // Baca dari Parser (Sebagai referensi untuk pimpinan)
            $nilai_parser = $this->dummyPhpParser($kode_unit, $akt->file_path);
            
            // Tentukan Nilai Profil (Jika update, ambil yang sudah diedit. Jika belum, pakai parser)
            $nilai_profil_cf = $is_update ? ($skor_lama_array[$akt->aktivitas_id] ?? $nilai_parser) : $nilai_parser;
            
            $gap_cf = $nilai_profil_cf - $target_cf;
            $bobot_cf = $this->konversiBobotGap($gap_cf);
            
            $total_bobot_cf += $bobot_cf;
            $item_cf++;

            // -- SECONDARY FACTOR (Administratif) --
            $target_sf1 = 4;
            $nilai_sf1 = ($akt->jml_upload >= $akt->jumlah_evidence_wa) ? 5 : (($akt->jml_upload > 0) ? 3 : 1);
            $bobot_sf1 = $this->konversiBobotGap($nilai_sf1 - $target_sf1);

            $target_sf2 = 4;
            $nilai_sf2 = !empty($akt->tanggal_upload) ? 4 : 1;
            $bobot_sf2 = $this->konversiBobotGap($nilai_sf2 - $target_sf2);

            $rata_bobot_sf_item = ($bobot_sf1 + $bobot_sf2) / 2;
            $total_bobot_sf += $rata_bobot_sf_item;
            $item_sf++;

            $hasil_pm[] = [
                'aktivitas_id' => $akt->aktivitas_id,
                'detail_aktivitas' => $akt->detail_aktivitas,
                'jml_upload' => $akt->jml_upload,
                'target_upload' => $akt->jumlah_evidence_wa,
                'file_path' => $akt->file_path,
                'target_cf' => $target_cf,
                'nilai_parser' => $nilai_parser, // Data original dari parser
                'nilai_profil_cf' => $nilai_profil_cf, // Data yang tampil di dropdown
                'gap_cf' => $gap_cf,
                'bobot_cf' => $bobot_cf,
                'bobot_sf_avg' => $rata_bobot_sf_item
            ];
        }

        $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;
        $nsf = ($item_sf > 0) ? ($total_bobot_sf / $item_sf) : 0;
        $ni = (0.6 * $ncf) + (0.4 * $nsf);
        $nilai_akhir_100 = ($ni / 5) * 100;

        return view('pimpinan.skoring.beri_nilai', compact(
            'pegawai', 'unit', 'pegawai_id', 'kode_unit', 'is_update', 
            'rekomendasi_lama', 'penilaian_lama', 'hasil_pm', 'ncf', 'nsf', 'ni', 'nilai_akhir_100'
        ));
    }

    // --- 5. PROSES SIMPAN NILAI (MENGKALKULASI ULANG DI BACKEND) ---
    public function simpanNilai(Request $request, $pegawai_id, $kode_unit)
    {
        DB::beginTransaction();
        try {
            $is_update = $request->is_update;
            $penilaian_id_lama = $request->penilaian_id_lama;
            
            // Ambil data untuk kalkulasi ulang di backend (Demi Keamanan Database)
            $sql_aktivitas = "
                SELECT 
                    ak.aktivitas_id, ak.jumlah_evidence_wa,
                    (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ?) AS jml_upload,
                    (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ? ORDER BY tanggal_upload DESC LIMIT 1) AS tanggal_upload
                FROM aktivitas_kompeten ak
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                WHERE ek.kode_unit = ? AND ak.aktif = 'Y'
            ";
            $list_aktivitas_raw = DB::select($sql_aktivitas, [$pegawai_id, $pegawai_id, $kode_unit]);

            $total_bobot_cf = 0; $total_bobot_sf = 0;
            $item_cf = 0; $item_sf = 0;
            $detail_to_insert = [];

            // REKALKULASI PROFILE MATCHING BERDASARKAN INPUT PIMPINAN
            foreach ($list_aktivitas_raw as $akt) {
                $akt_id = $akt->aktivitas_id;
                // Ambil nilai yang diedit pimpinan dari Form
                $skor_aktual = isset($request->skor[$akt_id]) ? (int)$request->skor[$akt_id] : 0; 

                // -- CORE FACTOR --
                $target_cf = ($kode_unit === 'UK 1-01') ? 5 : 4;
                $gap_cf = $skor_aktual - $target_cf;
                $bobot_cf = $this->konversiBobotGap($gap_cf);
                
                $total_bobot_cf += $bobot_cf;
                $item_cf++;

                // -- SECONDARY FACTOR --
                $target_sf1 = 4;
                $nilai_sf1 = ($akt->jml_upload >= $akt->jumlah_evidence_wa) ? 5 : (($akt->jml_upload > 0) ? 3 : 1);
                $bobot_sf1 = $this->konversiBobotGap($nilai_sf1 - $target_sf1);

                $target_sf2 = 4;
                $nilai_sf2 = !empty($akt->tanggal_upload) ? 4 : 1;
                $bobot_sf2 = $this->konversiBobotGap($nilai_sf2 - $target_sf2);

                $total_bobot_sf += ($bobot_sf1 + $bobot_sf2) / 2;
                $item_sf++;

                // Siapkan data detail untuk diinsert
                $detail_to_insert[] = [
                    'aktivitas_id' => $akt_id,
                    'skor_final' => $skor_aktual,
                    'jumlah_bukti_uploa' => $akt->jml_upload
                ];
            }

            // Hitung Nilai Akhir Setelah Rekalkulasi
            $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;
            $nsf = ($item_sf > 0) ? ($total_bobot_sf / $item_sf) : 0;
            $ni = (0.6 * $ncf) + (0.4 * $nsf);
            $nilai_akhir_100 = ($ni / 5) * 100;

            if ($nilai_akhir_100 >= 85) $kategori = "Sangat Kompeten";
            elseif ($nilai_akhir_100 >= 70) $kategori = "Kompeten";
            elseif ($nilai_akhir_100 >= 55) $kategori = "Cukup Kompeten";
            else $kategori = "Belum Kompeten";

            $waktu_sekarang = date('Y-m-d H:i:s'); 

            if ($is_update) {
                DB::table('penilaian_header')->where('penilaian_id', $penilaian_id_lama)->update([
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori, 
                    'rekomendasi' => $request->rekomendasi, 'waktu_submit' => $waktu_sekarang
                ]);
                DB::table('penilaian_detail')->where('penilaian_id', $penilaian_id_lama)->delete();
                $penilaian_id = $penilaian_id_lama;
            } else {
                $penilaian_id = DB::table('penilaian_header')->max('penilaian_id') + 1;
                DB::table('penilaian_header')->insert([
                    'penilaian_id' => $penilaian_id, 'pegawai_id' => $pegawai_id, 'kode_unit' => $kode_unit,
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori, 'rekomendasi' => $request->rekomendasi,
                    'status' => 'Selesai', 'detail_penilaian_i' => 0, 'waktu_submit' => $waktu_sekarang, 'catatan_umum' => ''
                ]);
            }

            // Insert Detail Penilaian Terbaru
            foreach ($detail_to_insert as $dt) {
                $new_detail_id = DB::table('penilaian_detail')->max('detail_penilaian_i') + 1;
                DB::table('penilaian_detail')->insert([
                    'detail_penilaian_i' => $new_detail_id, 'skor_final' => $dt['skor_final'],
                    'jumlah_bukti_uploa' => $dt['jumlah_bukti_uploa'], 'catatan_penilai' => 'Penilaian PM (Dikoreksi Pimpinan)',
                    'aktivitas_id' => $dt['aktivitas_id'], 'penilaian_id' => $penilaian_id
                ]);
            }

            DB::commit();
            return redirect()->route('pimpinan.tim_saya.index')->with('success', 'Koreksi Penilaian Profile Matching Berhasil Disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem!');
        }
    }
}