<?php
// Pastikan session dimulai jika belum ada session yang aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ganti 'username' menjadi 'status_login' karena kita pakai variabel itu di proses_login.php
if (!isset($_SESSION['status_login']) || $_SESSION['status_login'] !== true) {
    // Jika tidak ada sesi, munculkan alert dan tendang ke halaman login
    echo "<script>
            alert('Akses Ditolak! Silakan login terlebih dahulu.');
            window.location.href = '../auth/login.php';
          </script>";
    exit; 
}
?>