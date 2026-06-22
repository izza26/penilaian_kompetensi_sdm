<?php
session_start();
require_once '../config/koneksi.php';

if (isset($_POST['simpan'])) {
    $nip     = $_POST['nip'];
    $nama    = $_POST['nama_pegawai'];
    $email   = $_POST['email'];      
    $no_hp   = $_POST['no_hp'];      
    $jabatan = $_POST['jabatan'];    
    $unit    = $_POST['unit_kerja'];
    $pass    = '123456'; 

    $q_id = pg_query($koneksi, "SELECT MAX(pegawai_id) as max_id FROM pegawai");
    $row_id = pg_fetch_assoc($q_id);
    $next_id = $row_id['max_id'] ? $row_id['max_id'] + 1 : 1;

    $query_insert = "INSERT INTO pegawai 
                     (pegawai_id, nip_nik, pegawai_nama, email, no_hp, jabatan, unit_kerja, password) 
                     VALUES 
                     ($next_id, '$nip', '$nama', '$email', '$no_hp', '$jabatan', '$unit', '$pass')";
    
    $eksekusi = pg_query($koneksi, $query_insert);

    if ($eksekusi) {
        echo "<script>
                alert('Mantap! Data Pegawai baru berhasil ditambahkan.');
                window.location.href = 'pegawai.php';
              </script>";
        exit();
    } else {
        $error = pg_last_error($koneksi);
        echo "<script>alert('Waduh gagal: $error');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Pegawai</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/tambah_pegawai.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">

    <?php
    include '../layouts/sidebar_admin.php';
    $page_title = "Tambah Pegawai";
    $page_subtitle = "Tambahkan data pegawai baru ke sistem";
    ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="page-card">

            <div class="page-header">
                <div class="page-title">
                    <h2>Tambah Pegawai</h2>
                    <p>Lengkapi informasi pegawai yang akan mengikuti proses penilaian kompetensi.</p>
                </div>
                <a href="pegawai.php" class="btn-secondary">Kembali</a>
            </div>

            <form action="" method="POST">

                <div class="form-grid">

                    <div class="form-group">
                        <label>NIP / NIK</label>
                        <input type="text" name="nip" placeholder="Masukkan NIP atau NIK" required>
                    </div>

                    <div class="form-group">
                        <label>Nama Pegawai</label>
                        <input type="text" name="nama_pegawai" placeholder="Masukkan nama pegawai" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Masukkan email">
                    </div>

                    <div class="form-group">
                        <label>No. HP</label>
                        <input type="text" name="no_hp" placeholder="Masukkan nomor HP">
                    </div>

                    <div class="form-group full-width">
                        <label>Jabatan (Berdasarkan SKKNI)</label>
                        <select name="jabatan" required>
                            <option value="">-- Pilih Jabatan Teknis Museum --</option>
                            <option value="Kepala Museum">Kepala Museum</option>
                            <option value="Register">Register</option>
                            <option value="Kurator">Kurator</option>
                            <option value="Konservator">Konservator</option>
                            <option value="Penata Pameran">Penata Pameran</option>
                            <option value="Edukator">Edukator</option>
                            <option value="Humas dan Pemasaran">Hubungan Masyarakat dan Pemasaran</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Unit Kerja / Instansi</label>
                        <input type="text" name="unit_kerja" placeholder="Contoh: Museum Geologi" required>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select>
                            <option>Aktif</option>
                            <option>Nonaktif</option>
                        </select>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" name="simpan" class="btn-primary">
                        Simpan Pegawai
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

</body>
</html>