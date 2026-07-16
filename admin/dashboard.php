<?php 
session_start();
require_once '../auth/cek_role.php';
cekRole('admin');
require_once '../config/koneksi.php';

/* ===========================================================
   FUNGSI HELPER & QUERY STATISTIK (LOGIKA TETAP SAMA 100%)
=========================================================== */
function getCountSafe($koneksi, $query) {
    $q = @pg_query($koneksi, $query); 
    if ($q) {
        $r = pg_fetch_assoc($q);
        return $r ? (int)$r['total'] : 0;
    }
    return 0;
}

// 1. KPI Admin
$tot_pegawai = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM pegawai");
$tot_masuk   = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header");
$tot_tunggu  = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penugasan_penilai WHERE status_penugasan = 'Menunggu'");
$tot_selesai = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header WHERE status = 'Selesai'");

// 2. Statistik Kompetensi (Persentase)
$tot_elemen = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360");
$kompeten   = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Kompeten'");
$cukup      = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Cukup Kompeten'");
$bina       = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM rekap_elemen_360 WHERE status_kompeten = 'Perlu Pembinaan'");

$pct_kompeten = ($tot_elemen > 0) ? round(($kompeten / $tot_elemen) * 100) : 0;
$pct_cukup    = ($tot_elemen > 0) ? round(($cukup / $tot_elemen) * 100) : 0;
$pct_bina     = ($tot_elemen > 0) ? round(($bina / $tot_elemen) * 100) : 0;

// 3. Query Aktivitas Terbaru (Pegawai Baru)
$q_aktivitas = @pg_query($koneksi, "SELECT * FROM pegawai ORDER BY pegawai_id DESC LIMIT 4");

/* ===========================================================
   WELCOME MESSAGE
=========================================================== */
date_default_timezone_set("Asia/Jakarta");
$hari = ["Sunday"=>"Minggu", "Monday"=>"Senin", "Tuesday"=>"Selasa", "Wednesday"=>"Rabu", "Thursday"=>"Kamis", "Friday"=>"Jumat", "Saturday"=>"Sabtu"];
$namaHari = $hari[date("l")];
$tanggalSekarang = $namaHari . ", " . date("d F Y");

$nama_admin = $_SESSION['nama_lengkap'] ?? 'Administrator';

$page_title = "Dashboard Admin";
$page_subtitle = "Ringkasan sistem penilaian kompetensi Museum Geologi";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Museum Geologi</title>

    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app">

    <?php include "../layouts/sidebar_admin.php"; ?>

    <div class="main-content">

        <?php include "../layouts/header.php"; ?>

        <!-- ==========================
             WELCOME CARD (GOLD ELEGANT)
        ========================== -->
        <div class="welcome-card">
            <div class="welcome-text">
                <h2>Halo, <?= htmlspecialchars($nama_admin); ?></h2>
                <div class="welcome-date"><?= $tanggalSekarang; ?></div>
                <p>Selamat datang di panel pengelola Sistem Penilaian Kompetensi SDM. Pantau seluruh aktivitas pegawai, instrumen, dan hasil penilaian dari sini.</p>
            </div>
            <div class="welcome-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
        </div>

        <!-- ==========================
             KPI GRID (STRUKTUR SAMA DENGAN PIMPINAN)
        ========================== -->
        <div class="pegawai-kpi-grid">
            <!-- Total Pegawai -->
            <div class="pegawai-kpi-card blue">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="pegawai-kpi-label">Total Pegawai</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value blue-text"><?= $tot_pegawai ?></div>
                    <div class="pegawai-kpi-desc">Akun pegawai terdaftar.</div>
                </div>
            </div>

            <!-- Penilaian Masuk -->
            <div class="pegawai-kpi-card orange">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-inbox-fill"></i></div>
                    <div class="pegawai-kpi-label">Penilaian Masuk</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value orange-text"><?= $tot_masuk ?></div>
                    <div class="pegawai-kpi-desc">Total dokumen pada sistem.</div>
                </div>
            </div>

            <!-- Menunggu Review -->
            <div class="pegawai-kpi-card purple">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="pegawai-kpi-label">Menunggu Review</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value purple-text"><?= $tot_tunggu ?></div>
                    <div class="pegawai-kpi-desc">Dokumen belum dinilai.</div>
                </div>
            </div>

            <!-- Sudah Dinilai -->
            <div class="pegawai-kpi-card green">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-shield-check"></i></div>
                    <div class="pegawai-kpi-label">Sudah Dinilai</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value green-text"><?= $tot_selesai ?></div>
                    <div class="pegawai-kpi-desc">Dokumen selesai divalidasi.</div>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <!-- LEFT: STATISTIK KOMPETENSI -->
            <div class="progress-card">
                <div class="card-header">
                    <h3>Statistik Kompetensi Museum</h3>
                    <span>Total Elemen: <?= $tot_elemen ?></span>
                </div>
                
                <div class="progress-detail" style="margin-top: 10px;">
                    <div class="progress-item">
                        <span>Sangat Kompeten / Kompeten (≥ 70)</span>
                        <strong><?= $pct_kompeten; ?>%</strong>
                    </div>
                </div>
                <div class="progress" style="height: 8px; margin-bottom: 15px;">
                    <div class="progress-bar bar-kompeten" style="width:<?= $pct_kompeten; ?>%"></div>
                </div>

                <div class="progress-detail">
                    <div class="progress-item">
                        <span>Cukup Kompeten (55 - 69)</span>
                        <strong><?= $pct_cukup; ?>%</strong>
                    </div>
                </div>
                <div class="progress" style="height: 8px; margin-bottom: 15px;">
                    <div class="progress-bar bar-cukup" style="width:<?= $pct_cukup; ?>%"></div>
                </div>

                <div class="progress-detail">
                    <div class="progress-item">
                        <span>Perlu Pembinaan (< 55)</span>
                        <strong><?= $pct_bina; ?>%</strong>
                    </div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bar-bina" style="width:<?= $pct_bina; ?>%"></div>
                </div>
            </div>

            <!-- RIGHT: AKTIVITAS PEGAWAI -->
            <div class="timeline-card">
                <h3>Aktivitas Pegawai Terbaru</h3>
                <div class="timeline">
                    <?php 
                    if ($q_aktivitas && pg_num_rows($q_aktivitas) > 0) {
                        while($row = pg_fetch_assoc($q_aktivitas)): 
                    ?>
                        <div class="timeline-item">
                            <div class="timeline-icon">
                                <i class="bi bi-person-badge"></i>
                            </div>
                            <div>
                                <div class="timeline-title">
                                    Data pegawai baru: <b><?= htmlspecialchars($row['pegawai_nama']); ?></b>
                                </div>
                                <div class="timeline-desc">
                                    Jabatan: <?= htmlspecialchars($row['jabatan'] ?? '-'); ?> <br> Unit: <?= htmlspecialchars($row['unit_kerja']); ?>
                                </div>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    } else {
                        echo "<p style='color:#777; font-size: 14px; margin-top:10px;'>Belum ada aktivitas pegawai baru.</p>";
                    }
                    ?>
                </div>
            </div>
        </div>

    </div>
</div>

</body>
</html>