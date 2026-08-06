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
        
        // --- TAMBAHKAN BARIS INI ---
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
            $tot_dokumen = \Illuminate\Support\Facades\DB::table('bukti_pegawai')->whereIn('pegawai_id', $bawahan_ids)->count();
            
            $tot_dinilai = \Illuminate\Support\Facades\DB::table('penilaian_header')
                            ->whereIn('pegawai_id', $bawahan_ids)
                            ->where('status', 'Selesai')
                            ->count();
                            
            $bukti_divalidasi = \Illuminate\Support\Facades\DB::table('penilaian_detail')
                            ->join('penilaian_header', 'penilaian_detail.penilaian_id', '=', 'penilaian_header.penilaian_id')
                            ->whereIn('penilaian_header.pegawai_id', $bawahan_ids)
                            ->count();
                            
            $belum_dinilai = max(0, $tot_dokumen - $bukti_divalidasi);

            $kompeten = \Illuminate\Support\Facades\DB::table('penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->where('nilai_akhir', '>=', 70)->count();
            $cukup = \Illuminate\Support\Facades\DB::table('penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->whereBetween('nilai_akhir', [55, 69.99])->count();
            $bina = \Illuminate\Support\Facades\DB::table('penilaian_header')->whereIn('pegawai_id', $bawahan_ids)->where('nilai_akhir', '<', 55)->count();
            
            $tot_dinilai_stat = $kompeten + $cukup + $bina;
            if ($tot_dinilai_stat > 0) {
                $pct_kompeten = round(($kompeten / $tot_dinilai_stat) * 100);
                $pct_cukup = round(($cukup / $tot_dinilai_stat) * 100);
                $pct_bina = round(($bina / $tot_dinilai_stat) * 100);
            }

            $timeline = \Illuminate\Support\Facades\DB::table('bukti_pegawai as bp')
                ->join('pegawai as p', 'bp.pegawai_id', '=', 'p.pegawai_id')
                ->join('aktivitas_kompeten as ak', 'bp.aktivitas_id', '=', 'ak.aktivitas_id')
                ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
                ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
                ->whereIn('bp.pegawai_id', $bawahan_ids)
                ->select('p.pegawai_nama', 'uk.judul_unit', 'bp.tanggal_upload')
                ->orderBy('bp.tanggal_upload', 'desc')
                ->limit(5)->get();
        }

        // 5. Cek Periode Aktif Khusus Bawahan
        $periodeAktif = \Illuminate\Support\Facades\DB::table('periode_penilaian')
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
        $pegawai = Auth::user();

        // 1. Tarik Data Statistik Total
        $totalAktivitas = DB::table('aktivitas_kompeten')->count();
        $totalUnit = DB::table('unit_kompetensi')->count();
        $totalEvidenceWajib = DB::table('evidence_wajib')->count();
        
        // 2. Tarik Data Upload Pegawai Ini Saja
        $totalUploadSaya = DB::table('bukti_pegawai')->where('pegawai_id', $pegawai->pegawai_id)->count();
        
        $sisaEvidence = max(0, $totalEvidenceWajib - $totalUploadSaya);
        $progress = ($totalEvidenceWajib > 0) ? round(($totalUploadSaya / $totalEvidenceWajib) * 100) : 0;

        // 3. Logika Status Penilaian
        if ($progress == 0) {
            $statusPenilaian = "Belum Dimulai";
        } elseif ($progress < 100) {
            $statusPenilaian = "Sedang Berjalan";
        } else {
            $statusPenilaian = "Menunggu Review";
        }

        // 4. Logika Pesan & Tombol Aksi
        $aktivitasDashboard = [];
        
        // Cek Upload
        if ($totalUploadSaya == 0) {
            $aktivitasDashboard[] = ["icon" => "cloud-upload", "judul" => "Belum ada upload evidence", "deskripsi" => "Upload evidence pertama Anda untuk memulai proses penilaian."];
        } else {
            $aktivitasDashboard[] = ["icon" => "check-circle-fill", "judul" => "Evidence berhasil diupload", "deskripsi" => "Anda telah mengupload {$totalUploadSaya} evidence."];
        }

        // Cek Progress
        if ($progress < 100) {
            $aktivitasDashboard[] = ["icon" => "clipboard-check", "judul" => "Penilaian belum selesai", "deskripsi" => "Lengkapi seluruh evidence yang masih belum diupload."];
            $aksiText = ($progress == 0) ? "Upload Evidence" : "Lanjut Upload Evidence";
            $aksiLink = "#"; // Nanti diganti ke route upload
            $aksiIcon = "cloud-arrow-up";
        } else {
            $aktivitasDashboard[] = ["icon" => "clipboard-check-fill", "judul" => "Seluruh evidence telah lengkap", "deskripsi" => "Silakan menunggu proses review dari penilai."];
            $aksiText = "Lihat Penilaian Saya";
            $aksiLink = "#"; // Nanti diganti ke route penilaian
            $aksiIcon = "clipboard-check";
        }

        // Cek Kompetensi
        if ($statusPenilaian == "Belum Dimulai") {
            $aktivitasDashboard[] = ["icon" => "award", "judul" => "Hasil kompetensi belum tersedia", "deskripsi" => "Nilai kompetensi akan muncul setelah seluruh proses penilaian selesai."];
        } else {
            $aktivitasDashboard[] = ["icon" => "award-fill", "judul" => "Progress kompetensi sedang diproses", "deskripsi" => "Pantau perkembangan penilaian Anda pada menu Hasil Kompetensi."];
        }

        // 5. Logika Welcome Message & Tanggal Indonesia
        setlocale(LC_TIME, 'id_ID.utf8', 'Indonesian');
        $tanggalSekarang = \Carbon\Carbon::now()->translatedFormat('l, d F Y');

        if ($progress == 0) {
            $welcomeMessage = "Anda belum memulai proses penilaian kompetensi. Mulailah dengan mengupload evidence pertama Anda.";
        } elseif ($progress < 100) {
            $welcomeMessage = "Progress penilaian Anda telah mencapai {$progress}%. Teruskan hingga seluruh evidence berhasil diupload.";
        } else {
            $welcomeMessage = "Selamat! Seluruh evidence telah berhasil diupload. Silakan menunggu proses review dari penilai.";
        }

        return view('pegawai.dashboard', compact(
            'pegawai', 'totalUploadSaya', 'totalEvidenceWajib', 'sisaEvidence', 
            'progress', 'statusPenilaian', 'aktivitasDashboard', 
            'aksiText', 'aksiLink', 'aksiIcon', 'tanggalSekarang', 'welcomeMessage'
        ));
    }
}