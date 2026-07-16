<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

/* ==========================================================
   AMBIL DAFTAR JABATAN UNTUK FILTER (DARI MANAJEMEN PERIODE)
========================================================== */
$q_jabatan = pg_query($koneksi, "SELECT DISTINCT nama_periode AS jabatan FROM periode_penilaian ORDER BY nama_periode ASC");
$list_jabatan = [];
while ($row = pg_fetch_assoc($q_jabatan)) {
    $list_jabatan[] = $row['jabatan'];
}

/* ==========================================================
   TANGKAP PARAMETER FILTER
========================================================== */
$filter_jabatan = $_GET['jabatan'] ?? 'ALL';
$filter_status = $_GET['status'] ?? 'ALL';

$params = [];
$jabatanCond = "";

if ($filter_jabatan !== 'ALL') {
    $jabatanCond = " AND p.jabatan = $1 ";
    $params[] = $filter_jabatan;
}

/* ==========================================================
   QUERY SUPER (Mengelompokkan Semua UK Default per Jabatan + Timestamp)
========================================================== */
$sql = "
    WITH Target_UK AS (
        SELECT ek.kode_unit, SUM(ak.jumlah_evidence_wa) AS total_target
        FROM aktivitas_kompeten ak
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        WHERE ak.aktif = 'Y'
        GROUP BY ek.kode_unit
    ),
    Upload_UK AS (
        SELECT 
            bp.pegawai_id, 
            ek.kode_unit, 
            COUNT(bp.bukti_id) AS total_upload,
            MAX(bp.tanggal_upload) AS waktu_terakhir_upload -- Mengambil timestamp paling baru
        FROM bukti_pegawai bp
        JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        GROUP BY bp.pegawai_id, ek.kode_unit
    ),
    UK_Jabatan AS (
        SELECT DISTINCT p.jabatan, uk.kode_unit, uk.judul_unit
        FROM bukti_pegawai bp
        JOIN pegawai p ON bp.pegawai_id = p.pegawai_id
        JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
        UNION
        SELECT DISTINCT p.jabatan, uk.kode_unit, uk.judul_unit
        FROM penilaian_header ph
        JOIN pegawai p ON p.pegawai_id = ph.pegawai_id
        JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
    ),
    Pegawai_Aktif AS (
        SELECT p.pegawai_id, p.pegawai_nama, p.jabatan
        FROM pegawai p
        WHERE (p.pegawai_id IN (SELECT DISTINCT pegawai_id FROM bukti_pegawai)
           OR p.pegawai_id IN (SELECT DISTINCT pegawai_id FROM penilaian_header))
           $jabatanCond
    )
    SELECT 
        pa.pegawai_id,
        pa.pegawai_nama,
        pa.jabatan,
        uj.kode_unit,
        uj.judul_unit,
        COALESCE(t.total_target, 0) AS target_dokumen,
        COALESCE(u.total_upload, 0) AS terkumpul_dokumen,
        u.waktu_terakhir_upload,
        (SELECT COUNT(*) FROM penilaian_header ph WHERE ph.pegawai_id = pa.pegawai_id AND ph.kode_unit = uj.kode_unit AND ph.status = 'Selesai') AS is_dinilai
    FROM Pegawai_Aktif pa
    JOIN UK_Jabatan uj ON pa.jabatan = uj.jabatan
    LEFT JOIN Target_UK t ON uj.kode_unit = t.kode_unit
    LEFT JOIN Upload_UK u ON u.pegawai_id = pa.pegawai_id AND u.kode_unit = uj.kode_unit
    ORDER BY pa.pegawai_nama ASC, uj.kode_unit ASC
";

if (count($params) > 0) {
    $q_data = pg_query_params($koneksi, $sql, $params);
} else {
    $q_data = pg_query($koneksi, $sql);
}

// ==========================================================
// PENGELOMPOKAN & FILTERING STATUS (PARENT-CHILD)
// ==========================================================
$data_pegawai = [];
while ($row = pg_fetch_assoc($q_data)) {
    $pid = $row['pegawai_id'];
    
    if (!isset($data_pegawai[$pid])) {
        $data_pegawai[$pid] = [
            'pegawai_id' => $row['pegawai_id'],
            'nama' => $row['pegawai_nama'],
            'jabatan' => $row['jabatan'],
            'total_target' => 0,
            'total_terkumpul' => 0,
            'total_uk' => 0,
            'uk_dinilai' => 0,
            'uks' => [],
            'first_uk_to_score' => null
        ];
    }
    
    $data_pegawai[$pid]['uks'][] = [
        'kode_unit' => $row['kode_unit'],
        'judul_unit' => $row['judul_unit'],
        'target' => $row['target_dokumen'],
        'terkumpul' => $row['terkumpul_dokumen'],
        'waktu_terakhir' => $row['waktu_terakhir_upload'], // Simpan timestamp ke array
        'is_dinilai' => $row['is_dinilai'] > 0
    ];
    
    $data_pegawai[$pid]['total_target'] += $row['target_dokumen'];
    $data_pegawai[$pid]['total_terkumpul'] += $row['terkumpul_dokumen'];
    $data_pegawai[$pid]['total_uk'] += 1;
    if ($row['is_dinilai'] > 0) {
        $data_pegawai[$pid]['uk_dinilai'] += 1;
    } else if (is_null($data_pegawai[$pid]['first_uk_to_score'])) {
        $data_pegawai[$pid]['first_uk_to_score'] = $row['kode_unit'];
    }
}

// Eksekusi Filter Status Penilaian
if ($filter_status !== 'ALL') {
    foreach ($data_pegawai as $pid => $peg) {
        $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk'] && $peg['total_uk'] > 0);
        
        if ($filter_status === 'belum' && $semua_selesai) {
            unset($data_pegawai[$pid]); 
        } elseif ($filter_status === 'selesai' && !$semua_selesai) {
            unset($data_pegawai[$pid]); 
        }
    }
}

$page_title = "Skoring & Penilaian Evidence";
$page_subtitle = "Kelola dokumen kompetensi pegawai dan berikan penilaian akhir.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Skoring Bukti Dokumen | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .filter-box-top { background: #ffffff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; gap: 20px; align-items: center; flex-wrap: wrap;}
        .filter-group { display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px;}
        .filter-group label { font-size: 13px; font-weight: 700; color: #475569; white-space: nowrap; margin: 0; display: flex; align-items: center; gap: 6px;}
        .filter-group select { flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; cursor: pointer; font-family: inherit; transition: 0.2s; background-color: #f8fafc;}
        .filter-group select:focus { border-color: #A08348; box-shadow: 0 0 0 3px rgba(160, 131, 72, 0.1); background-color: #fff;}
        
        .table-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #ffffff; color: #64748b; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        .parent-row { transition: 0.2s; cursor: pointer; }
        .parent-row:hover { background-color: #f8fafc; }
        .icon-toggle { transition: transform 0.3s ease; color: #94a3b8; font-size: 14px; font-weight: bold;}

        .child-container { padding: 20px 25px 20px 45px; background-color: #f8fafc; border-left: 4px solid #A08348; }
        .sub-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .sub-table th { background: #ffffff; padding: 12px 15px; font-size: 11px; color: #64748b; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-weight: 700;}
        .sub-table td { padding: 14px 15px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;}
        .sub-table tr:hover { background-color: #fbfdfc; }
        .sub-table tr:last-child td { border-bottom: none; }

        .evidence-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-size: 14px; font-weight: 700;}
        .evidence-badge i { font-size: 16px; margin-right: 2px;}
        .text-target { color: #94a3b8; font-size: 12px; font-weight: 500;}
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-align: center; white-space: nowrap; border: 1px solid transparent;}
        .badge-hijau { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-sm { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase;}
        
        .btn-nilai-main { background: #bda572; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: none; white-space: nowrap; width: 110px; justify-content: center;}
        .btn-nilai-main:hover { background: #A08348; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); color: white;}
        
        .btn-update-main { background: #fffdf5; color: #A08348; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #A08348; white-space: nowrap; width: 110px; justify-content: center;}
        .btn-update-main:hover { background: #fef9e8; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(160, 131, 72, 0.1); color: #A08348;}
        
        .text-id { font-size: 11px; font-weight: 700; color: #A08348; margin-bottom: 2px; display: block;}
        
        /* Tambahan Style untuk Waktu Upload */
        .time-badge { font-size: 11px; font-weight: 600; color: #64748b; display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;}
    </style>
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pimpinan.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <form method="GET" id="formFilter" class="filter-box-top">
            <div class="filter-group">
                <label><i class="bi bi-person-workspace" style="color: #3b82f6;"></i> Jabatan:</label>
                <select name="jabatan" onchange="document.getElementById('formFilter').submit();">
                    <option value="ALL">-- Semua Jabatan --</option>
                    <?php foreach($list_jabatan as $j): ?>
                        <option value="<?= htmlspecialchars($j) ?>" <?= $filter_jabatan == $j ? 'selected' : '' ?>>
                            <?= htmlspecialchars($j) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label><i class="bi bi-ui-checks" style="color: #10b981;"></i> Status:</label>
                <select name="status" onchange="document.getElementById('formFilter').submit();">
                    <option value="ALL" <?= $filter_status == 'ALL' ? 'selected' : '' ?>>-- Semua Status --</option>
                    <option value="belum" <?= $filter_status == 'belum' ? 'selected' : '' ?>>Belum Selesai Dinilai (Butuh Aksi)</option>
                    <option value="selesai" <?= $filter_status == 'selesai' ? 'selected' : '' ?>>Selesai Dinilai (Semua UK Lengkap)</option>
                </select>
            </div>
        </form>

        <div class="table-card">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"></th>
                        <th width="25%">Nama Pegawai</th>
                        <th width="25%">Jabatan Fungsional</th>
                        <th width="15%" style="text-align: center;">Jumlah Bukti Dokumen</th>
                        <th width="15%" style="text-align: center;">Status Penilaian</th>
                        <th width="15%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_pegawai)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 50px 20px; color: #94a3b8;">
                                <i class="bi bi-funnel" style="font-size: 36px; display:block; margin-bottom:12px; color: #cbd5e1;"></i>
                                <span style="font-size: 14px; font-weight: 500;">Tidak ada pegawai yang sesuai dengan kriteria filter tersebut.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($data_pegawai as $pid => $peg): 
                            $progress_color = '#ef4444'; 
                            if ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) $progress_color = '#10b981'; 
                            elseif ($peg['total_terkumpul'] > 0) $progress_color = '#f59e0b'; 
                            
                            $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk']);
                            $shortcut_kode = $semua_selesai ? $peg['uks'][0]['kode_unit'] : $peg['first_uk_to_score'];
                        ?>
                        
                        <tr class="parent-row" onclick="toggleChild('child-<?= $pid ?>', this)">
                            <td style="text-align: center;"><i class="bi bi-chevron-right icon-toggle"></i></td>
                            <td><b style="color: #0f172a; font-size: 13px;"><?= htmlspecialchars($peg['nama']) ?></b></td>
                            <td><span style="font-size: 13px; color: #475569; font-weight: 500;"><?= htmlspecialchars($peg['jabatan']) ?></span></td>
                            <td style="text-align: center;">
                                <div class="evidence-badge" title="Total Evidence: Terkumpul / Target">
                                    <i class="bi bi-folder-fill" style="color: <?= $progress_color ?>;"></i>
                                    <span style="color: #1e293b;"><?= $peg['total_terkumpul'] ?></span>
                                    <span class="text-target">/ <?= $peg['total_target'] ?></span>
                                </div>
                            </td>
                            <td style="text-align: center;">
                                <?php if($semua_selesai): ?>
                                    <span class="badge badge-hijau"><i class="bi bi-check-all"></i> Sudah Dinilai</span>
                                <?php else: ?>
                                    <span class="badge badge-kuning"><i class="bi bi-hourglass-split"></i> <?= $peg['uk_dinilai'] ?>/<?= $peg['total_uk'] ?> UK Selesai</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                <?php if($semua_selesai): ?>
                                    <a href="beri_nilai.php?pegawai_id=<?= $pid ?>&kode_unit=<?= urlencode($shortcut_kode) ?>" class="btn-update-main">
                                        <i class="bi bi-pencil-square"></i> Ubah Nilai
                                    </a>
                                <?php else: ?>
                                    <a href="beri_nilai.php?pegawai_id=<?= $pid ?>&kode_unit=<?= urlencode($shortcut_kode) ?>" class="btn-nilai-main">
                                    Beri Nilai
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- BARIS RINCIAN UNIT KOMPETENSI (CHILD) -->
                        <tr class="child-row" id="child-<?= $pid ?>" style="display: none;">
                            <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                                <div class="child-container">
                                    <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Unit Kompetensi (UK) Default untuk Jabatan <b><?= htmlspecialchars($peg['jabatan']) ?></b>:</h4>
                                    
                                    <table class="sub-table">
                                        <thead>
                                            <tr>
                                                <th width="35%">Unit Kompetensi Diujikan</th>
                                                <th width="15%" style="text-align: center;">Bukti Dokumen</th>
                                                <th width="20%" style="text-align: center;">Waktu Upload</th>
                                                <th width="15%" style="text-align: center;">Status</th>
                                                <th width="15%" style="text-align: center;">Aksi Penilaian</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($peg['uks'] as $uk): 
                                                $uk_prog_color = '#ef4444'; 
                                                if ($uk['terkumpul'] >= $uk['target'] && $uk['target'] > 0) $uk_prog_color = '#10b981';
                                                elseif ($uk['terkumpul'] > 0) $uk_prog_color = '#f59e0b';
                                            ?>
                                                <tr>
                                                    <td>
                                                        <span class="text-id"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($uk['kode_unit']) ?></span>
                                                        <span style="color: #334155; font-weight: 500; line-height: 1.4;"><?= htmlspecialchars($uk['judul_unit']) ?></span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <div class="evidence-badge">
                                                            <i class="bi bi-folder-fill" style="color: <?= $uk_prog_color ?>;"></i>
                                                            <span style="color: #1e293b;"><?= $uk['terkumpul'] ?></span> 
                                                            <span class="text-target">/ <?= $uk['target'] ?></span>
                                                        </div>
                                                    </td>
                                                    <!-- TAMPILAN TIMESTAMP BARU -->
                                                    <td style="text-align: center;">
                                                        <?php if(!empty($uk['waktu_terakhir'])): ?>
                                                            <span class="time-badge" title="Timestamp Audit"><i class="bi bi-clock-history"></i> <?= date('d M Y, H:i', strtotime($uk['waktu_terakhir'])) ?></span>
                                                        <?php else: ?>
                                                            <span style="color: #cbd5e1; font-size: 12px;">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <?php if($uk['is_dinilai']): ?>
                                                            <span class="badge-sm badge-hijau">SUDAH DINILAI</span>
                                                        <?php else: ?>
                                                            <span class="badge-sm badge-kuning">BELUM DINILAI</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <?php if($uk['is_dinilai']): ?>
                                                            <a href="beri_nilai.php?pegawai_id=<?= $pid ?>&kode_unit=<?= urlencode($uk['kode_unit']) ?>" class="btn-update-main" style="width: 100px;">
                                                                <i class="bi bi-pencil-square"></i> Ubah Nilai
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="beri_nilai.php?pegawai_id=<?= $pid ?>&kode_unit=<?= urlencode($uk['kode_unit']) ?>" class="btn-nilai-main" style="width: 100px;">
                                                                <i class="bi bi-ui-checks"></i> Beri Nilai
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script>
function toggleChild(childId, rowElement) {
    var childRow = document.getElementById(childId);
    var icon = rowElement.querySelector('.icon-toggle');
    
    if (childRow.style.display === 'none' || childRow.style.display === '') {
        childRow.style.display = 'table-row';
        rowElement.style.backgroundColor = '#f8fafc';
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    } else {
        childRow.style.display = 'none';
        rowElement.style.backgroundColor = 'transparent';
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    }
}

const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status');
const pesan = urlParams.get('pesan');

if (status === 'sukses') {
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: pesan, timer: 2000, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname);
}
</script>

</body>
</html>