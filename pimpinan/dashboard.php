<?php 
session_start();

// 1. Tambahkan Satpam Penjaga Role Pimpinan
require_once '../auth/cek_role.php';
cekRole('pimpinan');

require_once '../config/koneksi.php';

/* ===========================================================
   DATA PIMPINAN LOGIN
=========================================================== */
$idPegawai = $_SESSION['pegawai_id'];
$queryPegawai = @pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pimpinan = $queryPegawai ? pg_fetch_assoc($queryPegawai) : null;

/* ==========================================================
   CEK PERIODE PENILAIAN (OTOMATISASI WAKTU)
========================================================== */
$qPeriode = @pg_query($koneksi, "SELECT * FROM periode_penilaian WHERE status_aktif = 'Y' LIMIT 1");
$periodeAktif = $qPeriode ? pg_fetch_assoc($qPeriode) : null;

$is_open = false;
$pesan_periode = "Belum ada periode penilaian yang aktif saat ini.";
$badge_class = "danger";

if ($periodeAktif) {
    $tgl_mulai = strtotime($periodeAktif['tanggal_mulai']);
    $tgl_selesai = strtotime($periodeAktif['tanggal_selesai'] . ' 23:59:59'); 
    $sekarang = time();

    if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) {
        $is_open = true;
        $pesan_periode = "Periode Penilaian sedang berlangsung: <b>" . date('d M Y', $tgl_mulai) . "</b> s/d <b>" . date('d M Y', $tgl_selesai) . "</b>.";
        $badge_class = "success";
    } elseif ($sekarang < $tgl_mulai) {
        $pesan_periode = "Periode Penilaian baru akan dibuka pada tanggal <b>" . date('d M Y', $tgl_mulai) . "</b>.";
        $badge_class = "warning";
    } else {
        $pesan_periode = "Periode Penilaian telah DITUTUP sejak <b>" . date('d M Y', $tgl_selesai) . "</b>.";
        $badge_class = "danger";
    }
}

/* ===========================================================
   FUNGSI HELPER & QUERY STATISTIK (UPDATE SISTEM BARU)
=========================================================== */
function getCountSafe($koneksi, $query) {
    $q = @pg_query($koneksi, $query); 
    if ($q) {
        $r = pg_fetch_assoc($q);
        return $r ? (int)$r['total'] : 0;
    }
    return 0;
}

// Menghitung statistik berdasarkan sistem baru (penilaian_header & bukti_pegawai)
$tot_pegawai = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM pegawai WHERE role = 'pegawai'");
$tot_dokumen = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM bukti_pegawai");
$tot_dinilai = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header");

$belum_dinilai = ($tot_dokumen > $tot_dinilai) ? ($tot_dokumen - $tot_dinilai) : 0;

$kompeten = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header WHERE nilai_akhir >= 70");
$cukup    = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header WHERE nilai_akhir >= 55 AND nilai_akhir < 70");
$bina     = getCountSafe($koneksi, "SELECT COUNT(*) AS total FROM penilaian_header WHERE nilai_akhir < 55");

// Hitung persentase dari unit yang SUDAH dinilai
$pct_kompeten = ($tot_dinilai > 0) ? round(($kompeten / $tot_dinilai) * 100) : 0;
$pct_cukup    = ($tot_dinilai > 0) ? round(($cukup / $tot_dinilai) * 100) : 0;
$pct_bina     = ($tot_dinilai > 0) ? round(($bina / $tot_dinilai) * 100) : 0;

$tanggalSekarang = date("d F Y");

if($tot_dokumen > 0){
    $welcomeMessage = "Terdapat <b>{$tot_dokumen} dokumen</b> evidence yang terkumpul di sistem.";
} else {
    $welcomeMessage = "Belum ada dokumen yang diunggah oleh pegawai saat ini.";
}

/* ===========================================================
   QUERY UNTUK TIMELINE (ANTREAN REVIEW TERBARU)
=========================================================== */
$q_aktivitas = @pg_query($koneksi, "
    SELECT p.pegawai_nama, uk.judul_unit, bp.tanggal_upload 
    FROM bukti_pegawai bp 
    JOIN pegawai p ON bp.pegawai_id = p.pegawai_id
    JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    ORDER BY bp.tanggal_upload DESC LIMIT 5
");

// Variabel untuk layout Header agar konsisten
$page_title = "Dashboard Pimpinan";
$page_subtitle = "Pantau antrean penilaian dan perkembangan kompetensi pegawai Anda";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pimpinan | Museum Geologi</title>

    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pimpinan/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

</head>
<body>

<div class="app">

    <?php include "../layouts/sidebar_pimpinan.php"; ?>

    <div class="main-content">

        <?php include "../layouts/header.php"; ?>

        <!-- ALERT PERIODE PENILAIAN -->
        <?php if($periodeAktif): ?>
            <div class="alert-periode alert-<?= $badge_class ?>">
                <i class="bi <?= $is_open ? 'bi-calendar-check' : ($badge_class == 'warning' ? 'bi-hourglass-split' : 'bi-calendar-x') ?>" style="font-size: 22px;"></i>
                <div>
                    <h4 style="margin:0 0 2px 0; font-size: 14px;"><?= htmlspecialchars($periodeAktif['nama_periode']) ?></h4>
                    <p style="margin:0; font-size: 12px;"><?= $pesan_periode ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert-periode alert-info">
                <i class="bi bi-info-circle" style="font-size: 22px;"></i>
                <div>
                    <h4 style="margin:0 0 2px 0; font-size: 14px;">Tidak Ada Periode Aktif</h4>
                    <p style="margin:0; font-size: 12px;">Sistem penilaian saat ini sedang ditutup atau belum diatur.</p>
                </div>
            </div>
        <?php endif; ?>

        <!-- WELCOME CARD -->
        <div class="welcome-card" style="background: linear-gradient(135deg, #D6BB80 0%, #A08348 100%);">
            <div class="welcome-text">
                <h2>Halo, <?= htmlspecialchars($pimpinan['pegawai_nama'] ?? 'Pimpinan'); ?></h2>
                <div class="welcome-date"><?= $tanggalSekarang; ?></div>
                <p><?= $welcomeMessage; ?></p>
            </div>
            <div class="welcome-icon">
                <i class="bi bi-person-workspace"></i>
            </div>
        </div>

        <!-- KPI CARDS (UPDATED UNTUK SISTEM SKORING BARU) -->
        <!-- KPI CARDS (UPDATED UNTUK SISTEM SKORING BARU) -->
        <div class="pegawai-kpi-grid">
            <div class="pegawai-kpi-card blue">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="pegawai-kpi-label">Total Pegawai</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value blue-text"><?= $tot_pegawai ?></div>
                    <div class="pegawai-kpi-desc">Jumlah pegawai yang dinilai.</div>
                </div>
            </div>

            <div class="pegawai-kpi-card orange">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-folder-fill"></i></div>
                    <div class="pegawai-kpi-label">Dokumen Terkumpul</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value orange-text"><?= $tot_dokumen ?></div>
                    <div class="pegawai-kpi-desc">Evidence yang sudah diunggah.</div>
                </div>
            </div>

            <!-- Card 3: Menunggu Review (Sebelumnya Selesai Dinilai) -->
            <div class="pegawai-kpi-card purple">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="pegawai-kpi-label">Menunggu Review</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value purple-text"><?= $belum_dinilai ?></div>
                    <div class="pegawai-kpi-desc">Evidence belum divalidasi.</div>
                </div>
            </div>

            <!-- Card 4: Selesai Dinilai (Sebelumnya Kompeten) -->
            <div class="pegawai-kpi-card green">
                <div class="pegawai-kpi-top">
                    <div class="pegawai-kpi-icon"><i class="bi bi-check2-all"></i></div>
                    <div class="pegawai-kpi-label">Selesai Dinilai</div>
                </div>
                <div class="pegawai-kpi-content">
                    <div class="pegawai-kpi-value green-text"><?= $tot_dinilai ?></div>
                    <div class="pegawai-kpi-desc">Unit kompetensi tervalidasi.</div>
                </div>
            </div>
        </div>

        <div class="dashboard-row">
            <div class="progress-card">
                <div class="card-header">
                    <h3>Statistik Kompetensi Pegawai</h3>
                    <span>Total Unit Dinilai: <?= $tot_dinilai ?></span>
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
                        <span>Perlu Penguatan (< 55)</span>
                        <strong><?= $pct_bina; ?>%</strong>
                    </div>
                </div>
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bar-bina" style="width:<?= $pct_bina; ?>%"></div>
                </div>

                <!-- Link ini mengarah ke halaman Skoring terbaru yang kita buat tadi -->
                <a href="skoring.php" class="progress-btn" style="margin-top: 25px;">
                    <i class="bi bi-clipboard-check"></i> Mulai Skoring Evidence
                </a>
            </div>

            <div class="profile-summary">
                <h3>Informasi Profil Anda</h3>
                <table>
                    <tr>
                        <td width="35%">Nama</td>
                        <td width="5%">:</td>
                        <td><b><?= htmlspecialchars($pimpinan['pegawai_nama'] ?? '-'); ?></b></td>
                    </tr>
                    <tr>
                        <td>NIP/NIK</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pimpinan['nip_nik'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td>Unit Kerja</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pimpinan['unit_kerja'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td>Jabatan</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($pimpinan['jabatan'] ?? '-'); ?></td>
                    </tr>
                    <tr>
                        <td>Hak Akses</td>
                        <td>:</td>
                        <td><span style="background: #333; color: #fff; padding: 3px 8px; border-radius: 4px; font-size: 12px;">PIMPINAN</span></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="timeline-card">
            <h3>Antrean Upload Terbaru</h3>
            <div class="timeline">
                <?php 
                if ($q_aktivitas && pg_num_rows($q_aktivitas) > 0) {
                    while($item = pg_fetch_assoc($q_aktivitas)): 
                ?>
                    <div class="timeline-item">
                        <div class="timeline-icon" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div>
                            <div class="timeline-title">
                                <b><?= htmlspecialchars($item['pegawai_nama']); ?></b> baru saja mengunggah evidence.
                            </div>
                            <div class="timeline-desc">
                                Unit: <?= htmlspecialchars($item['judul_unit']); ?><br>
                                <small style="color: #94a3b8;"><i class="bi bi-clock"></i> <?= date('d M Y, H:i', strtotime($item['tanggal_upload'])) ?></small>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                } else {
                    echo "<p style='color:#777; font-size: 14px; margin-top:10px;'>Belum ada pegawai yang mengunggah dokumen baru saat ini.</p>";
                }
                ?>
            </div>
        </div>

    </div>
</div>

</body>
</html>