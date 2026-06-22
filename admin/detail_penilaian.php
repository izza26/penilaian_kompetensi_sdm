<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Detail Penilaian";
$page_subtitle = "Informasi lengkap hasil penilaian kompetensi";

$pegawai_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$pegawai_id) {
    die("<div style='color:red; padding:20px;'>Error: ID Pegawai tidak ditemukan di URL!</div>");
}

$query_utama = "
    SELECT 
        p.pegawai_nama, 
        p.jabatan, 
        p.unit_kerja,
        uk.kode_unit,
        uk.judul_unit,
        (SELECT elemen_kompetensi FROM elemen_kompetensi WHERE kode_unit = uk.kode_unit LIMIT 1) AS nama_elemen,
        ph.waktu_submit,
        ph.catatan_umum,
        ph.status AS status_penilaian,
        ra.skor_akhir_360,
        ra.status_kompeten
    FROM pegawai p
    LEFT JOIN jabatan j ON UPPER(p.jabatan) = UPPER(j.nama_jabatan)
    LEFT JOIN jabatan_unit_kompe juk ON j.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN unit_kompetensi uk ON juk.jabatan_unit_id = uk.jabatan_unit_id
    LEFT JOIN penilaian_header ph ON p.penilaian_id = ph.penilaian_id
    LEFT JOIN peserta_penilaian pp ON p.pegawai_id = pp.peserta_id
    LEFT JOIN rekap_aktivitas_36 ra ON pp.rekap_aktivitas_id = ra.rekap_aktivitas_id
    WHERE p.pegawai_id = $1
    LIMIT 1
";
$res_utama = pg_query_params($koneksi, $query_utama, array($pegawai_id));
$data = pg_fetch_assoc($res_utama);

if (!$data) {
    die("<div style='color:red; padding:20px;'>Error: Data penilaian pegawai tidak ditemukan!</div>");
}

$query_detail = "
    SELECT 
        ak.detail_aktivitas,
        ak.kriteria_kompetens,
        pd.skor_final
    FROM pegawai p
    JOIN penilaian_header ph ON p.penilaian_id = ph.penilaian_id
    JOIN penilaian_detail pd ON ph.detail_penilaian_i = pd.detail_penilaian_i
    JOIN aktivitas_kompeten ak ON pd.aktivitas_id = ak.aktivitas_id
    WHERE p.pegawai_id = $1
    ORDER BY ak.aktivitas_id ASC
";
$res_detail = pg_query_params($koneksi, $query_detail, array($pegawai_id));

$list_skor = [];
$total_skor_kualitatif = 0;
if ($res_detail) {
    while ($row = pg_fetch_assoc($res_detail)) {
        $list_skor[] = $row;
        $total_skor_kualitatif += (float)$row['skor_final'];
    }
}

// 4. LOGIKA FORMATTING DATA
$jumlah_instrumen = count($list_skor);
$rata_rata = ($jumlah_instrumen > 0) ? round($total_skor_kualitatif / $jumlah_instrumen, 1) : 0;

// Penentuan Teks Status Hasil Akhir
$status_akhir = (!empty($data['status_kompeten'])) ? $data['status_kompeten'] : 'Belum Terhitung';
$is_kompeten = ($status_akhir == 'K' || $status_akhir == 'Kompeten');
$badge_class = $is_kompeten ? 'kompeten' : 'belum';

// Otomatisasi Avatar Inisial Nama (Budi Santoso -> BS)
$nama_split = explode(" ", $data['pegawai_nama']);
$inisial = "";
foreach ($nama_split as $n) {
    if (!empty($n)) $inisial .= strtoupper($n[0]);
}
$inisial = substr($inisial, 0, 2);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Penilaian</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/detail_penilaian.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Detail Penilaian</h2>
                    <p>Informasi lengkap hasil penilaian kompetensi pegawai.</p>
                </div>
                <a href="penilaian.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="detail-card">
                <div class="pegawai-summary">
                    <div class="avatar">
                        <?= htmlspecialchars($inisial) ?>
                    </div>
                    <div class="pegawai-info">
                        <h3><?= htmlspecialchars($data['pegawai_nama']) ?></h3>
                        <p><?= htmlspecialchars($data['jabatan'] ?? '-') ?> • <?= htmlspecialchars($data['unit_kerja'] ?? 'Museum Geologi') ?></p>
                        <div class="status-wrapper">
                            <span class="status">
                                <?= htmlspecialchars($data['status_penilaian'] ?? 'Sudah Dinilai') ?>
                            </span>
                            <span class="nilai-badge">
                                Nilai Akhir : <?= htmlspecialchars($data['skor_akhir_360'] ?? '0') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h4 class="section-title">Informasi Kompetensi</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Unit Kompetensi</label>
                        <span><?= htmlspecialchars($data['kode_unit'] ?? '-') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Nama Unit</label>
                        <span><?= htmlspecialchars($data['judul_unit'] ?? '-') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Elemen Kompetensi</label>
                        <span><?= htmlspecialchars($data['nama_elemen'] ?? '-') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Aktivitas</label>
                        <span>Seluruh Aktivitas Terpeta</span>
                    </div>
                    <div class="detail-item">
                        <label>Jumlah Instrumen</label>
                        <span><?= $jumlah_instrumen ?> Pertanyaan</span>
                    </div>
                    <div class="detail-item">
                        <label>Tanggal Penilaian</label>
                        <span><?= htmlspecialchars($data['waktu_submit'] ?? 'Belum Submit') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Assessor</label>
                        <span>Tim Asesor / Admin</span>
                    </div>
                </div>
            </div>

            <div class="detail-card">
                <h4 class="section-title">Hasil Penilaian</h4>
                <div class="table-wrapper">
                    <table class="hasil-table">
                        <thead>
                            <tr>
                                <th width="10%">No</th>
                                <th width="75%">Kriteria / Indikator Kompeten (KUK)</th>
                                <th width="15%">Nilai Perolehan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($jumlah_instrumen > 0): ?>
                                <?php $no = 1; foreach ($list_skor as $skor): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td>
                                            <strong><?= htmlspecialchars($skor['detail_aktivitas']) ?></strong><br>
                                            <small style="color: #6c757d;"><?= htmlspecialchars($skor['kriteria_kompetens']) ?></small>
                                        </td>
                                        <td><b><?= htmlspecialchars($skor['skor_final']) ?></b></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; padding: 15px;">Belum ada item penilaian yang terekam.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="detail-card">
                <h4 class="section-title">Catatan Assessor</h4>
                <div class="note-box">
                    <?= nl2br(htmlspecialchars($data['catatan_umum'] ?? 'Tidak ada catatan khusus dari assessor untuk penilaian ini.')) ?>
                </div>
            </div>

            <div class="detail-card">
                <h4 class="section-title">Ringkasan Hasil</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <label>Jumlah Instrumen</label>
                        <span><?= $jumlah_instrumen ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Nilai Rata-rata</label>
                        <span><?= $rata_rata ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Nilai Akhir</label>
                        <span><?= htmlspecialchars($data['skor_akhir_360'] ?? '0') ?></span>
                    </div>
                    <div class="detail-item">
                        <label>Status Kompetensi</label>
                        <span class="<?= $badge_class ?>">
                            <?= ($is_kompeten) ? 'Kompeten' : $status_akhir ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>