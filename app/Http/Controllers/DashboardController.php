<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function pimpinan()
    {
        $pimpinan = Auth::user();

        // 1. Cek Periode Penilaian
        $periodeAktif = DB::table('periode_penilaian')->where('status_aktif', 'Y')->first();
        $is_open = false;
        $pesan_periode = "Belum ada periode penilaian yang aktif saat ini.";
        $badge_class = "danger";

        if ($periodeAktif) {
            $tgl_mulai = strtotime($periodeAktif->tanggal_mulai);
            $tgl_selesai = strtotime($periodeAktif->tanggal_selesai . ' 23:59:59');
            $sekarang = time();

            if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) {
                $is_open = true;
                $pesan_periode = "Periode Penilaian sedang berlangsung: <b>" . date('d M Y', $tgl_mulai) . "</b> s/d <b>" . date('d M Y', $tgl_selesai) . "</b>.";
                $badge_class = "success";
            } elseif ($sekarang < $tgl_mulai) {
                $pesan_periode = "Periode Penilaian baru akan dibuka pada tanggal <b>" . date('d M Y', $tgl_mulai) . "</b>.";
                $badge_class = "warning";
            } else {
                $pesan_periode = "Periode Penilaian telah DITUTUP sejak <b>" . date('d M Y', $tgl_selesai) . "</b>.";
                $badge_class = "danger";
            }
        }

        // 2. Statistik Pegawai & Dokumen
        $tot_pegawai = DB::table('pegawai')->where('role', 'pegawai')->count();
        $tot_dokumen = DB::table('bukti_pegawai')->count();
        $tot_dinilai = DB::table('penilaian_header')->count();
        $belum_dinilai = ($tot_dokumen > $tot_dinilai) ? ($tot_dokumen - $tot_dinilai) : 0;

        $kompeten = DB::table('penilaian_header')->where('nilai_akhir', '>=', 70)->count();
        $cukup    = DB::table('penilaian_header')->whereBetween('nilai_akhir', [55, 69.99])->count();
        $bina     = DB::table('penilaian_header')->where('nilai_akhir', '<', 55)->count();

        $pct_kompeten = ($tot_dinilai > 0) ? round(($kompeten / $tot_dinilai) * 100) : 0;
        $pct_cukup    = ($tot_dinilai > 0) ? round(($cukup / $tot_dinilai) * 100) : 0;
        $pct_bina     = ($tot_dinilai > 0) ? round(($bina / $tot_dinilai) * 100) : 0;

        // 3. Timeline Antrean Upload Terbaru
        $timeline = DB::table('bukti_pegawai as bp')
            ->join('pegawai as p', 'bp.pegawai_id', '=', 'p.pegawai_id')
            ->join('aktivitas_kompeten as ak', 'bp.aktivitas_id', '=', 'ak.aktivitas_id')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->select('p.pegawai_nama', 'uk.judul_unit', 'bp.tanggal_upload')
            ->orderBy('bp.tanggal_upload', 'desc')
            ->limit(5)
            ->get();

        // 4. Lempar Data ke View Blade
        return view('pimpinan.dashboard', compact(
            'pimpinan', 'periodeAktif', 'is_open', 'pesan_periode', 'badge_class',
            'tot_pegawai', 'tot_dokumen', 'tot_dinilai', 'belum_dinilai',
            'pct_kompeten', 'pct_cukup', 'pct_bina', 'timeline'
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