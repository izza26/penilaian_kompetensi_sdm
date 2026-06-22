<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Tambah User";
$page_subtitle = "Tambahkan akun pengguna baru ke sistem";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_user'])) {
    $username     = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email        = $_POST['email'];
    $role         = $_POST['role'];
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi_password'];
    $status       = $_POST['status'];

    if ($password !== $konfirmasi) {
        echo "<script>alert('Gagal: Password dan Konfirmasi Password tidak cocok!');</script>";
    } else {
        $cek_user = pg_query_params($koneksi, "SELECT username FROM users WHERE username = $1", array($username));
        
        if (pg_num_rows($cek_user) > 0) {
            echo "<script>alert('Gagal: Username sudah terdaftar, silakan gunakan username lain!');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $query_insert = "INSERT INTO users (username, nama_lengkap, email, role, password, status) 
                             VALUES ($1, $2, $3, $4, $5, $6)";
            
            $params = array($username, $nama_lengkap, $email, $role, $hashed_password, $status);

            $result = pg_query_params($koneksi, $query_insert, $params);

            if ($result) {
                echo "<script>alert('User baru berhasil ditambahkan!'); window.location.href='user.php';</script>";
                exit;
            } else {
                $error_msg = pg_last_error($koneksi);
                echo "<script>alert('Gagal menambahkan user: " . $error_msg . "');</script>";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah User</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/tambah_user.css">
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
                    <h2>Tambah User</h2>
                    <p>Lengkapi informasi akun yang akan digunakan untuk mengakses sistem.</p>
                </div>
                <a href="user.php" class="btn-secondary">Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Masukkan username" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Masukkan email" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="Admin">Admin</option>
                            <option value="Assessor">Assessor</option>
                            <option value="Pimpinan">Pimpinan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Masukkan password" required>
                    </div>

                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="konfirmasi_password" placeholder="Masukkan ulang password" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" name="simpan_user" class="btn-primary">
                        Simpan User
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>