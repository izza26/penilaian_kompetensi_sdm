<?php
// Cukup panggil session_start() SATU KALI SAJA di baris paling atas
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once '../config/koneksi.php';

$username = $_POST['username'];
$password = $_POST['password'];

$query_sql = "SELECT * FROM pegawai WHERE nip_nik = '$username' AND password = '$password'";
$result = pg_query($koneksi, $query_sql);

if (pg_num_rows($result) > 0) {
    $data = pg_fetch_assoc($result);

    $_SESSION['status_login'] = true;
    $_SESSION['pegawai_id']   = $data['pegawai_id'];
    $_SESSION['nip_nik']      = $data['nip_nik'];
    $_SESSION['nama_pegawai'] = $data['pegawai_nama'];
    $_SESSION['unit_kerja']   = $data['unit_kerja'];
    
    // --- INI OBATNYA: Bersihkan spasi dan jadikan huruf kecil semua ---
    $_SESSION['role'] = trim(strtolower($data['role'])); 

    // Redirect sesuai role
    if ($_SESSION['role'] == 'admin') {
        header("Location: ../admin/dashboard.php");
    } elseif ($_SESSION['role'] == 'pegawai') {
        header("Location: ../pegawai/dashboard.php");
    } elseif ($_SESSION['role'] == 'pimpinan') {
        header("Location: ../pimpinan/dashboard.php");
    } else {
        header("Location: login.php?pesan=role_tidak_dikenal");
    }

    exit();
} else {
    header("Location: login.php?pesan=gagal");
    exit();
}
?>