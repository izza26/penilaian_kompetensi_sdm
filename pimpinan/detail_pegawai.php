<?php
session_start();
require_once '../auth/cek_login.php';
require_once '../config/koneksi.php';

$page_title = "Detail Pegawai";
$page_subtitle = "Informasi lengkap pegawai Museum Geologi";

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die("Error: ID Pegawai tidak ditemukan di URL!");
}

$query_select = "SELECT * FROM pegawai WHERE pegawai_id = $1";
$result_select = pg_query_params($koneksi, $query_select, array($id));

if ($result_select) {
    $pegawai = pg_fetch_assoc($result_select);
} else {
    $pegawai = false;
}

if (!$pegawai) {
    die("Data pegawai tidak ditemukan di database!");
}

// 3. Logika untuk membuat Inisial Nama (Avatar)
$nama_lengkap = $pegawai['pegawai_nama'];
$kata_nama = explode(' ', trim($nama_lengkap));
$inisial = '';

if (count($kata_nama) >= 2) {
    $inisial = strtoupper(substr($kata_nama[0], 0, 1) . substr($kata_nama[1], 0, 1));
} elseif (count($kata_nama) == 1) {
    $inisial = strtoupper(substr($kata_nama[0], 0, 2)); // Jika cuma 1 kata, ambil 2 huruf pertama
} else {
    $inisial = '??';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pegawai</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/detail_pegawai.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; 
    $btn_kembali = "pegawai.php";
    ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Detail Pegawai</h2>
                    <p>Informasi lengkap pegawai yang terlibat dalam proses penilaian kompetensi.</p>
                </div>
                <a href="pegawai.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="detail-card">
                <div class="pegawai-summary">
                    <div class="avatar">
                        <?= htmlspecialchars($inisial) ?>
                    </div>

                    <div class="pegawai-info">
                        <h3><?= htmlspecialchars($pegawai['pegawai_nama']) ?></h3>
                        
                        <p><?= htmlspecialchars($pegawai['jabatan'] ?? '-') ?></p>

                        <span class="status">Aktif</span>
                    </div>
                </div>

                <div class="detail-divider"></div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <label>NIP/NIK</label>
                        <span><?= htmlspecialchars($pegawai['nip_nik']) ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Nama Pegawai</label>
                        <span><?= htmlspecialchars($pegawai['pegawai_nama']) ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Email</label>
                        <span><?= htmlspecialchars($pegawai['email'] ?? '-') ?></span>
                    </div>

                    <div class="detail-item">
                        <label>No. HP</label>
                        <span><?= htmlspecialchars($pegawai['no_hp'] ?? '-') ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Jabatan</label>
                        <span><?= htmlspecialchars($pegawai['jabatan'] ?? '-') ?></span>
                    </div>

                    <div class="detail-item">
                        <label>Unit Kerja</label>
                        <span><?= htmlspecialchars($pegawai['unit_kerja']) ?></span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>