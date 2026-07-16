<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pegawai');
require_once '../config/koneksi.php'; 

$idPegawai = $_SESSION['pegawai_id'];
$kode_unit_selected = isset($_GET['kode_unit']) ? $_GET['kode_unit'] : '';

// TANGKAP ROLE DISINI
$role = isset($_GET['role']) ? $_GET['role'] : 'pimpinan';
$role_label = ucfirst($role);
if ($role == 'diri') $role_label = 'Diri Sendiri';
elseif ($role == 'rekan') $role_label = 'Rekan Sejawat';

$page_title = "Daftar Evidence Penilaian";
$page_subtitle = "Melihat rincian aktivitas yang telah diunggah untuk penilai: " . $role_label;

$queryPegawai = pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($queryPegawai);
$jabatanPegawai = !empty($pegawai['jabatan']) ? $pegawai['jabatan'] : ''; 

/* ==========================================================
   AMBIL UNIT KOMPETENSI (UNTUK DROPDOWN)
========================================================== */
$sqlUnit = "
    SELECT DISTINCT uk.kode_unit, uk.judul_unit 
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    WHERE uk.posisi_target ILIKE $1
    ORDER BY uk.kode_unit ASC
";
$qUnit = pg_query_params($koneksi, $sqlUnit, array("%" . $jabatanPegawai . "%"));
$units = pg_fetch_all($qUnit) ?: [];

/* ==========================================================
   AMBIL DAFTAR AKTIVITAS
========================================================== */
$sqlEvidence = "
    SELECT 
        ak.aktivitas_id,
        ak.detail_aktivitas,
        ek.elemen_kompetensi,
        uk.kode_unit,
        uk.judul_unit,
        (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1) as is_uploaded,
        (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1 ORDER BY tanggal_upload DESC LIMIT 1) as tgl_upload
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    WHERE uk.posisi_target ILIKE $2 AND ak.aktif = 'Y'
";

$params = array($idPegawai, "%" . $jabatanPegawai . "%");

if (!empty($kode_unit_selected)) {
    $sqlEvidence .= " AND uk.kode_unit = $3";
    $params[] = $kode_unit_selected;
}

$sqlEvidence .= " ORDER BY uk.kode_unit ASC, ak.aktivitas_id ASC";

$qEvidence = pg_query_params($koneksi, $sqlEvidence, $params);
$evidenceList = pg_fetch_all($qEvidence) ?: [];

// Paginasi
$limit = 10; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$total_data = count($evidenceList);
$total_pages = ceil($total_data / $limit);
$offset = ($page - 1) * $limit;
$paginatedEvidence = array_slice($evidenceList, $offset, $limit);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?> | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pegawai/penilaian_list.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php"; 
    $btn_kembali = "penilaian.php";
    ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <div class="custom-hero">
            <div class="custom-hero-icon"><i class="bi bi-journal-richtext"></i></div>
            <div class="custom-hero-text">
                <h2>Daftar Aktivitas Penilaian</h2>
                <p>Pantau status seluruh evidence yang telah Anda kumpulkan berdasarkan aktivitas.</p>
                <div class="custom-hero-badge" style="display:inline-block; background:rgba(255,255,255,0.2); padding: 4px 10px; border-radius:6px; font-size:11px; margin-top:8px;">
                    <i class="bi bi-person-badge-fill"></i> Penilai: <?= htmlspecialchars($role_label) ?>
                </div>
            </div>
            <a href="penilaian.php" class="hero-back-btn"><i class="bi bi-arrow-left"></i> Kembali</a>
        </div>

        <div class="filter-box">
            <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Pilih Unit Kompetensi</label>
            <form method="GET" action="penilaian_list.php" style="flex: 1; display: flex;">
                <!-- HIDDEN INPUT ROLE -->
                <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
                
                <select name="kode_unit" onchange="this.form.submit()">
                    <option value="" style="font-weight: bold;" <?= empty($kode_unit_selected) ? 'selected' : '' ?>>
                        -- Tampilkan Semua Unit Kompetensi --
                    </option>
                    <?php foreach($units as $u): ?>
                        <option value="<?= htmlspecialchars($u['kode_unit']) ?>" <?= ($kode_unit_selected == $u['kode_unit']) ? 'selected' : '' ?>>
                            [<?= htmlspecialchars($u['kode_unit']) ?>] - <?= htmlspecialchars($u['judul_unit']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <div id="evidence-container">
            <?php if(empty($evidenceList)): ?>
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    <h3>Data Aktivitas Kosong</h3>
                    <p>Tidak ditemukan daftar aktivitas untuk unit kompetensi ini.</p>
                </div>
            <?php else: ?>
                <div class="unit-card" style="display: block;">
                    <div class="unit-header">
                        <span><i class="bi bi-tags-fill"></i> <?= empty($kode_unit_selected) ? 'Semua Unit Kompetensi' : htmlspecialchars($kode_unit_selected) ?></span>
                        <h3>Daftar Aktivitas & Evidence</h3>
                    </div>
                    <div style="overflow-x: auto;">
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th width="5%" style="text-align: center;">No</th>
                                    <th width="45%">Rincian Aktivitas</th>
                                    <th width="15%" style="text-align: center;">Upload Terakhir</th>
                                    <th width="15%" style="text-align: center;">Status Upload</th>
                                    <th width="20%" style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = $offset + 1; foreach($paginatedEvidence as $ev): 
                                    $is_uploaded = $ev['is_uploaded'] > 0;
                                ?>
                                <tr>
                                    <td style="text-align: center; color: #64748b; font-weight: 600;"><?= $no++ ?></td>
                                    <td>
                                        <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px; font-size: 13px;">
                                            <?php if(empty($kode_unit_selected)): ?>
                                                <span style="color: #A08338; font-size: 11px; font-weight: 700;"><?= htmlspecialchars($ev['kode_unit']) ?></span><br>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($ev['detail_aktivitas']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-size: 12px; font-weight: 500;">
                                        <?= $is_uploaded ? date('d/m/Y', strtotime($ev['tgl_upload'])) : '-' ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($is_uploaded): ?>
                                            <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;">
                                                <i class="bi bi-check-circle"></i> Uploaded
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                                <i class="bi bi-x-circle"></i> Belum Upload
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($is_uploaded): ?>
                                            <a href="penilaian_detail.php?aktivitas_id=<?= urlencode($ev['aktivitas_id']) ?>&role=<?= urlencode($role) ?>" class="btn-upload">
                                                <i class="bi bi-search"></i> Lihat Penilaian
                                            </a>
                                        <?php else: ?>
                                            <button class="btn-locked" disabled><i class="bi bi-slash-circle"></i> Kosong</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div> 

                    <?php if($total_data > 0): ?>
                    <div class="table-footer">
                        <div class="table-info">Menampilkan <?= $offset + 1 ?> hingga <?= min($offset + $limit, $total_data) ?> dari total <?= $total_data ?> aktivitas</div>
                        <div class="pagination">
                            <?php if($page > 1): ?>
                                <a href="?role=<?= urlencode($role) ?>&kode_unit=<?= urlencode($kode_unit_selected) ?>&page=<?= $page-1 ?>" class="page-btn"><i class="bi bi-chevron-left"></i></a>
                            <?php endif; ?>
                            
                            <a href="#" class="page-btn active"><?= $page ?></a>
                            
                            <?php if($page < $total_pages): ?>
                                <a href="?role=<?= urlencode($role) ?>&kode_unit=<?= urlencode($kode_unit_selected) ?>&page=<?= $page+1 ?>" class="page-btn"><i class="bi bi-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>