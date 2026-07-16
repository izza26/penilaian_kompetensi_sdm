<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

// ==========================================================
// TARIK DATA UNIT, AKTIVITAS, DAN EVIDENCE WAJIB
// ==========================================================
$q_ew = pg_query($koneksi, "SELECT aktivitas_id, nama_evidence FROM evidence_wajib ORDER BY evidence_wajib_id ASC");
$ew_data = [];
while ($row = pg_fetch_assoc($q_ew)) {
    $ew_data[$row['aktivitas_id']][] = $row['nama_evidence'];
}

$sql = "
SELECT 
    uk.kode_unit, uk.judul_unit,
    ak.aktivitas_id, ak.detail_aktivitas, ak.kriteria_kompetens, ak.jumlah_evidence_wa
FROM aktivitas_kompeten ak
JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
WHERE ak.aktif = 'Y'
ORDER BY uk.kode_unit ASC, ak.aktivitas_id ASC
";
$q_data = pg_query($koneksi, $sql);

$dataGrouped = [];
while ($row = pg_fetch_assoc($q_data)) {
    $unitKey = $row['kode_unit'] . "|||" . $row['judul_unit'];
    $dataGrouped[$unitKey][] = $row;
}

$page_title = "Manajemen Evidence";
$page_subtitle = "Tetapkan rincian dokumen apa saja yang harus diunggah pegawai per aktivitas.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Manajemen Kebutuhan Evidence | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .filter-box { background: #ffffff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.02); margin-bottom: 25px; display: flex; align-items: center; gap: 15px; }
        .filter-box label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; margin: 0; }
        .filter-box select { flex: 1; max-width: calc(100% - 200px); padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; color: #1e293b; outline: none; cursor: pointer; font-family: inherit; transition: 0.2s; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;}
        .filter-box select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }

        .unit-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 25px; overflow: hidden; }
        .unit-card-header { background: #f8fafc; padding: 18px 25px; border-bottom: 1px solid #e2e8f0; }
        .unit-card-header h3 { margin: 0 0 4px 0; font-size: 16px; color: #1e293b; line-height: 1.4;}
        .unit-card-header span { font-size: 12px; font-weight: 700; color: #A08348; background: #fffbeb; padding: 4px 10px; border-radius: 6px; border: 1px solid #fde68a;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #ffffff; color: #475569; padding: 14px 25px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 25px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: top; }
        
        .inline-edit-row { transition: 0.2s; cursor: pointer; }
        .inline-edit-row:hover { background-color: #f8fafc; }
        
        .text-id { font-size: 13px; font-weight: 700; color: #A08348; margin-bottom: 4px; display: block;}
        .text-detail { font-size: 14px; font-weight: 600; color: #0f172a; line-height: 1.4; display: block;}
        .text-kriteria { font-size: 13px; color: #475569; line-height: 1.5;}
        
        .badge-evidence { background: #fffbeb; color: #b45309; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #fde68a; display: inline-block; margin-bottom: 10px;}
        
        .ev-group { margin-bottom: 12px; }
        .ev-group-title { font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 4px; text-transform: uppercase;}
        .ev-list { padding-left: 18px; margin: 0; color: #0f172a; font-size: 13px; line-height: 1.5; font-weight: 500;}
        .ev-list li { margin-bottom: 4px; }
        .ev-list li::marker { color: #A08348; }
        .badge-kosong { background: #fef2f2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid #fecaca; display: inline-block; }

        .btn-atur { display: inline-flex; align-items: center; justify-content: center; gap: 6px; color: white; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; background: #bda572; padding: 8px 16px; border-radius: 8px; border: 1px solid #bda572;}
        .btn-atur:hover { background: #A08348; transform: translateY(-2px);}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <?php if (empty($dataGrouped)): ?>
            <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
                <i class="bi bi-inbox" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px; display: block;"></i>
                <h3 style="color: #0f172a; margin: 0 0 5px 0;">Belum Ada Aktivitas Aktif</h3>
                <p style="color: #64748b; font-size: 13px; margin: 0;">Silakan atur Periode Penilaian terlebih dahulu.</p>
            </div>
        <?php else: ?>

            <div class="filter-box">
                <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Pilih Unit Kompetensi</label>
                <select id="filterUK" onchange="applyFilterUK()">
                    <option value="ALL" style="font-weight: bold;">-- Tampilkan Semua Unit Kompetensi --</option>
                    <?php foreach ($dataGrouped as $unitKey => $aktivitas): 
                        $exUnit = explode("|||", $unitKey);
                        $kode_unit = $exUnit[0];
                        $judul_unit = $exUnit[1];
                    ?>
                        <option value="<?= htmlspecialchars($kode_unit) ?>">
                            [<?= htmlspecialchars($kode_unit) ?>] - <?= htmlspecialchars($judul_unit) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php foreach ($dataGrouped as $unitKey => $aktivitas): 
                $exUnit = explode("|||", $unitKey);
                $kode_unit = $exUnit[0];
                $judul_unit = $exUnit[1];
            ?>
            <div class="unit-card" data-kode="<?= htmlspecialchars($kode_unit) ?>">
                <div class="unit-card-header">
                    <span><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($kode_unit) ?></span>
                    <h3 style="margin-top: 8px;"><?= htmlspecialchars($judul_unit) ?></h3>
                </div>
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th width="30%">Detail Aktivitas</th>
                            <th width="30%">Kriteria Kompetensi</th>
                            <th width="30%">Daftar Evidence yang Diharapkan</th>
                            <th width="10%" style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aktivitas as $akt): 
                            $my_evidences = $ew_data[$akt['aktivitas_id']] ?? [];
                        ?>
                        <tr class="inline-edit-row" onclick="window.location.href='atur_evidence.php?id=<?= $akt['aktivitas_id'] ?>'">
                            <!-- KOLOM 1: DETAIL AKTIVITAS -->
                            <td>
                                <span class="text-id"></i> <?= htmlspecialchars($akt['aktivitas_id']) ?></span>
                                <span class="text-detail"><?= htmlspecialchars($akt['detail_aktivitas']) ?></span>
                            </td>
                            
                            <!-- KOLOM 2: KRITERIA -->
                            <td>
                                <div class="text-kriteria">
                                    <?= htmlspecialchars($akt['kriteria_kompetens'] ?? '-') ?>
                                </div>
                            </td>
                            
                            <!-- KOLOM 3: DAFTAR EVIDENCE BERSARANG -->
                            <td>
                                <?php if (!empty($my_evidences)): ?>
                                    <span class="badge-evidence"><i class="bi bi-folder-check"></i> Target: <?= count($my_evidences) ?> Dokumen</span>
                                    
                                    <?php 
                                    $evCount = 1;
                                    foreach($my_evidences as $ev): 
                                        // Pecah newline jadi array opsi
                                        $opsi_dokumen = explode("\n", $ev);
                                    ?>
                                        <div class="ev-group">
                                            <div class="ev-group-title"><i class="bi bi-file-earmark-text"></i> Evidence <?= $evCount++ ?></div>
                                            <ul class="ev-list">
                                                <?php foreach($opsi_dokumen as $opsi): ?>
                                                    <li><?= htmlspecialchars(trim($opsi)) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endforeach; ?>

                                <?php else: ?>
                                    <span class="badge-evidence"><i class="bi bi-folder-check"></i> Target: <?= htmlspecialchars($akt['jumlah_evidence_wa']) ?> Dokumen</span><br>
                                    <span class="badge-kosong"><i class="bi bi-exclamation-triangle"></i> Daftar belum dirincikan</span>
                                <?php endif; ?>
                            </td>
                            
                            <!-- KOLOM 4: AKSI -->
                            <td style="text-align: center; vertical-align: middle;">
                                <a href="atur_evidence.php?id=<?= $akt['aktivitas_id'] ?>" class="btn-atur"><i class="bi bi-pencil-square"></i> Atur</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function applyFilterUK() {
    let selectedUK = document.getElementById('filterUK').value;
    let cards = document.querySelectorAll('.unit-card');
    cards.forEach(card => {
        if (selectedUK === 'ALL' || card.getAttribute('data-kode') === selectedUK) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
window.onload = function() {
    let selectBox = document.getElementById('filterUK');
    if (selectBox && selectBox.options.length > 2) {
        selectBox.selectedIndex = 1; 
        applyFilterUK();
    }
}
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('status') === 'sukses') {
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Daftar kebutuhan evidence telah diperbarui.', timer: 2000, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname);
}
</script>

</body>
</html>