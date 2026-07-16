<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Penilaian";
$page_subtitle = "Pantau dan kelola proses penilaian kompetensi seluruh pegawai";

$kata_kunci = isset($_GET['cari']) ? $_GET['cari'] : '';

/* ==========================================================
   QUERY SUPER UNTUK ADMIN (Gabungan Mapping & Status Evidence)
========================================================== */
$base_query = "
    WITH Target_UK AS (
        SELECT ek.kode_unit, SUM(COALESCE(ak.jumlah_evidence_wa, 0)) AS total_target
        FROM aktivitas_kompeten ak
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        WHERE ak.aktif = 'Y'
        GROUP BY ek.kode_unit
    ),
    Upload_UK AS (
        SELECT bp.pegawai_id, ek.kode_unit, COUNT(bp.bukti_id) AS total_upload
        FROM bukti_pegawai bp
        JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        GROUP BY bp.pegawai_id, ek.kode_unit
    )
    SELECT 
        p.pegawai_id,
        p.pegawai_nama,
        p.jabatan,
        uk.kode_unit,
        uk.judul_unit,
        COALESCE(t.total_target, 0) AS target_dokumen,
        COALESCE(u.total_upload, 0) AS terkumpul_dokumen,
        (SELECT COUNT(*) FROM penilaian_header ph WHERE ph.pegawai_id = p.pegawai_id AND ph.kode_unit = uk.kode_unit AND ph.status = 'Selesai') AS is_dinilai
    FROM pegawai p
    LEFT JOIN jabatan j ON UPPER(p.jabatan) = UPPER(j.nama_jabatan)
    LEFT JOIN jabatan_unit_kompe juk ON j.jabatan_unit_id = juk.jabatan_unit_id
    LEFT JOIN unit_kompetensi uk ON juk.jabatan_unit_id = uk.jabatan_unit_id
    LEFT JOIN Target_UK t ON uk.kode_unit = t.kode_unit
    LEFT JOIN Upload_UK u ON u.pegawai_id = p.pegawai_id AND u.kode_unit = uk.kode_unit
";

if ($kata_kunci != '') {
    $query_sql = $base_query . " 
        WHERE p.pegawai_nama ILIKE $1 
           OR uk.kode_unit ILIKE $1 
           OR p.jabatan ILIKE $1
        ORDER BY p.pegawai_nama ASC, uk.kode_unit ASC";
            
    $params = array('%' . $kata_kunci . '%');
    $result = pg_query_params($koneksi, $query_sql, $params);
} else {
    $query_sql = $base_query . " ORDER BY p.pegawai_nama ASC, uk.kode_unit ASC";
    $result = pg_query($koneksi, $query_sql);
}

if (!$result) {
    die("<div style='color:red; padding:20px;'>Query Error: " . pg_last_error($koneksi) . "</div>");
}

/* ==========================================================
   PENGELOMPOKAN DATA (PARENT-CHILD)
========================================================== */
$data_pegawai = [];
while ($row = pg_fetch_assoc($result)) {
    $pid = $row['pegawai_id'];
    
    if (!isset($data_pegawai[$pid])) {
        $data_pegawai[$pid] = [
            'pegawai_id' => $row['pegawai_id'],
            'nama' => $row['pegawai_nama'],
            'jabatan' => $row['jabatan'] ?: 'Belum Diatur',
            'total_target' => 0,
            'total_terkumpul' => 0,
            'total_uk' => 0,
            'uk_dinilai' => 0,
            'uks' => [],
            'first_uk_to_score' => null
        ];
    }
    
    // Hanya masukkan UK jika pegawai tersebut sudah dipetakan ke jabatan/unit
    if (!empty($row['kode_unit'])) {
        $data_pegawai[$pid]['uks'][] = [
            'kode_unit' => $row['kode_unit'],
            'judul_unit' => $row['judul_unit'],
            'target' => (int)$row['target_dokumen'],
            'terkumpul' => (int)$row['terkumpul_dokumen'],
            'is_dinilai' => $row['is_dinilai'] > 0
        ];
        
        $data_pegawai[$pid]['total_target'] += (int)$row['target_dokumen'];
        $data_pegawai[$pid]['total_terkumpul'] += (int)$row['terkumpul_dokumen'];
        $data_pegawai[$pid]['total_uk'] += 1;
        
        if ($row['is_dinilai'] > 0) {
            $data_pegawai[$pid]['uk_dinilai'] += 1;
        } else if (is_null($data_pegawai[$pid]['first_uk_to_score'])) {
            $data_pegawai[$pid]['first_uk_to_score'] = $row['kode_unit'];
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Penilaian | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/pegawai.css">
    <link rel="stylesheet" href="../assets/css/css_admin/penilaian.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Modern Expandable Table Styles - Terinspirasi dari Skoring Pimpinan */
        .table-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; width: 100%;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 15px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        .parent-row { transition: 0.2s; cursor: pointer; }
        .parent-row:hover { background-color: #f8fafc; }
        .icon-toggle { transition: transform 0.3s ease; color: #94a3b8; font-size: 15px; font-weight: bold;}

        .child-container { padding: 20px 25px 20px 50px; background-color: #f8fafc; border-left: 4px solid #1e3a8a; }
        .sub-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .sub-table th { background: #ffffff; padding: 12px 15px; font-size: 11px; color: #64748b; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-weight: 700;}
        .sub-table td { padding: 14px 15px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;}
        .sub-table tr:hover { background-color: #fbfdfc; }

        .evidence-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-size: 14px; font-weight: 700;}
        .evidence-badge i { font-size: 16px; margin-right: 2px;}
        .text-target { color: #94a3b8; font-size: 12px; font-weight: 500;}
        
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-align: center; white-space: nowrap; border: 1px solid transparent;}
        .badge-hijau { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-merah { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .badge-sm { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase;}
        
        .btn-nilai-main { background: #1e3a8a; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: none; white-space: nowrap; width: 140px; justify-content: center;}
        .btn-nilai-main:hover { background: #1e40af; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2); color: white;}
        
        .btn-update-main { background: #eff6ff; color: #1e3a8a; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; border: 1px solid #1e3a8a; white-space: nowrap; width: 140px; justify-content: center;}
        .btn-update-main:hover { background: #dbeafe; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(30, 58, 138, 0.1); color: #1e3a8a;}
        
        .text-id { font-size: 11px; font-weight: 700; color: #3b82f6; margin-bottom: 3px; display: block;}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <div class="page-card" style="padding: 0; background: transparent; border: none; box-shadow: none;">

            <div class="table-tools" style="background: white; padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
                <form method="GET" action="">
                    <div style="position: relative; max-width: 400px;">
                        <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input 
                            type="text" 
                            name="cari"
                            class="search-input" 
                            style="width: 100%; padding: 12px 15px 12px 40px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;"
                            placeholder="Cari nama pegawai atau jabatan..."
                            value="<?= htmlspecialchars($kata_kunci) ?>"
                            onchange="this.form.submit()"
                        >
                    </div>
                </form>
            </div>

            <div class="table-card">
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th width="5%" style="text-align: center;"></th>
                            <th width="20%">Nama Pegawai</th>
                            <th width="20%">Jabatan</th>
                            <th width="20%" style="text-align: center;">Progress Evidence</th>
                            <th width="15%" style="text-align: center;">Status Penilaian</th>
                            <th width="20%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data_pegawai)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 50px 20px; color: #94a3b8;">
                                    <i class="bi bi-inbox" style="font-size: 36px; display:block; margin-bottom:12px; color: #cbd5e1;"></i>
                                    <span style="font-size: 14px; font-weight: 500;">Tidak ada data pegawai yang ditemukan.</span>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1;
                            foreach($data_pegawai as $pid => $peg): 
                                // Kalkulasi Status Parent
                                $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk'] && $peg['total_uk'] > 0);
                                $shortcut_kode = $semua_selesai ? ($peg['uks'][0]['kode_unit'] ?? '') : $peg['first_uk_to_score'];
                                
                                $progress_color = '#ef4444'; 
                                if ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) $progress_color = '#10b981'; 
                                elseif ($peg['total_terkumpul'] > 0) $progress_color = '#f59e0b';
                            ?>
                            
                            <!-- BARIS INDUK (PARENT) -->
                            <tr class="parent-row" onclick="toggleChild('child-<?= $pid ?>', this)">
                                <td style="text-align: center;">
                                    <?php if($peg['total_uk'] > 0): ?>
                                        <i class="bi bi-chevron-right icon-toggle"></i>
                                    <?php else: ?>
                                        <i class="bi bi-dash" style="color: #cbd5e1;"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <b style="color: #0f172a; font-size: 13px; display: block;"><?= htmlspecialchars($peg['nama']) ?></b>
                                    <span style="font-size: 11px; color: #94a3b8;">ID: <?= $pid ?></span>
                                </td>
                                <td><span style="font-size: 13px; color: #475569; font-weight: 500;"><?= htmlspecialchars($peg['jabatan']) ?></span></td>
                                
                                <?php if($peg['total_uk'] == 0): ?>
                                    <!-- Jika Belum Ada Mapping -->
                                    <td style="text-align: center;"><span style="color: #cbd5e1;">-</span></td>
                                    <td style="text-align: center;"><span class="badge badge-merah"><i class="bi bi-exclamation-triangle"></i> Belum Dipetakan</span></td>
                                    <td style="text-align: center;" onclick="event.stopPropagation();">
                                        <button class="btn-nilai-main" style="background-color:#e2e8f0; color:#94a3b8; border: none; cursor:not-allowed;" disabled><i class="bi bi-lock-fill"></i> Terkunci</button>
                                    </td>
                                <?php else: ?>
                                    <!-- Jika Sudah Ada Mapping UK -->
                                    <td style="text-align: center;">
                                        <div class="evidence-badge" title="Total Evidence: Terkumpul / Target">
                                            <i class="bi bi-folder-fill" style="color: <?= $progress_color ?>;"></i>
                                            <span style="color: #1e293b;"><?= $peg['total_terkumpul'] ?></span>
                                            <span class="text-target">/ <?= $peg['total_target'] ?></span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php if($semua_selesai): ?>
                                            <span class="badge badge-hijau"><i class="bi bi-check-all"></i> Selesai Dinilai</span>
                                        <?php else: ?>
                                            <span class="badge badge-kuning"><i class="bi bi-hourglass-split"></i> <?= $peg['uk_dinilai'] ?>/<?= $peg['total_uk'] ?> UK Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;" onclick="event.stopPropagation();">
                                        <?php if($semua_selesai): ?>
                                            <a href="form_penilaian.php?id=<?= $pid ?>&unit=<?= urlencode($shortcut_kode) ?>" class="btn-update-main">
                                                <i class="bi bi-eye"></i> Pantau / Ubah
                                            </a>
                                        <?php else: ?>
                                            <a href="form_penilaian.php?id=<?= $pid ?>&unit=<?= urlencode($shortcut_kode) ?>" class="btn-nilai-main">
                                                <i class="bi bi-play-fill"></i> Mulai Penilaian
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>

                            <!-- BARIS RINCIAN UNIT KOMPETENSI (CHILD) -->
                            <?php if($peg['total_uk'] > 0): ?>
                            <tr class="child-row" id="child-<?= $pid ?>" style="display: none;">
                                <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                                    <div class="child-container">
                                        <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Penilaian per Unit Kompetensi:</h4>
                                        
                                        <table class="sub-table">
                                            <thead>
                                                <tr>
                                                    <th width="40%">Kode & Judul Unit</th>
                                                    <th width="20%" style="text-align: center;">Progress Upload</th>
                                                    <th width="15%" style="text-align: center;">Status Penilaian</th>
                                                    <th width="25%" style="text-align: center;">Aksi</th>
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
                                                            <span style="color: #334155; font-weight: 500; line-height: 1.4; display:block; margin-top:2px;"><?= htmlspecialchars($uk['judul_unit']) ?></span>
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <div class="evidence-badge">
                                                                <i class="bi bi-folder-fill" style="color: <?= $uk_prog_color ?>;"></i>
                                                                <span style="color: #1e293b;"><?= $uk['terkumpul'] ?></span> 
                                                                <span class="text-target">/ <?= $uk['target'] ?></span>
                                                            </div>
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <?php if($uk['is_dinilai']): ?>
                                                                <span class="badge-sm badge-hijau">SELESAI DINILAI</span>
                                                            <?php else: ?>
                                                                <span class="badge-sm badge-kuning">BELUM DINILAI</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td style="text-align: center;">
                                                            <?php if($uk['is_dinilai']): ?>
                                                                <a href="form_penilaian.php?id=<?= $pid ?>&unit=<?= urlencode($uk['kode_unit']) ?>" class="btn-update-main" style="width: auto; padding: 6px 12px;">
                                                                    <i class="bi bi-eye"></i> Lihat Form Penilaian
                                                                </a>
                                                            <?php else: ?>
                                                                <a href="form_penilaian.php?id=<?= $pid ?>&unit=<?= urlencode($uk['kode_unit']) ?>" class="btn-nilai-main" style="width: auto; padding: 6px 12px;">
                                                                    <i class="bi bi-ui-checks"></i> Beri Penilaian
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
                            <?php endif; ?>
                            
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
// Fungsi untuk memunculkan tabel rincian saat baris di-klik
function toggleChild(childId, rowElement) {
    var childRow = document.getElementById(childId);
    if(!childRow) return; // Lewati jika tidak ada child row (Pegawai belum dipetakan)
    
    var icon = rowElement.querySelector('.icon-toggle');
    
    if (childRow.style.display === 'none' || childRow.style.display === '') {
        childRow.style.display = 'table-row';
        rowElement.style.backgroundColor = '#f8fafc';
        if(icon) icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    } else {
        childRow.style.display = 'none';
        rowElement.style.backgroundColor = 'transparent';
        if(icon) icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    }
}
</script>

</body>
</html>