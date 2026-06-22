<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Penilaian";
$page_subtitle = "Kelola proses penilaian kompetensi pegawai";

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

$base_query = "
    SELECT 
        p.pegawai_id,
        p.pegawai_nama,
        p.jabatan,
        uk.kode_unit,
        uk.judul_unit,
        (SELECT COUNT(*) 
         FROM rubrik_skor rs 
         JOIN aktivitas_kompeten ak ON rs.aktivitas_id = ak.aktivitas_id
         JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
         WHERE ek.kode_unit = uk.kode_unit) AS jumlah_instrumen
    FROM pegawai p
    LEFT JOIN jabatan j ON UPPER(p.jabatan) = UPPER(j.nama_jabatan)
    LEFT JOIN jabatan_unit_kompe juk ON j.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN unit_kompetensi uk ON juk.jabatan_unit_id = uk.jabatan_unit_id
";

if ($kata_kunci != '') {
    $query_sql = $base_query . " 
        WHERE p.pegawai_nama ILIKE $1 
           OR uk.kode_unit ILIKE $1 
           OR p.jabatan ILIKE $1
        ORDER BY p.pegawai_id DESC";
            
    $params = array('%' . $kata_kunci . '%');
    $result = pg_query_params($koneksi, $query_sql, $params);
} else {
    $query_sql = $base_query . " ORDER BY p.pegawai_id DESC";
    $result = pg_query($koneksi, $query_sql);
}

if (!$result) {
    die("<div style='color:red; padding:20px;'>Query Error: " . pg_last_error($koneksi) . "</div>");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Penilaian</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/penilaian.css">
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
                    <h2>Daftar Penilaian</h2>
                    <p>Kelola proses penilaian kompetensi pegawai berdasarkan instrumen yang telah dibuat.</p>
                </div>
            </div>

            <div class="table-tools">
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="cari"
                        class="search-input" 
                        placeholder="Cari nama pegawai atau unit kompetensi..."
                        value="<?= htmlspecialchars($kata_kunci) ?>"
                        onchange="this.form.submit()"
                    >
                </form>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Pegawai</th>
                            <th>Jabatan</th>
                            <th>Unit Kompetensi</th>
                            <th>Jumlah Instrumen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if (pg_num_rows($result) > 0) {
                            while ($row = pg_fetch_assoc($result)) {
                                $kode_unit = $row['kode_unit'];
                                $jumlah_instrumen = $row['jumlah_instrumen'];
                                
                                // --- LOGIKA STATUS & TOMBOL CERDAS ---
                                if (!$kode_unit) {
                                    // Skenario 1: Jabatan tidak ada di master tabel (Belum dipetakan)
                                    $teks_unit = "<span style='color:#dc3545; font-size:12px;'><i class='bi bi-exclamation-triangle'></i> Belum Dipetakan</span>";
                                    $jml_teks = "-";
                                    $status = "Menunggu Mapping";
                                    $badge_class = "pending";
                                    $tombol = "<button class='btn-start-assessment' style='background-color:#ced4da; color:#6c757d; cursor:not-allowed;' disabled><i class='bi bi-lock-fill'></i> Terkunci</button>";
                                } else {
                                    $teks_unit = htmlspecialchars($kode_unit);
                                    $jml_teks = $jumlah_instrumen . " Pertanyaan";
                                    
                                    if ($jumlah_instrumen > 0) {
                                        // Skenario 2: Rubrik sudah siap, bisa dimulai!
                                        $status = "Belum Dinilai"; 
                                        $badge_class = "pending";
                                        $tombol = "<a href='form_penilaian.php?id=" . $row['pegawai_id'] . "&unit=" . $row['kode_unit'] . "' class='btn-start-assessment'><i class='bi bi-play-fill'></i> Mulai</a>";
                                    } else {
                                        // Skenario 3: Unit ada, tapi rubrik belum diinput di menu Instrumen
                                        $status = "Instrumen Kosong";
                                        $badge_class = "pending";
                                        $tombol = "<button class='btn-start-assessment' style='background-color:#ced4da; color:#6c757d; cursor:not-allowed;' title='Isi rubrik dulu di menu Instrumen' disabled><i class='bi bi-lock-fill'></i> Terkunci</button>";
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><b><?= htmlspecialchars($row['pegawai_nama']); ?></b></td>
                                    <td><?= htmlspecialchars($row['jabatan'] ?? '-'); ?></td>
                                    <td><?= $teks_unit; ?></td>
                                    <td><?= $jml_teks; ?></td>
                                    <td><span class="badge <?= $badge_class ?>"><?= $status ?></span></td>
                                    <td class="action-cell">
                                        <div class="action-group">
                                            <?= $tombol ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding:20px;'>Belum ada data pegawai untuk dinilai.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <button class="page-btn"><i class="bi bi-chevron-left"></i> Sebelumnya</button>
                    <div class="page-numbers">
                        <button class="page-number active">1</button>
                    </div>
                    <button class="page-btn">Selanjutnya <i class="bi bi-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>