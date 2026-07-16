<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pegawai');
require_once '../config/koneksi.php';

$page_title = "Detail Penilaian Evidence";
$page_subtitle = "Rincian evidence dan hasil evaluasi penilai";

$idPegawai = $_SESSION['pegawai_id'];
$aktivitas_id = $_GET['aktivitas_id'] ?? '';
$role = $_GET['role'] ?? 'pimpinan'; // TANGKAP ROLE DISINI

if (empty($aktivitas_id)) {
    echo "<script>alert('ID Aktivitas tidak ditemukan!'); window.history.back();</script>";
    exit;
}

$role_label = ucfirst($role);
if ($role == 'diri') $role_label = 'Diri Sendiri';
elseif ($role == 'rekan') $role_label = 'Rekan Sejawat';

/* ==========================================================
   PILIH KOLOM SKOR BERDASARKAN ROLE
========================================================== */
$kolom_skor = 'pd.skor_final';
if ($role == 'rekan') {
    $kolom_skor = 'pd.skor_sejawat';
} elseif ($role == 'diri') {
    $kolom_skor = 'pd.skor_self';
}

/* ==========================================================
   QUERY DATA AKTIVITAS, FILE TERBARU, DAN NILAI ROLE
========================================================== */
$sqlDetail = "
    SELECT 
        ak.aktivitas_id, 
        ak.detail_aktivitas,
        uk.kode_unit, 
        uk.judul_unit,
        (SELECT file_path FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1 ORDER BY tanggal_upload DESC LIMIT 1) as file_path,
        (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1 ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload,
        $kolom_skor as skor_tampil,
        ph.status as status_penilaian,
        ph.rekomendasi
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN penilaian_header ph ON uk.kode_unit = ph.kode_unit AND ph.pegawai_id = $1
    LEFT JOIN penilaian_detail pd ON ph.penilaian_id = pd.penilaian_id AND pd.aktivitas_id = ak.aktivitas_id
    WHERE ak.aktivitas_id = $2
    LIMIT 1
";

$qDetail = pg_query_params($koneksi, $sqlDetail, array($idPegawai, $aktivitas_id));
$data = $qDetail ? pg_fetch_assoc($qDetail) : null;

if (!$data || empty($data['file_path'])) {
    echo "<script>alert('Aktivitas tidak ditemukan atau Anda belum mengunggah file!'); window.location='penilaian.php';</script>";
    exit;
}

// Logika Menampilkan Skor 1-5 berdasarkan Database ROLE TERKAIT
$skor_evidence = null;
$kategori = '-';
$catatan_penilai = 'Belum ada catatan evaluasi dari ' . $role_label . '.';

$bg_color = '#f1f5f9'; 
$text_color = '#64748b';

if (!empty($data['skor_tampil'])) {
    $skor_evidence = (int)$data['skor_tampil'];
    
    // Mapping Rubrik 1-5
    $teks_skor = [
        1 => "Tidak Ada Dokumen",
        2 => "1 Dokumen Tersedia",
        3 => "2 Dokumen Tersedia",
        4 => "Lengkap & Relevan",
        5 => "Lengkap & Tervalidasi"
    ];
    $kategori = $teks_skor[$skor_evidence] ?? "Dinilai";
    $catatan_penilai = !empty($data['rekomendasi']) ? $data['rekomendasi'] : "Penilaian telah disahkan oleh ".$role_label.".";
    
    // Pewarnaan dinamis
    if ($skor_evidence >= 4) {
        $bg_color = '#e9f8ee'; $text_color = '#16a34a';
    } elseif ($skor_evidence == 3) {
        $bg_color = '#fffbeb'; $text_color = '#d97706';
    } else {
        $bg_color = '#fee2e2'; $text_color = '#dc2626';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pegawai/penilaian_detail.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php";
    $btn_kembali = "penilaian_list.php";
     ?>
    <div class="main-content">
        <?php include "../layouts/header.php"; ?>


        <div class="row g-4">
            <!-- SISI KIRI (FILE EVIDENCE) -->
            <div class="col-lg-7">
                <div class="detail-card">
                    <div class="detail-title">
                        <i class="bi bi-info-circle" style="color: #b56c35;"></i> Informasi Kompetensi
                    </div>
                    <div class="info-group">
                        <div class="info-label">Unit Kompetensi</div>
                        <div class="info-value">
                            <span class="badge" style="background: #e2e8f0; color: #475569; margin-bottom: 5px;">
                                <?= htmlspecialchars($data['kode_unit']) ?>
                            </span><br>
                            <?= htmlspecialchars($data['judul_unit']) ?>
                        </div>
                    </div>
                    <div class="info-group mb-0">
                        <div class="info-label">Terkait Aktivitas Penilaian</div>
                        <div class="info-value" style="color: #b56c35; font-weight: 600;">
                            <?= htmlspecialchars($data['detail_aktivitas']) ?>
                        </div>
                    </div>
                </div>

                <div class="detail-card">
                    <div class="detail-title">
                        <i class="bi bi-paperclip" style="color: #b56c35;"></i> File yang Diupload
                    </div>
                    <div class="file-preview-box">
                        <div class="file-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <h4 style="margin: 0 0 5px 0; font-size: 15px; color: #1e293b;">Dokumen Evidence Terlampir</h4>
                        <p style="margin: 0 0 15px 0; font-size: 12px; color: #64748b;">
                            Terakhir diunggah pada: <?= date('d M Y, H:i', strtotime($data['tgl_upload'])) ?>
                        </p>
                        <!-- Peringatan: Path ini sama persis dengan yang di Pimpinan. Pastikan file fisiknya tidak terhapus -->
                        <a href="../uploads/evidence/<?= htmlspecialchars($data['file_path']) ?>" target="_blank" class="btn-download">
                            <i class="bi bi-cloud-arrow-down-fill"></i> Pratinjau / Unduh Dokumen
                        </a>
                    </div>
                </div>
            </div>

            <!-- SISI KANAN (HASIL SKOR) -->
            <div class="col-lg-5">
                <div class="detail-card" style="height: 100%;">
                    <div class="detail-title">
                        <i class="bi bi-award-fill" style="color: #b56c35;"></i> Hasil Skor <?= htmlspecialchars($role_label) ?>
                    </div>

                    <?php if (empty($skor_evidence)): ?>
                        <div style="text-align: center; padding: 40px 20px;">
                            <i class="bi bi-hourglass-split" style="font-size: 40px; color: #cbd5e1;"></i>
                            <h4 style="margin: 15px 0 5px 0; font-size: 15px; color: #475569;">Sedang Dalam Proses Review</h4>
                            <p style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($role_label) ?> belum memberikan evaluasi skor untuk aktivitas ini.</p>
                        </div>
                    <?php else: ?>
                        <div class="score-box">
                            <div class="score-circle">
                                <!-- Cetak format angka "4 / 5" -->
                                <div class="score-number" style="font-size: 32px;">
                                    <?= $skor_evidence ?> <span style="font-size: 16px; color:#94a3b8;">/ 5</span>
                                </div>
                            </div>
                            <span class="badge" style="background: <?= $bg_color ?>; color: <?= $text_color ?>; font-size: 13px; margin-bottom: 5px;">
                                <?= $kategori ?>
                            </span>
                        </div>

                        <div class="info-group mt-4">
                            <div class="info-label"><i class="bi bi-chat-quote-fill me-1"></i> Catatan Akhir Unit</div>
                            <div class="feedback-box">
                                "<?= htmlspecialchars($catatan_penilai) ?>"
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>