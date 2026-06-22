<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Data User";
$page_subtitle = "Kelola akun pengguna sistem";

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

if ($kata_kunci != '') {
    $query_sql = "SELECT * FROM users 
                  WHERE username ILIKE $1 
                  OR nama_lengkap ILIKE $1 
                  OR role ILIKE $1 
                  ORDER BY id DESC";
    
    $params = array('%' . $kata_kunci . '%');
    $result = pg_query_params($koneksi, $query_sql, $params);
} else {
    $query_sql = "SELECT * FROM users ORDER BY id DESC";
    $result = pg_query($koneksi, $query_sql);
}

if (!$result) {
    $error_msg = pg_last_error($koneksi);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data User</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/user.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app">

    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Daftar User</h2>
                    <p>Kelola akun administrator, assessor, dan pimpinan yang memiliki akses ke sistem.</p>
                </div>
                <a href="tambah_user.php" class="btn-primary">
                    + Tambah User
                </a>
            </div>

            <div class="table-tools">
                <form method="GET" action="">
                    <input 
                        type="text" 
                        name="cari"
                        class="search-input" 
                        placeholder="Cari user..."
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
                            <th>Username</th>
                            <th>Nama Lengkap</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result && pg_num_rows($result) > 0) {
                            while ($user = pg_fetch_assoc($result)) {
                                
                                $role_class = '';
                                $role_db = strtolower($user['role'] ?? '');
                                
                                if (strpos($role_db, 'admin') !== false) {
                                    $role_class = 'admin-role';
                                } elseif (strpos($role_db, 'assessor') !== false) {
                                    $role_class = 'assessor-role';
                                } elseif (strpos($role_db, 'pimpinan') !== false) {
                                    $role_class = 'pimpinan-role';
                                }

                                $status_user = !empty($user['status']) ? $user['status'] : 'Aktif';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($user['username']) ?></td>
                                    <td><?= htmlspecialchars($user['nama_lengkap']) ?></td>
                                    
                                    <td>
                                        <span class="role <?= $role_class ?>">
                                            <?= htmlspecialchars(ucwords($user['role'])) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <span class="status">
                                            <?= htmlspecialchars($status_user) ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <a href="detail_user.php?id=<?= $user['id'] ?>" class="action-btn view-btn" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="action-btn edit-btn" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        
                                        <a href="hapus_user.php?id=<?= $user['id'] ?>" class="action-btn delete-btn" title="Hapus" onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars($user['username']) ?>?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='6' style='text-align:center; padding: 20px;'>Data user tidak ditemukan.</td></tr>";
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