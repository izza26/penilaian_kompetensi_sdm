<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Edit Instrumen";
$page_subtitle = "Perbarui instrumen penilaian kompetensi";

$id_aktivitas = isset($_GET['id']) ? $_GET['id'] : null;

if (!$id_aktivitas) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: ID Aktivitas tidak ditemukan di URL!</div>");
}

$query = "
    SELECT 
        ak.aktivitas_id, ak.detail_aktivitas, ak.bobot_aktivitas,
        ek.elemen_kompetensi,
        uk.kode_unit, uk.judul_unit,
        j.nama_jabatan,
        rs.deskripsi_skor, rs.aturan_rubrik, rs.jenis_file, rs.verifikator
    FROM aktivitas_kompeten ak
    LEFT JOIN rubrik_skor rs ON ak.aktivitas_id = rs.aktivitas_id
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
    WHERE ak.aktivitas_id = $1
";
$result = pg_query_params($koneksi, $query, array($id_aktivitas));
$data = pg_fetch_assoc($result);

if (!$data) {
    die("<div style='color:red; padding:20px; text-align:center;'>Error: Data aktivitas tidak ditemukan!</div>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_instrumen'])) {
    
    $deskripsi  = $_POST['deskripsi_skor'];
    $aturan     = $_POST['aturan_rubrik'];
    $jenis_file = $_POST['jenis_file'];
    $verifikator= $_POST['verifikator'];
    $bobot      = $_POST['bobot']; // Bobot disimpennya ke aktivitas_kompeten

    $sql_bobot = "UPDATE aktivitas_kompeten SET bobot_aktivitas = $1 WHERE aktivitas_id = $2";
    pg_query_params($koneksi, $sql_bobot, array($bobot, $id_aktivitas));

    $cek_rubrik = pg_query_params($koneksi, "SELECT rubrik_id FROM rubrik_skor WHERE aktivitas_id = $1", array($id_aktivitas));
    
    if (pg_num_rows($cek_rubrik) > 0) {
        $sql = "UPDATE rubrik_skor SET deskripsi_skor = $1, aturan_rubrik = $2, jenis_file = $3, verifikator = $4 WHERE aktivitas_id = $5";
        $res = pg_query_params($koneksi, $sql, array($deskripsi, $aturan, $jenis_file, $verifikator, $id_aktivitas));
    } else {
        $sql = "INSERT INTO rubrik_skor (aktivitas_id, deskripsi_skor, aturan_rubrik, jenis_file, verifikator) VALUES ($1, $2, $3, $4, $5)";
        $res = pg_query_params($koneksi, $sql, array($id_aktivitas, $deskripsi, $aturan, $jenis_file, $verifikator));
    }

    if ($res) {
        echo "<script>alert('Instrumen berhasil disimpan!'); window.location.href='detail_instrumen.php?id=" . $id_aktivitas . "';</script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan data: " . pg_last_error($koneksi) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Instrumen</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/edit_instrumen.css">
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
                    <h2>Edit Instrumen</h2>
                    <p>Perbarui rubrik penilaian dan spesifikasi aktivitas.</p>
                </div>
                <a href="detail_instrumen.php?id=<?= $data['aktivitas_id'] ?>" class="btn-secondary">Batal / Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">
                    
                    <!-- DATA INDUK BACA SAJA -->
                    <div class="form-group">
                        <label>Jabatan</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['nama_jabatan'] ?? '-') ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Unit Kompetensi</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['kode_unit'] . ' - ' . $data['judul_unit']) ?>" readonly>
                    </div>

                    <div class="form-group full-width">
                        <label>Aktivitas</label>
                        <input type="text" class="readonly-input" value="<?= htmlspecialchars($data['detail_aktivitas'] ?? '-') ?>" readonly>
                    </div>

                    <!-- FORM INPUT UTAMA -->
                    <div class="form-group full-width">
                        <label>Deskripsi Rubrik / Pertanyaan</label>
                        <textarea rows="4" name="deskripsi_skor" required><?= htmlspecialchars($data['deskripsi_skor'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Aturan Penilaian / Keterangan</label>
                        <textarea rows="4" name="aturan_rubrik"><?= htmlspecialchars($data['aturan_rubrik'] ?? '') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Bobot Aktivitas (%)</label>
                        <!-- Bobot ini akan mengupdate tabel aktivitas_kompeten -->
                        <input type="number" name="bobot" value="<?= htmlspecialchars($data['bobot_aktivitas'] ?? '0') ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Jenis Bukti (Evidence)</label>
                        <input type="text" name="jenis_file" value="<?= htmlspecialchars($data['jenis_file'] ?? '') ?>" placeholder="Misal: PDF Dokumen Kajian">
                    </div>

                    <div class="form-group full-width">
                        <label>Verifikator / Assessor</label>
                        <input type="text" name="verifikator" value="<?= htmlspecialchars($data['verifikator'] ?? '') ?>" placeholder="Misal: Kurator Senior / Admin">
                    </div>

                </div>

                <div class="form-footer">
                    <button type="submit" name="simpan_instrumen" class="btn-primary">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>