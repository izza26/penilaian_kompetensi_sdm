<!-- <?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$page_title = "Cetak Laporan";
$page_subtitle = "Cetak rekapitulasi hasil penilaian kompetensi pegawai";

// Filter status jika pimpinan hanya ingin mencetak yang "Kompeten" atau "Perlu Pembinaan"
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'Semua';

$where_clause = "";
if ($filter_status == 'Kompeten') {
    $where_clause = " WHERE ra.status_kompeten = 'K' ";
} elseif ($filter_status == 'Belum') {
    $where_clause = " WHERE ra.status_kompeten != 'K' ";
}

$query_laporan = "
    SELECT 
        p.pegawai_nama, 
        p.jabatan, 
        uk.judul_unit, 
        ra.skor_akhir_360, 
        ra.status_kompeten
    FROM pegawai p
    JOIN peserta_penilaian pp ON p.pegawai_id = pp.peserta_id
    JOIN rekap_aktivitas_36 ra ON pp.rekap_aktivitas_id = ra.rekap_aktivitas_id
    JOIN unit_kompetensi uk ON pp.rekap_unit_id = uk.rekap_unit_id
    $where_clause
    ORDER BY p.pegawai_nama ASC
";
$result = @pg_query($koneksi, $query_laporan);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Laporan Kompetensi | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        .filter-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .filter-group select {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-family: 'Poppins', sans-serif;
            margin-right: 10px;
        }
        .btn-filter {
            background: #333; color: #fff; padding: 8px 16px;
            border: none; border-radius: 6px; cursor: pointer;
        }
        .btn-print {
            background: #4a90e2; color: #fff; padding: 10px 20px;
            border: none; border-radius: 6px; cursor: pointer;
            font-family: 'Poppins', sans-serif; font-weight: 500;
            display: flex; align-items: center; gap: 8px;
        }
        .btn-print:hover { background: #357abd; }
        
        .report-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .report-table th, .report-table td {
            border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 14px;
        }
        .report-table th { background: #f8f9fa; color: #333; }
        
        .kop-surat { display: none; text-align: center; margin-bottom: 30px; }
        .kop-surat h2 { margin: 0; font-size: 22px; }
        .kop-surat p { margin: 5px 0; font-size: 14px; color: #555; }
        .kop-surat hr { border: 1.5px solid #000; margin-top: 15px; }

        /* MANTRA MAGIC UNTUK PRINT (Menyembunyikan elemen yang tidak perlu dicetak) */
        @media print {
            body { background: #fff; }
            .sidebar, .header, .filter-card, .menu-divider { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; }
            .app { display: block; }
            .kop-surat { display: block; } /* Munculkan Kop Surat hanya saat dicetak */
            .report-table { border: 1px solid #000; }
            .report-table th, .report-table td { border: 1px solid #000; color: #000; }
        }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="kop-surat">
            <h2>MUSEUM GEOLOGI BANDUNG</h2>
            <p>Sistem Penilaian Kompetensi Sumber Daya Manusia</p>
            <p>Jl. Diponegoro No.57, Cihaur Geulis, Kec. Cibeunying Kaler, Kota Bandung</p>
            <hr>
            <h3>Laporan Rekapitulasi Hasil Kompetensi Pegawai</h3>
            <p style="text-align: right; margin-top: 20px;">Tanggal Cetak: <?= date('d F Y') ?></p>
        </div>

        <div class="filter-card">
            <form method="GET" action="laporan.php" class="filter-group">
                <select name="status">
                    <option value="Semua" <?= $filter_status == 'Semua' ? 'selected' : '' ?>>Semua Status</option>
                    <option value="Kompeten" <?= $filter_status == 'Kompeten' ? 'selected' : '' ?>>Hanya Kompeten</option>
                    <option value="Belum" <?= $filter_status == 'Belum' ? 'selected' : '' ?>>Perlu Pembinaan</option>
                </select>
                <button type="submit" class="btn-filter">Tampilkan</button>
            </form>

            <button onclick="window.print()" class="btn-print">
                <i class="bi bi-printer"></i> Cetak Laporan PDF
            </button>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 12px;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="25%">Nama Pegawai</th>
                        <th width="20%">Jabatan</th>
                        <th width="25%">Unit Kompetensi</th>
                        <th width="10%" style="text-align:center;">Skor Akhir</th>
                        <th width="15%" style="text-align:center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($result && pg_num_rows($result) > 0) {
                        while ($row = pg_fetch_assoc($result)) {
                            $is_kompeten = ($row['status_kompeten'] == 'K');
                            $status_text = $is_kompeten ? 'Kompeten' : 'Perlu Pembinaan';
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><b><?= htmlspecialchars($row['pegawai_nama']) ?></b></td>
                                <td><?= htmlspecialchars($row['jabatan']) ?></td>
                                <td><?= htmlspecialchars($row['judul_unit']) ?></td>
                                <td style="text-align:center;"><b><?= htmlspecialchars($row['skor_akhir_360']) ?></b></td>
                                <td style="text-align:center;"><?= $status_text ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>Belum ada data untuk dicetak.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html> -->