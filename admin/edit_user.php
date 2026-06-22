<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Edit User";
$page_subtitle = "Perbarui informasi akun pengguna";

$id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id) {
    die("Error: ID User tidak ditemukan di URL!");
}

$query_select = "SELECT * FROM users WHERE id = $1";
$result_select = pg_query_params($koneksi, $query_select, array($id));

if ($result_select) {
    $user = pg_fetch_assoc($result_select);
} else {
    $user = false;
}

if (!$user) {
    die("Data user tidak ditemukan di database!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $username     = $_POST['username'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $email        = $_POST['email'];
    $role         = $_POST['role'];
    $status       = $_POST['status'];
    $password     = $_POST['password'];
    $konfirmasi   = $_POST['konfirmasi_password'];

    $cek_username = pg_query_params($koneksi, "SELECT id FROM users WHERE username = $1 AND id != $2", array($username, $id));
    
    if (pg_num_rows($cek_username) > 0) {
        echo "<script>alert('Gagal: Username sudah dipakai oleh akun lain!');</script>";
    } else {
        $update_berhasil = false;

        if (!empty($password)) {
            if ($password !== $konfirmasi) {
                echo "<script>alert('Gagal: Password baru dan Konfirmasi Password tidak cocok!');</script>";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $query_update = "UPDATE users SET 
                                 username = $1, nama_lengkap = $2, email = $3, 
                                 role = $4, status = $5, password = $6 
                                 WHERE id = $7";
                $params = array($username, $nama_lengkap, $email, $role, $status, $hashed_password, $id);
                $result_update = pg_query_params($koneksi, $query_update, $params);
                $update_berhasil = $result_update ? true : false;
            }
        } else {
            $query_update = "UPDATE users SET 
                             username = $1, nama_lengkap = $2, email = $3, 
                             role = $4, status = $5 
                             WHERE id = $6";
            $params = array($username, $nama_lengkap, $email, $role, $status, $id);
            $result_update = pg_query_params($koneksi, $query_update, $params);
            $update_berhasil = $result_update ? true : false;
        }

        if ($update_berhasil) {
            echo "<script>alert('Data user berhasil diperbarui!'); window.location.href='user.php';</script>";
            exit;
        } elseif (isset($result_update) && !$result_update) {
            $error_msg = pg_last_error($koneksi);
            echo "<script>alert('Gagal memperbarui data: " . $error_msg . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/edit_user.css">
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
                    <h2>Edit User</h2>
                    <p>Perbarui informasi akun yang digunakan untuk mengakses sistem.</p>
                </div>
                <a href="user.php" class="btn-secondary">Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama_lengkap'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" required>
                            <option value="Admin" <?= ($user['role'] ?? '') == 'Admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="Assessor" <?= ($user['role'] ?? '') == 'Assessor' ? 'selected' : '' ?>>Assessor</option>
                            <option value="Pimpinan" <?= ($user['role'] ?? '') == 'Pimpinan' ? 'selected' : '' ?>>Pimpinan</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="password" placeholder="Kosongkan jika tidak diubah">
                    </div>

                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="konfirmasi_password" placeholder="Masukkan ulang password jika diubah">
                    </div>

                    <div class="form-group full-width">
                        <label>Status</label>
                        <select name="status" required>
                            <option value="Aktif" <?= ($user['status'] ?? '') == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="Nonaktif" <?= ($user['status'] ?? '') == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" name="update_user" class="btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

</body>
</html>