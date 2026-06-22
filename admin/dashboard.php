<!DOCTYPE html>
<html>
<head>

    <title>Dashboard Admin</title>

    <link rel="stylesheet"
    href="../assets/css/css_admin/layout.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/css_admin/dashboard.css">

</head>

<body>

<div class="app">

    <?php 
        session_start();
        require_once '../auth/cek_login.php';
        require_once '../config/koneksi.php';

        // 1. Query Total Pegawai
        $q_pegawai = pg_query($koneksi, "SELECT COUNT(*) AS total FROM pegawai");
        $r_pegawai = pg_fetch_assoc($q_pegawai);
        $tot_pegawai = $r_pegawai['total'];

        // 2. Query Penilaian Masuk
        $q_masuk = pg_query($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header");
        $r_masuk = pg_fetch_assoc($q_masuk);
        $tot_masuk = $r_masuk['total'];

        // 3. Query Menunggu Review
        $q_tunggu = pg_query($koneksi, "SELECT COUNT(*) AS total FROM penugasan_penilai WHERE status_penugasan = 'Menunggu'");
        $r_tunggu = pg_fetch_assoc($q_tunggu);
        $tot_tunggu = $r_tunggu['total'];

        // 4. Query Sudah Dinilai
        $q_selesai = pg_query($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header WHERE status = 'Selesai'");
        $r_selesai = pg_fetch_assoc($q_selesai);
        $tot_selesai = $r_selesai['total'];

        // 5. Query Statistik Kompetensi (Menghitung persentase)
        $q_tot_elemen = pg_query($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360");
        $tot_elemen = pg_fetch_assoc($q_tot_elemen)['total'];

        $q_kompeten = pg_query($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Kompeten'");
        $kompeten = pg_fetch_assoc($q_kompeten)['total'];

        $q_cukup = pg_query($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Cukup Kompeten'");
        $cukup = pg_fetch_assoc($q_cukup)['total'];

        $q_bina = pg_query($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Perlu Pembinaan'");
        $bina = pg_fetch_assoc($q_bina)['total'];

        // Logika menghindari error pembagian dengan nol
        $pct_kompeten = ($tot_elemen > 0) ? round(($kompeten / $tot_elemen) * 100) : 0;
        $pct_cukup    = ($tot_elemen > 0) ? round(($cukup / $tot_elemen) * 100) : 0;
        $pct_bina     = ($tot_elemen > 0) ? round(($bina / $tot_elemen) * 100) : 0;

        // 6. Query Aktivitas Terbaru 
        $q_aktivitas = pg_query($koneksi, "SELECT * FROM pegawai ORDER BY pegawai_id DESC LIMIT 3");
        
        $page_title = "Dashboard Admin";
        $page_subtitle = "Kelola Penilaian Kompetensi SDM Museum Geologi";
        include '../layouts/sidebar_admin.php'; 
    ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="kpi-grid">

    <div class="kpi-card">

        <div class="kpi-top">

            <div class="kpi-icon">
                <i class="bi bi-people"></i>
            </div>

        </div>

        <div class="kpi-value"><?= $tot_pegawai; ?></div>

            <div class="kpi-label">
                Total Pegawai
            </div>

        </div>

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon">
                    <i class="bi bi-inbox"></i>
                </div>

            </div>

            <div class="kpi-value"><?= $tot_masuk; ?></div>

                <div class="kpi-label">
                    Penilaian Masuk
                </div>

        </div>

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon">
                    <i class="bi bi-hourglass-split"></i>
                </div>

            </div>

            <div class="kpi-value"><?= $tot_tunggu; ?></div>

            <div class="kpi-label">
                Menunggu Review
            </div>

        </div>

        <div class="kpi-card">

            <div class="kpi-top">

                <div class="kpi-icon">
                    <i class="bi bi-check-circle"></i>
                </div>

            </div>

            <div class="kpi-value"><?= $tot_selesai; ?></div>

                <div class="kpi-label">
                    Sudah Dinilai
                </div>

            </div>

        </div>

        <div class="flow-card">

            <div class="flow-header">

                <div>

                    <h2>
                        Alur Penilaian Kompetensi
                    </h2>

                    <p>
                        Kelola proses penilaian kompetensi pegawai berbasis evidence.
                    </p>

                </div>

            </div>

            <div class="flow-steps">

                <div class="flow-step active">

                    <div class="step-number">
                        1
                    </div>

                    <div class="step-title">
                        Pilih Aktivitas
                    </div>

                </div>

                <div class="flow-line"></div>

                <div class="flow-step">

                    <div class="step-number">
                        2
                    </div>

                    <div class="step-title">
                        Upload Evidence
                    </div>

                </div>

                <div class="flow-line"></div>

                <div class="flow-step">

                    <div class="step-number">
                        3
                    </div>

                    <div class="step-title">
                        Review Pimpinan
                    </div>

                </div>

                <div class="flow-line"></div>

                <div class="flow-step">

                    <div class="step-number">
                        4
                    </div>

                    <div class="step-title">
                        Hasil Kompetensi
                    </div>

                </div>

            </div>

        </div>

        <div class="stats-card">

            <div class="stats-header">

                <h2>Statistik Kompetensi</h2>

                <p>
                    Ringkasan hasil penilaian kompetensi pegawai.
                </p>

            </div>

            <div class="stats-item">
                <div class="stats-top">
                    <span>Kompeten</span>
                    <span><?= $pct_kompeten; ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar kompeten" style="width: <?= $pct_kompeten; ?>%;"></div>
                </div>
            </div>

            <div class="stats-item">
                <div class="stats-top">
                    <span>Cukup Kompeten</span>
                    <span><?= $pct_cukup; ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar cukup" style="width: <?= $pct_cukup; ?>%;"></div>
                </div>
            </div>

            <div class="stats-item">
                <div class="stats-top">
                    <span>Perlu Pembinaan</span>
                    <span><?= $pct_bina; ?>%</span>
                </div>
                <div class="progress">
                    <div class="progress-bar pembinaan" style="width: <?= $pct_bina; ?>%;"></div>
                </div>
            </div>

        </div>

        <div class="activity-card">

            <div class="activity-header">

                <h2>Aktivitas Terbaru</h2>

                <p>
                    Pantau aktivitas terbaru pada sistem penilaian kompetensi.
                </p>

            </div>

            <div class="activity-list">

                <div class="activity-item">

                    <div class="activity-icon success">
                        <i class="bi bi-upload"></i>
                    </div>

                    <div class="activity-content">

                        <div class="activity-title">
                            Budi Santoso mengupload evidence kompetensi.
                        </div>

                        <div class="activity-time">
                            5 menit yang lalu
                        </div>

                    </div>

                </div>

                <div class="activity-item">

                    <div class="activity-icon info">
                        <i class="bi bi-send-check"></i>
                    </div>

                    <div class="activity-content">

                        <div class="activity-title">
                            Siti Nurhaliza mengirim penilaian untuk direview.
                        </div>

                        <div class="activity-time">
                            20 menit yang lalu
                        </div>

                    </div>

                </div>

                <div class="activity-list">
                    <?php 
                    if(pg_num_rows($q_aktivitas) > 0) {
                        while($row = pg_fetch_assoc($q_aktivitas)): 
                    ?>
                        <div class="activity-item">
                            <div class="activity-icon primary">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    Data pegawai baru ditambahkan: <b><?= $row['pegawai_nama']; ?></b>
                                </div>
                                <div class="activity-time">
                                    NIK: <?= $row['nip_nik']; ?> | <?= $row['unit_kerja']; ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile; 
                    } else {
                        echo "<p style='color:gray; font-size:12px;'>Belum ada aktivitas terbaru.</p>";
                    }
                    ?>
                </div>

                <div class="activity-item">

                    <div class="activity-icon primary">
                        <i class="bi bi-award"></i>
                    </div>

                    <div class="activity-content">

                        <div class="activity-title">
                            Hasil kompetensi pegawai telah diterbitkan.
                        </div>

                        <div class="activity-time">
                            Hari ini
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

</body>
</html>