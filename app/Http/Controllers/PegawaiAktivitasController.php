<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage; 

class PegawaiAktivitasController extends Controller
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
        return strtolower(trim($teks));
    }

    private function getJabatanFormal($jabatan) {
        $jabatan = trim($jabatan);
        if (in_array(strtolower($jabatan), ['humas', 'humas & pemasaran', 'humas dan pemasaran'])) return 'Hubungan Masyarakat dan Pemasaran';
        return $jabatan;
    }

    public function index()
    {
        $userLogin = Auth::user();
        $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        if (!$pegawai) return redirect()->route('login')->with('error', 'Sesi login tidak valid.');

        $jabatan = $this->getJabatanFormal($pegawai->jabatan ?? 'Belum Ada Jabatan');
        $relasiAtasan = DB::table('geotrax_v3.p_pegawai_penilai_sah')->where('id_pegawai', $pegawai->pegawai_id)->first();
        $pimpinan = $relasiAtasan ? DB::table('geotrax_v3.pegawai_skkni')->where('pegawai_id', $relasiAtasan->id_penilai)->first() : null;

        $periodeAktif = DB::table('geotrax_v3.periode_penilaian')->where('nama_periode', $jabatan)->where('status_aktif', 'Y')->first();
        $is_open = ($periodeAktif) ? true : false;

        $sqlData = "
            SELECT 
                ek.elemen_id, ek.kode_elemen_excel, ek.elemen_kompetensi, ek.kriteria_unjuk_kerja, ek.uni_kode_unit,
                uk.kode_unit, uk.judul_unit
            FROM geotrax_v3.elemen_kompetensi ek
            JOIN geotrax_v3.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            WHERE uk.posisi_target ILIKE ? AND ek.kode_elemen_excel ILIKE '%A' 
            ORDER BY uk.kode_unit ASC, ek.elemen_id ASC
        ";
        
        $elemen_raw = DB::select($sqlData, ["%" . $jabatan . "%"]);

        // OPTIMASI: Tarik semua memori
        $semua_bukti = DB::table('geotrax_v3.bukti_pegawai')->where('pegawai_id', $pegawai->pegawai_id)->get()->keyBy('aktivitas_id');
        $semua_rubrik = DB::table('geotrax_v3.rubrik_skor')->orderBy('rubik_id', 'asc')->get();
        $grouped_rubrik = [];
        foreach ($semua_rubrik as $r) { $grouped_rubrik[$r->akt_aktivitas_id][] = $r; }
        
        $dataGrouped = [];
        $unitStats = []; // UNTUK MENYIMPAN TARGET & REALISASI PER UNIT
        $unitNavs = [];  // UNTUK MENYIMPAN NAVIGASI CEPAT PER UNIT

        $unit_counter = 1;
        $prev_unit = '';

        foreach ($elemen_raw as $row) {
            if ($prev_unit !== $row->kode_unit) {
                $prev_unit = $row->kode_unit;
                $unitKey = $unit_counter . "|||" . $row->kode_unit . "|||" . $row->judul_unit;
                $unitStats[$unitKey] = ['target' => 0, 'uploaded' => 0];
                $unitNavs[$row->kode_unit] = [];
                $unit_counter++;
            } else {
                $unitKey = ($unit_counter - 1) . "|||" . $row->kode_unit . "|||" . $row->judul_unit;
            }
            
            $elemenNum = preg_replace('/[^0-9]/', '', $row->kode_elemen_excel); 
            $elemenKode = $row->uni_kode_unit . "-" . $elemenNum;
            $elemenKey = $elemenKode . "|||" . ($row->elemen_kompetensi ?? 'Tanpa Elemen');

            $kukTextList = preg_split('/\r\n|\r|\n|(?=\b\d+(?:\.\d+)*[\.\s]+)/', trim($row->kriteria_unjuk_kerja ?? ''));
            $kukTextList = array_values(array_filter(array_map('trim', $kukTextList)));

            $kuk_ke = 1;
            foreach ($kukTextList as $kuk_text) {
                if (empty($kuk_text)) continue; 

                $kuk_number_str = str_pad($kuk_ke, 2, '0', STR_PAD_LEFT);
                $base_code = str_replace('.', '', $row->uni_kode_unit); 
                $aktivitas_id = $base_code . '-' . $row->kode_elemen_excel . '-' . $kuk_number_str;
                $teks_kuk_bersih = preg_replace('/^\s*\d+(?:\.\d+)*[\.\s]+/', '', $kuk_text);

                $rubrik_list = $grouped_rubrik[$aktivitas_id] ?? [];
                $rubrik_count = count($rubrik_list);

                if ($rubrik_count > 1) {
                    $sub_items = [];
                    $semua_upload = true; 
                    $jumlah_upload_cabang = 0;

                    foreach ($rubrik_list as $index => $rubrik) {
                        $sub_id = $aktivitas_id . '_SUB_' . $index;
                        $bukti = $semua_bukti->get($sub_id); 
                        
                        $keterangan_evidence = $rubrik->jenis_file ?? 'Dokumen Laporan';
                        if (empty($rubrik->jenis_file) && !empty($rubrik->skor_5) && preg_match('/\(bukti\s*[\:\-]?\s*(.*?)\)/i', $rubrik->skor_5, $matches)) {
                            $keterangan_evidence = trim($matches[1]);
                        }

                        $sub_judul = "Tugas Bagian " . ($index + 1);
                        if (preg_match('/\((.*?)\)/', $rubrik->skor_1, $match)) $sub_judul = ucwords($match[1]); 

                        $sub_items[] = [
                            'aktivitas_id' => $sub_id,
                            'sub_judul' => $sub_judul,
                            'keterangan_evidence' => $keterangan_evidence,
                            'is_uploaded' => $bukti ? 1 : 0,
                            'file_path' => $bukti->file_path ?? null,
                            'bukti_id' => $bukti->bukti_id ?? null,
                            'status_asli' => $bukti->status_validasi ?? 'Belum Upload',
                            'tgl_upload' => $bukti->tanggal_upload ?? null,
                            'catatan_reviewer' => $bukti->catatan_penilai ?? '-' 
                        ];

                        if ($bukti) {
                            $unitStats[$unitKey]['uploaded'] += 1;
                            $jumlah_upload_cabang++;
                        } else {
                            $semua_upload = false; 
                        }
                        $unitStats[$unitKey]['target'] += 1;
                    }

                    $dataGrouped[$unitKey][$elemenKey][] = [
                        'aktivitas_id' => $aktivitas_id,
                        'kuk_ke' => $kuk_ke,
                        'teks_kuk' => $teks_kuk_bersih,
                        'detail_aktivitas' => $teks_kuk_bersih, 
                        'is_bercabang' => true,
                        'sub_items' => $sub_items,
                        'status_asli' => $semua_upload ? 'Sudah Diunggah Lengkap' : 'Belum Lengkap',
                        'keterangan_evidence' => 'KUK Multi-Tugas (Lihat Detail)',
                        'is_uploaded' => $semua_upload ? 1 : 0,
                        'file_path' => null,
                        'bukti_id' => null,
                        'tgl_upload' => null,
                        'catatan_reviewer' => '-'
                    ];

                    $unitNavs[$row->kode_unit][] = [
                        'id' => \Illuminate\Support\Str::slug($aktivitas_id), 
                        'label' => "KUK $kuk_ke", 
                        'is_uploaded' => $semua_upload ? 1 : 0,
                        'tooltip' => "Terkumpul $jumlah_upload_cabang dari $rubrik_count dokumen"
                    ];

                } else {
                    // KUK TUNGGAL
                    $bukti = $semua_bukti->get($aktivitas_id); 
                    $rubrik = $rubrik_count > 0 ? $rubrik_list[0] : null;
                    $keterangan_evidence = $rubrik->jenis_file ?? 'Dokumen Bukti/Laporan';
                    if ($rubrik && empty($rubrik->jenis_file) && !empty($rubrik->skor_5) && preg_match('/\(bukti\s*[\:\-]?\s*(.*?)\)/i', $rubrik->skor_5, $matches)) {
                        $keterangan_evidence = trim($matches[1]);
                    }

                    $unitStats[$unitKey]['target'] += 1;
                    if ($bukti) $unitStats[$unitKey]['uploaded'] += 1;

                    $dataGrouped[$unitKey][$elemenKey][] = [
                        'aktivitas_id' => $aktivitas_id,
                        'kuk_ke' => $kuk_ke,
                        'teks_kuk' => $teks_kuk_bersih,
                        'detail_aktivitas' => $teks_kuk_bersih, 
                        'keterangan_evidence' => $keterangan_evidence,
                        'is_bercabang' => false,
                        'is_uploaded' => $bukti ? 1 : 0,
                        'file_path' => $bukti->file_path ?? null,
                        'bukti_id' => $bukti->bukti_id ?? null,
                        'status_asli' => $bukti->status_validasi ?? 'Belum Upload',
                        'tgl_upload' => $bukti->tanggal_upload ?? null,
                        'catatan_reviewer' => $bukti->catatan_penilai ?? '-' 
                    ];

                    $unitNavs[$row->kode_unit][] = [
                        'id' => \Illuminate\Support\Str::slug($aktivitas_id), 
                        'label' => "KUK $kuk_ke", 
                        'is_uploaded' => $bukti ? 1 : 0,
                        'tooltip' => $bukti ? "Dokumen Tersimpan" : "Belum Diunggah"
                    ];
                }
                $kuk_ke++;
            }
        }

        return view('pegawai.aktivitas.index', compact('periodeAktif', 'is_open', 'dataGrouped', 'unitStats', 'unitNavs', 'jabatan', 'pimpinan'));
    }

    public function upload(Request $request, $id)
    {
        $userLogin = Auth::user();
        $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        $request->validate(['file_evidence' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120']);

        $clean_id = trim($id); 
        $file = $request->file('file_evidence');
        $nama_file_baru = "EVI_" . $pegawai->pegawai_id . "_" . time() . "_" . rand(10,99) . "." . $file->getClientOriginalExtension();
        
        try {
            // PAKSA LARAVEL UNTUK JUJUR: Cek apakah upload benar-benar berhasil!
            $uploadStatus = Storage::disk('s3')->put('uploads/evidence/' . $nama_file_baru, file_get_contents($file));
            
            if (!$uploadStatus) {
                throw new \Exception("Koneksi ke Supabase S3 gagal. Cek kembali file .env Anda.");
            }

            $new_id = (DB::table('geotrax_v3.bukti_pegawai')->max('bukti_id') ?? 0) + 1;
            
            DB::table('geotrax_v3.bukti_pegawai')->insert([
                'bukti_id' => $new_id, 
                'file_path' => $nama_file_baru, 
                'status_validasi' => 'Menunggu Review',
                'pegawai_id' => $pegawai->pegawai_id, 
                'tanggal_upload' => Carbon::now('Asia/Jakarta'), 
                'aktivitas_id' => $clean_id 
            ]);
            
            if ($request->ajax() || $request->wantsJson()) {
                $file_url = env('AWS_URL') . '/uploads/evidence/' . $nama_file_baru;
                return response()->json([
                    'success' => true,
                    'message' => 'Dokumen berhasil diunggah ke Cloud!',
                    'bukti_id' => $new_id,
                    'file_path' => $nama_file_baru,
                    'file_url' => $file_url
                ]);
            }
            return back()->with('success', 'Dokumen berhasil diunggah ke Cloud Storage!');
        } catch (\Exception $e) { 
            if ($request->ajax() || $request->wantsJson()) return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            return back()->with('error', 'Gagal menyimpan data ke Cloud: ' . $e->getMessage()); 
        }
    }

    public function destroyBukti(Request $request, $aktivitas_id, $bukti_id)
    {
        $userLogin = Auth::user();
        $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        $file_hapus = DB::table('geotrax_v3.bukti_pegawai')->where('bukti_id', $bukti_id)->where('pegawai_id', $pegawai->pegawai_id)->first();
        
        if ($file_hapus && in_array($file_hapus->status_validasi, ['Menunggu Review', 'Revisi', 'Belum Dinilai'])) {
            
            // BUNGKUS DENGAN TRY-CATCH: Hapus secara diam-diam. Kalau S3 error, abaikan saja dan lanjutkan hapus database!
            try {
                Storage::disk('s3')->delete('uploads/evidence/' . $file_hapus->file_path);
            } catch (\Exception $e) {
                // Abaikan error Supabase S3 (Misal: Unable to check existence)
            }
            
            $storagePath = storage_path('app/public/uploads/evidence/' . $file_hapus->file_path);
            if (File::exists($storagePath)) File::delete($storagePath);
            
            $publicPath = public_path('uploads/evidence/' . $file_hapus->file_path);
            if (File::exists($publicPath)) File::delete($publicPath);

            // PASTIKAN DATABASE TERHAPUS APAPUN YANG TERJADI
            DB::table('geotrax_v3.bukti_pegawai')->where('bukti_id', $bukti_id)->delete();

            if ($request->ajax() || $request->wantsJson()) return response()->json(['success' => true, 'message' => 'Dokumen terhapus.']);
            return back()->with('success', 'Dokumen KUK berhasil dihapus dari sistem.');
        }

        if ($request->ajax() || $request->wantsJson()) return response()->json(['success' => false, 'message' => 'File sudah dinilai, tidak bisa dihapus.'], 403);
        return back()->with('error', 'File tidak bisa dihapus karena sudah dinilai final.');
    }
}