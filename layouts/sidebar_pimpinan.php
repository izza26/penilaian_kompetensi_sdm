<?php
$current_page = basename($_SERVER['PHP_SELF']);
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
                <a href="dashboard.php"
                class="<?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
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
                <a href="master_unit.php"
                class="<?= ($current_page == 'master_unit.php' || $current_page == 'tambah_unit.php') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i>
                    Master Unit
                </a>
            </li>
        </ul>


        <ul>
            <li>
                <a href="manajemen_periode.php"
                class="<?= ($current_page == 'manajemen_periode.php' || $current_page == 'manajemen_aktivitas.php') ? 'active' : '' ?>">
                    <i class="bi bi-calendar2-check"></i>
                    Manajemen Penilaian
                </a>
            </li>

            <li>
                <a href="manajemen_evidence.php" class="<?= (basename($_SERVER['PHP_SELF']) == 'manajemen_evidence.php' || $current_page == 'atur_evidence.php') ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-arrow-up"></i> Kelola Template
                </a>
            </li>

            <li>
                <a href="skoring.php"
                class="<?= ($current_page == 'skoring.php' || $current_page == 'beri_nilai.php') ? 'active' : '' ?>">
                    <i class="bi bi-ui-checks"></i>
                    Skoring
                </a>
            </li>

            <li>
                <a href="hasil_kompetensi.php"
                class="<?= ($current_page == 'hasil_kompetensi.php' || $current_page == 'detail_hasil_kompetensi.php') ? 'active' : '' ?>">
                    <i class="bi bi-award"></i>
                    Hasil Kompetensi
                </a>
            </li>

            <li>
                <a href="profil.php"
                class="<?= ($current_page == 'profil.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-circle"></i>
                    Profil
                </a>
            </li>
        </ul>

    </div>

    <div class="sidebar-footer">

        <a href="../auth/logout.php"
        class="logout-link"
        onclick="return confirm('Apakah Anda yakin ingin keluar?');">

            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>

        </a>

    </div>

</div>