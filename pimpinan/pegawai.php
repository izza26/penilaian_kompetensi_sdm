<?php 
session_start();

// 1. Tambahkan Satpam Penjaga Role Pimpinan
require_once '../auth/cek_role.php';
cekRole('pimpinan');

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

$query_pegawai = @pg_query($koneksi, $query_sql);

// Variabel untuk layout
$page_title = "Data Pegawai";
$page_subtitle = "Pantau dan kelola status keaktifan pegawai divisi Anda";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Data Pegawai | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pimpinan/pegawai.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app">

    <!-- 2. Ubah Sidebar ke milik Pimpinan -->
    <?php include '../layouts/sidebar_pimpinan.php'; ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="page-card">

            <div class="page-header">
                <form action="" method="GET" style="display: inline-block;">
                    <input 
                        type="text" 
                        name="cari" 
                        placeholder="Cari nama pegawai..." 
                        value="<?= htmlspecialchars(isset($_GET['cari']) ? $_GET['cari'] : ''); ?>" 
                        class="search-input" 
                    >
                    <button type="submit" style="display: none;">Cari</button>
                </form>

                <?php if(isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                    <a href="pegawai.php" style="margin-left: 10px; color: red; text-decoration: none; font-size: 14px;">(X) Reset Pencarian</a>
                <?php endif; ?>

                <a href="tambah_pegawai.php" class="btn-primary">
                    + Tambah Pegawai
                </a>
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
                        if ($query_pegawai && pg_num_rows($query_pegawai) > 0) {
                            while($row = pg_fetch_assoc($query_pegawai)) { 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nip_nik']); ?></td>
                            <td><?= htmlspecialchars($row['pegawai_nama']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan'] ?? '-'); ?></td> 
                            <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                            <td><span class="status-badge aktif">Aktif</span></td> 
                            
                            <td class="action-buttons">
                                <!-- Tombol Aksi tetap ada: View, Edit, Delete -->
                                <a href="detail_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="btn-icon view" title="Lihat Detail"><i class="bi bi-eye"></i></a>
                                <a href="edit_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="btn-icon edit" title="Edit Data"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                        <?php 
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding: 20px;'>Tidak ada data pegawai ditemukan.</td></tr>";
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
                        <button class="page-number">2</button>
                        <button class="page-number">3</button>
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