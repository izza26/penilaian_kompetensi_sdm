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
        
        // PERBAIKAN: Menghapus JOIN ke tabel 'jabatan' dan 'jabatan_unit_kompe' yang tidak ada.
        // Diganti menggunakan pencocokan ILIKE ke kolom posisi_target milik unit_kompetensi
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
                $data_pegawai[$pid]['uks'][] = [
                    'kode_unit' => $row->kode_unit, 'judul_unit' => $row->judul_unit,
                    'target' => (int)$row->target_dokumen, 'terkumpul' => (int)$row->terkumpul_dokumen,
                    'is_dinilai' => $row->is_dinilai > 0
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
}