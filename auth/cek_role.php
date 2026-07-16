<?php

// Mulai session jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan user sudah login
require_once 'cek_login.php';

/**
 * Fungsi untuk mengecek hak akses berdasarkan role
 * Contoh:
 * cekRole('admin');
 * cekRole('pegawai');
 * cekRole(['admin','pimpinan']);
 */

function cekRole($roles)
{
    // Jika hanya satu role, ubah menjadi array
    if (!is_array($roles)) {
        $roles = [$roles];
    }

    // Jika role tidak ada atau tidak sesuai
    if (
        !isset($_SESSION['role']) ||
        !in_array($_SESSION['role'], $roles)
    ) {

        echo "
        <script>
            alert('Anda tidak memiliki hak akses ke halaman ini!');
            window.location='../auth/login.php';
        </script>";

        exit();
    }
}