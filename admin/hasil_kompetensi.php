<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Hasil Kompetensi";
$page_subtitle = "Kelola hasil akhir penilaian kompetensi pegawai";

$q_total = pg_query($koneksi, "SELECT COUNT(*) as total FROM pegawai");
$total_pegawai = pg_fetch_assoc($q_total)['total'];

$q_kompeten = pg_query($koneksi, "SELECT COUNT(*) as jml FROM rekap_aktivitas_36 WHERE status_kompeten = 'K'");
$jml_kompeten = pg_fetch_assoc($q_kompeten)['jml'];

$jml_belum = $total_pegawai - $jml_kompeten;

$q_avg = pg_query($koneksi, "SELECT AVG(skor_akhir_360::numeric) as rata FROM rekap_aktivitas_36");
$rata_rata = round(pg_fetch_assoc($q_avg)['rata'], 1);

$query_hasil = "
    SELECT 
        p.pegawai_id,        -- Tambahkan baris ini
        p.pegawai_nama, 
        p.jabatan, 
        uk.kode_unit, 
        ra.skor_akhir_360, 
        ra.status_kompeten
    FROM pegawai p
    JOIN peserta_penilaian pp ON p.pegawai_id = pp.peserta_id
    JOIN rekap_aktivitas_36 ra ON pp.rekap_aktivitas_id = ra.rekap_aktivitas_id
    JOIN unit_kompetensi uk ON pp.rekap_unit_id = uk.rekap_unit_id
";
$result = pg_query($koneksi, $query_hasil);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hasil Kompetensi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/hasil_kompetensi.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon brown"><i class="bi bi-people"></i></div>
                    <div><h3><?= $total_pegawai ?></h3><span>Total Pegawai</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon green"><i class="bi bi-patch-check"></i></div>
                    <div><h3><?= $jml_kompeten ?></h3><span>Kompeten</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
                    <div><h3><?= $jml_belum ?></h3><span>Belum Kompeten</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-bar-chart"></i></div>
                    <div><h3><?= $rata_rata ?></h3><span>Rata-rata Nilai</span></div>
                </div>
            </div>

            <div class="result-card">
                <div class="result-header">
                    <h3>Daftar Hasil Kompetensi</h3>
                    <p>Rekap hasil penilaian kompetensi pegawai.</p>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Pegawai</th>
                                <th>Jabatan</th>
                                <th>Unit Kompetensi</th>
                                <th>Nilai Akhir</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            if ($result && pg_num_rows($result) > 0) {
                                while ($row = pg_fetch_assoc($result)) {
                                    $is_kompeten = ($row['status_kompeten'] == 'K');
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><?= htmlspecialchars($row['pegawai_nama']) ?></td>
                                        <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                        <td><?= htmlspecialchars($row['kode_unit']) ?></td>
                                        <td class="nilai"><?= $row['skor_akhir_360'] ?></td>
                                        <td>
                                            <span class="status <?= $is_kompeten ? 'kompeten' : 'belum' ?>">
                                                <?= $is_kompeten ? 'Kompeten' : 'Belum Kompeten' ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <div class="action-group">
                                                <a href="detail_hasil.php?id=<?= $row['pegawai_id'] ?>" class="action-btn view-btn" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="#" class="action-btn print-btn"><i class="bi bi-printer"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center;'>Belum ada data hasil penilaian.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>