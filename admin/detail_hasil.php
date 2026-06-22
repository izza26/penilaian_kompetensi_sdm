<?php
session_start();
require_once '../auth/cek_login.php';
require_once '../config/koneksi.php';

$page_title = "Detail Hasil Kompetensi";
$page_subtitle = "Informasi lengkap hasil kompetensi pegawai";

// 1. Tangkap ID Pegawai dari URL
$pegawai_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$pegawai_id) {
    die("Error: ID Pegawai tidak ditemukan di URL!");
}

// 2. QUERY UTAMA: Ambil Biodata & Rekap Akhir
$query_utama = "
    SELECT 
        p.pegawai_nama, 
        p.jabatan, 
        p.unit_kerja,
        uk.kode_unit, 
        (SELECT elemen_kompetensi FROM elemen_kompetensi WHERE kode_unit = uk.kode_unit LIMIT 1) as nama_elemen,
        ra.skor_akhir_360, 
        ra.status_kompeten,
        ph.waktu_submit,
        ph.catatan_umum
    FROM pegawai p
    LEFT JOIN peserta_penilaian pp ON p.pegawai_id = pp.peserta_id
    LEFT JOIN rekap_aktivitas_36 ra ON pp.rekap_aktivitas_id = ra.rekap_aktivitas_id
    LEFT JOIN unit_kompetensi uk ON pp.rekap_unit_id = uk.rekap_unit_id
    LEFT JOIN penilaian_header ph ON p.penilaian_id = ph.penilaian_id
    WHERE p.pegawai_id = $1
    LIMIT 1
";
$res_utama = pg_query_params($koneksi, $query_utama, array($pegawai_id));
$data = pg_fetch_assoc($res_utama);

if (!$data) {
    die("Data pegawai tidak ditemukan di database!");
}

// 3. QUERY DETAIL: Ambil Daftar Nilai per Aktivitas
$query_detail = "
    SELECT 
        ak.detail_aktivitas,
        pd.skor_final
    FROM pegawai p
    JOIN penilaian_header ph ON p.penilaian_id = ph.penilaian_id
    JOIN penilaian_detail pd ON ph.detail_penilaian_i = pd.detail_penilaian_i
    JOIN aktivitas_kompeten ak ON pd.aktivitas_id = ak.aktivitas_id
    WHERE p.pegawai_id = $1
";
$res_detail = pg_query_params($koneksi, $query_detail, array($pegawai_id));

$list_nilai = [];
$total_skor = 0;
if ($res_detail) {
    while ($row = pg_fetch_assoc($res_detail)) {
        $list_nilai[] = $row;
        $total_skor += (float)$row['skor_final'];
    }
}

// 4. LOGIKA PERHITUNGAN & FORMATTING
$jml_instrumen = count($list_nilai);
$rata_rata = ($jml_instrumen > 0) ? round($total_skor / $jml_instrumen, 1) : 0;

// Menentukan Status (K / Kompeten)
$is_kompeten = ($data['status_kompeten'] == 'K' || $data['status_kompeten'] == 'Kompeten');
$status_text = $is_kompeten ? 'Kompeten' : 'Belum Kompeten';
$badge_class = $is_kompeten ? 'kompeten' : 'belum';

// Membuat Inisial Nama (Misal: Budi Santoso -> BS)
$words = explode(" ", $data['pegawai_nama']);
$inisial = "";
foreach ($words as $w) {
    if (!empty($w)) $inisial .= strtoupper($w[0]);
}
$inisial = substr($inisial, 0, 2); // Ambil maksimal 2 huruf
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Hasil Kompetensi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/detail_hasil.css">
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
                    <h2>Detail Hasil Kompetensi</h2>
                    <p>Informasi lengkap hasil kompetensi pegawai.</p>
                </div>
                <a href="hasil_kompetensi.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>

            <div class="pegawai-card">
                <div class="pegawai-left">
                    <div class="avatar">
                        <?= htmlspecialchars($inisial) ?>
                    </div>
                    <div>
                        <h3><?= htmlspecialchars($data['pegawai_nama']) ?></h3>
                        <p>
                            <?= htmlspecialchars($data['jabatan']) ?> • <?= htmlspecialchars($data['unit_kerja'] ?? 'Museum Geologi') ?>
                        </p>
                        <span class="badge <?= $badge_class ?>">
                            <?= $status_text ?>
                        </span>
                    </div>
                </div>
                <div class="pegawai-right">
                    <small>Nilai Akhir</small>
                    <h2><?= htmlspecialchars($data['skor_akhir_360'] ?? '0') ?></h2>
                </div>
            </div>

            <div class="content-card">
                <h3>Informasi Kompetensi</h3>
                <div class="info-grid">
                    <div>
                        <label>Unit Kompetensi</label>
                        <span><?= htmlspecialchars($data['kode_unit'] ?? '-') ?></span>
                    </div>
                    <div>
                        <label>Elemen Kompetensi</label>
                        <span><?= htmlspecialchars($data['nama_elemen'] ?? '-') ?></span>
                    </div>
                    <div>
                        <label>Aktivitas</label>
                        <span>Seluruh Aktivitas Terkait</span>
                    </div>
                    <div>
                        <label>Tanggal Penilaian</label>
                        <span><?= htmlspecialchars($data['waktu_submit'] ?? 'Belum ada data') ?></span>
                    </div>
                    <div>
                        <label>Assessor</label>
                        <span>Tim Asesor / Admin</span>
                    </div>
                    <div>
                        <label>Jumlah Instrumen</label>
                        <span><?= $jml_instrumen ?> Pertanyaan</span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h3>Ringkasan Nilai</h3>
                <div class="summary-grid">
                    <div class="summary-item">
                        <label>Nilai Rata-rata</label>
                        <h4><?= $rata_rata ?></h4>
                    </div>
                    <div class="summary-item">
                        <label>Nilai Akhir</label>
                        <h4><?= htmlspecialchars($data['skor_akhir_360'] ?? '0') ?></h4>
                    </div>
                    <div class="summary-item">
                        <label>Status</label>
                        <span class="badge <?= $badge_class ?>">
                            <?= $status_text ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <h3>Detail Perolehan Nilai</h3>
                <div class="score-list">
                    <?php if (count($list_nilai) > 0): ?>
                        <?php foreach ($list_nilai as $n): 
                            $skor = $n['skor_final'];
                            // Asumsi skala nilai maksimal adalah 5 (1-5)
                            // Jika database Anda pakai skala 100, ubah rumus ini menjadi: $persen = $skor;
                            $persen = ($skor / 5) * 100; 
                        ?>
                            <div class="score-item">
                                <div class="score-header">
                                    <span><?= htmlspecialchars($n['detail_aktivitas']) ?></span>
                                    <strong><?= htmlspecialchars($skor) ?> / 5</strong>
                                </div>
                                <div class="progress">
                                    <div class="progress-fill" style="width: <?= $persen ?>%;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #888; padding: 20px;">
                            Belum ada detail nilai yang diinputkan oleh assessor.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="content-card">
                <h3>Catatan Assessor</h3>
                <div class="note-box">
                    <?= nl2br(htmlspecialchars($data['catatan_umum'] ?? 'Tidak ada catatan khusus dari assessor.')) ?>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>