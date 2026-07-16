<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Hasil Kompetensi";
$page_subtitle = "Kelola hasil akhir penilaian kompetensi pegawai";

// 1. Hitung Total Pegawai
$q_total = pg_query($koneksi, "SELECT COUNT(*) as total FROM pegawai WHERE role = 'pegawai'");
$total_pegawai = pg_fetch_assoc($q_total)['total'];

// 2. Hitung Jumlah Kompeten (Berdasarkan nilai >= 70 di sistem baru)
$q_kompeten = pg_query($koneksi, "SELECT COUNT(*) as jml FROM penilaian_header WHERE status = 'Selesai' AND nilai_akhir >= 70");
$jml_kompeten = pg_fetch_assoc($q_kompeten)['jml'];

// 3. Hitung Jumlah Belum Kompeten (Berdasarkan nilai < 70)
$q_belum = pg_query($koneksi, "SELECT COUNT(*) as jml FROM penilaian_header WHERE status = 'Selesai' AND nilai_akhir < 70");
$jml_belum = pg_fetch_assoc($q_belum)['jml'];

// 4. Hitung Rata-rata Nilai Keseluruhan
$q_avg = pg_query($koneksi, "SELECT AVG(nilai_akhir) as rata FROM penilaian_header WHERE status = 'Selesai'");
$rata_db = pg_fetch_assoc($q_avg)['rata'];
$rata_rata = $rata_db ? round($rata_db, 1) : 0;

// 5. Tarik Data Tabel (Menggunakan struktur penilaian_header yang baru)
$query_hasil = "
    SELECT 
        ph.penilaian_id,
        p.pegawai_id,
        p.pegawai_nama, 
        p.jabatan, 
        uk.kode_unit,
        uk.judul_unit,
        ph.nilai_akhir, 
        ph.kategori
    FROM penilaian_header ph
    JOIN pegawai p ON ph.pegawai_id = p.pegawai_id
    JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
    WHERE ph.status = 'Selesai'
    ORDER BY ph.waktu_submit DESC
";
$result = pg_query($koneksi, $query_hasil);

if (!$result) {
    die("<div style='color:red; padding:20px;'>Query Error: " . pg_last_error($koneksi) . "</div>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hasil Kompetensi | Admin</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/hasil_kompetensi.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Style fallback untuk status */
        .status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-align: center; display: inline-block; white-space: nowrap;}
        .status.kompeten { background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;}
        .status.cukup { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;}
        .status.belum { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}

        /* Style untuk tombol Cetak Excel */
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .btn-cetak-excel:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transform: translateY(-2px); color: white;}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <!-- WIDGET STATISTIK BAWAAN ADMIN -->
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-icon brown"><i class="bi bi-people"></i></div>
                    <div><h3><?= $total_pegawai ?></h3><span>Total Pegawai</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon green"><i class="bi bi-patch-check"></i></div>
                    <div><h3><?= $jml_kompeten ?></h3><span>Kompeten (≥70)</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div>
                    <div><h3><?= $jml_belum ?></h3><span>Belum Kompeten</span></div>
                </div>
                <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-bar-chart"></i></div>
                    <div><h3><?= $rata_rata ?></h3><span>Rata-rata Nilai</span></div>
                </div>
            </div>

            <!-- TABEL HASIL PENILAIAN -->
            <div class="result-card" style="padding: 25px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                
                <!-- HEADER TABEL & TOMBOL EXCEL -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 16px; color: #0f172a;">Riwayat Penilaian Pegawai</h3>
                        <p style="margin: 0; font-size: 13px; color: #64748b;">Daftar nilai akhir kompetensi dari seluruh pegawai.</p>
                    </div>
                    <a href="cetak_excel.php" class="btn-cetak-excel" target="_blank" title="Unduh rekapitulasi nilai ini dalam format Excel">
                        <i class="bi bi-file-earmark-excel-fill"></i> Cetak Penilaian
                    </a>
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
                            if (pg_num_rows($result) > 0) {
                                while ($row = pg_fetch_assoc($result)) {
                                    
                                    // Set warna status berdasarkan kategori baru
                                    $kategori = trim($row['kategori']);
                                    if ($kategori === 'Sangat Kompeten' || $kategori === 'Kompeten') {
                                        $class_status = 'kompeten';
                                    } elseif ($kategori === 'Cukup Kompeten') {
                                        $class_status = 'cukup';
                                    } else {
                                        $class_status = 'belum';
                                    }
                                    ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td><b><?= htmlspecialchars($row['pegawai_nama']) ?></b></td>
                                        <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                        <td>
                                            <span style="font-size: 11px; font-weight: 700; color: #1e3a8a; display: block; margin-bottom: 2px;"> <?= htmlspecialchars($row['kode_unit']) ?></span>
                                            <span style="font-size: 12px;"><?= htmlspecialchars($row['judul_unit']) ?></span>
                                        </td>
                                        <td class="nilai"><b><?= number_format($row['nilai_akhir'], 2) ?></b></td>
                                        <td>
                                            <span class="status <?= $class_status ?>">
                                                <?= htmlspecialchars($kategori) ?>
                                            </span>
                                        </td>
                                        <td class="action-cell">
                                            <div class="action-group">
                                                <a href="detail_hasil.php?id=<?= $row['penilaian_id'] ?>" class="action-btn view-btn" title="Lihat Detail">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' style='text-align:center; padding: 30px;'>Belum ada data hasil penilaian.</td></tr>";
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