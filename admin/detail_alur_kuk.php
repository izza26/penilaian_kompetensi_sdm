<?php
session_start();
require_once '../auth/cek_login.php';
require_once '../config/koneksi.php';

$elemen_id = isset($_GET['id']) ? $_GET['id'] : null;
$mode      = isset($_GET['mode']) ? $_GET['mode'] : 'view'; // Default ke view (Read-Only)

if (!$elemen_id) {
    die("Error: ID Elemen tidak ditemukan di URL!");
}

$page_title = "Detail Alur KUK";
$page_subtitle = ($mode == 'edit') ? "Pilih salah satu aktivitas di bawah ini untuk diperbarui" : "Informasi lengkap elemen kompetensi dan daftar aktivitas";

$query_elemen = "
    SELECT 
        ek.elemen_id, ek.elemen_kompetensi, ek.kode_elemen_excel, ek.input_output_outco,
        uk.kode_unit, uk.judul_unit, uk.jenis_kompetensi,
        j.nama_jabatan
    FROM elemen_kompetensi ek
    LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
    WHERE ek.elemen_id = $1
";
$result_elemen = pg_query_params($koneksi, $query_elemen, array($elemen_id));
$elemen = pg_fetch_assoc($result_elemen);

if (!$elemen) {
    die("Data Elemen Kompetensi tidak ditemukan di database!");
}

$query_aktivitas = "SELECT * FROM aktivitas_kompeten WHERE elemen_id = $1 ORDER BY aktivitas_id ASC";
$result_aktivitas = pg_query_params($koneksi, $query_aktivitas, array($elemen_id));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Alur KUK</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/alur_kuk.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .detail-info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 20px; }
        .info-box label { font-size: 13px; color: #6c757d; display: block; margin-bottom: 5px; }
        .info-box span { font-weight: 600; color: #343a40; font-size: 15px; }
        .section-title { font-size: 16px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #e0e0e0; color: #333; }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; 
    $btn_kembali = "instrumen.php";
    ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card" style="margin-bottom: 20px; padding: 25px;">
            <div class="page-header" style="margin-bottom: 20px;">
                <div class="page-title">
                    <h2><?= ($mode == 'edit') ? "Kelola / Edit Aktivitas" : "Detail Elemen Kompetensi" ?></h2>
                </div>
                <a href="alur_kuk.php" class="btn-secondary">Kembali</a>
            </div>

            <div class="detail-info-grid">
                <div class="info-box"><label>Jabatan</label><span><?= htmlspecialchars($elemen['nama_jabatan'] ?? '-') ?></span></div>
                <div class="info-box"><label>Unit Kompetensi</label><span><?= htmlspecialchars($elemen['kode_unit'] . ' - ' . $elemen['judul_unit']) ?></span></div>
                <div class="info-box"><label>Jenis Kompetensi</label><span><?= htmlspecialchars($elemen['jenis_kompetensi']) ?></span></div>
                <div class="info-box"><label>Elemen Kompetensi</label><span><?= htmlspecialchars($elemen['elemen_kompetensi']) ?></span></div>
                <div class="info-box"><label>Kode Elemen Excel</label><span><?= htmlspecialchars($elemen['kode_elemen_excel'] ?? '-') ?></span></div>
                <div class="info-box"><label>Input/Output Dasar</label><span><?= htmlspecialchars($elemen['input_output_outco'] ?? '-') ?></span></div>
            </div>
        </div>

        <div class="page-card" style="padding: 25px;">
            <h3 class="section-title">Daftar Aktivitas & KUK</h3>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="15%">ID Aktivitas</th>
                            <th width="30%">Detail Aktivitas / Proses</th>
                            <th width="35%">Kriteria Kompeten (KUK)</th>
                            <th width="10%">Bobot</th>
                            <?php if ($mode == 'edit'): ?>
                                <th width="10%">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result_aktivitas && pg_num_rows($result_aktivitas) > 0): ?>
                            <?php while ($ak = pg_fetch_assoc($result_aktivitas)): ?>
                            <tr>
                                <td><?= htmlspecialchars($ak['aktivitas_id']) ?></td>
                                <td><?= htmlspecialchars($ak['detail_aktivitas']) ?></td>
                                <td><?= htmlspecialchars($ak['kriteria_kompetens']) ?></td>
                                <td><?= htmlspecialchars($ak['bobot_aktivitas']) ?>%</td>
                                <?php if ($mode == 'edit'): ?>
                                    <td class="action-cell">
                                        <div class="action-group">
                                            <a href="edit_alur_kuk.php?id_aktivitas=<?= $ak['aktivitas_id'] ?>" class="action-btn edit-btn" title="Edit Aktivitas ini">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="<?= ($mode == 'edit') ? '5' : '4' ?>" style="text-align:center;">Belum ada aktivitas terdaftar.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>