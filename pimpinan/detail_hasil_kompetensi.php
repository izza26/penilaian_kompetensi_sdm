<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$penilaian_id = $_GET['id'] ?? '';
if (empty($penilaian_id)) {
    header("Location: hasil_kompetensi.php");
    exit;
}

// 1. Tarik Data Header Penilaian
$sql_header = "
    SELECT 
        ph.*, 
        p.pegawai_nama, 
        p.jabatan, 
        uk.judul_unit 
    FROM penilaian_header ph
    JOIN pegawai p ON ph.pegawai_id = p.pegawai_id
    JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
    WHERE ph.penilaian_id = $1
";
$q_header = pg_query_params($koneksi, $sql_header, array($penilaian_id));
$header = pg_fetch_assoc($q_header);

if (!$header) {
    echo "<script>alert('Data penilaian tidak ditemukan.'); window.location='hasil_kompetensi.php';</script>";
    exit;
}

// 2. Tarik Data Detail Penilaian (Rincian Skor)
$sql_detail = "
    SELECT 
        pd.*, 
        ak.detail_aktivitas 
    FROM penilaian_detail pd
    JOIN aktivitas_kompeten ak ON pd.aktivitas_id = ak.aktivitas_id
    WHERE pd.penilaian_id = $1
    ORDER BY ak.aktivitas_id ASC
";
$q_detail = pg_query_params($koneksi, $sql_detail, array($penilaian_id));
$details = pg_fetch_all($q_detail) ?: [];

// Warna Kategori
$badge_class = 'badge-merah';
if ($header['nilai_akhir'] >= 70) $badge_class = 'badge-hijau';
elseif ($header['nilai_akhir'] >= 55) $badge_class = 'badge-kuning';

$page_title = "Hasil Kompetensi";
$page_subtitle = "Rincian nilai aktivitas kompetensi pegawai.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rincian Penilaian | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .btn-kembali { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; background: #ffffff; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; border: 1px solid #e2e8f0; margin-bottom: 20px;}
        .btn-kembali:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1;}

        /* Summary Card */
        .summary-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;}
        .summary-left { flex: 1; }
        .summary-left h2 { margin: 0 0 5px 0; font-size: 20px; color: #0f172a; display: flex; align-items: center; gap: 8px;}
        .summary-left p { margin: 0 0 15px 0; color: #64748b; font-size: 13px; }
        
        .unit-info { background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .unit-info strong { display: block; color: #3b82f6; font-size: 12px; margin-bottom: 2px; }
        .unit-info span { color: #1e293b; font-size: 14px; font-weight: 500; }

        .summary-right { background: #f8fafc; padding: 20px 30px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; min-width: 150px;}
        .summary-right span { display: block; font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .summary-right h1 { margin: 0 0 10px 0; font-size: 32px; color: #A08348; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        .catatan-box { background: #fffbeb; padding: 15px; border-radius: 10px; border: 1px dashed #fcd34d; margin-top: 20px;}
        .catatan-box b { color: #b45309; font-size: 13px; display: block; margin-bottom: 5px;}
        .catatan-box p { margin: 0; color: #92400e; font-size: 13px; font-style: italic;}

        /* Table Card */
        .table-card { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .table-card h3 { margin: 0 0 20px 0; font-size: 16px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .styled-table tr:hover { background-color: #f8fafc; }
        
        .skor-box { background: #eff6ff; color: #3b82f6; font-size: 16px; font-weight: 700; width: 36px; height: 36px; display: inline-flex; justify-content: center; align-items: center; border-radius: 8px; border: 1px solid #bfdbfe; }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <a href="hasil_kompetensi.php" class="btn-kembali">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <!-- Kartu Ringkasan Atas -->
        <div class="summary-card">
            <div class="summary-left">
                <h2><i class="bi bi-person-badge" style="color: #3b82f6;"></i> <?= htmlspecialchars($header['pegawai_nama']) ?></h2>
                <p><?= htmlspecialchars($header['jabatan']) ?></p>
                
                <div class="unit-info">
                    <strong>[<?= htmlspecialchars($header['kode_unit']) ?>]</strong>
                    <span><?= htmlspecialchars($header['judul_unit']) ?></span>
                </div>

                <?php if (!empty($header['rekomendasi'])): ?>
                <div class="catatan-box">
                    <b><i class="bi bi-chat-quote-fill"></i> Catatan Pimpinan:</b>
                    <p>"<?= htmlspecialchars($header['rekomendasi']) ?>"</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="summary-right">
                <span>Nilai Akhir (Skala 100)</span>
                <h1><?= number_format($header['nilai_akhir'], 2) ?></h1>
                <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($header['kategori']) ?></span>
                <div style="font-size: 11px; color: #94a3b8; margin-top: 10px; font-weight: normal; text-transform: none;">
                    Disubmit pada:<br><?= date('d M Y, H:i', strtotime($header['waktu_submit'])) ?> WIB
                </div>
            </div>
        </div>

        <!-- Tabel Rincian Skor Per Aktivitas -->
        <div class="table-card">
            <h3><i class="bi bi-list-check" style="color: #3b82f6; margin-right: 8px;"></i> Rincian Skor per Aktivitas</h3>
            
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">No</th>
                        <th width="15%">ID Aktivitas</th>
                        <th width="65%">Detail Aktivitas Kompetensi</th>
                        <th width="15%" style="text-align: center;">Skor (1-5)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($details)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 30px; color: #94a3b8;">Tidak ada rincian yang ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($details as $d): ?>
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600;"><?= $no++ ?></td>
                                <td><b style="color: #1e293b;"><?= htmlspecialchars($d['aktivitas_id']) ?></b></td>
                                <td style="line-height: 1.5; color: #334155;"><?= htmlspecialchars($d['detail_aktivitas']) ?></td>
                                <td style="text-align: center;">
                                    <div class="skor-box"><?= $d['skor_final'] ?></div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>