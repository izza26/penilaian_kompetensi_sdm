<?php
session_start();
require_once '../config/koneksi.php';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $nip_nik      = $_POST['nip_nik'];
    $pegawai_nama = $_POST['pegawai_nama'];
    $email        = $_POST['email'];
    $no_hp        = $_POST['no_hp'];
    $jabatan      = $_POST['jabatan'];
    $unit_kerja   = $_POST['unit_kerja'];
    
    // Siapkan query update dengan parameter binding ($1, $2, dst)
    $sql_update = "UPDATE pegawai SET
            nip_nik = $1,
            pegawai_nama = $2,
            email = $3,
            no_hp = $4,
            jabatan = $5,
            unit_kerja = $6
            WHERE pegawai_id = $7";

    $params = array($nip_nik, $pegawai_nama, $email, $no_hp, $jabatan, $unit_kerja, $id);

    $result_update = pg_query_params($koneksi, $sql_update, $params);

    if ($result_update) {
        echo "<script>alert('Data pegawai berhasil diperbarui!'); window.location.href='pegawai.php';</script>";
        exit;
    } else {
        $error_msg = pg_last_error($koneksi);
        echo "<script>alert('Gagal memperbarui data: " . $error_msg . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Pegawai</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/edit_pegawai.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<div class="app">

    <?php
    include '../layouts/sidebar_admin.php';
    $page_title = "Edit Pegawai";
    $page_subtitle = "Perbarui informasi data pegawai";
    ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Edit Pegawai</h2>
                    <p>Perbarui informasi pegawai yang terlibat dalam proses penilaian kompetensi.</p>
                </div>
                <a href="pegawai.php" class="btn-secondary">Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">
                    
                    <div class="form-group">
                        <label>NIP/NIK</label>
                        <input type="text" name="nip_nik" value="<?= htmlspecialchars($pegawai['nip_nik'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Pegawai</label>
                        <input type="text" name="pegawai_nama" value="<?= htmlspecialchars($pegawai['pegawai_nama'] ?? '') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($pegawai['email'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" value="<?= htmlspecialchars($pegawai['no_hp'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" value="<?= htmlspecialchars($pegawai['jabatan'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Unit Kerja</label>
                        <input type="text" name="unit_kerja" value="<?= htmlspecialchars($pegawai['unit_kerja'] ?? '') ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Status</label>
                        <select name="status">
                            <option value="Aktif" selected>Aktif</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" name="update" class="btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>