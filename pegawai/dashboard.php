<?php
session_start();

require_once '../auth/cek_role.php';
cekRole('pegawai');

require_once '../config/koneksi.php';

/* ===========================================================
   PAGE
=========================================================== */

$page_title = "Dashboard Pegawai";
$page_subtitle = "Pantau aktivitas dan perkembangan kompetensi Anda";

/* ===========================================================
   DATA PEGAWAI LOGIN
=========================================================== */

$idPegawai = $_SESSION['pegawai_id'];

$queryPegawai = pg_query_params(
    $koneksi,
    "
    SELECT *
    FROM pegawai
    WHERE pegawai_id = $1
    ",
    array($idPegawai)
);

$pegawai = pg_fetch_assoc($queryPegawai);


/* ===========================================================
   KPI DASHBOARD
=========================================================== */

/*
|--------------------------------------------------------------------------
| Total Aktivitas Kompetensi
|--------------------------------------------------------------------------
*/

$qAktivitas = pg_query($koneksi,"
SELECT COUNT(*) AS total
FROM aktivitas_kompeten
");

$totalAktivitas = pg_fetch_assoc($qAktivitas)['total'];


/*
|--------------------------------------------------------------------------
| Total Unit Kompetensi
|--------------------------------------------------------------------------
*/

$qUnit = pg_query($koneksi,"
SELECT COUNT(*) AS total
FROM unit_kompetensi
");

$totalUnit = pg_fetch_assoc($qUnit)['total'];


/*
|--------------------------------------------------------------------------
| Total Evidence Wajib
|--------------------------------------------------------------------------
*/

$qEvidence = pg_query($koneksi,"
SELECT COUNT(*) AS total
FROM evidence_wajib
");

$totalEvidenceWajib = pg_fetch_assoc($qEvidence)['total'];


/*
|--------------------------------------------------------------------------
| Total Instrumen
|--------------------------------------------------------------------------
*/

$qInstrumen = pg_query($koneksi,"
SELECT COUNT(*) AS total
FROM rubrik_skor
");

$totalInstrumen = pg_fetch_assoc($qInstrumen)['total'];

/*
|--------------------------------------------------------------------------
| TOTAL UPLOAD EVIDENCE SAYA
|--------------------------------------------------------------------------
*/

$qUploadSaya = pg_query_params(
    $koneksi,
    "
    SELECT COUNT(*) AS total
    FROM bukti_pegawai
    WHERE pegawai_id = $1
    ",
    array($idPegawai)
);

$totalUploadSaya = pg_fetch_assoc($qUploadSaya)['total'];


/*
|--------------------------------------------------------------------------
| SISA EVIDENCE
|--------------------------------------------------------------------------
*/

$sisaEvidence = $totalEvidenceWajib - $totalUploadSaya;

if($sisaEvidence < 0){
    $sisaEvidence = 0;
}


/*
|--------------------------------------------------------------------------
| HITUNG PROGRESS
|--------------------------------------------------------------------------
*/

$progress = 0;

if($totalEvidenceWajib > 0){

    $progress = round(
        ($totalUploadSaya / $totalEvidenceWajib) * 100
    );

}


/*
|--------------------------------------------------------------------------
| STATUS PENILAIAN
|--------------------------------------------------------------------------
*/

if($progress == 0){

    $statusPenilaian = "Belum Dimulai";

}
elseif($progress < 100){

    $statusPenilaian = "Sedang Berjalan";

}
else{

    $statusPenilaian = "Menunggu Review";

}


/*
|--------------------------------------------------------------------------
| AKTIVITAS DASHBOARD
|--------------------------------------------------------------------------
*/

$aktivitasDashboard = [];


/* ======================================
   Upload Evidence
====================================== */

if($totalUploadSaya == 0){

    $aktivitasDashboard[] = [
        "icon"=>"cloud-upload",
        "judul"=>"Belum ada upload evidence",
        "deskripsi"=>"Upload evidence pertama Anda untuk memulai proses penilaian."
    ];

}else{

    $aktivitasDashboard[] = [
        "icon"=>"check-circle-fill",
        "judul"=>"Evidence berhasil diupload",
        "deskripsi"=>"Anda telah mengupload {$totalUploadSaya} evidence."
    ];

}


/* ======================================
   Progress Penilaian
====================================== */

if($progress < 100){

    $aktivitasDashboard[] = [
        "icon"=>"clipboard-check",
        "judul"=>"Penilaian belum selesai",
        "deskripsi"=>"Lengkapi seluruh evidence yang masih belum diupload."
    ];

}else{

    $aktivitasDashboard[] = [
        "icon"=>"clipboard-check-fill",
        "judul"=>"Seluruh evidence telah lengkap",
        "deskripsi"=>"Silakan menunggu proses review dari penilai."
    ];

}


/* ======================================
   Kompetensi
====================================== */

if($statusPenilaian == "Belum Dimulai"){

    $aktivitasDashboard[] = [
        "icon"=>"award",
        "judul"=>"Hasil kompetensi belum tersedia",
        "deskripsi"=>"Nilai kompetensi akan muncul setelah seluruh proses penilaian selesai."
    ];

}else{

    $aktivitasDashboard[] = [
        "icon"=>"award-fill",
        "judul"=>"Progress kompetensi sedang diproses",
        "deskripsi"=>"Pantau perkembangan penilaian Anda pada menu Hasil Kompetensi."
    ];

}

/* ===========================================================
   TOMBOL AKSI DASHBOARD
=========================================================== */

if($progress == 0){

    $aksiText = "Upload Evidence";
    $aksiLink = "upload_evidence.php";
    $aksiIcon = "cloud-arrow-up";

}
elseif($progress < 100){

    $aksiText = "Lanjut Upload Evidence";
    $aksiLink = "upload_evidence.php";
    $aksiIcon = "cloud-arrow-up";

}
else{

    $aksiText = "Lihat Penilaian Saya";
    $aksiLink = "penilaian_saya.php";
    $aksiIcon = "clipboard-check";

}

/* ===========================================================
   WELCOME MESSAGE
=========================================================== */

date_default_timezone_set("Asia/Jakarta");

$hari = [
    "Sunday"=>"Minggu",
    "Monday"=>"Senin",
    "Tuesday"=>"Selasa",
    "Wednesday"=>"Rabu",
    "Thursday"=>"Kamis",
    "Friday"=>"Jumat",
    "Saturday"=>"Sabtu"
];

$namaHari = $hari[date("l")];

$tanggalSekarang =
$namaHari . ", " . date("d F Y");


if($progress == 0){

    $welcomeMessage =
    "Anda belum memulai proses penilaian kompetensi. Mulailah dengan mengupload evidence pertama Anda.";

}
elseif($progress < 100){

    $welcomeMessage =
    "Progress penilaian Anda telah mencapai {$progress}%. Teruskan hingga seluruh evidence berhasil diupload.";

}
else{

    $welcomeMessage =
    "Selamat! Seluruh evidence telah berhasil diupload. Silakan menunggu proses review dari penilai.";

}
?>

<!DOCTYPE html>
    <html lang="id">

    <head>

        <meta charset="UTF-8">

        <meta name="viewport"
        content="width=device-width, initial-scale=1.0">

        <title>Dashboard Pegawai</title>

        <link rel="stylesheet"
        href="../assets/css/css_pegawai/layout.css">

        <link rel="stylesheet"
        href="../assets/css/css_pegawai/dashboard.css">

        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

        <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        </head>

    <body>

    <div class="app">

    <?php include "../layouts/sidebar_pegawai.php"; ?>

    <div class="main-content">

<?php include "../layouts/header.php"; ?>

<!-- ==========================
     WELCOME CARD
========================== -->

<div class="welcome-card">

    <div class="welcome-text">

        <h2>

            Halo, <?= htmlspecialchars($pegawai['pegawai_nama']); ?> 

        </h2>

        <div class="welcome-date">

            <?= $tanggalSekarang; ?>

        </div>

        <p>

            <?= $welcomeMessage; ?>

        </p>

    </div>

    <div class="welcome-icon">

        <i class="bi bi-person-workspace"></i>

    </div>

</div>


<!-- ==========================
     KPI
========================== -->

<div class="pegawai-kpi-grid">

    <!-- Evidence Saya -->

    <div class="pegawai-kpi-card blue">

        <div class="pegawai-kpi-top">

            <div class="pegawai-kpi-icon">
                <i class="bi bi-cloud-upload"></i>
            </div>

            <div class="pegawai-kpi-label">
                Evidence Saya
            </div>

        </div>

        <div class="pegawai-kpi-content">

            <div class="pegawai-kpi-value blue-text">
                <?= $totalUploadSaya ?> / <?= $totalEvidenceWajib ?>
            </div>

            <div class="pegawai-kpi-desc">
                Jumlah evidence yang sudah Anda upload.
            </div>

        </div>

    </div>

    <!-- Penilaian -->

    <div class="pegawai-kpi-card purple">

        <div class="pegawai-kpi-top">

            <div class="pegawai-kpi-icon">
                <i class="bi bi-clipboard-check"></i>
            </div>

            <div class="pegawai-kpi-label">
                Penilaian Saya
            </div>

        </div>

        <div class="pegawai-kpi-content">

            <div class="pegawai-kpi-value purple-text">
                <?= $statusPenilaian ?>
            </div>

            <div class="pegawai-kpi-desc">
                Status proses penilaian kompetensi.
            </div>

        </div>
    </div>

    <!-- Review -->

    <div class="pegawai-kpi-card orange">

        <div class="pegawai-kpi-top">

            <div class="pegawai-kpi-icon">
                <i class="bi bi-hourglass-split"></i>
            </div>

            <div class="pegawai-kpi-label">
                Menunggu Review
            </div>

        </div>

        <div class="pegawai-kpi-content">

            <div class="pegawai-kpi-value orange-text">
                <?= $sisaEvidence ?>
            </div>

            <div class="pegawai-kpi-desc">
                Evidence yang belum diupload.
            </div>

        </div>
    </div>

    <!-- Kompetensi -->

    <div class="pegawai-kpi-card green">

        <div class="pegawai-kpi-top">

            <div class="pegawai-kpi-icon">
                <i class="bi bi-award"></i>
            </div>

            <div class="pegawai-kpi-label">
                Status Kompetensi
            </div>

        </div>

        <div class="pegawai-kpi-content">

            <div class="pegawai-kpi-value green-text">
                <?= $statusPenilaian ?>
            </div>

            <div class="pegawai-kpi-desc">
                Perkembangan kompetensi Anda.
            </div>

        </div>
    </div>

</div>

<div class="dashboard-row">

    <!-- LEFT -->

    <div class="progress-card">

        <div class="card-header">

            <h3>Progress Kompetensi</h3>

            <span><?= $progress; ?>%</span>

        </div>

        <div class="progress">

            <div
                class="progress-bar"
                style="width:<?= $progress; ?>%">
            </div>

        </div>

        <div class="progress-detail">

            <div class="progress-item">

                <span>Status Penilaian</span>

                <strong><?= $statusPenilaian; ?></strong>

            </div>

            <div class="progress-item">

                <span>Progress Upload</span>

                <strong><?= $progress; ?>%</strong>

            </div>

            <div class="progress-item">

                <span>Evidence Saya</span>

                <strong><?= $totalUploadSaya; ?> / <?= $totalEvidenceWajib; ?></strong>

            </div>

            <div class="progress-item">

                <span>Evidence Tersisa</span>

                <strong><?= $sisaEvidence; ?></strong>

            </div>

        </div>

        <a href="<?= $aksiLink; ?>" class="progress-btn">

            <i class="bi bi-<?= $aksiIcon; ?>"></i>

            <?= $aksiText; ?>

        </a>
    </div>


    <!-- RIGHT -->

    <div class="profile-summary">

        <h3>Informasi Pegawai</h3>

        <table>

            <tr>

                <td>Nama</td>

                <td>:</td>

                <td><?= htmlspecialchars($pegawai['pegawai_nama']); ?></td>

            </tr>

            <tr>

                <td>NIP/NIK</td>

                <td>:</td>

                <td><?= htmlspecialchars($pegawai['nip_nik']); ?></td>

            </tr>

            <tr>

                <td>Unit Kerja</td>

                <td>:</td>

                <td><?= htmlspecialchars($pegawai['unit_kerja']); ?></td>

            </tr>

            <tr>

                <td>Jabatan</td>

                <td>:</td>

                <td><?= htmlspecialchars($pegawai['jabatan']); ?></td>

            </tr>

        </table>

    </div>

</div>

<div class="timeline-card">

    <h3>Aktivitas Terbaru Saya</h3>

    <div class="timeline">

        <?php foreach($aktivitasDashboard as $item): ?>

            <div class="timeline-item">

                <div class="timeline-icon">

                    <i class="bi bi-<?= $item['icon']; ?>"></i>

                </div>

                <div>

                    <div class="timeline-title">

                        <?= htmlspecialchars($item['judul']); ?>

                    </div>

                    <div class="timeline-desc">

                        <?= htmlspecialchars($item['deskripsi']); ?>

                    </div>

                </div>

            </div>

        <?php endforeach; ?>

    </div>

</div>