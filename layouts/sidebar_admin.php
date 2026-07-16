<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'admin';
?>

<link rel="stylesheet" href="../assets/css/css_admin/layout.css">

<div class="sidebar">

    <div class="sidebar-header">
        <!-- Tambahan Ikon Kompas -->
        <div class="sidebar-logo">
            <i class="bi bi-compass"></i>
        </div>
        
        <!-- Teks Judul -->
        <div class="sidebar-title-wrapper">
            <div class="sidebar-title">MUSEUM GEOLOGI</div>
            <div class="sidebar-subtitle">GEOTRAX: Sistem Informasi Penilaian SDM</div>
        </div>
    </div>

    <div class="sidebar-menu">

        <ul>
            <li>
                <a href="dashboard.php" class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
        </ul>

        <ul>
            <li>
                <a href="pegawai.php" class="<?= ($current_page == 'pegawai.php' || $current_page == 'tambah_pegawai.php' || $current_page == 'edit_pegawai.php') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    Data Pegawai
                </a>
            </li>
            <li>
                <a href="user.php" class="<?= ($current_page == 'user.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    User
                </a>
            </li>
        </ul>

        <ul>
            <li>
                <a href="instrumen.php" class="<?= ($current_page == 'instrumen.php' || $current_page == 'detail_instrumen.php' || $current_page == 'edit_instrumen.php') ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3"></i>
                    Alur KUK
                </a>
            </li>
            <!-- <li>
                <a href="instrumen.php" class="<?= ($current_page == 'instrumen.php') ? 'active' : '' ?>">
                    <i class="bi bi-journal-text"></i>
                    Instrumen
                </a>
            </li> -->
            <li>
                <a href="penilaian.php" class="<?= ($current_page == 'penilaian.php') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i>
                    Penilaian
                </a>
            </li>
            <li>
                <a href="hasil_kompetensi.php" class="<?= ($current_page == 'hasil_kompetensi.php') ? 'active' : '' ?>">
                    <i class="bi bi-award"></i>
                    Hasil Kompetensi
                </a>
            </li>
            <li>
                <a href="profil.php" class="<?= ($current_page == 'profil.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-circle"></i>
                    Profil
                </a>
            </li>
        </ul>

    </div>

    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="logout-link" onclick="return confirm('Apakah Anda yakin ingin keluar?');">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>

</div>