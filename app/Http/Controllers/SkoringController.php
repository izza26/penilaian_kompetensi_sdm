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

    private function prosesAnalisisDokumen($aktivitas_id, $file_path)
    {
        $teks_dokumen = '';
        $temp_path = '';

        $full_path_local = storage_path('app/public/uploads/evidence/' . $file_path);
        $full_path_public = public_path('uploads/evidence/' . $file_path);

        if (file_exists($full_path_local)) {
            $target_path = $full_path_local;
        } elseif (file_exists($full_path_public)) {
            $target_path = $full_path_public;
        } else {
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

        $rubrik = null;
        if (str_contains($aktivitas_id, '_SUB_')) {
            $parts = explode('_SUB_', $aktivitas_id);
            $parent_id = $parts[0];
            $index_cabang = (int)$parts[1];

            $parent_id_wildcard = substr(explode('-', $parent_id)[0], 0, -1) . '_' . substr($parent_id, strlen(explode('-', $parent_id)[0]));

            $rubrik_list = DB::table('geotrax_v4.rubrik_skor')
                ->where('akt_aktivitas_id', 'LIKE', $parent_id_wildcard)
                ->orderBy('rubik_id', 'asc')->get();

            if ($rubrik_list->count() > $index_cabang) {
                $rubrik = $rubrik_list[$index_cabang];
            } else {
                $rubrik = $rubrik_list->last();
            }
        } else {
            $aktivitas_id_wildcard = substr(explode('-', $aktivitas_id)[0], 0, -1) . '_' . substr($aktivitas_id, strlen(explode('-', $aktivitas_id)[0]));
            $rubrik = DB::table('geotrax_v4.rubrik_skor')->where('akt_aktivitas_id', 'LIKE', $aktivitas_id_wildcard)->first();
        }

        if (!$rubrik) {
            $rubrik = (object)[
                'skor_1' => 'Laporan tidak relevan.', 'skor_2' => 'Laporan kurang lengkap.',
                'skor_3' => 'Laporan cukup baik.', 'skor_4' => 'Sesuai dengan kriteria.',
                'skor_5' => 'Laporan sangat lengkap dan sempurna.'
            ];
        }

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

        // ... (kode penyiapan prompt sebelumnya tetap sama)

        try {
            // 1. Ambil array API Key dari .env yang dipisah koma
            $api_keys_string = trim(env('GEMINI_API_KEYS', env('GEMINI_API_KEY')));
            $api_keys = array_values(array_filter(array_map('trim', explode(',', $api_keys_string))));

            if (empty($api_keys)) return ['skor' => 0, 'alasan' => 'Konfigurasi kunci sistem belum diatur.'];

            // 1b. Daftar model yang dicoba BERURUTAN.
            //     Google sering menonaktifkan nama model lama (HTTP 404) dan
            //     model populer sering overload (HTTP 503). Dengan daftar ini
            //     sistem otomatis pindah model, jadi tidak langsung mati total.
            //     Bisa ditimpa lewat env GEMINI_MODELS tanpa mengubah kode.
            $models_string = trim(env('GEMINI_MODELS', 'gemini-3-flash-preview,gemini-3.1-flash-lite,gemini-3.5-flash'));
            $models = array_values(array_filter(array_map('trim', explode(',', $models_string))));
            if (empty($models)) $models = ['gemini-3-flash-preview'];

            $hasil_eksekusi = null;
            $pesan_error_terakhir = '';

            // 2. Looping ganda: model di lapisan luar, API Key di lapisan dalam
            foreach ($models as $model) {
                foreach ($api_keys as $key) {
                    $api_key = trim($key);
                    if (empty($api_key)) continue;

                    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

                    // Hit API tanpa fitur 'retry' bawaan, karena retry dikelola loop ini
                    $response = Http::withoutVerifying()->timeout(120)->withHeaders([
                        'Content-Type' => 'application/json',
                    ])->post($url, [
                        'contents' => [['parts' => [['text' => $prompt]]]],
                        'generationConfig' => ['temperature' => 0.1, 'responseMimeType' => 'application/json']
                    ]);

                    if ($response->successful()) {
                        $hasil_eksekusi = $response->json();
                        break 2; // Sukses! Hentikan seluruh looping.
                    }

                    $status = $response->status();
                    $pesan_error_terakhir = "model {$model} -> HTTP {$status}";

                    // 429 (kuota habis) ATAU 500-504 (server Google overload)
                    // -> coba API Key berikutnya; kalau habis, lanjut model berikutnya.
                    if (in_array($status, [429, 500, 502, 503, 504])) {
                        \Illuminate\Support\Facades\Log::warning("Gemini {$model} status {$status}: pindah ke kandidat berikutnya...");
                        continue;
                    }

                    // 404 = nama model salah/kedaluwarsa -> lewati model ini,
                    // lanjut ke model berikutnya (bukan mati total seperti dulu).
                    if ($status == 404) {
                        \Illuminate\Support\Facades\Log::error("Gemini API Error (404 Model {$model}): " . $response->body());
                        break; // keluar dari loop API Key, lanjut ke model berikutnya
                    }

                    // 400/403 = format request salah atau API Key tidak valid
                    \Illuminate\Support\Facades\Log::error('Gemini API Error: ' . $response->body());
                    if ($status == 403 || $status == 400) {
                        continue; // coba key berikutnya, mungkin key ini yang tidak valid
                    }

                    return ['skor' => 0, 'alasan' => 'Server AI menolak request (Status ' . $status . '). Cek laravel.log.'];
                }
            }

            // 3. Semua kombinasi model + API Key sudah dicoba dan tetap gagal
            if (!$hasil_eksekusi) {
                return ['skor' => 0, 'alasan' => 'Semua API Key gagal atau server AI sedang sibuk. Silakan coba lagi beberapa saat lagi. (Detail: ' . $pesan_error_terakhir . ')'];
            }

            // 4. Proses JSON hasil balasan dari API Key yang berhasil
            $hasil = $hasil_eksekusi;
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

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Gemini Exception: ' . $e->getMessage());
            $pesan = strtolower($e->getMessage());
            if (str_contains($pesan, 'timed out') || str_contains($pesan, 'timeout')) {
                return ['skor' => 1, 'alasan' => 'Proses analisis terlalu lama (Timeout). Silakan muat ulang halaman.'];
            }
            return ['skor' => 1, 'alasan' => 'System Error: ' . $e->getMessage()];
        }
    }

    /**
     * Memetakan jabatan pegawai (kolom `jabatan`) ke nilai `posisi_target`
     * yang dipakai di tabel unit_kompetensi.
     *
     * Penting: nama jabatan TIDAK selalu sama dengan posisi_target.
     * Contoh: "Humas" -> posisi_target "Hubungan Masyarakat dan Pemasaran".
     * Hasil dikembalikan sebagai array keyword agar query bisa ILIKE.
     */
    private function posisiTargetDariJabatan($jabatan)
    {
        $jab = strtolower(trim($jabatan));

        // Jabatan pelaksana: cocokkan ke posisi_target yang sesuai
        if (str_contains($jab, 'humas') || str_contains($jab, 'pemasaran')) {
            return ['Hubungan Masyarakat dan Pemasaran'];
        }
        if (str_contains($jab, 'pameran')) {
            return ['Penata Pameran'];
        }
        if (str_contains($jab, 'edukator') || str_contains($jab, 'edukasi')) {
            return ['Edukator'];
        }
        if (str_contains($jab, 'konservator') || str_contains($jab, 'konservasi')) {
            return ['Konservator'];
        }
        if (str_contains($jab, 'register') || str_contains($jab, 'registrasi')) {
            return ['Register'];
        }
        if (str_contains($jab, 'kurator')) {
            return ['Kurator'];
        }

        // Jabatan struktural (Kepala Museum, Manajer, Koordinator) tidak punya
        // unit kompetensi sendiri di posisi_target -> kembalikan kosong.
        return [];
    }

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

    /**
     * Menentukan daftar jabatan bawahan berdasarkan jabatan pimpinan.
     * Dipakai hanya sebagai fallback jika tabel relasi p_pegawai_penilai_sah kosong.
     * Urutan kondisi penting: yang lebih spesifik dicek lebih dulu.
     */
    private function tentukanBawahanDariJabatan($jabatanPimpinan)
    {
        $jab = strtolower(trim($jabatanPimpinan));

        // Koordinator (Registrasi & Konservasi) -> Konservator + Register
        if (str_contains($jab, 'registrasi') || str_contains($jab, 'konservasi')) {
            return ['Konservator', 'Register'];
        }
        // Koordinator Pameran -> Penata Pameran
        if (str_contains($jab, 'pameran')) {
            return ['Penata Pameran'];
        }
        // Manajer (Edukasi & Program Publik) -> Edukator + Penata Pameran
        if (str_contains($jab, 'edukasi') || str_contains($jab, 'program publik')) {
            return ['Edukator', 'Penata Pameran'];
        }
        // Manajer (Humas & Pemasaran) -> Humas
        if (str_contains($jab, 'humas') || str_contains($jab, 'pemasaran')) {
            return ['Humas'];
        }
        // Kurator -> Kurator (mis. koordinator kurator)
        if (str_contains($jab, 'kurator')) {
            return ['Kurator'];
        }

        return [];
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

        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');

        // ============================================================
        // ATURAN HIERARKI PENILAIAN
        // 1. Kepala Museum  -> berhak menilai SEMUA pegawai (kecuali dirinya sendiri & akun sistem)
        // 2. Pimpinan lain  -> ambil dari tabel relasi p_pegawai_penilai_sah,
        //                      divalidasi agar hanya pegawai yang benar-benar ada & jabatannya sesuai
        // ============================================================
        $isKepalaMuseum = str_contains(strtolower($jabatanPimpinan), 'kepala museum');

        // Jabatan yang TIDAK boleh dinilai (akun sistem / admin)
        $jabatanDikecualikan = ['system administrator', 'super administrator', 'administrator hr'];

        if ($isKepalaMuseum) {
            // Kepala Museum: SEMUA pegawai SKKNI, kecuali dirinya sendiri dan akun sistem
            $bawahanRaw = DB::table('geotrax_v4.pegawai_skkni')
                ->where('pegawai_id', '!=', $pimpinan->pegawai_id)
                ->whereNotIn(DB::raw('LOWER(jabatan)'), $jabatanDikecualikan)
                ->orderBy('pegawai_id')
                ->get();
        } else {
            // Ambil id bawahan dari tabel relasi, lalu validasi keberadaannya di pegawai_skkni
            $bawahanIds = DB::table('geotrax_v4.p_pegawai_penilai_sah')
                ->where('id_penilai', $pimpinan->pegawai_id)
                ->pluck('id_pegawai')
                ->toArray();

            $bawahanIds = array_filter(array_unique($bawahanIds));

            if (!empty($bawahanIds)) {
                $bawahanRaw = DB::table('geotrax_v4.pegawai_skkni')
                    ->whereIn('pegawai_id', $bawahanIds)
                    ->where('pegawai_id', '!=', $pimpinan->pegawai_id)
                    ->whereNotIn(DB::raw('LOWER(jabatan)'), $jabatanDikecualikan)
                    ->orderBy('pegawai_id')
                    ->get();
            } else {
                // Fallback: bila relasi tidak ada, tentukan bawahan berdasarkan pola jabatan
                $filterJabatan = $this->tentukanBawahanDariJabatan($jabatanPimpinan);

                $bawahanRaw = DB::table('geotrax_v4.pegawai_skkni')
                    ->when(!empty($filterJabatan), function ($q) use ($filterJabatan) {
                        $q->where(function ($sub) use ($filterJabatan) {
                            foreach ($filterJabatan as $jab) {
                                $sub->orWhere('jabatan', 'ILIKE', '%' . $jab . '%');
                            }
                        });
                    }, function ($q) {
                        $q->whereRaw('1=0'); // tidak punya bawahan
                    })
                    ->where('pegawai_id', '!=', $pimpinan->pegawai_id)
                    ->whereNotIn(DB::raw('LOWER(jabatan)'), $jabatanDikecualikan)
                    ->orderBy('pegawai_id')
                    ->get();
            }
        }

        // Buang data bawahan yang duplikat
        $bawahanRaw = $bawahanRaw->unique('pegawai_id')->values();

        $allBawahanIds = $bawahanRaw->pluck('pegawai_id')->toArray();

        $allBukti = DB::table('geotrax_v4.bukti_pegawai')->whereIn('pegawai_id', $allBawahanIds)->select('pegawai_id', 'aktivitas_id', 'tanggal_upload')->get();
        $groupedBukti = [];
        foreach ($allBukti as $b) { $groupedBukti[$b->pegawai_id][$b->aktivitas_id][] = $b->tanggal_upload; }

        $allPenilaian = DB::table('geotrax_v4.penilaian_header')->whereIn('pegawai_id', $allBawahanIds)->where('status', 'Selesai')->select('pegawai_id', 'kode_unit')->get();
        $groupedPenilaian = [];
        foreach ($allPenilaian as $p) { $groupedPenilaian[$p->pegawai_id][$p->kode_unit] = true; }

        $allJabatan = array_unique($bawahanRaw->pluck('jabatan')->toArray());
        $elemenCache = [];
        foreach($allJabatan as $jab) {
            $targets = $this->posisiTargetDariJabatan($jab);

            if (empty($targets)) {
                $elemenCache[$jab] = [];
                continue;
            }

            $elemenCache[$jab] = DB::table('geotrax_v4.elemen_kompetensi as ek')
                ->join('geotrax_v4.unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
                ->where(function ($q) use ($targets) {
                    foreach ($targets as $t) {
                        $q->orWhere('uk.posisi_target', 'ILIKE', '%' . $t . '%');
                    }
                })
                ->where('ek.kode_elemen_excel', 'ILIKE', '%A')
                ->select('ek.elemen_id', 'ek.kriteria_unjuk_kerja', 'uk.kode_unit',
                         'uk.judul_unit', 'ek.uni_kode_unit', 'ek.kode_elemen_excel')
                ->orderBy('uk.kode_unit')
                ->orderBy('ek.elemen_id')
                ->get()
                ->toArray();
        }

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
                        $waktu_terakhir = max($uploads);

                        if (count($uploads) > 0) {
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
        return view('pimpinan.tim_saya.index', compact('data_pegawai', 'pimpinan'));
    }

    public function beriNilai($pegawai_id, $kode_unit)
    {
        $pegawai = DB::table('geotrax_v4.pegawai_skkni')->where('pegawai_id', $pegawai_id)->first();
        $unit = DB::table('geotrax_v4.unit_kompetensi')->where('kode_unit', $kode_unit)->first();
        if (!$pegawai || !$unit) return redirect()->route('pimpinan.tim_saya.index');

        // Daftar UK milik pegawai ini, dipetakan lewat posisi_target (bukan nama jabatan mentah)
        $targets = $this->posisiTargetDariJabatan($pegawai->jabatan);

        if (empty($targets)) {
            $list_uk_pegawai = [];
        } else {
            $list_uk_pegawai = DB::table('geotrax_v4.elemen_kompetensi as ek')
                ->join('geotrax_v4.unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
                ->where(function ($q) use ($targets) {
                    foreach ($targets as $t) {
                        $q->orWhere('uk.posisi_target', 'ILIKE', '%' . $t . '%');
                    }
                })
                ->distinct()
                ->select('uk.kode_unit', 'uk.judul_unit')
                ->orderBy('uk.kode_unit')
                ->get();
        }

        $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')
            ->where('jabatan', 'ILIKE', trim($pegawai->jabatan))
            ->first();
        // Fallback bila nama jabatan tidak persis sama
        if (!$master_jabatan) {
            $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')
                ->where('jabatan', 'ILIKE', '%' . trim($pegawai->jabatan) . '%')
                ->first();
        }
        $persen_ncf = $master_jabatan ? $master_jabatan->persentase_cf : 60;
        $persen_nsf = $master_jabatan ? $master_jabatan->persentase_sf : 40;

        $penilaian_lama = DB::table('geotrax_v4.penilaian_header')->where('pegawai_id', $pegawai_id)->where('kode_unit', $kode_unit)->first();
        $is_update = $penilaian_lama ? true : false;
        $rekomendasi_lama = $penilaian_lama ? $penilaian_lama->rekomendasi : '';

        // DATA SKKNI LAMA
        $skor_lama_array = [];
        if ($is_update) {
            $detail_lama = DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->get();
            foreach ($detail_lama as $d) { $skor_lama_array[$d->aktivitas_id] = $d->skor_final; }
        }

        // ==============================================================
        // TARIK DATA SOFTSKILL (SECONDARY FACTOR)
        // ==============================================================
        $list_softskill = DB::table('standar_softskill')->get();
        $skor_sf_lama = [];
        if ($is_update) {
            $sf_lama = DB::table('penilaian_softskill')->where('penilaian_id', $penilaian_lama->penilaian_id)->get();
            foreach ($sf_lama as $sf) {
                $skor_sf_lama[$sf->softskill_id] = $sf->nilai_aktual;
            }
        }

        $bukti_pegawai = DB::table('geotrax_v4.bukti_pegawai')->where('pegawai_id', $pegawai_id)->get()->keyBy('aktivitas_id');

        $sql_elemen = "
            SELECT ek.elemen_id, ek.kode_elemen_excel, ek.elemen_kompetensi, ek.kriteria_unjuk_kerja, ek.uni_kode_unit
            FROM geotrax_v4.elemen_kompetensi ek WHERE ek.kode_unit = ? AND ek.kode_elemen_excel ILIKE '%A' ORDER BY ek.elemen_id ASC
        ";
        $list_elemen_raw = DB::select($sql_elemen, [$kode_unit]);

        $dataGrouped = [];
        $total_bobot_cf = 0; $item_cf = 0;

        // KALKULASI CF MURNI (SKKNI)
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

                 $base_code_wildcard = substr($base_code, 0, -1) . '_';
                 $aktivitas_id_wildcard = $base_code_wildcard . '-' . $ek->kode_elemen_excel . '-' . $kuk_number_str;

                 $rubrik_list = DB::table('geotrax_v4.rubrik_skor')->where('akt_aktivitas_id', 'LIKE', $aktivitas_id_wildcard)->orderBy('rubik_id', 'asc')->get();

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
                     $sudah_dinilai = false;
                     $butuh_panggil_ai = false;

                     if ($jml_upload > 0) {
                         $sudah_dinilai = $is_update && isset($skor_lama_array[$id_upload]);

                         if ($sudah_dinilai) {
                             $nilai_parser = $skor_lama_array[$id_upload];
                             $catatan_lama = DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $penilaian_lama->penilaian_id)->where('aktivitas_id', $id_upload)->first()->catatan_penilai ?? '';
                             $catatan_sistem = (!empty($catatan_lama) && $catatan_lama != 'Penilaian PM (Dikoreksi Pimpinan)') ? $catatan_lama : 'Dokumen dievaluasi pada sesi sebelumnya.';
                         } else {
                             $file_to_read = $file_path ?? $file_parameter;
                             if ($file_to_read) {
                                 $cache_key = 'ai_eval_' . $id_upload . '_' . md5($file_to_read);
                                 if (\Illuminate\Support\Facades\Cache::has($cache_key)) {
                                     $hasil_evaluasi = \Illuminate\Support\Facades\Cache::get($cache_key);
                                     $nilai_parser = $hasil_evaluasi['skor'];
                                     $catatan_sistem = $hasil_evaluasi['alasan'];
                                     $butuh_panggil_ai = false;
                                 } else {
                                     $butuh_panggil_ai = true;
                                     $nilai_parser = 0;
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

                     $dataGrouped[$elemenKode]['kuks'][] = [
                         'aktivitas_id' => $id_upload,
                         'kuk_ke' => $kuk_ke . ($rubrik_list->count() > 1 ? '.' . ($index + 1) : ''),
                         'detail_aktivitas' => $teks_kuk_bersih . "<br><b style='color:#3e54a0; font-size:12px;'>" . $sub_judul . "</b>",
                         'keterangan_evidence' => $rubrik->jenis_file ?? 'Dokumen Bukti/Laporan',
                         'jml_upload' => $jml_upload,
                         'file_path' => $file_path,
                         'tanggal_upload' => $bukti->tanggal_upload ?? null,
                         'target_cf' => $target_cf,
                         'nilai_parser' => $nilai_parser,
                         'catatan_sistem' => $catatan_sistem,
                         'nilai_profil_cf' => $nilai_profil_cf,
                         'gap_cf' => $gap_cf,
                         'bobot_cf' => $bobot_cf,
                         'aktivitas_id_induk' => $aktivitas_id_induk,
                         'butuh_panggil_ai' => $butuh_panggil_ai
                     ];
                 }
                 $kuk_ke++;
             }
        }

        // Hitung Base Nilai Sementara
        $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;

        // NSF akan dihitung ulang secara real-time via Javascript di View berdasarkan Softskill
        $nsf = 0; $ni = 0; $nilai_akhir_100 = 0;

        return view('pimpinan.tim_saya.beri_nilai', compact(
            'pegawai', 'unit', 'pegawai_id', 'kode_unit', 'is_update',
            'rekomendasi_lama', 'penilaian_lama', 'dataGrouped', 'ncf', 'nsf', 'ni', 'nilai_akhir_100',
            'persen_ncf', 'persen_nsf', 'list_uk_pegawai', 'list_softskill', 'skor_sf_lama'
        ));
    }

    public function simpanNilai(Request $request, $pegawai_id, $kode_unit)
    {
        DB::beginTransaction();
        try {
            $is_update = $request->is_update;
            $penilaian_id_lama = $request->penilaian_id_lama;

            $pegawai = DB::table('geotrax_v4.pegawai_skkni')->where('pegawai_id', $pegawai_id)->first();
            $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')
                ->where('jabatan', 'ILIKE', trim($pegawai->jabatan))
                ->first();
            if (!$master_jabatan) {
                $master_jabatan = DB::table('geotrax_v4.pm_pengaturan_jabatan')
                    ->where('jabatan', 'ILIKE', '%' . trim($pegawai->jabatan) . '%')
                    ->first();
            }
            $persen_ncf = $master_jabatan ? $master_jabatan->persentase_cf : 60;
            $persen_nsf = $master_jabatan ? $master_jabatan->persentase_sf : 40;

            $total_bobot_cf = 0; $item_cf = 0;
            $detail_to_insert = [];

            // 1. SIMPAN NILAI SKKNI SEBAGAI CORE FACTOR (NCF)
            if ($request->has('skor')) {
                foreach ($request->skor as $id_upload => $skor_aktual) {
                     $catatan_aktual = $request->catatan_sistem[$id_upload] ?? 'Penilaian PM (Dikoreksi Pimpinan)';

                     $target_cf = 5;
                     $gap_cf = (int)$skor_aktual - $target_cf;
                     $bobot_cf = $this->konversiBobotGap($gap_cf);
                     $total_bobot_cf += $bobot_cf;
                     $item_cf++;

                     $detail_to_insert[] = [
                         'aktivitas_id' => $id_upload,
                         'skor_final' => (int)$skor_aktual,
                         'jumlah_bukti_uploa' => 1,
                         'catatan' => $catatan_aktual
                     ];
                }
            }

            // 2. SIMPAN NILAI SOFTSKILL SEBAGAI SECONDARY FACTOR (NSF)
            $total_bobot_sf = 0; $item_sf = 0;
            $softskill_to_insert = [];

            if ($request->has('skor_softskill')) {
                $master_sf = DB::table('standar_softskill')->get()->keyBy('softskill_id');
                foreach ($request->skor_softskill as $sf_id => $aktual) {
                    $target = $master_sf->get($sf_id)->target_skor ?? 4;
                    $gap = (int)$aktual - $target;
                    $bobot = $this->konversiBobotGap($gap);

                    $total_bobot_sf += $bobot;
                    $item_sf++;

                    $softskill_to_insert[] = [
                        'softskill_id' => $sf_id,
                        'nilai_aktual' => (int)$aktual,
                        'gap' => $gap,
                        'bobot_gap' => $bobot,
                    ];
                }
            }

            // 3. MENGHITUNG NILAI AKHIR 100
            $ncf = ($item_cf > 0) ? ($total_bobot_cf / $item_cf) : 0;
            $nsf = ($item_sf > 0) ? ($total_bobot_sf / $item_sf) : 0;
            $ni = (($persen_ncf / 100) * $ncf) + (($persen_nsf / 100) * $nsf);
            $nilai_akhir_100 = ($ni / 5) * 100;

            // ADAPTASI SKALA 5 PERANKINGAN EXCEL (REVISI BATAS)
            if ($nilai_akhir_100 >= 85) $kategori = "Sangat Tinggi";
            elseif ($nilai_akhir_100 >= 70) $kategori = "Tinggi";
            elseif ($nilai_akhir_100 >= 55) $kategori = "Sedang";
            elseif ($nilai_akhir_100 >= 40) $kategori = "Rendah";
            else $kategori = "Sangat Rendah";

            $waktu_sekarang = date('Y-m-d H:i:s');

            // 4. SIMPAN HEADER
            if ($is_update && $penilaian_id_lama) {
                DB::table('geotrax_v4.penilaian_header')->where('penilaian_id', $penilaian_id_lama)->update([
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori,
                    'rekomendasi' => $request->rekomendasi, 'waktu_submit' => $waktu_sekarang
                ]);
                DB::table('geotrax_v4.penilaian_detail')->where('penilaian_id', $penilaian_id_lama)->delete();
                $penilaian_id = $penilaian_id_lama;
            } else {
                $penilaian_id = (DB::table('geotrax_v4.penilaian_header')->max('penilaian_id') ?? 0) + 1;
                DB::table('geotrax_v4.penilaian_header')->insert([
                    'penilaian_id' => $penilaian_id, 'pegawai_id' => $pegawai_id, 'kode_unit' => $kode_unit,
                    'nilai_akhir' => $nilai_akhir_100, 'kategori' => $kategori, 'rekomendasi' => $request->rekomendasi,
                    'status' => 'Selesai', 'detail_penilaian_i' => 0, 'waktu_submit' => $waktu_sekarang, 'catatan_umum' => ''
                ]);
            }

            // 5. BULK INSERT SKKNI
            if (count($detail_to_insert) > 0) {
                $current_max_detail = DB::table('geotrax_v4.penilaian_detail')->max('detail_penilaian_i') ?? 0;
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
                DB::table('geotrax_v4.penilaian_detail')->insert($inserts);
            }

            // 6. BULK INSERT SOFTSKILL
            if ($is_update && $penilaian_id_lama) {
                DB::table('penilaian_softskill')->where('penilaian_id', $penilaian_id_lama)->delete();
            }
            if (count($softskill_to_insert) > 0) {
                foreach ($softskill_to_insert as &$sf_insert) {
                    $sf_insert['penilaian_id'] = $penilaian_id;
                    $sf_insert['created_at'] = now();
                    $sf_insert['updated_at'] = now();
                }
                DB::table('penilaian_softskill')->insert($softskill_to_insert);
            }

            // =========================================================
            // 7. SIMPAN HASIL KE TABEL PROFILE MATCHING (TAMBAHAN BARU)
            // =========================================================
            // Cari ID jabatan target di tabel master
            $master_jab_target = DB::table('geotrax_v4.p_master_jabatan')
                ->where('nama_jabatan', 'ILIKE', "%{$pegawai->jabatan}%")
                ->first();

            if ($master_jab_target) {
                // Hapus data lama agar tidak menumpuk saat di-update
                DB::table('geotrax_v4.p_hasil_profile_matching')
                    ->where('id_pegawai', $pegawai_id)
                    ->delete();

                // Simpan hasil hitungan CF, SF, dan Nilai Total (ni) dari Jurnal
                DB::table('geotrax_v4.p_hasil_profile_matching')->insert([
                    'id_pegawai' => $pegawai_id,
                    'id_jabatan' => $master_jab_target->id_jabatan,
                    'nilai_core_factor' => $ncf,
                    'nilai_secondary_factor' => $nsf,
                    'nilai_total' => $ni, 
                    'peringkat' => 1 // Di set peringkat 1 karena menilai kecocokan dengan jabatannya sendiri
                ]);
            }

            DB::commit();
            return redirect()->route('pimpinan.tim_saya.index')->with('success', 'Penilaian SKKNI dan Softskill berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        }
    }

    public function ajaxPanggilAI(Request $request)
    {
        $id_upload = $request->id_upload;
        $file_to_read = $request->file_to_read;
        $force_reload = filter_var($request->force_reload, FILTER_VALIDATE_BOOLEAN);

        if (!$file_to_read) {
            return response()->json(['skor' => 1, 'alasan' => 'File bukti fisik rusak atau tidak ditemukan.']);
        }

        $cache_key = 'ai_eval_' . $id_upload . '_' . md5($file_to_read);

        if ($force_reload) Cache::forget($cache_key);

        if (Cache::has($cache_key)) {
            $hasil_evaluasi = Cache::get($cache_key);
            if (str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan') || str_contains(strtolower($hasil_evaluasi['alasan']), 'timeout')) {
                Cache::forget($cache_key);
                $hasil_evaluasi = $this->prosesAnalisisDokumen($id_upload, $file_to_read);
                if (!str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan')) {
                    Cache::put($cache_key, $hasil_evaluasi, now()->addDays(30));
                }
            }
        } else {
            $hasil_evaluasi = $this->prosesAnalisisDokumen($id_upload, $file_to_read);
            if (!str_contains(strtolower($hasil_evaluasi['alasan']), 'kendala jaringan') && !str_contains(strtolower($hasil_evaluasi['alasan']), 'timeout')) {
                Cache::put($cache_key, $hasil_evaluasi, now()->addDays(30));
            }
        }

        return response()->json($hasil_evaluasi);
    }

    public function simpanBobotGap(Request $request) { }
}
