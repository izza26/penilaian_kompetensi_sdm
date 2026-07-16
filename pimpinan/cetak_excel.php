<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

// Tangkap parameter filter periode/jabatan
$periode = $_GET['periode'] ?? 'ALL';
$filterCond = "";
$params = [];

// Penamaan file dinamis berdasarkan filter
if ($periode !== 'ALL') {
    $filterCond = " AND p.jabatan = $1 ";
    $params[] = $periode;
    $nama_file = "Rekap_Penilaian_" . str_replace(" ", "_", $periode) . ".xls";
} else {
    $nama_file = "Rekap_Penilaian_Semua_Pegawai.xls";
}

// =========================================================================
// HEADER UNTUK FORCE DOWNLOAD EXCEL
// =========================================================================
header("Content-Type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=\"$nama_file\"");
header("Pragma: no-cache");
header("Expires: 0");

// =========================================================================
// TARIK DATA DARI DATABASE
// =========================================================================
$sql = "
    SELECT 
        ph.penilaian_id,
        p.pegawai_nama,
        p.jabatan,
        ph.kode_unit,
        uk.judul_unit,
        ph.nilai_akhir,
        ph.kategori,
        ph.rekomendasi,
        ph.waktu_submit
    FROM penilaian_header ph
    JOIN pegawai p ON ph.pegawai_id = p.pegawai_id
    JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
    WHERE ph.status = 'Selesai' $filterCond
    ORDER BY p.jabatan ASC, p.pegawai_nama ASC, ph.waktu_submit DESC
";

if (count($params) > 0) {
    $q_data = pg_query_params($koneksi, $sql, $params);
} else {
    $q_data = pg_query($koneksi, $sql);
}

$list_data = pg_fetch_all($q_data) ?: [];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>

<!-- Struktur Tabel yang akan dikonversi menjadi Excel -->
<table border="1" cellpadding="5" cellspacing="0">
    <thead>
        <tr>
            <th colspan="9" style="font-size: 16px; font-weight: bold; text-align: center; background-color: #d1d5db;">
                REKAPITULASI HASIL PENILAIAN KOMPETENSI SDM
            </th>
        </tr>
        <tr>
            <th colspan="9" style="text-align: center; background-color: #f3f4f6;">
                Museum Geologi | Filter Jabatan/Periode: <?= htmlspecialchars($periode) ?>
            </th>
        </tr>
        <tr>
            <th colspan="9" style="text-align: left; font-style: italic;">
                Diunduh pada: <?= date('d M Y, H:i') ?> WIB
            </th>
        </tr>
        <tr></tr> <!-- Baris kosong sebagai pemisah -->
        
        <tr style="background-color: #cbd5e1; font-weight: bold; text-align: center;">
            <th width="50">No</th>
            <th width="200">Nama Pegawai</th>
            <th width="150">Jabatan</th>
            <th width="120">Kode Unit</th>
            <th width="350">Judul Unit Kompetensi</th>
            <th width="100">Nilai Akhir (Skala 100)</th>
            <th width="150">Kategori / Predikat</th>
            <th width="200">Waktu Penilaian</th>
            <th width="350">Catatan / Rekomendasi Pimpinan</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($list_data)): ?>
            <tr>
                <td colspan="9" style="text-align: center; color: red;">Tidak ada data penilaian untuk periode/jabatan ini.</td>
            </tr>
        <?php else: ?>
            <?php $no = 1; foreach ($list_data as $row): ?>
            <tr>
                <td style="text-align: center;"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['pegawai_nama']) ?></td>
                <td><?= htmlspecialchars($row['jabatan']) ?></td>
                <td style="text-align: center;"><?= htmlspecialchars($row['kode_unit']) ?></td>
                <td><?= htmlspecialchars($row['judul_unit']) ?></td>
                
                <!-- Format angka desimal agar rapi di excel -->
                <td style="text-align: center;"><?= number_format($row['nilai_akhir'], 2) ?></td>
                
                <td style="text-align: center;"><?= htmlspecialchars($row['kategori']) ?></td>
                <td style="text-align: center;"><?= date('d/m/Y H:i', strtotime($row['waktu_submit'])) ?></td>
                <td><?= htmlspecialchars($row['rekomendasi']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>