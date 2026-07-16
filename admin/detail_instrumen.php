<?php
session_start();
require_once '../auth/cek_login.php';
require_once '../config/koneksi.php';

$page_title = "Detail Unit Kompetensi";
$page_subtitle = "Informasi lengkap unit kompetensi penilaian";

$id_aktivitas = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_aktivitas) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: ID Aktivitas tidak ditemukan!</div>");
}

$query_induk = "
    SELECT 
        ak.aktivitas_id, ak.detail_aktivitas, ak.kriteria_kompetens, ak.bobot_aktivitas,
        ek.elemen_kompetensi,
        uk.kode_unit, uk.judul_unit, uk.aktif AS status_unit,
        j.nama_jabatan
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
    WHERE ak.aktivitas_id = $1
";
$res_induk = pg_query_params($koneksi, $query_induk, array($id_aktivitas));
$data = pg_fetch_assoc($res_induk);

if (!$data) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: Data aktivitas tidak ditemukan!</div>");
}

$query_rubrik = "SELECT * FROM rubrik_skor WHERE aktivitas_id = $1 LIMIT 1";
$res_rubrik = pg_query_params($koneksi, $query_rubrik, array($id_aktivitas));
$rubrik = pg_fetch_assoc($res_rubrik);

$deskripsi_skor = $rubrik['deskripsi_skor'] ?? '<i style="color:#999;">Instrumen belum ditambahkan.</i>';
$aturan_rubrik = $rubrik['aturan_rubrik'] ?? 'Penilai akan memberikan skor 1-5 berdasarkan rubrik.';
$jenis_file = $rubrik['jenis_file'] ?? 'Belum ditentukan';
$verifikator = $rubrik['verifikator'] ?? 'Belum ditentukan';

$words = explode(" ", $data['detail_aktivitas']);
$inisial = "";
foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); }
$inisial = substr($inisial, 0, 2);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Unit Kompetensi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .detail-header-actions { display: flex; gap: 10px; }
        .avatar-box { width: 60px; height: 60px; background: #e9ecef; color: #0d6efd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; margin-right: 20px; flex-shrink: 0;}
        .card-header-flex { display: flex; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 20px;}
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item label { display: block; font-size: 13px; color: #6c757d; margin-bottom: 5px; }
        .info-item span { font-size: 15px; font-weight: 500; color: #212529; }
        .rubrik-box { background: #f8f9fa; border: 1px dashed #dee2e6; padding: 20px; border-radius: 10px; margin-top: 10px; font-size: 15px;}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; 
    $btn_kembali = "instrumen.php";
    ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card" style="padding: 30px;">

            <div class="card-header-flex">
                <div class="avatar-box"><?= $inisial ?></div>
                <div>
                    <h3 style="margin:0 0 5px 0; font-size:18px;"><?= htmlspecialchars($data['detail_aktivitas']) ?></h3>
                    <p style="margin:0 0 10px 0; color:#6c757d; font-size:14px;">ID Aktivitas: <strong><?= htmlspecialchars($data['aktivitas_id']) ?></strong></p>
                </div>
            </div>
            

            <h4 style="margin-bottom:15px; font-size:16px;">Informasi Aktivitas</h4>
            <div class="info-grid" style="margin-bottom: 30px;">
                <div class="info-item"><label>Jabatan</label><span><?= htmlspecialchars($data['nama_jabatan'] ?? '-') ?></span></div>
                <div class="info-item"><label>Unit Kompetensi</label><span><?= htmlspecialchars($data['kode_unit']) ?></span></div>
                <div class="info-item"><label>Elemen Kompetensi</label><span><?= htmlspecialchars($data['elemen_kompetensi']) ?></span></div>
                <div class="info-item"><label>Kriteria Kompeten (KUK)</label><span><?= htmlspecialchars($data['kriteria_kompetens'] ?? '-') ?></span></div>
            </div>

            <h4 style="margin-bottom:15px; font-size:16px;">Rubrik Penilaian / Pertanyaan</h4>
            <div class="rubrik-box" style="margin-bottom: 30px;">
                <?= $deskripsi_skor ?>
            </div>

            <h4 style="margin-bottom:15px; font-size:16px;">Spesifikasi Penilaian & Verifikasi</h4>
            <div class="info-grid" style="margin-bottom: 30px;">
                <div class="info-item"><label>Bobot Aktivitas</label><span><?= htmlspecialchars($data['bobot_aktivitas'] ?? '0') ?>%</span></div>
                <div class="info-item"><label>Jenis Bukti/File (Evidence)</label><span><?= htmlspecialchars($jenis_file) ?></span></div>
                <div class="info-item"><label>Verifikator</label><span><?= htmlspecialchars($verifikator) ?></span></div>
            </div>

            <h4 style="margin-bottom:15px; font-size:16px;">Aturan / Keterangan Penilai</h4>
            <div class="rubrik-box">
                <?= nl2br(htmlspecialchars($aturan_rubrik)) ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>