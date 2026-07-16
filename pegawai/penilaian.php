<?php
session_start();

require_once '../auth/cek_role.php';
cekRole('pegawai');

require_once '../config/koneksi.php';

$page_title = "Penilaian";
$page_subtitle = "Pilih jenis penilaian kompetensi yang ingin Anda lihat";

$idPegawai = $_SESSION['pegawai_id'];

/* ==========================================================
   DATA PEGAWAI
========================================================== */
$queryPegawai = pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($queryPegawai);

/* ==========================================================
   DATA PERIODE AKTIF
========================================================== */
$qPeriode = @pg_query($koneksi, "SELECT * FROM periode_penilaian WHERE status_aktif = 'Y' LIMIT 1");
$periodeAktif = $qPeriode ? pg_fetch_assoc($qPeriode) : null;

if ($periodeAktif) {
    $periode = htmlspecialchars($periodeAktif['nama_periode']);
    $semester = date('d M Y', strtotime($periodeAktif['tanggal_mulai'])) . " - " . date('d M Y', strtotime($periodeAktif['tanggal_selesai']));
    $statusPeriode = "Sedang Berlangsung";
} else {
    $periode = "Tidak ada periode aktif";
    $semester = "-";
    $statusPeriode = "Ditutup";
}

/* ==========================================================
   STATISTIK PENILAIAN (PERSENTASE AKURAT BERDASARKAN AKTIVITAS)
========================================================== */
$queryStat = "
    WITH Jabatan_Pegawai AS (
        SELECT jabatan FROM pegawai WHERE pegawai_id = $1
    ),
    Target_Aktivitas AS (
        SELECT COUNT(DISTINCT ak.aktivitas_id) as total_target
        FROM aktivitas_kompeten ak
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
        WHERE uk.posisi_target ILIKE '%' || (SELECT jabatan FROM Jabatan_Pegawai) || '%' AND ak.aktif = 'Y'
    )
    SELECT 
        (SELECT total_target FROM Target_Aktivitas) as total_target_akt,
        (SELECT COUNT(DISTINCT pd.aktivitas_id) FROM penilaian_detail pd JOIN penilaian_header ph ON pd.penilaian_id = ph.penilaian_id WHERE ph.pegawai_id = $1 AND pd.skor_final > 0) as total_pimpinan,
        (SELECT COUNT(DISTINCT pd.aktivitas_id) FROM penilaian_detail pd JOIN penilaian_header ph ON pd.penilaian_id = ph.penilaian_id WHERE ph.pegawai_id = $1 AND pd.skor_sejawat > 0) as total_rekan,
        (SELECT COUNT(DISTINCT pd.aktivitas_id) FROM penilaian_detail pd JOIN penilaian_header ph ON pd.penilaian_id = ph.penilaian_id WHERE ph.pegawai_id = $1 AND pd.skor_self > 0) as total_diri
";

$qStat = @pg_query_params($koneksi, $queryStat, array($idPegawai));
$progData = $qStat ? pg_fetch_assoc($qStat) : null;

$totalTargetAkt = (int)($progData['total_target_akt'] ?? 0);
$totPimpinan    = (int)($progData['total_pimpinan'] ?? 0);
$totRekan       = (int)($progData['total_rekan'] ?? 0);
$totDiri        = (int)($progData['total_diri'] ?? 0);

function formatProgress($assessed, $total) {
    if ($total == 0) return ["progress" => 0, "status" => "Belum Ada Target", "warna" => "waiting"];
    $pct = round(($assessed / $total) * 100);
    
    if ($pct >= 100) return ["progress" => 100, "status" => "Selesai Dinilai", "warna" => "success"];
    if ($pct > 0) return ["progress" => $pct, "status" => "Sedang Berlangsung", "warna" => "running"];
    
    return ["progress" => 0, "status" => "Belum Dinilai", "warna" => "waiting"];
}

$penilaian = [
    "pimpinan" => formatProgress($totPimpinan, $totalTargetAkt),
    "rekan"    => formatProgress($totRekan, $totalTargetAkt), 
    "diri"     => formatProgress($totDiri, $totalTargetAkt)  
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Museum Geologi</title>
    
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pegawai/penilaian.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* CSS Perbaikan Tombol dan Footer Card */
        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-lihat {
            background-color: #1e3a8a;
            color: #ffffff !important;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border: none;
        }
        .btn-lihat:hover {
            background-color: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(30, 58, 138, 0.2);
        }
    </style>
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <div class="hero-card">
            <div class="hero-left">
                <div class="hero-label"><i class="bi bi-star-fill me-1"></i> Penilaian Kompetensi 360°</div>
                <h1>Cek Status Penilaian Anda</h1>
                <p>Pantau kemajuan evaluasi dari atasan, rekan kerja, dan diri sendiri atas unit kompetensi dan dokumen evidence yang telah Anda kumpulkan.</p>
            </div>
            
            <div class="hero-right">
                <div class="periode-card-modern">
                    <div class="periode-header">
                        <div class="periode-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="periode-teks">
                            <span class="label">Periode Aktif</span>
                            <span class="value"><?= $periode ?></span>
                        </div>
                    </div>
                    <div class="periode-badges">
                        <div class="badge-date">
                            <i class="bi bi-calendar3"></i> <?= $semester ?>
                        </div>
                        <div class="badge-status">
                            <i class="bi bi-circle-fill"></i> <?= $statusPeriode ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="page-card">
            <div class="penilaian-grid">

                <div class="penilaian-card pimpinan">
                    <div class="card-top">
                        <div class="card-icon"><i class="bi bi-briefcase-fill"></i></div>
                        <div class="card-progress"><span><?= $penilaian['pimpinan']['progress']; ?>%</span></div>
                    </div>
                    <h3>Penilaian Pimpinan</h3>
                    <p>Evaluasi langsung dari atasan terkait terhadap evidence yang Anda unggah.</p>
                    <div class="progress-bar">
                        <div class="fill fill-blue" style="width:<?= $penilaian['pimpinan']['progress']; ?>%"></div>
                    </div>
                    <div class="card-footer">
                        <span class="status <?= $penilaian['pimpinan']['warna']; ?>"><?= $penilaian['pimpinan']['status']; ?></span>
                        <a href="penilaian_list.php?role=pimpinan" class="btn-lihat">Lihat Penilaian <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="penilaian-card rekan">
                    <div class="card-top">
                        <div class="card-icon"><i class="bi bi-people-fill"></i></div>
                        <div class="card-progress"><span><?= $penilaian['rekan']['progress']; ?>%</span></div>
                    </div>
                    <h3>Penilaian Rekan Sejawat</h3>
                    <p>Evaluasi kolaboratif dari rekan satu tim yang memahami kinerja Anda.</p>
                    <div class="progress-bar">
                        <div class="fill fill-purple" style="width:<?= $penilaian['rekan']['progress']; ?>%"></div>
                    </div>
                    <div class="card-footer">
                        <span class="status <?= $penilaian['rekan']['warna']; ?>"><?= $penilaian['rekan']['status']; ?></span>
                        <a href="penilaian_list.php?role=rekan" class="btn-lihat">Lihat Penilaian <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

                <div class="penilaian-card diri">
                    <div class="card-top">
                        <div class="card-icon"><i class="bi bi-person-check-fill"></i></div>
                        <div class="card-progress"><span><?= $penilaian['diri']['progress']; ?>%</span></div>
                    </div>
                    <h3>Penilaian Diri Sendiri</h3>
                    <p>Self Assessment terhadap aktivitas kompetensi yang telah Anda selesaikan.</p>
                    <div class="progress-bar">
                        <div class="fill fill-orange" style="width:<?= $penilaian['diri']['progress']; ?>%"></div>
                    </div>
                    <div class="card-footer">
                        <span class="status <?= $penilaian['diri']['warna']; ?>"><?= $penilaian['diri']['status']; ?></span>
                        <a href="penilaian_list.php?role=diri" class="btn-lihat">Lihat Penilaian <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

</body>
</html>