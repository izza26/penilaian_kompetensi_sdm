<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Spatie\PdfToText\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class SkoringController extends Controller
{
    private function bacaTeksWord($filePath)
    {
        $teks = '';
        $zip = new \ZipArchive;
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $data = str_replace('</w:p>', " \n", $data); 
                $teks = strip_tags($data);
            }
            $zip->close();
        }
        return trim($teks);
    }

    // =========================================================================
    // FUNGSI ANALISIS TEKS OTOMATIS (Support Hybrid S3 Cloud & Lokal) ☁️🕵️‍♂️
    // =========================================================================
    private function prosesAnalisisDokumen($aktivitas_id, $file_path)
    {
        $teks_dokumen = '';
        $temp_path = ''; // Jalur file sementara
        
        // CEK LOKASI FILE: Lokal atau Cloud (Supabase)?
        $full_path_local = storage_path('app/public/uploads/evidence/' . $file_path);
        $full_path_public = public_path('uploads/evidence/' . $file_path);
        
        if (file_exists($full_path_local)) {
            $target_path = $full_path_local; // File lama di lokal
        } elseif (file_exists($full_path_public)) {
            $target_path = $full_path_public; // File lama di public
        } else {
            // File baru di Supabase S3
            $s3_filename = basename(parse_url($file_path, PHP_URL_PATH));
            $s3_path = 'uploads/evidence/' . $s3_filename;
            
            try {
                if (\Illuminate\Support\Facades\Storage::disk('s3')->exists($s3_path)) {
                    $file_content = \Illuminate\Support\Facades\Storage::disk('s3')->get($s3_path);
                    $temp_path = sys_get_temp_dir() . '/' . uniqid() . '_' . $s3_filename;
                    file_put_contents($temp_path, $file_content);
                    $target_path = $temp_path;
                } else {
                    return ['skor' => 0, 'alasan' => 'File bukti fisik tidak ditemukan di Server maupun Cloud.'];
                }
            } catch (\Exception $e) {
                return ['skor' => 0, 'alasan' => 'Gagal mengunduh dokumen dari Cloud Storage.'];
            }
        }

        // 1. Ekstrak Teks dari Dokumen Fisik
        $ekstensi = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if (in_array($ekstensi, ['doc', 'docx'])) {
            $teks_dokumen = $this->bacaTeksWord($target_path);
        } elseif ($ekstensi == 'pdf') {
            try { $teks_dokumen = Pdf::getText($target_path); } catch (\Exception $e) {}
        } elseif (in_array($ekstensi, ['xlsx', 'xls', 'csv'])) {
            if ($temp_path) @unlink($temp_path);
            return ['skor' => 1, 'alasan' => 'Sistem evaluasi otomatis mendeteksi format file yang tidak sesuai. Harap unggah laporan deskriptif (Word/PDF).'];
        }

        if ($temp_path) @unlink($temp_path);

        if (empty(trim($teks_dokumen))) return ['skor' => 1, 'alasan' => 'Teks dokumen kosong atau tidak terbaca oleh sistem ekstraksi.'];

        // =========================================================================
        // 2. AMBIL RUBRIK SPESIFIK BERDASARKAN CABANG (PENCARIAN FLEKSIBEL)
        // =========================================================================
        $rubrik = null;
        if (str_contains($aktivitas_id, '_SUB_')) {
            $parts = explode('_SUB_', $aktivitas_id);
            $parent_id = $parts[0];
            $index_cabang = (int)$parts[1]; 
            
            // Pencarian fleksibel mengabaikan 1 digit terakhir sebelum tanda strip (-)
            $parent_id_wildcard = substr(explode('-', $parent_id)[0], 0, -1) . '_' . substr($parent_id, strlen(explode('-', $parent_id)[0]));
            
            $rubrik_list = DB::table('geotrax_v3.rubrik_skor')
                ->where('akt_aktivitas_id', 'LIKE', $parent_id_wildcard)
                ->orderBy('rubik_id', 'asc')->get();
            
            if ($rubrik_list->count() > $index_cabang) {
                $rubrik = $rubrik_list[$index_cabang];
            } else {
                $rubrik = $rubrik_list->last(); 
            }
        } else {
            $aktivitas_id_wildcard = substr(explode('-', $aktivitas_id)[0], 0, -1) . '_' . substr($aktivitas_id, strlen(explode('-', $aktivitas_id)[0]));
            $rubrik = DB::table('geotrax_v3.rubrik_skor')->where('akt_aktivitas_id', 'LIKE', $aktivitas_id_wildcard)->first();
        }

        // Rubrik darurat jika database benar-benar kosong
        if (!$rubrik) {
            $rubrik = (object)[
                'skor_1' => 'Laporan tidak relevan.', 'skor_2' => 'Laporan kurang lengkap.', 
                'skor_3' => 'Laporan cukup baik.', 'skor_4' => 'Sesuai dengan kriteria.', 
                'skor_5' => 'Laporan sangat lengkap dan sempurna sesuai standar SKKNI.'
            ];
        }

        if (!$rubrik) return ['skor' => 0, 'alasan' => 'Rubrik belum diatur di database.'];

        // 3. Merakit Prompt Tersembunyi (PERBAIKAN FATAL UTF-8)
        // MENGHINDARI ERROR JSON ENCODE KARENA KARAKTER TERPOTONG
        $teks_diproses = mb_substr($teks_dokumen, 0, 15000, "UTF-8");
        $teks_diproses = mb_convert_encoding($teks_diproses, 'UTF-8', 'UTF-8');

        $prompt = "Kamu adalah sistem Penilai Ahli di sebuah museum. Tugasmu adalah membaca teks Laporan Pegawai dan memberikan skor 1 sampai 5 secara objektif berdasarkan Rubrik Penilaian berikut.
=== RUBRIK PENILAIAN ===
SKOR 1: {$rubrik->skor_1}
SKOR 2: {$rubrik->skor_2}
SKOR 3: {$rubrik->skor_3}
SKOR 4: {$rubrik->skor_4}
SKOR 5: {$rubrik->skor_5}
=== TEKS LAPORAN PEGAWAI ===
{$teks_diproses}
=== INSTRUKSI OUTPUT ===
Berikan jawabanmu DALAM FORMAT JSON murni tanpa markdown. Format wajib:
{
  \"skor\": <angka bulat 1 sampai 5>,
  \"alasan\": \"<berikan 1 kalimat alasan sistematis kenapa laporan tersebut mendapat skor ini>\"
}";

        // 4. Eksekusi API Gemini
        try {
            $api_key = trim(env('GEMINI_API_KEY'));
            if (empty($api_key)) return ['skor' => 0, 'alasan' => 'Konfigurasi kunci sistem belum diatur.'];

            // PERBAIKAN CLAUDE: Gunakan model 2026 yang valid!
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash:generateContent?key=' . $api_key;
            
            $response = Http::withoutVerifying()->timeout(120)->retry(2, 2000)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post($url, [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json']
            ]);

            if ($response->successful()) {
                $hasil = $response->json();
                if (isset($hasil['candidates'][0]['finishReason']) && $hasil['candidates'][0]['finishReason'] !== 'STOP') {
                    return ['skor' => 1, 'alasan' => 'Sistem menolak memproses teks karena kebijakan keamanan.'];
                }
                $gemini_text = $hasil['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (preg_match('/\{[\s\S]*\}/', $gemini_text, $matches)) {
                    $clean_json = $matches[0];
                    $data_ai = json_decode($clean_json, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($data_ai['skor'])) {
                        return ['skor' => (int) $data_ai['skor'], 'alasan' => $data_ai['alasan'] ?? 'Evaluasi disimpulkan secara otomatis oleh sistem.'];
                    }
                }
                return ['skor' => 1, 'alasan' => 'Format balasan sistem pusat tidak valid.'];
            } else {
                // PERBAIKAN CLAUDE: Catat Error Asli ke laravel.log agar tidak buta arah!
                \Illuminate\Support\Facades\Log::error('Gemini API Error: ' . $response->body());
                
                if ($response->status() == 404) {
                    return ['skor' => 1, 'alasan' => 'Error 404: Model AI tidak ditemukan. Cek laravel.log.'];
                }
                
                return ['skor' => 1, 'alasan' => 'Server AI menolak request (Status ' . $response->status() . ').'];
            }
        } catch (\Exception $e) {
            // PERBAIKAN CLAUDE: Catat Exception ke laravel.log
            \Illuminate\Support\Facades\Log::error('Gemini Exception: ' . $e->getMessage());
            
            $pesan = strtolower($e->getMessage());
            if (str_contains($pesan, 'timed out') || str_contains($pesan, 'timeout')) {
                return ['skor' => 1, 'alasan' => 'Proses analisis terlalu lama (Timeout). Silakan muat ulang halaman.'];
            }
            
            // Tampilkan error aslinya ke layar, jangan disamarkan lagi!
            return ['skor' => 1, 'alasan' => 'System Error: ' . $e->getMessage()];
        }
    }

    private function konversiBobotGap($gap) {
        static $tabel_bobot = null;
        if ($tabel_bobot === null) {
            $bobot_db = DB::table('geotrax_v3.pm_bobot_gap')->get();
            if ($bobot_db->isEmpty()) $bobot_db = DB::table('geotrax_v3.p_master_bobot_gap')->get(); 
            $tabel_bobot = [];
            foreach ($bobot_db as $row) {
                $gap_val = isset($row->selisih_gap) ? $row->selisih_gap : $row->gap;
                $bobot_val = isset($row->bobot_nilai) ? $row->bobot_nilai : $row->bobot;
                $tabel_bobot[(string)$gap_val] = (float)$bobot_val;
            }
        }
        return $tabel_bobot[(string)$gap] ?? 1.0;
    }

    public function timSaya(Request $request)
    {
        $userLogin = Auth::user();
        if (isset($userLogin->jabatan)) { $pimpinan = $userLogin; } 
        else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
        }
        if (!$pimpinan) return redirect()->route('pimpinan.dashboard')->with('error', 'Data pegawai Anda tidak ditemukan!');

        $bawahanIds = DB::table('geotrax_v3.p_pegawai_penilai_sah')->where('id_penilai', $pimpinan->pegawai_id)->pluck('id_pegawai')->toArray();

        if (empty($bawahanIds)) {
             $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
             $filterJabatan = [];
             if (str_contains($jabatanPimpinan, 'Kepala Museum')) $filterJabatan = ['Koordinator', 'Manajer', 'Kurator'];
             elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) $filterJabatan = ['Konservator', 'Register'];
             elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) $filterJabatan = ['Edukator', 'Penata Pameran'];
             elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) $filterJabatan = ['Humas'];
             elseif (str_contains($jabatanPimpinan, 'Kurator')) $filterJabatan = ['Kurator'];
             
             $bawahanRaw = DB::table('geotrax_v3.pegawai_skkni')->where('pegawai_id', '!=', $pimpinan->pegawai_id) 
                ->where(function($query) use ($filterJabatan) {
                    if (empty($filterJabatan)) { $query->whereRaw('1=0'); } 
                    else { foreach ($filterJabatan as $jab) { $query->orWhere('jabatan', 'LIKE', '%' . $jab . '%'); } }
                })->get();
        } else {
            $bawahanRaw = DB::table('geotrax_v3.pegawai_skkni')->whereIn('pegawai_id', $bawahanIds)->get();
        }

        $allBawahanIds = $bawahanRaw->pluck('pegawai_id')->toArray();

        // =========================================================================
        // PERBAIKAN FATAL: TARIK SEMUA BUKTI SEKALIGUS AGAR TIDAK N+1 TIMEOUT
        // =========================================================================
        $allBukti = DB::table('geotrax_v3.bukti_pegawai')
            ->whereIn('pegawai_id', $allBawahanIds)
            ->select('pegawai_id', 'aktivitas_id', 'tanggal_upload')
            ->get();
        
        $groupedBukti = [];
        foreach ($allBukti as $b) {
            $groupedBukti[$b->pegawai_id][$b->aktivitas_id][] = $b->tanggal_upload;
        }

        // Tarik juga semua data yang sudah dinilai sekaligus
        $allPenilaian = DB::table('geotrax_v3.penilaian_header')
            ->whereIn('pegawai_id', $allBawahanIds)
            ->where('status', 'Selesai')
            ->select('pegawai_id', 'kode_unit')
            ->get();
            
        $groupedPenilaian = [];
        foreach ($allPenilaian as $p) {
            $groupedPenilaian[$p->pegawai_id][$p->kode_unit] = true;
        }

        // Hindari query Unit Kompetensi berulang-ulang untuk jabatan yang sama
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
        // =========================================================================

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

                    // KITA CEK DARI MEMORI, BUKAN DARI DATABASE! (SUPER CEPAT)
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
                // CEK STATUS PENILAIAN DARI MEMORI
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
        return view('pimpinan.tim_saya.index', compact('data_pegawai', 'pimpinan'));
    }

    public function beriNilai($pegawai_id, $kode_unit)
    {
        $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('pegawai_id', $pegawai_id)->first();
        $unit = DB::table('geotrax_v3.unit_kompetensi')->where('kode_unit', $kode_unit)->first();
        if (!$pegawai || !$unit) return redirect()->route('pimpinan.tim_saya.index');

        $list_uk_pegawai = DB::select("
            SELECT DISTINCT uk.kode_unit, uk.judul_unit FROM geotrax_v3.elemen_kompetensi ek
            JOIN geotrax_v3.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            WHERE uk.posisi_target ILIKE ? ORDER BY uk.kode_unit ASC
        ", ["%{$pegawai->jabatan}%"]);

        $master_jabatan = DB::table('geotrax_v3.pm_pengaturan_jabatan')->where('jabatan', 'ILIKE', "%{$pegawai->jabatan}%")->first();
        $persen_ncf = $master_jabatan ? $master_jabatan->persentase_cf : 60; 
        $persen_nsf = $master_jabatan ? $master_jabatan->persentase_sf : 40; 

        $penilaian_lama = DB::table('geotrax_v3.penilaian_header')->where('pegawai_id', $pegawai_id)->where('kode_unit', $kode_unit)->first();
        $is_update = $penilaian_lama ? true : false;
        $rekomendasi_lama = $penilaian_lama ? $penilaian_lama->rekomendasi : '';

        $skor_lama_array = [];
        if ($is_update) {
            $detail_lama = DB::table('geotrax_v3.penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->get();
            foreach ($detail_lama as $d) { $skor_lama_array[$d->aktivitas_id] = $d->skor_final; }
        }

        $bukti_pegawai = DB::table('geotrax_v3.bukti_pegawai')->where('pegawai_id', $pegawai_id)->get()->keyBy('aktivitas_id'); 

        $sql_elemen = "
            SELECT ek.elemen_id, ek.kode_elemen_excel, ek.elemen_kompetensi, ek.kriteria_unjuk_kerja, ek.uni_kode_unit
            FROM geotrax_v3.elemen_kompetensi ek WHERE ek.kode_unit = ? AND ek.kode_elemen_excel ILIKE '%A' ORDER BY ek.elemen_id ASC
        ";
        $list_elemen_raw = DB::select($sql_elemen, [$kode_unit]);

        $dataGrouped = [];
        $total_bobot_cf = 0; $total_bobot_sf = 0;
        $item_cf = 0; $item_sf = 0;

        foreach ($list_elemen_raw as $ek) {
             $elemenNum = preg_replace('/[^0-9]/', '', $ek->kode_elemen_excel); 
             $elemenKode = $ek->uni_kode_unit . "-" . $elemenNum;
             
             if(!isset($dataGrouped[$elemenKode])) {
                 $dataGrouped[$elemenKode] = ['nama_elemen' => $ek->elemen_kompetensi ?? 'Tanpa Elemen', 'kuks' => []];
             }

             $kukTextList = preg_split('/\r\n|\r|\n|(?=\b\d+(?:\.\d+)*[\.\s]+)/', trim($ek->kriteria_unjuk_kerja ?? ''));
             $kukTextList = array_values(array_filter(array_map('trim', $kukTextList)));
             
             $kuk_ke = 1;
             foreach ($kukTextList as $kuk_text) {
                 if (empty($kuk_text)) continue;

                 $kuk_number_str = str_pad($kuk_ke, 2, '0', STR_PAD_LEFT);
                 $base_code = str_replace('.', '', $ek->uni_kode_unit); 
                 $aktivitas_id_induk = $base_code . '-' . $ek->kode_elemen_excel . '-' . $kuk_number_str;
                 $teks_kuk_bersih = preg_replace('/^\s*\d+(?:\.\d+)*[\.\s]+/', '', trim($kuk_text));

                 // ==============================================================
                 // PERBAIKAN FATAL: PENCARIAN FLEKSIBEL (ANTI ERROR PYTHON)
                 // Kita abaikan 1 digit terakhir dari base_code menggunakan "_"
                 // ==============================================================
                 $base_code_wildcard = substr($base_code, 0, -1) . '_'; 
                 $aktivitas_id_wildcard = $base_code_wildcard . '-' . $ek->kode_elemen_excel . '-' . $kuk_number_str;

                 $rubrik_list = DB::table('geotrax_v3.rubrik_skor')
                     ->where('akt_aktivitas_id', 'LIKE', $aktivitas_id_wildcard)
                     ->orderBy('rubik_id', 'asc')
                     ->get();

                 // Jika rubrik benar-benar kosong, buat 1 baris dummy agar layar tidak blank!
                 if ($rubrik_list->isEmpty()) {
                     $rubrik_list = collect([(object)[
                         'skor_1' => 'Sangat Kurang', 'skor_2' => 'Kurang', 'skor_3' => 'Cukup', 
                         'skor_4' => 'Baik', 'skor_5' => 'Sangat Baik', 'jenis_file' => 'Dokumen Laporan'
                     ]]);
                 }

                 foreach ($rubrik_list as $index => $rubrik) {
                     $id_upload = ($rubrik_list->count() > 1) ? ($aktivitas_id_induk . '_SUB_' . $index) : $aktivitas_id_induk;

                     $sub_judul = "";
                     if ($rubrik_list->count() > 1) {
                         $sub_judul = " [Bagian " . ($index + 1) . "]";
                         if (preg_match('/\((.*?)\)/', $rubrik->skor_1, $match)) $sub_judul = " [" . ucwords($match[1]) . "]";
                     }

                     $bukti = $bukti_pegawai->get($id_upload);
                     $jml_upload = $bukti ? 1 : 0;
                     $file_path = $bukti->file_path ?? null;           
                     $file_parameter = $bukti->file_parameter ?? null; 

                     $target_cf = 5; 
                     $nilai_parser = 0;
                     $catatan_sistem = '';
                     
                     // PERUBAHAN CERDAS: Cek Ingatan (Cache) Langsung dari Server!
                     $sudah_dinilai = false;
                     $butuh_panggil_ai = false;

                     if ($jml_upload > 0) {
                         $sudah_dinilai = $is_update && isset($skor_lama_array[$id_upload]);

                         if ($sudah_dinilai) {
                             $nilai_parser = $skor_lama_array[$id_upload];
                             $catatan_lama = DB::table('geotrax_v3.penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->where('aktivitas_id', $id_upload)->first()->catatan_penilai ?? '';
                             $catatan_sistem = (!empty($catatan_lama) && $catatan_lama != 'Penilaian PM (Dikoreksi Pimpinan)') ? $catatan_lama : 'Dokumen dievaluasi pada sesi sebelumnya.';
                         } else {
                             $file_to_read = $file_path ?? $file_parameter; 
                             if ($file_to_read) {
                                 // Cek apakah AI sudah pernah membaca file ini (walau belum disahkan)
                                 $cache_key = 'ai_eval_' . $id_upload . '_' . md5($file_to_read);
                                 
                                 if (\Illuminate\Support\Facades\Cache::has($cache_key)) {
                                     $hasil_evaluasi = \Illuminate\Support\Facades\Cache::get($cache_key);
                                     $nilai_parser = $hasil_evaluasi['skor'];
                                     $catatan_sistem = $hasil_evaluasi['alasan'];
                                     $butuh_panggil_ai = false; // Matikan perintah AJAX
                                 } else {
                                     $butuh_panggil_ai = true;
                                     $nilai_parser = 0; // Set 0 sementara untuk loading
                                     $catatan_sistem = 'Membaca Dokumen...';
                                 }
                             } else {
                                 $nilai_parser = 1;
                                 $catatan_sistem = 'File bukti fisik rusak atau kosong.';
                                 $butuh_panggil_ai = false;
                             }
                         }
                     }
                     
                     $nilai_profil_cf = $is_update ? ($skor_lama_array[$id_upload] ?? $nilai_parser) : $nilai_parser;
                     $gap_cf = $nilai_profil_cf - $target_cf;
                     $bobot_cf = $this->konversiBobotGap($gap_cf); 
                     $total_bobot_cf += $bobot_cf;
                     $item_cf++;

                     $target_sf1 = 5;
                     $nilai_sf1 = ($jml_upload >= 1) ? 5 : 1;
                     $bobot_sf1 = $this->konversiBobotGap($nilai_sf1 - $target_sf1);

                     $target_sf2 = 5;
                     $nilai_sf2 = !empty($bukti->tanggal_upload) ? 4 : 1;
                     $bobot_sf2 = $this->konversiBobotGap($nilai_sf2 - $target_sf2);

                     $rata_bobot_sf_item = ($bobot_sf1 + $bobot_sf2) / 2;
                     $total_bobot_sf += $rata_bobot_sf_item;
                     $item_sf++;

                     $keterangan_evidence = $rubrik->jenis_file ?? 'Dokumen Bukti/Laporan';

                     $dataGrouped[$elemenKode]['kuks'][] = [
                         'aktivitas_id' => $id_upload,
                         'kuk_ke' => $kuk_ke . ($rubrik_list->count() > 1 ? '.' . ($index + 1) : ''), 
                         'detail_aktivitas' => $teks_kuk_bersih . "<br><b style='color:#3e54a0; font-size:12px;'>" . $sub_judul . "</b>",
                         'keterangan_evidence' => $keterangan_evidence,
                         'jml_upload' => $jml_upload,
                         'file_path' => $file_path,
                         'tanggal_upload' => $bukti->tanggal_upload ?? null,
                         'target_cf' => $target_cf,
                         'nilai_parser' => $nilai_parser, 
                         'catatan_sistem' => $catatan_sistem, 
                         'nilai_profil_cf' => $nilai_profil_cf, 
                         'gap_cf' => $gap_cf,
                         'bobot_cf' => $bobot_cf,
                         'bobot_sf_avg' => $rata_bobot_sf_item,
                         'aktivitas_id_induk' => $aktivitas_id_induk,
                         'butuh_panggil_ai' => $butuh_panggil_ai
                     ];
                 }
                 $kuk_ke++;
             }
        }

        $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;
        $nsf = ($item_sf > 0) ? ($total_bobot_sf / $item_sf) : 0;
        $ni = (($persen_ncf / 100) * $ncf) + (($persen_nsf / 100) * $nsf);
        $nilai_akhir_100 = ($ni / 5) * 100;

        return view('pimpinan.tim_saya.beri_nilai', compact(
            'pegawai', 'unit', 'pegawai_id', 'kode_unit', 'is_update', 
            'rekomendasi_lama', 'penilaian_lama', 'dataGrouped', 'ncf', 'nsf', 'ni', 'nilai_akhir_100',
            'persen_ncf', 'persen_nsf', 'list_uk_pegawai'
        ));
    }

    public function simpanNilai(Request $request, $pegawai_id, $kode_unit)
    {
        DB::beginTransaction();
        try {
            $is_update = $request->is_update;
            $penilaian_id_lama = $request->penilaian_id_lama;
            
            $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('pegawai_id', $pegawai_id)->first();
            $master_jabatan = DB::table('geotrax_v3.pm_pengaturan_jabatan')->where('jabatan', 'ILIKE', "%{$pegawai->jabatan}%")->first();
            $persen_ncf = $master_jabatan ? $master_jabatan->persentase_cf : 60;
            $persen_nsf = $master_jabatan ? $master_jabatan->persentase_sf : 40;

            $bukti_pegawai = DB::table('geotrax_v3.bukti_pegawai')->where('pegawai_id', $pegawai_id)->get()->keyBy('aktivitas_id'); 

            $total_bobot_cf = 0; $total_bobot_sf = 0;
            $item_cf = 0; $item_sf = 0;
            $detail_to_insert = [];

            // MENGAMBIL DATA LANGSUNG DARI FORM (Lebih Cerdas & Mendukung Sub-Cabang)
            $skor_aktual_list = $request->skor; 
            $catatan_sistem_list = $request->catatan_sistem;

            if ($skor_aktual_list) {
                foreach ($skor_aktual_list as $id_upload => $skor_aktual) {
                     $catatan_aktual = $catatan_sistem_list[$id_upload] ?? 'Penilaian PM (Dikoreksi Pimpinan)';

                     $bukti = $bukti_pegawai->get($id_upload);
                     $jml_upload = $bukti ? 1 : 0;
                     $tanggal_upload = $bukti->tanggal_upload ?? null;

                     // KALKULASI CORE FACTOR
                     $target_cf = 4; 
                     $gap_cf = (int)$skor_aktual - $target_cf;
                     $bobot_cf = $this->konversiBobotGap($gap_cf);
                     $total_bobot_cf += $bobot_cf;
                     $item_cf++;

                     // KALKULASI SECONDARY FACTOR
                     $target_sf1 = 4;
                     $nilai_sf1 = ($jml_upload >= 1) ? 5 : 1;
                     $bobot_sf1 = $this->konversiBobotGap($nilai_sf1 - $target_sf1);

                     $target_sf2 = 4;
                     $nilai_sf2 = !empty($tanggal_upload) ? 4 : 1;
                     $bobot_sf2 = $this->konversiBobotGap($nilai_sf2 - $target_sf2);

                     $total_bobot_sf += ($bobot_sf1 + $bobot_sf2) / 2;
                     $item_sf++;

                     // KUMPULKAN UNTUK DIKIRIM SEKALIGUS (BULK INSERT)
                     $detail_to_insert[] = [
                         'aktivitas_id' => $id_upload,
                         'skor_final' => (int)$skor_aktual,
                         'jumlah_bukti_uploa' => $jml_upload,
                         'catatan' => $catatan_aktual 
                     ];
                }
            }

            // MENGHITUNG NILAI AKHIR 100
            $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;
            $nsf = ($item_sf > 0) ? ($total_bobot_sf / $item_sf) : 0;
            $ni = (($persen_ncf / 100) * $ncf) + (($persen_nsf / 100) * $nsf);
            $nilai_akhir_100 = ($ni / 5) * 100;

            if ($nilai_akhir_100 >= 85) $kategori = "Sangat Kompeten";
            elseif ($nilai_akhir_100 >= 70) $kategori = "Kompeten";
            elseif ($nilai_akhir_100 >= 55) $kategori = "Cukup Kompeten";
            else $kategori = "Belum Kompeten";

            $waktu_sekarang = date('Y-m-d H:i:s'); 

            // SIMPAN HEADER
            if ($is_update && $penilaian_id_lama) {
                DB::table('geotrax_v3.penilaian_header')->where('penilaian_id', $penilaian_id_lama)->update([
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori, 
                    'rekomendasi' => $request->rekomendasi, 'waktu_submit' => $waktu_sekarang
                ]);
                DB::table('geotrax_v3.penilaian_detail')->where('penilaian_id', $penilaian_id_lama)->delete();
                $penilaian_id = $penilaian_id_lama;
            } else {
                $penilaian_id = (DB::table('geotrax_v3.penilaian_header')->max('penilaian_id') ?? 0) + 1;
                DB::table('geotrax_v3.penilaian_header')->insert([
                    'penilaian_id' => $penilaian_id, 'pegawai_id' => $pegawai_id, 'kode_unit' => $kode_unit,
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori, 'rekomendasi' => $request->rekomendasi,
                    'status' => 'Selesai', 'detail_penilaian_i' => 0, 'waktu_submit' => $waktu_sekarang, 'catatan_umum' => ''
                ]);
            }

            // SIMPAN DETAIL SECARA BORONGAN (ANTI TIMEOUT!)
            if (count($detail_to_insert) > 0) {
                $current_max_detail = DB::table('geotrax_v3.penilaian_detail')->max('detail_penilaian_i') ?? 0;
                $inserts = [];
                foreach ($detail_to_insert as $dt) {
                    $current_max_detail++;
                    $inserts[] = [
                        'detail_penilaian_i' => $current_max_detail,
                        'skor_final' => $dt['skor_final'],
                        'jumlah_bukti_uploa' => $dt['jumlah_bukti_uploa'], 
                        'catatan_penilai' => $dt['catatan'], 
                        'aktivitas_id' => $dt['aktivitas_id'], 
                        'penilaian_id' => $penilaian_id
                    ];
                }
                DB::table('geotrax_v3.penilaian_detail')->insert($inserts);
            }

            DB::commit();
            return redirect()->route('pimpinan.tim_saya.index')->with('success', 'Koreksi Penilaian Profile Matching Berhasil Disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    // --- FUNGSI BARU UNTUK AJAX AI (ANTI TIMEOUT) ---
    public function ajaxPanggilAI(Request $request)
    {
        $id_upload = $request->id_upload;
        $file_to_read = $request->file_to_read;
        $force_reload = filter_var($request->force_reload, FILTER_VALIDATE_BOOLEAN); // Memastikan tipe boolean

        if (!$file_to_read) {
            return response()->json(['skor' => 1, 'alasan' => 'File bukti fisik rusak atau tidak ditemukan.']);
        }

        $cache_key = 'ai_eval_' . $id_upload . '_' . md5($file_to_read);

        // Jika tombol "Baca Ulang" diklik, hapus ingatan lama AI
        if ($force_reload) {
            Cache::forget($cache_key);
        }

        // Kita gunakan Cache::get() dan Cache::put() manual agar bisa memfilter kegagalan
        if (Cache::has($cache_key)) {
            $hasil_evaluasi = Cache::get($cache_key);
            
            // Jaring Pengaman Ekstra: 
            // Jika isi Cache ternyata menyimpan error (karena kelolosan sistem lama), hapus dan baca ulang!
            if (str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan') || str_contains(strtolower($hasil_evaluasi['alasan']), 'timeout')) {
                Cache::forget($cache_key);
                $hasil_evaluasi = $this->prosesAnalisisDokumen($id_upload, $file_to_read);
                if (!str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan')) {
                    Cache::put($cache_key, $hasil_evaluasi, now()->addDays(30));
                }
            }
        } else {
            $hasil_evaluasi = $this->prosesAnalisisDokumen($id_upload, $file_to_read);
            
            // JANGAN simpan ke Cache jika terjadi error jaringan (Agar selalu dicoba ulang saat refresh)
            if (!str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan') && !str_contains(strtolower($hasil_evaluasi['alasan']), 'timeout')) {
                Cache::put($cache_key, $hasil_evaluasi, now()->addDays(30));
            }
        }

        return response()->json($hasil_evaluasi);
    }

    public function simpanBobotGap(Request $request) { /* Abaikan isinya, sudah aman */ }
}