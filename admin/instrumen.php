<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Instrumen";
$page_subtitle = "Kelola instrumen penilaian kompetensi";

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

$base_query = "
    SELECT 
        ak.aktivitas_id AS id,
        j.nama_jabatan AS jabatan,
        uk.kode_unit,
        ek.elemen_kompetensi,
        ak.detail_aktivitas,
        uk.aktif,
        (SELECT COUNT(*) FROM rubrik_skor rp WHERE rp.aktivitas_id = ak.aktivitas_id) AS jumlah_instrumen
    FROM 
        aktivitas_kompeten ak
    JOIN 
        elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    LEFT JOIN 
        unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN 
        jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN 
        jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
";

if ($kata_kunci != '') {
    $query_sql = $base_query . " 
        WHERE j.nama_jabatan ILIKE $1 
           OR uk.kode_unit ILIKE $1 
           OR ek.elemen_kompetensi ILIKE $1
           OR ak.detail_aktivitas ILIKE $1
        ORDER BY uk.kode_unit ASC, ak.aktivitas_id ASC";
            
    $params = array('%' . $kata_kunci . '%');
    $result = pg_query_params($koneksi, $query_sql, $params);
} else {
    $query_sql = $base_query . " ORDER BY uk.kode_unit ASC, ak.aktivitas_id ASC";
    $result = pg_query($koneksi, $query_sql);
}

// Cek error database
$error_db = "";
if (!$result) {
    $error_db = pg_last_error($koneksi);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Instrumen</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
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
                    <h2>Daftar Instrumen</h2>
                    <p>Kelola pertanyaan instrumen penilaian berdasarkan aktivitas kompetensi.</p>
                </div>
            </div>

            <?php if($error_db != ""): ?>
                <div style='background-color: #ffebee; color: #c62828; padding: 15px; margin-bottom: 20px; border-radius: 8px;'>
                    <strong>Error Database:</strong> <?= htmlspecialchars($error_db) ?>
                </div>
            <?php endif; ?>

            <div class="table-tools">
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="cari"
                        class="search-input" 
                        placeholder="Cari instrumen, aktivitas, atau unit..."
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
                            <th>Jabatan</th>
                            <th>Kode Unit</th>
                            <th>Elemen</th>
                            <th>Aktivitas</th>
                            <th>Jumlah Instrumen</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result && pg_num_rows($result) > 0) {
                            while ($row = pg_fetch_assoc($result)) {
                                $status_db = $row['aktif'] ?? 'Y';
                                $status_text = ($status_db == 'Y') ? 'Aktif' : 'Nonaktif';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['kode_unit'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['elemen_kompetensi'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['detail_aktivitas'] ?? '-') ?></td>
                                    
                                    <td>
                                        <?= htmlspecialchars($row['jumlah_instrumen'] ?? '0') ?> Pertanyaan
                                    </td>
                                    
                                    <td>
                                        <span class="status"><?= htmlspecialchars($status_text) ?></span>
                                    </td>
                                    
                                    <td class="action-cell">
                                        <a href="detail_instrumen.php?id=<?= $row['id'] ?>" class="action-btn view-btn" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="edit_instrumen.php?id=<?= $row['id'] ?>" class="action-btn edit-btn" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <a href="hapus_instrumen.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" title="Hapus" onclick="return confirm('Yakin ingin menghapus instrumen ini?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='8' style='text-align:center; padding: 20px;'>Data instrumen belum tersedia atau tidak ditemukan.</td></tr>";
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