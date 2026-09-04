<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPenilaianController extends Controller
{
    // --- 1. TAMPILAN LIST PEGAWAI (penilaian.php) ---
    public function index(Request $request)
    {
        $cari = $request->cari;

        $sql = "
            WITH Target_UK AS (
                SELECT ek.kode_unit, SUM(COALESCE(ak.jumlah_evidence_wa, 0)) AS total_target
                FROM aktivitas_kompeten ak
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                WHERE ak.aktif = 'Y' GROUP BY ek.kode_unit
            ),
            Upload_UK AS (
                SELECT bp.pegawai_id, ek.kode_unit, COUNT(bp.bukti_id) AS total_upload
                FROM bukti_pegawai bp
                JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
                JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
                GROUP BY bp.pegawai_id, ek.kode_unit
            )
            SELECT
                p.pegawai_id, p.pegawai_nama, p.jabatan,
                uk.kode_unit, uk.judul_unit,
                COALESCE(t.total_target, 0) AS target_dokumen,
                COALESCE(u.total_upload, 0) AS terkumpul_dokumen,
                (SELECT COUNT(*) FROM penilaian_header ph WHERE ph.pegawai_id = p.pegawai_id AND ph.kode_unit = uk.kode_unit AND ph.status = 'Selesai') AS is_dinilai
            FROM pegawai p
            LEFT JOIN unit_kompetensi uk ON p.jabatan IS NOT NULL AND p.jabatan != '' AND uk.posisi_target ILIKE '%' || p.jabatan || '%'
            LEFT JOIN Target_UK t ON uk.kode_unit = t.kode_unit
            LEFT JOIN Upload_UK u ON u.pegawai_id = p.pegawai_id AND u.kode_unit = uk.kode_unit
        ";

        $params = [];
        if ($cari) {
            $sql .= " WHERE p.pegawai_nama ILIKE ? OR uk.kode_unit ILIKE ? OR p.jabatan ILIKE ?";
            $sql .= " ORDER BY p.pegawai_nama ASC, uk.kode_unit ASC";
            $params = ["%$cari%", "%$cari%", "%$cari%"];
        } else {
            $sql .= " ORDER BY p.pegawai_nama ASC, uk.kode_unit ASC";
        }

        $result = DB::select($sql, $params);

        // --- MENGAMBIL DATA RINCIAN AKTIVITAS & BUKTI ---
        // PERBAIKAN: Mengambil kolom kriteria_kompetens (KUK) alih-alih detail_aktivitas
        $semua_aktivitas = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->where('ak.aktif', 'Y')
            ->select('ek.kode_unit', 'ak.aktivitas_id', 'ak.kriteria_kompetens')
            ->get();

        $aktivitas_per_uk = [];
        foreach ($semua_aktivitas as $akt) {
            $aktivitas_per_uk[$akt->kode_unit][] = $akt;
        }

        $semua_bukti = DB::table('bukti_pegawai')->select('pegawai_id', 'aktivitas_id', 'file_path')->get();
        $bukti_per_pegawai = [];
        foreach ($semua_bukti as $b) {
            $bukti_per_pegawai[$b->pegawai_id][$b->aktivitas_id] = $b->file_path;
        }
        // ------------------------------------------------

        $data_pegawai = [];
        foreach ($result as $row) {
            $pid = $row->pegawai_id;
            if (!isset($data_pegawai[$pid])) {
                $data_pegawai[$pid] = [
                    'pegawai_id' => $row->pegawai_id, 'nama' => $row->pegawai_nama,
                    'jabatan' => $row->jabatan ?: 'Belum Diatur',
                    'total_target' => 0, 'total_terkumpul' => 0, 'total_uk' => 0, 'uk_dinilai' => 0,
                    'uks' => [], 'first_uk_to_score' => null
                ];
            }

            if (!empty($row->kode_unit)) {
                // Proses Pemisahan yang sudah diupload dan belum berdasarkan kalimat KUK
                $list_sudah = [];
                $list_belum = [];

                if (isset($aktivitas_per_uk[$row->kode_unit])) {
                    foreach ($aktivitas_per_uk[$row->kode_unit] as $akt) {
                        if (isset($bukti_per_pegawai[$pid][$akt->aktivitas_id])) {
                            $list_sudah[] = $akt->kriteria_kompetens; // Dokumen Ada -> Tampilkan KUK
                        } else {
                            $list_belum[] = $akt->kriteria_kompetens; // Dokumen Kurang -> Tampilkan KUK
                        }
                    }
                }

                $data_pegawai[$pid]['uks'][] = [
                    'kode_unit' => $row->kode_unit, 'judul_unit' => $row->judul_unit,
                    'target' => (int)$row->target_dokumen, 'terkumpul' => (int)$row->terkumpul_dokumen,
                    'is_dinilai' => $row->is_dinilai > 0,
                    'rincian_sudah' => $list_sudah,
                    'rincian_belum' => $list_belum
                ];

                $data_pegawai[$pid]['total_target'] += (int)$row->target_dokumen;
                $data_pegawai[$pid]['total_terkumpul'] += (int)$row->terkumpul_dokumen;
                $data_pegawai[$pid]['total_uk'] += 1;

                if ($row->is_dinilai > 0) $data_pegawai[$pid]['uk_dinilai'] += 1;
                else if (is_null($data_pegawai[$pid]['first_uk_to_score'])) $data_pegawai[$pid]['first_uk_to_score'] = $row->kode_unit;
            }
        }

        return view('admin.penilaian.index', compact('data_pegawai', 'cari'));
    }

    // --- 2. FORM PENILAIAN / EDIT PENILAIAN (form_penilaian.php & edit_penilaian.php) ---
    public function form($pegawai_id, $kode_unit)
    {
        $pegawai = DB::table('pegawai')->where('pegawai_id', $pegawai_id)->first();
        if (!$pegawai) return redirect()->route('admin.penilaian.index')->with('error', 'Pegawai tidak ditemukan.');

        $unit_info = DB::table('unit_kompetensi as uk')
            ->leftJoin('elemen_kompetensi as ek', 'uk.kode_unit', '=', 'ek.kode_unit')
            ->where('uk.kode_unit', $kode_unit)
            ->select('uk.judul_unit', 'ek.elemen_kompetensi')
            ->first();

        $daftar_pertanyaan = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->where('ek.kode_unit', $kode_unit)
            ->select('ak.aktivitas_id', 'ak.detail_aktivitas', 'ak.kriteria_kompetens')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        $penilaian_lama = DB::table('penilaian_header')->where('pegawai_id', $pegawai_id)->where('kode_unit', $kode_unit)->first();
        $is_update = $penilaian_lama ? true : false;

        $skor_lama_array = [];
        if ($is_update) {
            $detail_lama = DB::table('penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->get();
            foreach ($detail_lama as $d) $skor_lama_array[$d->aktivitas_id] = $d->skor_final;
        }

        return view('admin.penilaian.form', compact('pegawai', 'pegawai_id', 'kode_unit', 'unit_info', 'daftar_pertanyaan', 'is_update', 'penilaian_lama', 'skor_lama_array'));
    }

    // --- 3. PROSES SIMPAN PENILAIAN ---
    public function store(Request $request, $pegawai_id, $kode_unit)
    {
        DB::beginTransaction();
        try {
            $catatan = $request->catatan_assessor ?? '';
            $skor_array = $request->skor ?? [];
            $status_simpan = $request->status_simpan;
            $is_update = $request->is_update;

            if ($is_update) {
                $penilaian_id = $request->penilaian_id_lama;
                DB::table('penilaian_header')->where('penilaian_id', $penilaian_id)->update([
                    'catatan_umum' => $catatan, 'status' => $status_simpan, 'waktu_submit' => now()
                ]);
                DB::table('penilaian_detail')->where('penilaian_id', $penilaian_id)->delete();
            } else {
                $penilaian_id = DB::table('penilaian_header')->max('penilaian_id') + 1;
                DB::table('penilaian_header')->insert([
                    'penilaian_id' => $penilaian_id, 'pegawai_id' => $pegawai_id, 'kode_unit' => $kode_unit,
                    'catatan_umum' => $catatan, 'status' => $status_simpan, 'waktu_submit' => now(), 'detail_penilaian_i' => 0
                ]);
                DB::table('pegawai')->where('pegawai_id', $pegawai_id)->update(['penilaian_id' => $penilaian_id]);
            }

            foreach ($skor_array as $akt_id => $nilai) {
                $new_detail_id = DB::table('penilaian_detail')->max('detail_penilaian_i') + 1;
                DB::table('penilaian_detail')->insert([
                    'detail_penilaian_i' => $new_detail_id, 'aktivitas_id' => $akt_id,
                    'skor_final' => $nilai, 'penilaian_id' => $penilaian_id
                ]);
                DB::table('penilaian_header')->where('penilaian_id', $penilaian_id)->update(['detail_penilaian_i' => $new_detail_id]);
            }

            DB::commit();
            return redirect()->route('admin.penilaian.index')->with('success', 'Penilaian berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menyimpan penilaian!');
        }
    }

    // --- 4. DETAIL PENILAIAN (detail_penilaian.php) ---
    public function show($id)
    {
        // PERBAIKAN: Menghapus JOIN ke rekap_aktivitas_36 & peserta_penilaian
        // Semua data nilai diambil langsung dari penilaian_header hasil Profile Matching
        $data = DB::table('pegawai as p')
            ->leftJoin('penilaian_header as ph', 'p.pegawai_id', '=', 'ph.pegawai_id')
            ->leftJoin('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('p.pegawai_id', $id)
            ->select(
                'p.pegawai_nama', 'p.jabatan', 'p.unit_kerja',
                'uk.kode_unit', 'uk.judul_unit',
                'ph.waktu_submit', 'ph.catatan_umum', 'ph.status as status_penilaian',
                'ph.nilai_akhir as skor_akhir_360', 'ph.kategori as status_kompeten'
            )
            ->first();

        if (!$data) return redirect()->route('admin.penilaian.index')->with('error', 'Data penilaian pegawai tidak ditemukan!');

        $data->nama_elemen = DB::table('elemen_kompetensi')->where('kode_unit', $data->kode_unit)->value('elemen_kompetensi');

        // Menggunakan join yang lebih presisi (penilaian_id)
        $list_skor = DB::table('pegawai as p')
            ->join('penilaian_header as ph', 'p.pegawai_id', '=', 'ph.pegawai_id')
            ->join('penilaian_detail as pd', 'ph.penilaian_id', '=', 'pd.penilaian_id')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('p.pegawai_id', $id)
            ->select('ak.detail_aktivitas', 'ak.kriteria_kompetens', 'pd.skor_final')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        return view('admin.penilaian.show', compact('data', 'list_skor'));
    }

    // ====================================================================
    // FUNGSI KHUSUS SUPERADMIN: TAKEOVER TIM SAYA
    // ====================================================================
    public function takeoverIndex(Request $request)
    {
        // 1. Tarik semua data pegawai (Tanpa melihat Pimpinan-nya siapa)
        $bawahanRaw = DB::table('geotrax_v3.pegawai_skkni')
            ->where('role', '!=', 'admin') // Exclude akun Admin
            ->get();

        $allBawahanIds = $bawahanRaw->pluck('pegawai_id')->toArray();

        // 2. Tarik Bukti Upload Sekaligus (Anti N+1 Timeout)
        $allBukti = DB::table('geotrax_v3.bukti_pegawai')
            ->whereIn('pegawai_id', $allBawahanIds)
            ->select('pegawai_id', 'aktivitas_id', 'tanggal_upload')
            ->get();

        $groupedBukti = [];
        foreach ($allBukti as $b) {
            $groupedBukti[$b->pegawai_id][$b->aktivitas_id][] = $b->tanggal_upload;
        }

        // 3. Tarik Penilaian Selesai Sekaligus
        $allPenilaian = DB::table('geotrax_v3.penilaian_header')
            ->whereIn('pegawai_id', $allBawahanIds)
            ->where('status', 'Selesai')
            ->select('pegawai_id', 'kode_unit')
            ->get();

        $groupedPenilaian = [];
        foreach ($allPenilaian as $p) {
            $groupedPenilaian[$p->pegawai_id][$p->kode_unit] = true;
        }

        // 4. Cache Unit Kompetensi Per Jabatan
        $allJabatan = array_unique($bawahanRaw->pluck('jabatan')->toArray());
        $elemenCache = [];
        foreach($allJabatan as $jab) {
            $ukQuery = "
                SELECT ek.elemen_id, ek.kriteria_unjuk_kerja, uk.kode_unit, uk.judul_unit, ek.uni_kode_unit, ek.kode_elemen_excel
                FROM geotrax_v3.elemen_kompetensi ek
                JOIN geotrax_v3.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
                WHERE uk.posisi_target ILIKE ? AND ek.kode_elemen_excel ILIKE '%A'
                ORDER BY uk.kode_unit ASC, ek.elemen_id ASC
            ";
            $elemenCache[$jab] = DB::select($ukQuery, ["%" . $jab . "%"]);
        }

        // 5. Susun Data Mirip Pimpinan
        $data_pegawai = [];
        foreach ($bawahanRaw as $row) {
            $pid = $row->pegawai_id;
            $jab = $row->jabatan;

            $data_pegawai[$pid] = [
                'pegawai_id' => $row->pegawai_id, 'pegawai_nama' => $row->pegawai_nama, 'nip_nik' => $row->nip_nik,
                'jabatan' => $row->jabatan, 'total_target' => 0, 'total_terkumpul' => 0, 'total_uk' => 0, 'uk_dinilai' => 0,
                'uks' => [], 'first_uk_to_score' => 'UK-DEFAULT'
            ];

            $elemenRaw = $elemenCache[$jab] ?? [];
            $ukData = [];

            foreach ($elemenRaw as $ek) {
                if (!isset($ukData[$ek->kode_unit])) {
                    $ukData[$ek->kode_unit] = ['judul_unit' => $ek->judul_unit, 'target' => 0, 'terkumpul' => 0, 'waktu_terakhir' => null];
                }

                $kukTextList = preg_split('/\r\n|\r|\n|(?=\b\d+(?:\.\d+)*[\.\s]+)/', trim($ek->kriteria_unjuk_kerja ?? ''));
                $kukTextList = array_values(array_filter(array_map('trim', $kukTextList)));

                $kuk_ke = 1;
                foreach($kukTextList as $kt) {
                    if (empty($kt)) continue;

                    $kuk_number_str = str_pad($kuk_ke, 2, '0', STR_PAD_LEFT);
                    $base_code = str_replace('.', '', $ek->uni_kode_unit);
                    $aktivitas_id = $base_code . '-' . $ek->kode_elemen_excel . '-' . $kuk_number_str;

                    $ukData[$ek->kode_unit]['target'] += 1;

                    if (isset($groupedBukti[$pid][$aktivitas_id])) {
                        $uploads = $groupedBukti[$pid][$aktivitas_id];
                        $total_upload = count($uploads);
                        $waktu_terakhir = max($uploads);

                        if ($total_upload > 0) {
                            $ukData[$ek->kode_unit]['terkumpul'] += 1;
                            if (empty($ukData[$ek->kode_unit]['waktu_terakhir']) || $waktu_terakhir > $ukData[$ek->kode_unit]['waktu_terakhir']) {
                                $ukData[$ek->kode_unit]['waktu_terakhir'] = $waktu_terakhir;
                            }
                        }
                    }
                    $kuk_ke++;
                }
            }

            foreach ($ukData as $kode_unit => $detail) {
                $is_dinilai = isset($groupedPenilaian[$pid][$kode_unit]);

                $data_pegawai[$pid]['uks'][] = [
                    'kode_unit' => $kode_unit, 'judul_unit' => $detail['judul_unit'], 'target' => $detail['target'],
                    'terkumpul' => $detail['terkumpul'], 'waktu_terakhir' => $detail['waktu_terakhir'], 'is_dinilai' => $is_dinilai
                ];
                $data_pegawai[$pid]['total_target'] += $detail['target'];
                $data_pegawai[$pid]['total_terkumpul'] += $detail['terkumpul'];
                $data_pegawai[$pid]['total_uk'] += 1;

                if ($is_dinilai) $data_pegawai[$pid]['uk_dinilai'] += 1;
                elseif ($data_pegawai[$pid]['first_uk_to_score'] === 'UK-DEFAULT') $data_pegawai[$pid]['first_uk_to_score'] = $kode_unit;
            }
        }

        // Pimpinan bohongan untuk View
        $pimpinan = (object)['pegawai_nama' => 'Super Administrator (Takeover)'];

        return view('admin.tim_saya.index', compact('data_pegawai', 'pimpinan'));
    }
}
