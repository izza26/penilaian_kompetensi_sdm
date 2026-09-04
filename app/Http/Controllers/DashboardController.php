<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function pimpinan()
    {
        // 1. Ambil data pimpinan yang login
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
        }

        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
        $filterJabatanBawahan = [];

        // 2. Mapping Jabatan Bawahan
        if (str_contains($jabatanPimpinan, 'Kepala Museum')) {
            $filterJabatanBawahan = ['Koordinator', 'Manajer', 'Kurator'];
        } elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) {
            $filterJabatanBawahan = ['Konservator', 'Register'];
        } elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) {
            $filterJabatanBawahan = ['Edukator', 'Penata Pameran'];
        } elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) {
            $filterJabatanBawahan = ['Humas', 'Hubungan Masyarakat'];
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $filterJabatanBawahan = ['Kurator'];
        }

        // 3. Tarik ID Bawahan (KECUALI DIRI SENDIRI)
        $bawahanRaw = \App\Models\Pegawai::where('pegawai_id', '!=', $pimpinan->pegawai_id)
            ->where(function($q) use ($filterJabatanBawahan) {
            if (empty($filterJabatanBawahan)) {
                $q->whereRaw('1=0');
            } else {
                foreach ($filterJabatanBawahan as $jab) {
                    $q->orWhere('jabatan', 'LIKE', '%' . $jab . '%');
                }
            }
        })->get();
        
        $bawahan_ids = $bawahanRaw->pluck('pegawai_id')->toArray();
        $tot_pegawai = count($bawahan_ids);

        // 4. Kalkulasi KPI Default
        $tot_dokumen = 0; $belum_dinilai = 0; $tot_dinilai = 0;
        $pct_kompeten = 0; $pct_cukup = 0; $pct_bina = 0;
        $timeline = [];

        // Hitung Data HANYA JIKA punya bawahan
        if (!empty($bawahan_ids)) {
            $tot_dokumen = DB::table('geotrax_v3.bukti_pegawai')->whereIn('pegawai_id', $bawahan_ids)->count();
            
            $tot_dinilai = DB::table('geotrax_v3.penilaian_header')
                            ->whereIn('pegawai_id', $bawahan_ids)
                            ->where('status', 'Selesai')
                            ->count();
                            
            $bukti_divalidasi = DB::table('geotrax_v3.penilaian_detail')
                            ->join('geotrax_v3.penilaian_header', 'penilaian_detail.penilaian_id', '=', 'penilaian_header.penilaian_id')
                            ->whereIn('penilaian_header.pegawai_id', $bawahan_ids)
                            ->count();
                            
            $belum_dinilai = max(0, $tot_dokumen - $bukti_divalidasi);

            $kompeten = DB::table('geotrax_v3.penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->where('nilai_akhir', '>=', 70)->count();
            $cukup = DB::table('geotrax_v3.penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->whereBetween('nilai_akhir', [55, 69.99])->count();
            $bina = DB::table('geotrax_v3.penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->where('nilai_akhir', '<', 55)->count();
            
            $tot_dinilai_stat = $kompeten + $cukup + $bina;
            if ($tot_dinilai_stat > 0) {
                $pct_kompeten = round(($kompeten / $tot_dinilai_stat) * 100);
                $pct_cukup = round(($cukup / $tot_dinilai_stat) * 100);
                $pct_bina = round(($bina / $tot_dinilai_stat) * 100);
            }

            $timeline = DB::table('geotrax_v3.bukti_pegawai as bp')
                ->join('geotrax_v3.pegawai_skkni as p', 'bp.pegawai_id', '=', 'p.pegawai_id') 
                ->join('geotrax_v3.aktivitas_kompeten as ak', 'bp.aktivitas_id', '=', 'ak.aktivitas_id')
                ->join('geotrax_v3.elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
                ->join('geotrax_v3.unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
                ->whereIn('bp.pegawai_id', $bawahan_ids)
                ->select('p.pegawai_nama', 'uk.judul_unit', 'bp.tanggal_upload')
                ->orderBy('bp.tanggal_upload', 'desc')
                ->limit(5)->get();
        }

        // 5. Cek Periode Aktif Khusus Bawahan
        $periodeAktif = DB::table('geotrax_v3.periode_penilaian')
            ->whereIn('nama_periode', $filterJabatanBawahan)
            ->orderBy('tanggal_mulai', 'asc')
            ->first();

        $badge_class = 'info'; $is_open = false; $pesan_periode = '';
        if ($periodeAktif && !empty($periodeAktif->tanggal_mulai) && strpos($periodeAktif->tanggal_mulai, '1970') === false) {
            $tgl_mulai = strtotime($periodeAktif->tanggal_mulai);
            $tgl_selesai = strtotime($periodeAktif->tanggal_selesai . ' 23:59:59');
            $now = time();

            if ($now < $tgl_mulai) {
                $badge_class = 'warning'; $is_open = false;
                $pesan_periode = "Periode Penilaian untuk tim Anda akan dimulai pada: <b>" . date('d M Y', $tgl_mulai) . "</b>.";
            } elseif ($now > $tgl_selesai) {
                $badge_class = 'danger'; $is_open = false;
                $pesan_periode = "Periode Penilaian untuk tim Anda telah DITUTUP sejak <b>" . date('d M Y', $tgl_selesai) . "</b>.";
            } else {
                $badge_class = 'success'; $is_open = true;
                $pesan_periode = "Periode Penilaian tim Anda sedang berlangsung: <b>" . date('d M Y', $tgl_mulai) . " s/d " . date('d M Y', $tgl_selesai) . "</b>.";
            }
        }

        return view('pimpinan.dashboard', compact(
            'pimpinan', 'tot_pegawai', 'tot_dokumen', 'belum_dinilai', 'tot_dinilai', 
            'pct_kompeten', 'pct_cukup', 'pct_bina', 'timeline',
            'periodeAktif', 'badge_class', 'is_open', 'pesan_periode'
        ));
    }

    public function pegawai()
    {
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        $pegawai = \Illuminate\Support\Facades\DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        if (!$pegawai) return redirect()->route('login');

        // Normalisasi Jabatan
        $jabatanRaw = trim($pegawai->jabatan ?? '');
        $jabatan = strtolower($jabatanRaw);
        if (in_array($jabatan, ['humas', 'humas & pemasaran', 'humas dan pemasaran'])) {
            $jabatan = 'hubungan masyarakat dan pemasaran';
        }

        // =========================================================================
        // PERBAIKAN FATAL: MESIN HITUNG DISAMAKAN 100% DENGAN HALAMAN AKTIVITAS
        // =========================================================================
        $sqlData = "
            SELECT 
                ek.elemen_id, ek.kode_elemen_excel, ek.elemen_kompetensi, ek.kriteria_unjuk_kerja, ek.uni_kode_unit,
                uk.kode_unit, uk.judul_unit
            FROM geotrax_v3.elemen_kompetensi ek
            JOIN geotrax_v3.unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            WHERE uk.posisi_target ILIKE ? AND ek.kode_elemen_excel ILIKE '%A' 
        ";
        $elemen_raw = \Illuminate\Support\Facades\DB::select($sqlData, ["%" . $jabatan . "%"]);

        // Tarik semua memori
        $semua_bukti = \Illuminate\Support\Facades\DB::table('geotrax_v3.bukti_pegawai')->where('pegawai_id', $pegawai->pegawai_id)->get()->keyBy('aktivitas_id');
        $semua_rubrik = \Illuminate\Support\Facades\DB::table('geotrax_v3.rubrik_skor')->orderBy('rubik_id', 'asc')->get();
        $grouped_rubrik = [];
        foreach ($semua_rubrik as $r) { $grouped_rubrik[$r->akt_aktivitas_id][] = $r; }

        $totalEvidenceWajib = 0;
        $totalUploadSaya = 0;

        foreach ($elemen_raw as $row) {
            $kukTextList = preg_split('/\r\n|\r|\n|(?=\b\d+(?:\.\d+)*[\.\s]+)/', trim($row->kriteria_unjuk_kerja ?? ''));
            $kukTextList = array_values(array_filter(array_map('trim', $kukTextList)));

            $kuk_ke = 1;
            foreach ($kukTextList as $kuk_text) {
                if (empty($kuk_text)) continue; 
                $kuk_number_str = str_pad($kuk_ke, 2, '0', STR_PAD_LEFT);
                $base_code = str_replace('.', '', $row->uni_kode_unit); 
                $aktivitas_id = $base_code . '-' . $row->kode_elemen_excel . '-' . $kuk_number_str;

                $rubrik_list = $grouped_rubrik[$aktivitas_id] ?? [];
                $rubrik_count = count($rubrik_list);

                // MENGHITUNG SESUAI CABANG KUK
                if ($rubrik_count > 1) {
                    foreach ($rubrik_list as $index => $rubrik) {
                        $sub_id = $aktivitas_id . '_SUB_' . $index;
                        $bukti = $semua_bukti->get($sub_id); 
                        $totalEvidenceWajib++;
                        if ($bukti) $totalUploadSaya++;
                    }
                } else {
                    $bukti = $semua_bukti->get($aktivitas_id); 
                    $totalEvidenceWajib++;
                    if ($bukti) $totalUploadSaya++;
                }
                $kuk_ke++;
            }
        }
        // =========================================================================

        $sisaEvidence = max(0, $totalEvidenceWajib - $totalUploadSaya);
        $progress = ($totalEvidenceWajib > 0) ? round(($totalUploadSaya / $totalEvidenceWajib) * 100) : 0;

        // Logika Status Penilaian
        if ($progress == 0) {
            $statusPenilaian = "Belum Dimulai";
        } elseif ($progress < 100) {
            $statusPenilaian = "Sedang Berjalan";
        } else {
            $statusPenilaian = "Menunggu Review";
        }

        // Logika Pesan & Tombol Aksi
        $aktivitasDashboard = [];
        
        if ($totalUploadSaya == 0) {
            $aktivitasDashboard[] = ["icon" => "cloud-upload", "judul" => "Belum ada upload bukti", "deskripsi" => "Upload dokumen bukti pertama Anda untuk memulai proses penilaian."];
        } else {
            $aktivitasDashboard[] = ["icon" => "check-circle-fill", "judul" => "Dokumen berhasil diupload", "deskripsi" => "Anda telah mengupload {$totalUploadSaya} dokumen bukti."];
        }

        if ($progress < 100) {
            $aktivitasDashboard[] = ["icon" => "clipboard-check", "judul" => "Penilaian belum selesai", "deskripsi" => "Lengkapi seluruh dokumen bukti yang masih belum diupload."];
            $aksiText = ($progress == 0) ? "Upload Dokumen Bukti" : "Lanjut Upload Dokumen";
            $aksiLink = route('pegawai.aktivitas.index'); 
            $aksiIcon = "cloud-arrow-up";
        } else {
            $aktivitasDashboard[] = ["icon" => "clipboard-check-fill", "judul" => "Seluruh dokumen telah lengkap", "deskripsi" => "Silakan menunggu proses review dari pimpinan."];
            $aksiText = "Lihat Penilaian Saya";
            $aksiLink = route('pegawai.aktivitas.index'); 
            $aksiIcon = "clipboard-check";
        }

        if ($statusPenilaian == "Belum Dimulai") {
            $aktivitasDashboard[] = ["icon" => "award", "judul" => "Hasil kompetensi belum tersedia", "deskripsi" => "Nilai kompetensi akan muncul setelah seluruh proses penilaian selesai."];
        } else {
            $aktivitasDashboard[] = ["icon" => "award-fill", "judul" => "Progress kompetensi sedang diproses", "deskripsi" => "Pantau perkembangan penilaian Anda pada menu Hasil Kompetensi."];
        }

        setlocale(LC_TIME, 'id_ID.utf8', 'Indonesian');
        $tanggalSekarang = \Carbon\Carbon::now()->timezone('Asia/Jakarta')->translatedFormat('l, d F Y');

        if ($progress == 0) {
            $welcomeMessage = "Anda belum memulai proses penilaian kompetensi. Mulailah dengan mengupload dokumen bukti pertama Anda.";
        } elseif ($progress < 100) {
            $welcomeMessage = "Progress penilaian Anda telah mencapai {$progress}%. Teruskan hingga seluruh dokumen bukti berhasil diupload.";
        } else {
            $welcomeMessage = "Selamat! Seluruh dokumen bukti telah lengkap. Silakan menunggu proses evaluasi dari pimpinan.";
        }

        return view('pegawai.dashboard', compact(
            'pegawai', 'totalUploadSaya', 'totalEvidenceWajib', 'sisaEvidence', 
            'progress', 'statusPenilaian', 'aktivitasDashboard', 
            'aksiText', 'aksiLink', 'aksiIcon', 'tanggalSekarang', 'welcomeMessage'
        ));
    }
}