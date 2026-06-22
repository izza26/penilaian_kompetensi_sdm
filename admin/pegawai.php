<?php 
session_start();
require_once '../config/koneksi.php';

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

if ($kata_kunci != '') {
    $query_sql = "SELECT * FROM pegawai 
                  WHERE pegawai_nama ILIKE '%$kata_kunci%' 
                  OR nip_nik ILIKE '%$kata_kunci%' 
                  OR unit_kerja ILIKE '%$kata_kunci%' 
                  ORDER BY pegawai_id DESC";
} else {
    $query_sql = "SELECT * FROM pegawai ORDER BY pegawai_id DESC";
}

$query_pegawai = pg_query($koneksi, $query_sql);

// Variabel untuk layout
$page_title = "Data Pegawai";
$page_subtitle = "Kelola data pegawai Museum Geologi";
?>

<!DOCTYPE html>
<html>

<head>

    <title>Data Pegawai</title>

    <link rel="stylesheet"
    href="../assets/css/css_admin/layout.css">

    <link rel="stylesheet"
    href="../assets/css/css_admin/pegawai.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>

<body>

<div class="app">

    <?php include '../layouts/sidebar_admin.php';
    
    $page_title = "Data Pegawai";
    $page_subtitle = "Kelola data pegawai Museum Geologi";
    ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="page-card">

            <div class="page-header">

                <div class="page-title">

                    <h2>Daftar Pegawai</h2>

                    <p>
                        Kelola informasi pegawai yang terlibat dalam proses penilaian kompetensi.
                    </p>

                </div>

                <a href="tambah_pegawai.php" class="btn-primary">
                    + Tambah Pegawai
                </a>

            </div>

            <div class="table-tools">

                <form action="" method="GET" style="display: inline-block;">
                    <input 
                        type="text" 
                        name="cari" 
                        placeholder="Cari pegawai..." 
                        value="<?= isset($_GET['cari']) ? $_GET['cari'] : ''; ?>" 
                        class="search-input" 
                    >
                    <button type="submit" style="display: none;">Cari</button>
                </form>

                <?php if(isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                    <a href="pegawai.php" style="margin-left: 10px; color: red; text-decoration: none; font-size: 14px;">(X) Reset Pencarian</a>
                <?php endif; ?>

            </div>

            <div class="table-wrapper">

                <table class="data-table">

                    <thead>

                        <tr>
                            <th>No</th>
                            <th>NIP</th>
                            <th>Nama Pegawai</th>
                            <th>Jabatan</th>
                            <th>Unit Kerja</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>

                    </thead>

                    <tbody>
                        <?php 
                        $no = 1; 
                        
                        while($row = pg_fetch_assoc($query_pegawai)) { 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= $row['nip_nik']; ?></td>
                            <td><?= $row['pegawai_nama']; ?></td>
                            
                            <td><?= htmlspecialchars($row['jabatan'] ?? '-'); ?></td> 
                            
                            <td><?= $row['unit_kerja']; ?></td>
                            
                            <td><span class="status-badge aktif">Aktif</span></td> 
                            
                            <td class="action-buttons">
                                <a href="detail_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="btn-icon view"><i class="bi bi-eye"></i></a>
                                <a href="edit_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="btn-icon edit"><i class="bi bi-pencil"></i></a>
                                <a href="hapus_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="btn-icon delete" onclick="return confirm('Yakin mau menghapus data ini?');"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>

                </table>

                <div class="pagination">

                    <button class="page-btn">
                        <i class="bi bi-chevron-left"></i>
                        Sebelumnya
                    </button>

                    <div class="page-numbers">

                        <button class="page-number active">
                            1
                        </button>

                        <button class="page-number">
                            2
                        </button>

                        <button class="page-number">
                            3
                        </button>

                    </div>

                    <button class="page-btn">
                        Selanjutnya
                        <i class="bi bi-chevron-right"></i>
                    </button>

                </div>
            </div>

        </div>

    </div>

</div>

</body>
</html>