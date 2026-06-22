<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Detail User";
$page_subtitle = "Informasi lengkap akun pengguna sistem";

$user_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$user_id) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: ID User tidak ditemukan di URL!</div>");
}

$query = "SELECT * FROM users WHERE id = $1 LIMIT 1";
$result = pg_query_params($koneksi, $query, array($user_id));
$user = pg_fetch_assoc($result);

if (!$user) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: Data user tidak ditemukan di database!</div>");
}

$username = htmlspecialchars($user['username'] ?? '-');
$nama_lengkap = htmlspecialchars($user['nama_lengkap'] ?? '-');
$email = htmlspecialchars($user['email'] ?? '-');
$role = htmlspecialchars(ucwords($user['role'] ?? '-'));
$status = htmlspecialchars($user['status'] ?? 'Aktif');

$created_at = isset($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : '-';

$words = explode(" ", $nama_lengkap);
$inisial = "";
foreach ($words as $w) {
    if (!empty($w)) {
        $inisial .= strtoupper($w[0]);
    }
}
$inisial = substr($inisial, 0, 2);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail User</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/detail_user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Detail User</h2>
                    <p>Informasi akun yang digunakan untuk mengakses sistem.</p>
                </div>
                <a href="user.php" class="btn-secondary">Kembali</a>
            </div>

            <div class="profile-card">
                <div class="user-avatar">
                    <?= $inisial ?>
                </div>
                <h3><?= $nama_lengkap ?></h3>
                <p><?= $role ?></p>
                <span class="status">
                    <?= $status ?>
                </span>
            </div>

            <div class="info-card">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Username</label>
                        <span><?= $username ?></span>
                    </div>

                    <div class="info-item">
                        <label>Nama Lengkap</label>
                        <span><?= $nama_lengkap ?></span>
                    </div>

                    <div class="info-item">
                        <label>Email</label>
                        <span><?= $email ?></span>
                    </div>

                    <div class="info-item">
                        <label>Role</label>
                        <span><?= $role ?></span>
                    </div>

                    <div class="info-item">
                        <label>Status</label>
                        <span><?= $status ?></span>
                    </div>

                    <div class="info-item">
                        <label>Tanggal Dibuat</label>
                        <span><?= $created_at ?></span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>