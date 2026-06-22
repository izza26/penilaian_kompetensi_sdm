<?php
session_start();
// require_once '../config/cek_sesi.php';
require_once '../config/koneksi.php';

$page_title = "Form Penilaian";
$page_subtitle = "Lakukan penilaian kompetensi pegawai";

// 1. Tangkap ID Pegawai dan Kode Unit dari URL
$pegawai_id = isset($_GET['id']) ? $_GET['id'] : null;
$kode_unit  = isset($_GET['unit']) ? $_GET['unit'] : null;

if (!$pegawai_id || !$kode_unit) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: Parameter ID Pegawai atau Kode Unit tidak lengkap!</div>");
}

// 2. Ambil Data Pegawai
$q_pegawai = "SELECT pegawai_nama, jabatan, unit_kerja FROM pegawai WHERE pegawai_id = $1";
$r_pegawai = pg_query_params($koneksi, $q_pegawai, array($pegawai_id));
$pegawai = pg_fetch_assoc($r_pegawai);

if (!$pegawai) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: Data pegawai tidak ditemukan!</div>");
}

// 3. Ambil Data Unit & Elemen (Untuk Header)
$q_unit = "
    SELECT uk.judul_unit, ek.elemen_kompetensi 
    FROM unit_kompetensi uk
    LEFT JOIN elemen_kompetensi ek ON uk.kode_unit = ek.kode_unit
    WHERE uk.kode_unit = $1 LIMIT 1
";
$r_unit = pg_query_params($koneksi, $q_unit, array($kode_unit));
$unit_info = pg_fetch_assoc($r_unit);

// 4. Ambil Daftar Rubrik/Pertanyaan untuk Unit ini
$q_rubrik = "
    SELECT 
        ak.aktivitas_id, 
        ak.detail_aktivitas,
        rs.rubrik_id,
        rs.deskripsi_skor,
        rs.tipe_penilaian
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    LEFT JOIN rubrik_skor rs ON ak.aktivitas_id = rs.aktivitas_id
    WHERE ek.kode_unit = $1
    ORDER BY ak.aktivitas_id ASC
";
$r_rubrik = pg_query_params($koneksi, $q_rubrik, array($kode_unit));

$daftar_pertanyaan = [];
if ($r_rubrik) {
    while ($row = pg_fetch_assoc($r_rubrik)) {
        $daftar_pertanyaan[] = $row;
    }
}
$jumlah_instrumen = count($daftar_pertanyaan);

// Bikin Inisial Nama (Misal: Budi Santoso -> BS)
$words = explode(" ", $pegawai['pegawai_nama']);
$inisial = "";
foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); }
$inisial = substr($inisial, 0, 2);


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_penilaian'])) {
    $catatan = $_POST['catatan_assessor'] ?? '';
    $skor_array = $_POST['skor'] ?? []; 
    $status_simpan = $_POST['status_simpan']; 

    $q_insert_header = "
        INSERT INTO penilaian_header (catatan_umum, status, waktu_submit) 
        VALUES ($1, $2, NOW()) RETURNING penilaian_id
    ";
    $res_header = pg_query_params($koneksi, $q_insert_header, array($catatan, $status_simpan));
    
    if ($res_header) {
        $header_row = pg_fetch_assoc($res_header);
        $penilaian_id = $header_row['penilaian_id'];

        pg_query_params($koneksi, "UPDATE pegawai SET penilaian_id = $1 WHERE pegawai_id = $2", array($penilaian_id, $pegawai_id));

        foreach ($skor_array as $akt_id => $nilai) {
            $q_detail = "INSERT INTO penilaian_detail (aktivitas_id, skor_final) VALUES ($1, $2) RETURNING detail_penilaian_i";
            $res_detail = pg_query_params($koneksi, $q_detail, array($akt_id, $nilai));
            
            if ($res_detail) {
                $detail_row = pg_fetch_assoc($res_detail);
                $detail_id = $detail_row['detail_penilaian_i'];
                pg_query_params($koneksi, "UPDATE penilaian_header SET detail_penilaian_i = $1 WHERE penilaian_id = $2", array($detail_id, $penilaian_id));
            }
        }

        echo "<script>alert('Penilaian berhasil disimpan!'); window.location.href='penilaian.php';</script>";
        exit;
    } else {
        echo "<script>alert('Terjadi kesalahan saat menyimpan penilaian!');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Penilaian</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/form_penilaian.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styling tambahan agar Radio Button berbentuk seperti kotak skor Anda */
        .score-radio-group { display: flex; gap: 10px; margin-top: 10px; }
        .score-radio-group input[type="radio"] { display: none; }
        .score-label { 
            width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; 
            border: 2px solid #dee2e6; border-radius: 8px; font-weight: 600; color: #495057; cursor: pointer; transition: 0.2s;
        }
        .score-radio-group input[type="radio"]:checked + .score-label {
            background-color: #A15D33; color: white; border-color: #A15D33;
        }
        
        /* Khusus Ya/Tidak */
        .yesno-label {
            padding: 8px 20px; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500; color: #495057; cursor: pointer; transition: 0.2s;
        }
        .score-radio-group input[type="radio"]:checked + .yesno-label {
            background-color: #A15D33; color: white; border-color: #A15D33;
        }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <form method="POST" action="">
            <div class="page-card">
                <div class="page-header">
                    <div class="page-title">
                        <h2>Form Penilaian</h2>
                        <p>Berikan penilaian berdasarkan instrumen kompetensi yang tersedia.</p>
                    </div>
                    <a href="penilaian.php" class="btn-back">
                        <i class="bi bi-arrow-left"></i> Kembali
                    </a>
                </div>

                <div class="detail-card">
                    <div class="pegawai-summary">
                        <div class="avatar"><?= $inisial ?></div>
                        <div class="pegawai-info">
                            <h3><?= htmlspecialchars($pegawai['pegawai_nama']) ?></h3>
                            <p><?= htmlspecialchars($pegawai['jabatan']) ?> • <?= htmlspecialchars($pegawai['unit_kerja'] ?? 'Museum Geologi') ?></p>
                            <span class="status-badge pending" style="background:#fff3cd; color:#198754; padding:5px 15px; border-radius:20px; font-size:12px; font-weight:bold;">
                                Menunggu Penilaian
                            </span>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <h4 class="section-title">Informasi Kompetensi</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Unit Kompetensi</label>
                            <span><?= htmlspecialchars($kode_unit) ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Elemen Kompetensi</label>
                            <span><?= htmlspecialchars($unit_info['elemen_kompetensi'] ?? '-') ?></span>
                        </div>
                        <div class="detail-item">
                            <label>Aktivitas</label>
                            <span>Seluruh Aktivitas Terpeta</span>
                        </div>
                        <div class="detail-item">
                            <label>Jumlah Instrumen</label>
                            <span><?= $jumlah_instrumen ?> Pertanyaan</span>
                        </div>
                    </div>
                </div>

                <?php 
                $no = 1;
                foreach ($daftar_pertanyaan as $q): 
                    $akt_id = $q['aktivitas_id'];
                    $tipe = $q['tipe_penilaian'];
                ?>
                    <div class="detail-card">
                        <h4 class="question-title">Pertanyaan <?= $no++ ?> <small style="color:#aaa; font-weight:normal;">(<?= htmlspecialchars($akt_id) ?>)</small></h4>
                        
                        <p class="question-text">
                            <strong>[<?= htmlspecialchars($q['detail_aktivitas']) ?>]</strong><br>
                            <?= htmlspecialchars($q['deskripsi_skor'] ?? 'Rubrik pertanyaan belum diatur oleh admin.') ?>
                        </p>

                        <div class="score-radio-group">
                            <?php if (strpos(strtolower($tipe), 'ya') !== false): // Jika Tipe Ya/Tidak ?>
                                <input type="radio" id="skor_<?= $akt_id ?>_5" name="skor[<?= $akt_id ?>]" value="5" required>
                                <label for="skor_<?= $akt_id ?>_5" class="yesno-label">Ya (Skor 5)</label>

                                <input type="radio" id="skor_<?= $akt_id ?>_1" name="skor[<?= $akt_id ?>]" value="1" required>
                                <label for="skor_<?= $akt_id ?>_1" class="yesno-label">Tidak (Skor 1)</label>
                            
                            <?php else: // Jika Tipe Skala 1-5 ?>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <input type="radio" id="skor_<?= $akt_id ?>_<?= $i ?>" name="skor[<?= $akt_id ?>]" value="<?= $i ?>" required>
                                    <label for="skor_<?= $akt_id ?>_<?= $i ?>" class="score-label"><?= $i ?></label>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($jumlah_instrumen == 0): ?>
                    <div class="detail-card" style="text-align: center; color: red;">
                        Instrumen untuk unit kompetensi ini belum dibuat. Silakan tambahkan di menu Instrumen terlebih dahulu.
                    </div>
                <?php endif; ?>

                <div class="detail-card">
                    <h4 class="section-title">Catatan Assessor</h4>
                    <textarea class="assessment-note" name="catatan_assessor" rows="5" placeholder="Tambahkan catatan penilaian..." style="width: 100%; padding:15px; border-radius:10px; border:1px solid #ddd;"></textarea>
                </div>

                <?php if ($jumlah_instrumen > 0): ?>
                    <div class="form-footer" style="display:flex; justify-content:flex-end; gap:15px; padding-top:20px;">
                        <button type="submit" name="status_simpan" value="Draft" class="btn-secondary" style="background:#f1f3f5; color:#333; padding:12px 25px; border-radius:8px; border:none; cursor:pointer;">
                            Simpan Draft
                        </button>

                        <button type="submit" name="simpan_penilaian" value="Selesai" class="btn-primary" style="background:#A15D33; color:white; padding:12px 25px; border-radius:8px; border:none; cursor:pointer;">
                            Selesaikan Penilaian
                        </button>
                    </div>
                <?php endif; ?>

            </div>
        </form>

    </div>
</div>

</body>
</html>