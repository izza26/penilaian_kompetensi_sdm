<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$page_title = "Profil Pimpinan";
$page_subtitle = "Kelola informasi akun dan otoritas Anda";

$idPimpinan = $_SESSION['pegawai_id'];

/*
|--------------------------------------------------------------------------
| Data Pimpinan (Diambil dari tabel pegawai karena pimpinan juga pegawai)
|--------------------------------------------------------------------------
*/
$queryPimpinan = pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPimpinan));
$pimpinan = pg_fetch_assoc($queryPimpinan);

/*
|--------------------------------------------------------------------------
| Kalkulasi Statistik Kinerja Pimpinan
|--------------------------------------------------------------------------
*/
// 1. Total Penilaian yang sudah diselesaikan
$q_penilaian = pg_query("SELECT COUNT(*) AS total FROM penilaian_header WHERE status = 'Selesai'");
$totalPenilaian = pg_fetch_assoc($q_penilaian)['total'];

// 2. Total Pegawai Unik yang pernah dinilai
$q_pegawai_dinilai = pg_query("SELECT COUNT(DISTINCT pegawai_id) AS total_pegawai FROM penilaian_header WHERE status = 'Selesai'");
$totalPegawaiDinilai = pg_fetch_assoc($q_pegawai_dinilai)['total_pegawai'];

// 3. Rata-rata Skor Total yang diberikan
$q_rata_rata = pg_query("SELECT AVG(nilai_akhir) AS avg_skor FROM penilaian_header WHERE status = 'Selesai'");
$rataRataSkor = round((float)pg_fetch_assoc($q_rata_rata)['avg_skor'], 1);

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Pimpinan | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .profil-container { padding: 10px 0; }

        /* HERO PROFILE PIMPIMAN */
        .hero-profile { background: linear-gradient(135deg, #1B2D46 0%, #29466b 100%); border-radius: 20px; padding: 40px; display: flex; justify-content: space-between; align-items: center; gap: 30px; color: white; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(27, 45, 70, 0.15); position: relative; overflow: hidden; }
        .hero-profile::after { content: ''; position: absolute; right: -50px; bottom: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.03); border-radius: 50%; }
        
        .hero-left { z-index: 2; flex: 1; }
        .hero-label { background: rgba(255,255,255,0.1); padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #bda572; display: inline-block; margin-bottom: 15px; border: 1px solid rgba(160, 131, 72, 0.4);}
        .hero-profile h1 { margin: 0 0 10px 0; font-size: 36px; font-weight: 800; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .hero-position { font-size: 16px; color: #e2e8f0; margin-bottom: 5px; display: flex; align-items: center; gap: 8px;}
        .hero-unit { font-size: 14px; color: #cbd5e1; display: flex; align-items: center; gap: 8px;}
        .hero-contact { display: flex; gap: 20px; margin-top: 25px; flex-wrap: wrap; }
        .hero-contact div { background: rgba(255,255,255,0.08); padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);}

        /* SUMMARY STATS (RIGHT) - DIPERBAIKI AGAR TIDAK KEPOTONG */
        .hero-right { 
            background: #ffffff; 
            padding: 25px; 
            border-radius: 16px; 
            width: 320px; /* Sedikit diperlebar */
            flex-shrink: 0; /* KUNCI: Mencegah kotak menyusut/gepeng saat layar mengecil */
            box-sizing: border-box; /* Memastikan padding tidak merusak width */
            z-index: 2; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.1); 
        }
        
        .summary-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .summary-top span { font-size: 13px; font-weight: 600; color: #64748b; }
        .status-active { background: #ecfdf5; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 6px; border: 1px solid #a7f3d0;}
        .summary-divider { height: 1px; background: #e2e8f0; margin-bottom: 15px; }
        .summary-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .summary-item small { font-size: 13px; color: #475569; font-weight: 500; }
        .summary-item strong { font-size: 18px; color: #0f172a; font-weight: 700; }

        /* GRID INFORMASI PRIBADI */
        .card-section { margin-bottom: 30px; background: #fff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02);}
        .section-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; padding-bottom: 15px; border-bottom: 1px solid #f1f5f9; }
        .section-title i { color: #A08348; }
        
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        .info-card { background: #f8fafc; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0; }
        .info-card span { display: block; font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
        .info-card h4 { margin: 0; font-size: 14px; color: #1e293b; font-weight: 600; line-height: 1.4; }

        /* PENGATURAN AKUN */
        .setting-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .setting-card { display: flex; align-items: center; gap: 15px; padding: 20px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; text-decoration: none; transition: 0.2s; box-shadow: 0 2px 10px rgba(0,0,0,0.01);}
        .setting-card:hover { border-color: #cbd5e1; box-shadow: 0 5px 15px rgba(0,0,0,0.05); transform: translateY(-2px);}
        .setting-icon { width: 45px; height: 45px; border-radius: 10px; background: #eff6ff; color: #3b82f6; display: flex; justify-content: center; align-items: center; font-size: 20px; }
        .setting-card h4 { margin: 0 0 5px 0; font-size: 15px; color: #0f172a; font-weight: 600; }
        .setting-card p { margin: 0; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pimpinan.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <div class="profil-container">

            <div class="hero-profile">
                <div class="hero-left">
                    <span class="hero-label">PROFIL PIMPINAN</span>
                    <h1><?= htmlspecialchars($pimpinan['pegawai_nama']); ?></h1>
                    
                    <div class="hero-position">
                        <i class="bi bi-person-workspace"></i> <?= htmlspecialchars($pimpinan['jabatan']); ?>
                    </div>
                    <div class="hero-unit">
                        <i class="bi bi-building"></i> Museum Geologi
                    </div>

                    <div class="hero-contact">
                        <div><i class="bi bi-person-vcard"></i> <?= htmlspecialchars($pimpinan['nip_nik']); ?></div>
                        <div><i class="bi bi-envelope"></i> <?= htmlspecialchars($pimpinan['email']); ?></div>
                        <div><i class="bi bi-telephone"></i> <?= htmlspecialchars($pimpinan['no_hp']); ?></div>
                    </div>
                </div>

                <div class="hero-right">
                    <div class="profile-summary">
                        <div class="summary-top">
                            <span>Status Akun Pimpinan</span>
                            <div class="status-active"><i class="bi bi-check-circle-fill"></i> Aktif</div>
                        </div>
                        <div class="summary-divider"></div>
                        <div class="summary-item">
                            <small>Total Riwayat Penilaian</small>
                            <strong><?= $totalPenilaian; ?> Data</strong>
                        </div>
                        <div class="summary-item">
                            <small>Pegawai Telah Dinilai</small>
                            <strong><?= $totalPegawaiDinilai; ?> Orang</strong>
                        </div>
                        <div class="summary-item" style="margin-bottom:0;">
                            <small>Rata-rata Skor Diberikan</small>
                            <strong style="color: #A08348;"><?= $rataRataSkor; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-section">
                <div class="section-title"><i class="bi bi-person-lines-fill"></i> Informasi Kredensial</div>
                <div class="info-grid">
                    <div class="info-card"><span>Nama Lengkap</span><h4><?= htmlspecialchars($pimpinan['pegawai_nama']); ?></h4></div>
                    <div class="info-card"><span>NIP / NIK</span><h4><?= htmlspecialchars($pimpinan['nip_nik']); ?></h4></div>
                    <div class="info-card"><span>Role Otoritas</span><h4><span style="color:#b56c35;">Pimpinan</span></h4></div>
                    <div class="info-card"><span>Email Kontak</span><h4><?= htmlspecialchars($pimpinan['email']); ?></h4></div>
                    <div class="info-card"><span>Nomor Handphone</span><h4><?= htmlspecialchars($pimpinan['no_hp']); ?></h4></div>
                    <div class="info-card"><span>Status Unit Kerja</span><h4>Museum Geologi - ESDM</h4></div>
                </div>
            </div>

            <div class="card-section" style="margin-bottom: 0;">
                <div class="section-title"><i class="bi bi-shield-check"></i> Pengaturan Kemanan Akun</div>
                <div class="setting-grid">
                    <a href="#" class="setting-card">
                        <div class="setting-icon" style="background:#fefce8; color:#A08348;"><i class="bi bi-pencil-square"></i></div>
                        <div>
                            <h4>Edit Kredensial</h4>
                            <p>Perbarui data email, NIP, atau kontak Anda.</p>
                        </div>
                    </a>
                    <a href="#" class="setting-card">
                        <div class="setting-icon" style="background:#fef2f2; color:#ef4444;"><i class="bi bi-key-fill"></i></div>
                        <div>
                            <h4>Ubah Password Keamanan</h4>
                            <p>Ganti password akses portal Pimpinan.</p>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>