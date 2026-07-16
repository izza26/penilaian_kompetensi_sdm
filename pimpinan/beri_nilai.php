<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$pegawai_id = $_GET['pegawai_id'] ?? '';
$kode_unit = $_GET['kode_unit'] ?? '';

if (!$pegawai_id || !$kode_unit) {
    header("Location: skoring.php"); exit;
}

// Cek Apakah Sudah Pernah Dinilai Sebelumnya
$q_cek_lama = pg_query_params($koneksi, "SELECT penilaian_id, rekomendasi FROM penilaian_header WHERE pegawai_id = $1 AND kode_unit = $2", array($pegawai_id, $kode_unit));
$is_update = pg_num_rows($q_cek_lama) > 0;
$rekomendasi_lama = "";
$skor_lama_array = [];

if ($is_update) {
    $row_lama = pg_fetch_assoc($q_cek_lama);
    $penilaian_id_lama = $row_lama['penilaian_id'];
    $rekomendasi_lama = $row_lama['rekomendasi'];
    
    // Tarik skor per aktivitas yang pernah disimpan
    $q_skor_lama = pg_query_params($koneksi, "SELECT aktivitas_id, skor_final FROM penilaian_detail WHERE penilaian_id = $1", array($penilaian_id_lama));
    while($row_skor = pg_fetch_assoc($q_skor_lama)) {
        $skor_lama_array[$row_skor['aktivitas_id']] = $row_skor['skor_final'];
    }
}

// Ambil info Pegawai & Unit
$q_info = pg_query_params($koneksi, "SELECT pegawai_nama, jabatan FROM pegawai WHERE pegawai_id = $1", array($pegawai_id));
$pegawai = pg_fetch_assoc($q_info);

$q_unit = pg_query_params($koneksi, "SELECT judul_unit FROM unit_kompetensi WHERE kode_unit = $1", array($kode_unit));
$unit = pg_fetch_assoc($q_unit);

// TARIK DATA AKTIVITAS (Ditambahkan query untuk tanggal_upload)
$sql_aktivitas = "
SELECT 
    ak.aktivitas_id, ak.detail_aktivitas, ak.jumlah_evidence_wa, ak.bobot_aktivitas, ak.kriteria_kompetens,
    ek.input AS nama_input,
    (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1) AS jml_upload,
    (SELECT file_path FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1 ORDER BY tanggal_upload DESC LIMIT 1) AS file_path,
    (SELECT tanggal_upload FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $1 ORDER BY tanggal_upload DESC LIMIT 1) AS tanggal_upload
FROM aktivitas_kompeten ak
JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
WHERE ek.kode_unit = $2 AND ak.aktif = 'Y'
ORDER BY ek.elemen_id ASC, ak.aktivitas_id ASC
";
$q_akt = pg_query_params($koneksi, $sql_aktivitas, array($pegawai_id, $kode_unit));
$list_aktivitas_raw = pg_fetch_all($q_akt) ?: [];

// NORMALISASI BOBOT PROPORSIONAL 
$total_bobot_per_input = [];
foreach ($list_aktivitas_raw as $akt) {
    $input = $akt['nama_input'];
    if (!isset($total_bobot_per_input[$input])) {
        $total_bobot_per_input[$input] = 0;
    }
    $total_bobot_per_input[$input] += (float)$akt['bobot_aktivitas'];
}

$list_aktivitas = [];
$list_input = [];
foreach ($list_aktivitas_raw as $akt) {
    $input = $akt['nama_input'];
    $bobot_asli = (float)$akt['bobot_aktivitas'];
    $bobot_proporsional = ($total_bobot_per_input[$input] > 0) ? ($bobot_asli / $total_bobot_per_input[$input]) * 100 : 0;
    
    $akt['bobot_proporsional'] = round($bobot_proporsional, 2);
    $list_aktivitas[] = $akt;

    if (!in_array($input, $list_input)) {
        $list_input[] = $input;
    }
}

// =========================================================================
// PROSES SIMPAN PENILAIAN (DENGAN 4 KATEGORI)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_nilai'])) {
    $total_bobot = 0;
    $total_weighted = 0;

    foreach ($list_aktivitas as $akt) {
        $akt_id = $akt['aktivitas_id'];
        $skor = isset($_POST['skor'][$akt_id]) ? (int)$_POST['skor'][$akt_id] : 1;
        $bobot = $akt['bobot_proporsional']; 
        
        $total_bobot += $bobot;
        $total_weighted += ($skor * ($bobot / 100));
    }

    $normalized = ($total_bobot > 0) ? ($total_weighted / ($total_bobot / 100)) : 0; 
    $score100 = ($normalized / 5) * 100;

    // RULE 4 KATEGORI
    if ($score100 >= 85) { $kategori = "Sangat Kompeten"; }
    elseif ($score100 >= 70) { $kategori = "Kompeten"; }
    elseif ($score100 >= 55) { $kategori = "Cukup Kompeten"; }
    else { $kategori = "Belum Kompeten"; }

    $rekomendasi = trim($_POST['rekomendasi'] ?? '');
    $waktu_sekarang = date('Y-m-d H:i:s'); 

    if ($is_update) {
        $q_update = "UPDATE penilaian_header SET nilai_akhir = $1, kategori = $2, rekomendasi = $3, status = 'Selesai', waktu_submit = $4 WHERE penilaian_id = $5";
        pg_query_params($koneksi, $q_update, array($score100, $kategori, $rekomendasi, $waktu_sekarang, $penilaian_id_lama));
        
        pg_query_params($koneksi, "DELETE FROM penilaian_detail WHERE penilaian_id = $1", array($penilaian_id_lama));
        $penilaian_id = $penilaian_id_lama;
    } else {
        $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(penilaian_id), 0) + 1 AS new_id FROM penilaian_header");
        $penilaian_id = pg_fetch_assoc($q_id)['new_id'];
        
        $q_insert = "INSERT INTO penilaian_header (penilaian_id, pegawai_id, kode_unit, nilai_akhir, kategori, rekomendasi, status, detail_penilaian_i, waktu_submit, catatan_umum) VALUES ($1, $2, $3, $4, $5, $6, 'Selesai', 0, $7, '')";
        pg_query_params($koneksi, $q_insert, array($penilaian_id, $pegawai_id, $kode_unit, $score100, $kategori, $rekomendasi, $waktu_sekarang));
    }

    foreach ($list_aktivitas as $akt) {
        $akt_id = $akt['aktivitas_id'];
        $skor_individu = isset($_POST['skor'][$akt_id]) ? (int)$_POST['skor'][$akt_id] : 1;
        $jumlah_bukti = (int)$akt['jml_upload'];
        
        $q_detail_id = pg_query($koneksi, "SELECT COALESCE(MAX(detail_penilaian_i), 0) + 1 AS new_detail_id FROM penilaian_detail");
        $new_detail_id = pg_fetch_assoc($q_detail_id)['new_detail_id'];
        
        $q_detail_insert = "INSERT INTO penilaian_detail (detail_penilaian_i, skor_final, jumlah_bukti_uploa, catatan_penilai, aktivitas_id, penilaian_id) VALUES ($1, $2, $3, '', $4, $5)";
        pg_query_params($koneksi, $q_detail_insert, array($new_detail_id, $skor_individu, $jumlah_bukti, $akt_id, $penilaian_id));
    }
    
    header("Location: skoring.php?status=sukses&pesan=Penilaian+Berhasil+Diperbarui!");
    exit;
}

$page_title = $is_update ? "Ubah Penilaian Kompetensi" : "Kalkulasi Nilai Kompetensi";
$page_subtitle = "Berikan penilaian final berbasis kualitas evidence.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Beri Nilai | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pimpinan/beri_nilai.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .page-header-skoring { background: #ffffff; border-radius: 12px; padding: 20px 25px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.02); margin-bottom: 25px; }
        .header-left-content { display: flex; flex-direction: column; gap: 8px; flex: 1; }
        .header-left-content h2 { font-size: 16px; font-weight: 600; color: #64748b; margin: 0; display: flex; align-items: center; gap: 6px; letter-spacing: -0.3px; }
        .header-left-content h2 span { color: #0f172a; font-weight: 800; }
        .unit-info-group { display: flex; flex-direction: column; gap: 2px; }
        .unit-code-text { font-size: 13px; font-weight: 700; color: #A08348; }
        .unit-title-text { font-size: 14px; color: #334155; font-weight: 500; line-height: 1.5; }
        .btn-submit.btn-update-style { background: #f8fafc; color: #3b82f6; border: 1px solid #3b82f6;}
        .btn-submit.btn-update-style:hover { background: #3b82f6; color: white;}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>

    <div class="main-content">
        <?php 
        $btn_kembali = "skoring.php";
        include '../layouts/header.php'; ?>

        <div class="page-header-skoring">
            <div class="header-left-content">
                <h2><?= $is_update ? 'Ubah Penilaian:' : 'Penilaian:' ?> <span><?= htmlspecialchars($pegawai['pegawai_nama']) ?></span></h2>
                <div class="unit-info-group">
                    <div class="unit-code-text"><?= htmlspecialchars($kode_unit) ?></div>
                    <div class="unit-title-text"><?= htmlspecialchars($unit['judul_unit']) ?></div>
                </div>
            </div>
        </div>

        <?php if(empty($list_aktivitas)): ?>
            <div style="background:#fff; padding: 40px; text-align:center; border-radius: 12px; border: 1px solid #e2e8f0; color: #64748b;">
                <i class="bi bi-inbox" style="font-size: 40px; margin-bottom: 10px; display: block; color: #cbd5e1;"></i>
                <h3 style="margin: 0 0 5px 0; color: #0f172a;">Tidak Ada Aktivitas Aktif</h3>
                <p style="margin:0; font-size:13px;">Pimpinan belum mengaktifkan satu pun aktivitas untuk unit kompetensi ini.</p>
            </div>
        <?php else: ?>
        <form method="POST" id="formPenilaian">
            <div class="score-layout">
                
                <div class="score-main">
                    
                    <div class="filter-box" style="box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                        <label><i class="bi bi-funnel-fill" style="color: #A08348;"></i> Pilih Input yang Dinilai</label>
                        <select id="filterInput" onchange="filterAktivitas()">
                            <?php if(empty($list_input)): ?>
                                <option value="ALL">Tidak ada Input yang tersedia</option>
                            <?php else: ?>
                                <option value="ALL" style="font-weight: bold;">-- Tampilkan Semua Aktivitas --</option>
                                <?php foreach($list_input as $inp): ?>
                                    <option value="<?= htmlspecialchars($inp) ?>"><?= htmlspecialchars($inp) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <?php 
                    $js_data = [];

                    foreach($list_aktivitas as $akt): 
                        $id = $akt['aktivitas_id'];
                        $bobot = $akt['bobot_proporsional']; 
                        $uploaded = (int)$akt['jml_upload'];
                        $target = (int)$akt['jumlah_evidence_wa'];
                        $nama_input = $akt['nama_input'] ?? 'Tanpa Input';
                        $file_path = $akt['file_path'] ?? ''; 
                        $tanggal_upload = $akt['tanggal_upload'] ?? ''; // Tarik timestamp

                        // Tentukan default skor
                        if ($is_update && isset($skor_lama_array[$id])) {
                            $default_skor = $skor_lama_array[$id];
                        } else {
                            if ($uploaded == 0) $default_skor = 1;
                            elseif ($uploaded >= $target) $default_skor = 4;
                            elseif ($uploaded > 0 && $uploaded < $target) $default_skor = 3;
                            else $default_skor = 2;
                        }

                        $js_data[$id] = [
                            'bobot' => $bobot,
                            'skor' => $default_skor
                        ];
                    ?>
                    <div class="activity-card" data-input="<?= htmlspecialchars($nama_input) ?>">
                        <div class="activity-header">
                            <div>
                                <h3 style="margin:0; font-size: 15px; color: #0f172a; line-height: 1.4;"><?= htmlspecialchars($akt['detail_aktivitas']) ?></h3>
                                <span class="activity-id">ID: <?= htmlspecialchars($id) ?></span>
                            </div>
                            <div style="display: flex; gap: 6px; flex-direction: column; align-items: flex-end; flex-shrink: 0;">
                                <span class="badge green" style="font-size: 11px; padding: 6px 10px;" title="Bobot asli: <?= $akt['bobot_aktivitas'] ?>%">Bobot <?= $bobot ?>%</span>
                                <span class="badge blue" style="font-size: 11px; padding: 6px 10px;">Evidence: <?= $uploaded ?>/<?= $target ?></span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div class="info-box" style="margin-bottom: 0;">
                                <b><i class="bi bi-ui-checks"></i> Kriteria Kompeten</b>
                                <?= htmlspecialchars($akt['kriteria_kompetens'] ?? 'Kriteria belum didefinisikan.') ?>
                            </div>
                            
                            <div class="info-box" style="background: #fffef6; border-color: #e1dfcf; margin-bottom: 0;">
                                <b><?= $uploaded ?> bukti dokumen</b> ter-upload dari target <?= $target ?> evidence.<br>
                                <?php if($uploaded > 0): ?>
                                    <div style="margin-top: 10px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                                        <?php if(!empty($file_path)): ?>
                                            <a href="../uploads/evidence/<?= htmlspecialchars($file_path) ?>" target="_blank" class="btn-doc"><i class="bi bi-file-earmark-pdf"></i> Lihat File Dokumen</a>
                                        <?php else: ?>
                                            <span style="display: inline-block; background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; border: 1px solid #a7f3d0;"><i class="bi bi-check-circle-fill"></i> Bukti Tersimpan</span>
                                        <?php endif; ?>
                                        
                                        <!-- MENAMPILKAN TIMESTAMP -->
                                        <?php if(!empty($tanggal_upload)): ?>
                                            <span style="font-size: 11px; color: #64748b; font-weight: 500; background: white; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1;"><i class="bi bi-clock-history"></i> Upload: <b><?= date('d M Y, H:i', strtotime($tanggal_upload)) ?></b></span>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <div style="margin-top: 8px; color: #dc2626; font-size: 11px; font-weight: 600;"><i class="bi bi-exclamation-triangle"></i> Dokumen belum diunggah.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="info-box" style="margin-bottom: 12px; background: white;">
                            <b style="font-size: 14px;"></i> Rubrik Skor Berdasarkan Excel</b>
                            <span style="color: #64748b;">Pilih skor yang paling sesuai. Skor 4 adalah standar jika bukti lengkap. Skor 5 untuk analisis/validasi mendalam.</span>
                        </div>
                        
                        <input type="hidden" name="skor[<?= $id ?>]" id="input_skor_<?= $id ?>" value="<?= $default_skor ?>">

                        <div class="score-grid">
                            <?php
                            $skor_texts = [
                                1 => "Tidak ada dokumen",
                                2 => "1 dokumen tersedia",
                                3 => "2 dokumen tersedia",
                                4 => "Dokumen lengkap dan relevan",
                                5 => "Dokumen lengkap dan tervalidasi"
                            ];
                            for ($s = 1; $s <= 5; $s++): 
                                $is_active = ($s == $default_skor) ? 'active' : '';
                            ?>
                                <div class="score-card <?= $is_active ?>" id="card_<?= $id ?>_<?= $s ?>" onclick="pilihSkor('<?= $id ?>', <?= $s ?>)">
                                    <div class="score-head score-<?= $s ?>">Skor <?= $s ?></div>
                                    <div class="score-text"><?= $skor_texts[$s] ?></div>
                                    <div class="selected-badge"><i class="bi bi-check2-circle"></i> Dipilih</div>
                                </div>
                            <?php endfor; ?>
                        </div>
                        
                        <div class="info-box" style="margin-bottom:0; background: #f8fafc; border-top: 3px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                            <b style="font-size: 13px; color: #334155;">Nilai Aktivitas Saat Ini:</b>
                            <div style="font-size: 14px; color: #0f172a; background: #fff; padding: 6px 12px; border-radius: 8px; border: 1px solid #cbd5e1; font-weight: 600;">
                                <span style="color: #64748b; font-weight: 500;">Skor <span id="text_skor_<?= $id ?>"><?= $default_skor ?></span> × Bobot proporsional <?= $bobot ?>% =</span>
                                <span style="font-size: 16px; color: #A08348; margin-left: 5px;" id="text_nilai_<?= $id ?>"><?= number_format($default_skor * ($bobot/100), 2) ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <aside class="score-side">
                    <div class="side-card" style="box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);">
                        <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Hasil Total Unit</h3>
                        <div class="result-row"><span>Total Bobot</span><b id="live_total_bobot">0%</b></div>
                        <div class="result-row"><span>Skor Tertimbang</span><b id="live_skor_tertimbang">0.00 / 5</b></div>
                        <div class="result-row" style="background: #fffef6; border-color: #e1dfcf;"><span>Nilai Akhir Unit</span><b id="live_nilai_akhir" style="color: #A08348; font-size: 16px;">0.00 / 100</b></div>
                        <div class="result-row" style="margin-bottom: 0;">
                            <span>Kategori</span>
                            <span id="live_kategori" class="badge red" style="font-size: 13px; padding: 6px 12px;">Belum Kompeten</span>
                        </div>
                    </div>

                    <div class="side-card" style="box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);">
                        <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #0f172a;"><i class="bi bi-info-circle"></i> Rumus Penilaian</h3>
                        <div style="font-size: 12px; color: #475569; line-height: 1.6; background: #f8fafc; padding: 12px; border-radius: 8px;">
                            <b>Nilai Aktivitas</b> = Skor × Bobot Proporsional<br>
                            <b>Nilai Akhir</b> = (Σ nilai aktivitas / 5) × 100<br>
                            <span style="color:#b45309; font-style:italic; display: block; margin-top: 5px;">*Total nilai dihitung dari seluruh Input. Bobot disesuaikan jika ada aktivitas yang dinonaktifkan pimpinan.</span>
                        </div>
                    </div>

                    <div class="side-card" style="background: #f8fafc; border: 2px solid #e2e8f0; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);">
                        <label style="display:block; font-weight:700; color:#0f172a; margin-bottom:8px; font-size:14px;"><i class="bi bi-pencil-square"></i> Catatan (Opsional):</label>
                        <textarea name="rekomendasi" rows="4" style="width:100%; box-sizing:border-box; padding:12px; border-radius:10px; border:1px solid #cbd5e1; font-family:inherit; font-size:13px; outline:none; resize: vertical;" placeholder="Tuliskan evaluasi atau catatan akhir untuk unit ini..."><?= htmlspecialchars($rekomendasi_lama) ?></textarea>
                        
                        <button type="submit" name="simpan_nilai" class="btn-submit <?= $is_update ? 'btn-update-style' : '' ?>" style="margin-top: 15px; padding: 14px;" onclick="return confirm('<?= $is_update ? 'Update penilaian ini?' : 'Sahkan penilaian ini?' ?> Data akan disimpan permanen untuk seluruh aktivitas di unit ini.');">
                            <i class="bi bi-save"></i> <?= $is_update ? 'Update Hasil Penilaian' : 'Simpan Hasil Penilaian' ?>
                        </button>
                    </div>
                </aside>
                
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
const activities = <?= empty($js_data) ? '{}' : json_encode($js_data) ?>;

function filterAktivitas() {
    let selectedInput = document.getElementById('filterInput').value;
    let cards = document.querySelectorAll('.activity-card');
    
    cards.forEach(card => {
        if (selectedInput === 'ALL' || card.getAttribute('data-input') === selectedInput) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function pilihSkor(aktId, skorBaru) {
    for(let i = 1; i <= 5; i++) {
        document.getElementById('card_' + aktId + '_' + i).classList.remove('active');
    }
    
    document.getElementById('card_' + aktId + '_' + skorBaru).classList.add('active');
    document.getElementById('input_skor_' + aktId).value = skorBaru;
    
    activities[aktId].skor = skorBaru;
    
    let bobot = activities[aktId].bobot;
    let nilaiAkt = skorBaru * (bobot / 100);
    document.getElementById('text_skor_' + aktId).innerText = skorBaru;
    document.getElementById('text_nilai_' + aktId).innerText = nilaiAkt.toFixed(2);
    
    kalkulasiLive();
}

function kalkulasiLive() {
    let totalBobot = 0;
    let totalTertimbang = 0;
    
    for (let id in activities) {
        let b = parseFloat(activities[id].bobot);
        let s = parseInt(activities[id].skor);
        totalBobot += b;
        totalTertimbang += (s * (b / 100));
    }
    
    let normalizedScore = (totalBobot > 0) ? (totalTertimbang / (totalBobot / 100)) : 0;
    let nilaiAkhir = (normalizedScore / 5) * 100;
    
    document.getElementById('live_total_bobot').innerText = totalBobot.toFixed(0) + '%';
    document.getElementById('live_skor_tertimbang').innerText = normalizedScore.toFixed(2) + ' / 5';
    document.getElementById('live_nilai_akhir').innerText = nilaiAkhir.toFixed(2) + ' / 100';
    
    let badge = document.getElementById('live_kategori');
    
    if (nilaiAkhir >= 85) {
        badge.innerText = 'Sangat Kompeten';
        badge.className = 'badge green';
    } else if (nilaiAkhir >= 70) {
        badge.innerText = 'Kompeten';
        badge.className = 'badge green';
    } else if (nilaiAkhir >= 55) {
        badge.innerText = 'Cukup Kompeten';
        badge.className = 'badge yellow';
    } else {
        badge.innerText = 'Belum Kompeten';
        badge.className = 'badge red';
    }
}

window.onload = function() {
    if(Object.keys(activities).length > 0) {
        kalkulasiLive();
        let filterSelect = document.getElementById('filterInput');
        if (filterSelect && filterSelect.options.length > 1) {
            filterSelect.selectedIndex = 1; 
            filterAktivitas();
        }
    }
};
</script>

</body>
</html>