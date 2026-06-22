<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Edit Alur KUK";
$page_subtitle = "Perbarui data alur kompetensi dan instrumen penilaian";

$id_aktivitas = isset($_GET['id_aktivitas']) ? $_GET['id_aktivitas'] : null;

if (!$id_aktivitas) {
    die("Error: ID Aktivitas tidak ditemukan di URL!");
}

$query = "
    SELECT 
        ak.*, 
        ek.elemen_kompetensi, ek.kode_elemen_excel, ek.input_output_outco,
        uk.kode_unit, uk.judul_unit, uk.jenis_kompetensi,
        j.nama_jabatan
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
    WHERE ak.aktivitas_id = $1
";
$result = pg_query_params($koneksi, $query, array($id_aktivitas));
$data = pg_fetch_assoc($result);

if (!$data) {
    die("Data Aktivitas tidak ditemukan di database!");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_aktivitas'])) {
    $detail_aktivitas = $_POST['detail_aktivitas'];
    $kriteria         = $_POST['kriteria_kompetens'];
    $bobot            = $_POST['bobot'];

    $update_sql = "UPDATE aktivitas_kompeten SET detail_aktivitas = $1, kriteria_kompetens = $2, bobot_aktivitas = $3 WHERE aktivitas_id = $4";
    $res = pg_query_params($koneksi, $update_sql, array($detail_aktivitas, $kriteria, $bobot, $id_aktivitas));

    if ($res) {
        echo "<script>alert('Data Aktivitas berhasil diperbarui!'); window.location.href='detail_alur_kuk.php?id=" . $data['elemen_id'] . "&mode=edit';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Alur KUK</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/alur_kuk.css">
    <link rel="stylesheet" href="../assets/css/css_admin/tambah_alur_kuk.css">
    <link rel="stylesheet" href="../assets/css/css_admin/edit_alur_kuk.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .readonly-input { background-color: #f1f3f5; cursor: not-allowed; border: 1px solid #ced4da; }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Edit Aktivitas KUK</h2>
                    <p>Perbarui informasi aktivitas dan instrumen penilaian spesifik.</p>
                </div>
                <a href="detail_alur_kuk.php?id=<?= $data['elemen_id'] ?>" class="btn-secondary">Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">

                    <div class="form-group"><label>Jabatan</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['nama_jabatan'] ?? '-') ?>" readonly>
                    </div>
                    <div class="form-group"><label>Unit Kompetensi</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['kode_unit'] . ' - ' . $data['judul_unit']) ?>" readonly>
                    </div>
                    <div class="form-group"><label>Jenis Kompetensi</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['jenis_kompetensi']) ?>" readonly>
                    </div>
                    <div class="form-group"><label>Elemen Kompetensi</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['elemen_kompetensi']) ?>" readonly>
                    </div>
                    <div class="form-group full-width"><label>Kode Aktivitas (ID)</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['aktivitas_id']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Aktivitas / Proses</label>
                        <input type="text" name="detail_aktivitas" value="<?= htmlspecialchars($data['detail_aktivitas']) ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Ukuran Kinerja / KUK / Kriteria Kompeten</label>
                        <textarea rows="4" name="kriteria_kompetens" required><?= htmlspecialchars($data['kriteria_kompetens']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Bobot (%)</label>
                        <input type="number" name="bobot" value="<?= htmlspecialchars($data['bobot_aktivitas']) ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Evidence Wajib</label>
                        <div id="evidence-wrapper">
                            <div class="evidence-item">
                                <input type="text" value="Sistem akan mengelola ini via Modul Evidence terpisah" class="readonly-input" readonly>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" name="update_aktivitas" class="btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>