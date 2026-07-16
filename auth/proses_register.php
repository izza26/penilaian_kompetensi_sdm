<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Tangkap data dari form register
    $nip_nik     = pg_escape_string($koneksi, $_POST['nip_nik']);
    $nama        = pg_escape_string($koneksi, $_POST['pegawai_nama']);
    $email       = pg_escape_string($koneksi, $_POST['email']);
    $no_hp       = pg_escape_string($koneksi, $_POST['no_hp']);
    $role        = pg_escape_string($koneksi, strtolower($_POST['role'])); // pegawai / pimpinan
    $jabatan     = pg_escape_string($koneksi, $_POST['jabatan']);
    $unit_kerja  = pg_escape_string($koneksi, $_POST['unit_kerja']);
    
    // Perhatian: Karena proses_login.php membandingkan string langsung, kita simpan tanpa hash (Plain Text). 
    // Di dunia nyata, ini tidak disarankan, namun ini untuk menyesuaikan sistem eksisting Anda.
    $password    = pg_escape_string($koneksi, $_POST['password']); 

    // Cek apakah NIP/NIK sudah pernah terdaftar
    $cek_sql = "SELECT pegawai_id FROM pegawai WHERE nip_nik = '$nip_nik'";
    $cek_res = pg_query($koneksi, $cek_sql);
    
    if (pg_num_rows($cek_res) > 0) {
        echo "<script>
                alert('Pendaftaran Gagal! NIP / NIK tersebut sudah terdaftar.');
                window.history.back();
              </script>";
        exit;
    }

    // Generate ID Baru (Mencari ID Tertinggi + 1)
    $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(pegawai_id), 0) + 1 AS new_id FROM pegawai");
    $new_id = pg_fetch_assoc($q_id)['new_id'];

    // Query Insert Data
    $insert_sql = "INSERT INTO pegawai 
        (pegawai_id, nip_nik, pegawai_nama, email, no_hp, jabatan, unit_kerja, password, role, status_aktif) 
        VALUES 
        ($new_id, '$nip_nik', '$nama', '$email', '$no_hp', '$jabatan', '$unit_kerja', '$password', '$role', 'Aktif')";

    $eksekusi = pg_query($koneksi, $insert_sql);

    if ($eksekusi) {
        header("Location: login.php?pesan=register_sukses");
        exit;
    } else {
        echo "<script>
                alert('Terjadi kesalahan pada server. Gagal mendaftarkan akun.');
                window.history.back();
              </script>";
        exit;
    }
}
?>