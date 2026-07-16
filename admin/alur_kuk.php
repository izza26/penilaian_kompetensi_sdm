<?php
session_start();
require_once '../auth/cek_login.php';
require_once '../config/koneksi.php';

$page_title = "Alur KUK";
$page_subtitle = "Kelola alur kompetensi dan instrumen penilaian";

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

$base_query = "
    SELECT 
        ek.elemen_id AS id,
        j.nama_jabatan AS jabatan,
        uk.kode_unit,
        ek.elemen_kompetensi,
        COUNT(ak.aktivitas_id) AS jumlah_aktivitas,
        uk.aktif
    FROM 
        elemen_kompetensi ek
    LEFT JOIN 
        unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    LEFT JOIN 
        jabatan_unit_kompe juk ON uk.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN 
        jabatan j ON juk.jabatan_unit_id = j.jabatan_unit_id
    LEFT JOIN 
        aktivitas_kompeten ak ON ek.elemen_id = ak.elemen_id
";

if ($kata_kunci != '') {
    $query_sql = $base_query . " 
        WHERE j.nama_jabatan ILIKE $1 
           OR uk.kode_unit ILIKE $1 
           OR ek.elemen_kompetensi ILIKE $1
        GROUP BY 
            ek.elemen_id, j.nama_jabatan, uk.kode_unit, ek.elemen_kompetensi, uk.aktif
        ORDER BY 
            ek.elemen_id ASC";
            
    $params = array('%' . $kata_kunci . '%');
    $result = pg_query_params($koneksi, $query_sql, $params);
} else {
    $query_sql = $base_query . " 
        GROUP BY 
            ek.elemen_id, j.nama_jabatan, uk.kode_unit, ek.elemen_kompetensi, uk.aktif
        ORDER BY 
            ek.elemen_id ASC";
            
    $result = pg_query($koneksi, $query_sql);
}

if (!$result) {
    $error_msg = pg_last_error($koneksi);
    echo "<div style='background-color: #ffebee; color: #c62828; padding: 15px; margin: 20px; border-radius: 8px;'>";
    echo "<strong>Error Database:</strong> " . htmlspecialchars($error_msg);
    echo "</div>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Alur KUK</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/alur_kuk.css">
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
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="cari"
                        class="search-input" 
                        placeholder="Cari aktivitas, kode unit, atau jabatan..."
                        value="<?= htmlspecialchars($kata_kunci) ?>"
                        onchange="this.form.submit()"
                    >
                </form>
                
                <a href="tambah_alur_kuk.php" class="btn-primary">
                    + Tambah Alur KUK
                </a>
            </div>

            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jabatan</th>
                            <th>Kode Unit</th>
                            <th>Elemen Kompetensi</th>
                            <th>Jumlah Aktivitas</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result && pg_num_rows($result) > 0) {
                            while ($row = pg_fetch_assoc($result)) {
                                // Menerjemahkan kode 'Y'/'N' dari database menjadi teks
                                $status_db = $row['aktif'] ?? 'Y';
                                $status_text = ($status_db == 'Y') ? 'Aktif' : 'Nonaktif';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($row['jabatan'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['kode_unit'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['elemen_kompetensi'] ?? '-') ?></td>
                                    
                                    <td>
                                        <?= htmlspecialchars($row['jumlah_aktivitas'] ?? '0') ?> Aktivitas
                                    </td>
                                    
                                    <td>
                                        <span class="status">
                                            <?= htmlspecialchars($status_text) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="action-group">
                                            <a href="detail_alur_kuk.php?id=<?= $row['id'] ?>&mode=view" class="action-btn view-btn" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            
                                            <a href="detail_alur_kuk.php?id=<?= $row['id'] ?>&mode=edit" class="action-btn edit-btn" title="Pilih Aktivitas untuk Diedit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            
                                            <a href="hapus_alur_kuk.php?id=<?= $row['id'] ?>" class="action-btn delete-btn" title="Hapus" onclick="return confirm('Yakin ingin menghapus Elemen Kompetensi ini?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>Data Alur KUK belum tersedia atau tidak ditemukan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <button class="page-btn">
                        <i class="bi bi-chevron-left"></i> Sebelumnya
                    </button>
                    <div class="page-numbers">
                        <button class="page-number active">1</button>
                    </div>
                    <button class="page-btn">
                        Selanjutnya <i class="bi bi-chevron-right"></i>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>