<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="../assets/css/css_admin/layout.css">

<div class="sidebar">

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-compass"></i>
        </div>
        
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
                <a href="aktivitas_saya.php" class="<?= ($current_page == 'aktivitas_saya.php' || $current_page == 'aktivitas_detail.php') ? 'active' : '' ?>">
                    <i class="bi bi-list-check"></i>
                    Aktivitas Saya
                </a>
            </li>

            <li>
                <a href="penilaian.php" class="<?= ($current_page == 'penilaian.php' || $current_page == 'penilaian_list.php' || $current_page == 'penilaian_detail.php') ? 'active' : '' ?>">
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
        </ul>

        <ul>
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