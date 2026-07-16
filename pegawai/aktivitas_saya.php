<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pegawai');
require_once '../config/koneksi.php';

$page_title = "Aktivitas Saya";
$page_subtitle = "Pantau seluruh aktivitas kompetensi Anda";

$idPegawai = $_SESSION['pegawai_id'];

/* ==========================================================
   DATA PEGAWAI & DETEKSI JABATAN
========================================================== */
$queryPegawai = pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($queryPegawai);

$jabatanPegawai = !empty($pegawai['jabatan']) ? $pegawai['jabatan'] : 'Belum Ada Jabatan'; 

/* ==========================================================
   CEK PERIODE PENILAIAN (OTOMATISASI WAKTU)
========================================================== */
$qPeriode = pg_query($koneksi, "SELECT * FROM periode_penilaian WHERE status_aktif = 'Y' LIMIT 1");
$periodeAktif = pg_fetch_assoc($qPeriode);

$is_open = false;
$pesan_periode = "Belum ada periode penilaian yang aktif saat ini.";
$badge_class = "danger";

if ($periodeAktif) {
    $tgl_mulai = strtotime($periodeAktif['tanggal_mulai']);
    $tgl_selesai = strtotime($periodeAktif['tanggal_selesai'] . ' 23:59:59'); 
    $sekarang = time();

    if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) {
        $is_open = true;
        $pesan_periode = "Periode Pengisian: <b>" . date('d M Y', $tgl_mulai) . "</b> s/d <b>" . date('d M Y', $tgl_selesai) . "</b>.";
        $badge_class = "success";
    } elseif ($sekarang < $tgl_mulai) {
        $pesan_periode = "Periode Pengisian baru akan dibuka pada tanggal <b>" . date('d M Y', $tgl_mulai) . "</b>.";
        $badge_class = "warning";
    } else {
        $pesan_periode = "Periode Pengisian telah DITUTUP sejak <b>" . date('d M Y', $tgl_selesai) . "</b>.";
        $badge_class = "danger";
    }
}

/* ==========================================================
   QUERY AKTIVITAS (GROUP BY UNIT KOMPETENSI)
========================================================== */
$sqlData = 
"SELECT 
    ak.aktivitas_id, 
    ak.detail_aktivitas, 
    ak.jumlah_evidence_wa, 
    ek.elemen_kompetensi, 
    uk.kode_unit, 
    uk.judul_unit,
    (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = $idPegawai) as is_uploaded
FROM aktivitas_kompeten ak
LEFT JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
WHERE uk.posisi_target ILIKE $1
ORDER BY uk.kode_unit ASC, ek.elemen_id ASC, ak.aktivitas_id ASC
";

// Parameter 1 pasti Jabatan Pegawai
$queryAktivitas = pg_query_params($koneksi, $sqlData, array("%" . $jabatanPegawai . "%"));

// Grouping data berdasarkan Unit Kompetensi
$dataGrouped = [];
while($row = pg_fetch_assoc($queryAktivitas)){
    $unitKey = $row['kode_unit'] . "|||" . $row['judul_unit'];
    
    // Logika Status: Jika ada yg diupload, ubah status
    if($row['is_uploaded'] > 0){
        $row['status_asli'] = "Diproses"; 
        $row['classStatus'] = "warning";
    } else {
        $row['status_asli'] = "Belum Dimulai"; 
        $row['classStatus'] = "danger";
    }

    $dataGrouped[$unitKey][] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivitas Saya | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pegawai/aktivitas_saya.css?v=<?= time(); ?>">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <!-- ALERT PERIODE (VERSI KECIL & RAPI) -->
        <?php if($periodeAktif): ?>
            <div class="alert-periode alert-<?= $badge_class ?>">
                <i class="bi <?= $is_open ? 'bi-calendar-check' : ($badge_class == 'warning' ? 'bi-hourglass-split' : 'bi-calendar-x') ?>" style="font-size: 22px;"></i>
                <div>
                    <h4 style="margin:0 0 2px 0; font-size: 14px;"><?= htmlspecialchars($periodeAktif['nama_periode']) ?></h4>
                    <p style="margin:0; font-size: 12px;"><?= $pesan_periode ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="alert-periode alert-info">
                <i class="bi bi-info-circle" style="font-size: 22px;"></i>
                <div>
                    <h4 style="margin:0 0 2px 0; font-size: 14px;">Tidak Ada Periode Aktif</h4>
                    <p style="margin:0; font-size: 12px;">Pimpinan belum membuka periode penilaian kompetensi. Harap menunggu informasi selanjutnya.</p>
                </div>
            </div>
        <?php endif; ?>

        <?php if(empty($dataGrouped)): ?>
            <div style="padding: 60px 0; text-align: center; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                <i class="bi bi-person-workspace" style="font-size: 48px; color: #94a3b8; display: block; margin-bottom: 10px;"></i>
                <h3 style="margin: 0; color: #1e293b; font-size: 16px;">Tidak Ada Aktivitas Ditugaskan</h3>
                <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Pimpinan belum menugaskan Unit Kompetensi apapun untuk posisi <b><?= htmlspecialchars($jabatanPegawai) ?></b>.</p>
            </div>
        <?php else: ?>
            
            <div class="filter-box">
                <label><i class="bi bi-funnel-fill" style="color: #A08348;"></i> Pilih Unit Kompetensi untuk Menampilkan Aktivitas</label>
                <select id="filterUnit" onchange="filterUnit()">
                    <option value="ALL" style="font-weight: bold;">-- Tampilkan Semua Unit Kompetensi --</option>
                    <?php $limit = 5; ?>
                    <?php foreach($dataGrouped as $unitKey => $aktivitasList): 
                        $ex = explode("|||", $unitKey);
                        $kd_unit = $ex[0];
                        $jd_unit = $ex[1];
                    ?>
                        <option value="<?= htmlspecialchars($kd_unit) ?>">[<?= htmlspecialchars($kd_unit) ?>] - <?= htmlspecialchars($jd_unit) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php $limit = 5; ?>
            <?php foreach($dataGrouped as $unitKey => $aktivitasList): 
                $ex = explode("|||", $unitKey);
                $kd_unit = $ex[0];
                $jd_unit = $ex[1];
                $totalData = count($aktivitasList);
                $totalPage = ceil($totalData / $limit);
                $pageParam = 'page_' . md5($kd_unit);
                $currentPage = isset($_GET[$pageParam]) ? (int)$_GET[$pageParam] : 1;
                if ($currentPage < 1) {
                    $currentPage = 1;
                }

                if ($currentPage > $totalPage) {
                    $currentPage = $totalPage;
                }
                $offset = ($currentPage - 1) * $limit;
                $aktivitasPage = array_slice($aktivitasList, $offset, $limit);
            ?>
            
            <div id="<?= htmlspecialchars($kd_unit) ?>" class="unit-card" data-unit="<?= htmlspecialchars($kd_unit) ?>">
                <div class="unit-header">
                    <span><i class="bi bi-tags-fill"></i> <?= htmlspecialchars($kd_unit) ?></span>
                    <h3><?= htmlspecialchars($jd_unit) ?></h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th width="5%" style="text-align: center;">No</th>
                                <th width="45%">Detail Aktivitas & Elemen</th>
                                <th width="15%" style="text-align: center;">Evidence Wajib</th>
                                <th width="15%" style="text-align: center;">Status</th>
                                <th width="20%" style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; foreach($aktivitasPage as $akt): ?>
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600;"><?= $no++ ?></td>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">
                                        <?= htmlspecialchars($akt['detail_aktivitas']) ?>
                                    </div>
                                    <div style="font-size: 11px; color: #64748b;">
                                        <i class="bi bi-arrow-return-right"></i> <?= htmlspecialchars($akt['elemen_kompetensi']) ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge badge-doc">
                                        <i class="bi bi-file-earmark-text"></i> <?= $akt['jumlah_evidence_wa'] ?> Dokumen
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge badge-<?= $akt['classStatus'] ?>">
                                        <?= $akt['status_asli'] ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <?php if ($is_open): ?>
                                        <!-- LOGIKA BARU: Cek apakah sudah upload atau belum -->
                                        <?php if ($akt['is_uploaded'] > 0): ?>
                                            <a href="aktivitas_detail.php?id=<?= urlencode($akt['aktivitas_id']) ?>" class="btn-detail">
                                                <i class="bi bi-eye-fill"></i> Lihat Detail
                                            </a>
                                        <?php else: ?>
                                            <a href="aktivitas_detail.php?id=<?= urlencode($akt['aktivitas_id']) ?>" class="btn-upload">
                                                <i class="bi bi-cloud-arrow-up-fill"></i> Upload Bukti
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="#" class="btn-locked" onclick="alert('Pengisian Tidak Tersedia. <?= strip_tags($pesan_periode) ?>'); return false;">
                                            <i class="bi bi-lock-fill"></i> Terkunci
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if($totalPage > 1): ?>

<div style="display:flex;justify-content:center;gap:6px;padding:15px;">

<?php
$queryString = $_GET;
?>

<?php if($currentPage > 1): ?>

<?php
$queryString[$pageParam] = $currentPage - 1;
?>

<a href="?<?= http_build_query($queryString) ?>#<?= urlencode($kd_unit) ?>"
style="
padding:6px 12px;
border:1px solid #d1d5db;
border-radius:8px;
text-decoration:none;
background:#fff;
color:#1B2D46;
font-size:12px;
font-weight:600;
">
«
</a>

<?php endif; ?>

<?php for($i=1;$i<=$totalPage;$i++): ?>

<?php
$queryString[$pageParam] = $i;
?>

<a href="?<?= http_build_query($queryString) ?>#<?= urlencode($kd_unit) ?>"
style="
padding:6px 12px;
border-radius:8px;
text-decoration:none;
font-size:12px;
font-weight:600;
border:1px solid #d1d5db;
<?= $i==$currentPage
? 'background:#1B2D46;color:#fff;'
: 'background:#fff;color:#1B2D46;' ?>
">
<?= $i ?>
</a>

<?php endfor; ?>

<?php if($currentPage < $totalPage): ?>

<?php
$queryString[$pageParam] = $currentPage + 1;
?>

<a href="?<?= http_build_query($queryString) ?>#<?= urlencode($kd_unit) ?>"
style="
padding:6px 12px;
border:1px solid #d1d5db;
border-radius:8px;
text-decoration:none;
background:#fff;
color:#1B2D46;
font-size:12px;
font-weight:600;
">
»
</a>

<?php endif; ?>

</div>

<?php endif; ?>
                </div>
            </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>
</div>

<script>
// FUNGSI UNTUK FILTER BERDASARKAN UNIT
function filterUnit() {
    let selectedUnit = document.getElementById('filterUnit').value;
    let cards = document.querySelectorAll('.unit-card');
    
    cards.forEach(card => {
        // Tampilkan jika pilihan adalah 'ALL' atau kode unit-nya sama dengan data-unit
        if (selectedUnit === 'ALL' || card.getAttribute('data-unit') === selectedUnit) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

window.onload = function() {
    let filterSelect = document.getElementById('filterUnit');
    if (filterSelect) {
        filterSelect.value = 'ALL'; // Setel ke opsi 'ALL' (Tampilkan Semua)
        filterUnit(); // Jalankan filter untuk memunculkan semua unit
    }
};
</script>

</body>
</html>