<?php 
session_start();
require_once '../config/koneksi.php';

// --- LOGIKA PAGINATION ---
$limit = 10; // Tentukan jumlah data per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';
$where_sql = "";

// Jika ada pencarian
if ($kata_kunci != '') {
    $where_sql = " WHERE pegawai_nama ILIKE '%$kata_kunci%' 
                   OR nip_nik ILIKE '%$kata_kunci%' 
                   OR unit_kerja ILIKE '%$kata_kunci%' ";
}

// 1. Query untuk menghitung TOTAL data (untuk tahu jumlah halaman)
$query_count = "SELECT COUNT(*) as total FROM pegawai" . $where_sql;
$result_count = pg_query($koneksi, $query_count);
$total_records = pg_fetch_assoc($result_count)['total'];
$total_pages = ceil($total_records / $limit);

// 2. Query utama untuk mengambil data dengan LIMIT dan OFFSET
$query_sql = "SELECT * FROM pegawai " . $where_sql . " ORDER BY pegawai_id DESC LIMIT $limit OFFSET $offset";
$query_pegawai = pg_query($koneksi, $query_sql);

// Menyiapkan parameter pencarian untuk link pagination agar pencarian tidak hilang saat pindah halaman
$cari_param = ($kata_kunci != '') ? "&cari=" . urlencode($kata_kunci) : "";

// Variabel untuk layout
$page_title = "Data Pegawai";
$page_subtitle = "Kelola data pegawai Museum Geologi";
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= $page_title ?> | Museum Geologi</title>
    
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
                <!-- Dibungkus dengan form-wrapper agar sejajar (flex) -->
                <form action="" method="GET" class="search-form">
                    <input 
                        type="text" 
                        name="cari" 
                        placeholder="Cari nama, NIP, atau unit..." 
                        value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : ''; ?>" 
                        class="search-input" 
                    >
                    <button type="submit" style="display: none;">Cari</button>

                    <?php if(isset($_GET['cari']) && $_GET['cari'] != ''): ?>
                        <a href="pegawai.php" class="btn-reset">(X) Reset</a>
                    <?php endif; ?>
                </form>

                <a href="tambah_pegawai.php" class="btn-primary" style="text-decoration:none;">
                    <i class="bi bi-plus-lg"></i> Tambah Pegawai
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
                        // Nomor urut menyesuaikan dengan offset halaman aktif
                        $no = $offset + 1; 
                        
                        if(pg_num_rows($query_pegawai) > 0) {
                            while($row = pg_fetch_assoc($query_pegawai)) { 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nip_nik']); ?></td>
                            <td style="font-weight: 500; color: #1e293b;"><?= htmlspecialchars($row['pegawai_nama']); ?></td>
                            <td><?= htmlspecialchars($row['jabatan'] ?? '-'); ?></td> 
                            <td><?= htmlspecialchars($row['unit_kerja']); ?></td>
                            <td><span class="status-badge aktif">Aktif</span></td> 
                            
                            <td class="action-buttons">
                                <a href="detail_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="action-btn view-btn" title="Detail"><i class="bi bi-eye"></i></a>
                                <a href="edit_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="action-btn edit-btn" title="Edit"><i class="bi bi-pencil"></i></a>
                                <a href="hapus_pegawai.php?id=<?= $row['pegawai_id']; ?>" class="action-btn delete-btn" onclick="return confirm('Yakin mau menghapus data ini?');" title="Hapus"><i class="bi bi-trash"></i></a>
                            </td>
                        </tr>
                        <?php 
                            } 
                        } else {
                            echo "<tr><td colspan='7' style='text-align:center; padding: 20px; color: #94a3b8;'>Data pegawai tidak ditemukan.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <!-- Komponen Pagination Dinamis -->
                <?php if ($total_pages > 0): ?> <!-- Diubah menjadi > 0 agar selalu muncul selama ada data -->
                <div class="pagination">
                    
                    <!-- Tombol Sebelumnya -->
                    <?php if($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $cari_param ?>" class="page-btn" style="text-decoration:none;">
                            <i class="bi bi-chevron-left"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <button class="page-btn" disabled style="opacity:0.5; cursor:not-allowed;">
                            <i class="bi bi-chevron-left"></i> Sebelumnya
                        </button>
                    <?php endif; ?>

                    <!-- Angka Halaman -->
                    <div class="page-numbers">
                        <?php for($i = 1; $i <= $total_pages; $i++): ?>
                            <?php if($i == $page): ?>
                                <button class="page-number active"><?= $i ?></button>
                            <?php else: ?>
                                <a href="?page=<?= $i ?><?= $cari_param ?>" class="page-number" style="text-decoration:none; display:flex; justify-content:center; align-items:center;">
                                    <?= $i ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <!-- Tombol Selanjutnya -->
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $cari_param ?>" class="page-btn" style="text-decoration:none;">
                            Selanjutnya <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <button class="page-btn" disabled style="opacity:0.5; cursor:not-allowed;">
                            Selanjutnya <i class="bi bi-chevron-right"></i>
                        </button>
                    <?php endif; ?>
                    
                </div>
                <?php endif; ?>
                
            </div>

        </div>

    </div>

</div>

</body>
</html>