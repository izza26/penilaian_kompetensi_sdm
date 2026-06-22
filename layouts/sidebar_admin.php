<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<div class="sidebar">

    <div class="sidebar-header">

        <div class="sidebar-title">
            MUSEUM GEOLOGI
        </div>

        <div class="sidebar-subtitle">
            Penilaian Kompetensi SDM
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

        <div class="menu-divider"></div>

        <ul>

            <li>
                <a href="pegawai.php"
                    class="<?= ($current_page == 'pegawai.php') ? 'active' : '' ?>">
                    <i class="bi bi-people"></i>
                    Data Pegawai
                </a>
            </li>

            <li>
                <a href="user.php"
                    class="<?= ($current_page == 'user.php') ? 'active' : '' ?>">
                    <i class="bi bi-person-gear"></i>
                    User
                </a>
            </li>

        </ul>

        <div class="menu-divider"></div>

        <ul>

            <li>
                <a href="alur_kuk.php"
                    class="<?= ($current_page == 'alur_kuk.php') ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3"></i>
                    Alur KUK
                </a>
            </li>

            <li>
                <a href="instrumen.php"
                    class="<?= ($current_page == 'instrumen.php') ? 'active' : '' ?>">
                    <i class="bi bi-journal-text"></i>
                    Instrumen
                </a>
            </li>

            <li>
                <a href="penilaian.php"
                    class="<?= ($current_page == 'penilaian.php') ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i>
                    Penilaian
                </a>
            </li>

            <li>
                <a href="hasil_kompetensi.php"
                    class="<?= ($current_page == 'hasil_kompetensi.php') ? 'active' : '' ?>">
                    <i class="bi bi-award"></i>
                    Hasil Kompetensi
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