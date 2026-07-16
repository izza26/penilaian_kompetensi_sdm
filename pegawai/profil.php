<?php
session_start();

require_once '../auth/cek_role.php';
cekRole('pegawai');

require_once '../config/koneksi.php';

$page_title = "Profil";
$page_subtitle = "Kelola informasi akun Anda";

$idPegawai = $_SESSION['pegawai_id'];

/*
|--------------------------------------------------------------------------
| Data Pegawai
|--------------------------------------------------------------------------
*/

$queryPegawai = pg_query_params(

    $koneksi,

    "SELECT *

    FROM pegawai

    WHERE pegawai_id = $1

    ",

    array($idPegawai)

);

$pegawai = pg_fetch_assoc($queryPegawai);

/*
|--------------------------------------------------------------------------
| Dummy Statistik
|--------------------------------------------------------------------------
*/

$totalEvidence = 27;
$totalKompetensi = 34;
$totalKompeten = 29;
$loginTerakhir = "Hari ini, 08:13 WIB";

?>

<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Profil Pegawai</title>

<link
rel="stylesheet"
href="../assets/css/css_pegawai/layout.css">

<link
rel="stylesheet"
href="../assets/css/css_pegawai/profil.css">

<link
href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
rel="stylesheet">

<link
rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>

<body>

<div class="app">

<?php include "../layouts/sidebar_pegawai.php"; ?>

<div class="main-content">

<?php include "../layouts/header.php"; ?>

<div class="profil-container">

<!-- =======================================================
     HERO PROFILE
======================================================= -->

<div class="hero-profile">

    <!-- ===========================
         LEFT
    ============================ -->

    <div class="hero-left">

        <span class="hero-label">

            PROFIL PEGAWAI

        </span>

        <h1>

            <?= htmlspecialchars($pegawai['pegawai_nama']); ?>

        </h1>

        <div class="hero-position">

            <i class="bi bi-briefcase"></i>

            <?= htmlspecialchars($pegawai['jabatan']); ?>

        </div>

        <div class="hero-unit">

            <i class="bi bi-buildings"></i>

            <?= htmlspecialchars($pegawai['unit_kerja']); ?>

        </div>

        <div class="hero-contact">

            <div>

                <i class="bi bi-person-vcard"></i>

                <?= htmlspecialchars($pegawai['nip_nik']); ?>

            </div>

            <div>

                <i class="bi bi-envelope"></i>

                <?= htmlspecialchars($pegawai['email']); ?>

            </div>

            <div>

                <i class="bi bi-telephone"></i>

                <?= htmlspecialchars($pegawai['no_hp']); ?>

            </div>

        </div>

    </div>

    <!-- ===========================
         RIGHT
    ============================ -->

    <div class="hero-right">

        <div class="profile-summary">

            <div class="summary-top">

                <span>Status Akun</span>

                <div class="status-active">

                    <i class="bi bi-patch-check-fill"></i>

                    Aktif

                </div>

            </div>

            <div class="summary-divider"></div>

            <div class="summary-item">

                <small>Total Kompetensi</small>

                <strong><?= $totalKompetensi; ?></strong>

            </div>

            <div class="summary-item">

                <small>Evidence</small>

                <strong><?= $totalEvidence; ?></strong>

            </div>

            <div class="summary-item">

                <small>Kompeten</small>

                <strong><?= $totalKompeten; ?></strong>

            </div>

        </div>

    </div>

</div>

<!-- =======================================================
     INFORMASI PRIBADI
======================================================= -->

<div class="card-section">

    <div class="section-title">

        <i class="bi bi-person-vcard"></i>

        Informasi Pribadi

    </div>

    <div class="info-grid">

        <div class="info-card">

            <span>Nama Lengkap</span>

            <h4>

                <?= htmlspecialchars($pegawai['pegawai_nama']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>NIP / NIK</span>

            <h4>

                <?= htmlspecialchars($pegawai['nip_nik']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>Email</span>

            <h4>

                <?= htmlspecialchars($pegawai['email']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>Nomor HP</span>

            <h4>

                <?= htmlspecialchars($pegawai['no_hp']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>Jabatan</span>

            <h4>

                <?= htmlspecialchars($pegawai['jabatan']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>Unit Kerja</span>

            <h4>

                <?= htmlspecialchars($pegawai['unit_kerja']); ?>

            </h4>

        </div>

        <div class="info-card">

            <span>Role</span>

            <h4>

                <?= ucfirst($pegawai['role']); ?>

            </h4>

        </div>

    </div>

</div>

<!-- =======================================================
     PENGATURAN AKUN
======================================================= -->

<div class="card-section">

    <div class="section-title">

        <i class="bi bi-shield-lock"></i>

        Pengaturan Akun

    </div>

    <div class="setting-grid">

        <a href="#" class="setting-card">

            <div class="setting-icon">

                <i class="bi bi-pencil-square"></i>

            </div>

            <div>

                <h4>Edit Profil</h4>

                <p>Perbarui data diri pegawai.</p>

            </div>

        </a>

        <a href="#" class="setting-card">

            <div class="setting-icon">

                <i class="bi bi-key"></i>

            </div>

            <div>

                <h4>Ubah Password</h4>

                <p>Ganti password akun Anda.</p>

            </div>

        </a>

    </div>

</div>

</div>

</div>

</div>

</body>

</html>