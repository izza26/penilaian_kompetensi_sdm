<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

// ==========================================================
// PROSES HAPUS PENILAIAN
// ==========================================================
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    pg_query_params($koneksi, "DELETE FROM penilaian_detail WHERE penilaian_id = $1", array($id_hapus));
    pg_query_params($koneksi, "DELETE FROM penilaian_header WHERE penilaian_id = $1", array($id_hapus));
    header("Location: hasil_kompetensi.php?status=sukses");
    exit;
}

// ==========================================================
// AMBIL DAFTAR PERIODE AKTIF UNTUK FILTER DROPDOWN
// ==========================================================
$q_periode = pg_query($koneksi, "SELECT DISTINCT nama_periode FROM periode_penilaian ORDER BY nama_periode ASC");
$list_periode = pg_fetch_all($q_periode) ?: [];

// Array Bulan
$arr_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$tahun_sekarang = date('Y');
$arr_tahun = [];
for ($i = 2024; $i <= $tahun_sekarang + 1; $i++) {
    $arr_tahun[] = $i;
}

// ==========================================================
// LOGIKA FILTERING (JABATAN + BULAN + TAHUN)
// ==========================================================
$filter_periode = $_GET['periode'] ?? 'ALL';
$filter_bulan = $_GET['bulan'] ?? 'ALL';
$filter_tahun = $_GET['tahun'] ?? 'ALL';

$filterCond = "";
$params = [];
$param_count = 1;

if ($filter_periode !== 'ALL') {
    $filterCond .= " AND p.jabatan = $" . $param_count++;
    $params[] = $filter_periode;
}
if ($filter_bulan !== 'ALL') {
    $filterCond .= " AND EXTRACT(MONTH FROM ph.waktu_submit) = $" . $param_count++;
    $params[] = $filter_bulan;
}
if ($filter_tahun !== 'ALL') {
    $filterCond .= " AND EXTRACT(YEAR FROM ph.waktu_submit) = $" . $param_count++;
    $params[] = $filter_tahun;
}

// ==========================================================
// TARIK DATA PENILAIAN (STATUS = SELESAI)
// ==========================================================
$sql = "
    SELECT 
        ph.penilaian_id,
        ph.pegawai_id,
        p.pegawai_nama,
        p.jabatan,
        ph.kode_unit,
        uk.judul_unit,
        ph.nilai_akhir,
        ph.kategori,
        ph.waktu_submit,
        ph.rekomendasi
    FROM penilaian_header ph
    JOIN pegawai p ON ph.pegawai_id = p.pegawai_id
    JOIN unit_kompetensi uk ON ph.kode_unit = uk.kode_unit
    WHERE ph.status = 'Selesai' $filterCond
    ORDER BY ph.waktu_submit DESC
";

if (count($params) > 0) {
    $q_riwayat = pg_query_params($koneksi, $sql, $params);
} else {
    $q_riwayat = pg_query($koneksi, $sql);
}

$list_riwayat = pg_fetch_all($q_riwayat) ?: [];

// ==========================================================
// KALKULASI WIDGET ATAS (HANYA 4 KATEGORI KELULUSAN)
// ==========================================================
$stat_sangat_kompeten = 0;
$stat_kompeten = 0;
$stat_cukup_kompeten = 0;
$stat_belum_kompeten = 0;

foreach ($list_riwayat as $r) {
    $kat = trim($r['kategori']);
    if ($kat === "Sangat Kompeten") $stat_sangat_kompeten++;
    elseif ($kat === "Kompeten") $stat_kompeten++;
    elseif ($kat === "Cukup Kompeten") $stat_cukup_kompeten++;
    else $stat_belum_kompeten++; 
}

$page_title = "Hasil Kompetensi";
$page_subtitle = "Kelola hasil akhir penilaian kompetensi pegawai";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Kompetensi | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* WIDGET KEMBALI KE GAYA CLASSIC (4 KOTAK HORIZONTAL) */
        .widget-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .widget-box { background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; transition: 0.2s;}
        .widget-box:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.04); }
        .widget-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 22px; flex-shrink: 0;}
        .widget-info { display: flex; flex-direction: column; gap: 2px;}
        .widget-info h4 { margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700; line-height: 1.3;}
        .widget-info p { margin: 0; font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1; }

        /* WARNA ICON KLASIK */
        .w-sangat .widget-icon { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;}
        .w-kompeten .widget-icon { background: #f0fdfa; color: #0d9488; border: 1px solid #bbf7d0;}
        .w-cukup .widget-icon { background: #fffbeb; color: #d97706; border: 1px solid #fde68a;}
        .w-belum .widget-icon { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}

        .filter-box { background: #ffffff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.02); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;}
        .filter-left { display: flex; align-items: center; gap: 15px; flex: 1; flex-wrap: wrap;}
        .filter-left label { font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap; margin: 0; }
        .filter-left select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; color: #1e293b; outline: none; cursor: pointer; font-family: inherit; transition: 0.2s; }
        .filter-left select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .btn-cetak-excel:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transform: translateY(-2px); color: white;}

        .table-card { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; }
        .table-card h3 { margin: 0 0 5px 0; font-size: 16px; color: #0f172a; }
        .table-card p { margin: 0 0 20px 0; font-size: 13px; color: #64748b; }

        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        .row-clickable { transition: 0.2s; cursor: pointer; }
        .row-clickable:hover { background-color: #f8fafc; }
        .row-clickable:hover td { border-bottom-color: #cbd5e1; }

        .badge { padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; display: inline-flex; justify-content: center; width: 120px; text-align: center; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; } 
        .badge-kuning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; } 
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; } 

        .btn-aksi { width: 34px; height: 34px; border-radius: 8px; border: none; cursor: pointer; transition: 0.2s; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; margin: 0 3px; text-decoration: none;}
        .btn-detail { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;} .btn-detail:hover { background: #3b82f6; color: white; }
        .btn-hapus { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca;} .btn-hapus:hover { background: #ef4444; color: white; }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>

        <!-- WIDGET 4 KATEGORI (Gaya Horizontal Classic Dashboard) -->
        <div class="widget-container">
            <div class="widget-box w-sangat">
                <div class="widget-icon"><i class="bi bi-star-fill"></i></div>
                <div class="widget-info">
                    <h4>Sangat Kompeten</h4>
                    <p><?= $stat_sangat_kompeten ?></p>
                </div>
            </div>
            <div class="widget-box w-kompeten">
                <div class="widget-icon"><i class="bi bi-check-circle-fill"></i></div>
                <div class="widget-info">
                    <h4>Kompeten</h4>
                    <p><?= $stat_kompeten ?></p>
                </div>
            </div>
            <div class="widget-box w-cukup">
                <div class="widget-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
                <div class="widget-info">
                    <h4>Cukup Kompeten</h4>
                    <p><?= $stat_cukup_kompeten ?></p>
                </div>
            </div>
            <div class="widget-box w-belum">
                <div class="widget-icon"><i class="bi bi-x-circle-fill"></i></div>
                <div class="widget-info">
                    <h4>Belum Kompeten</h4>
                    <p><?= $stat_belum_kompeten ?></p>
                </div>
            </div>
        </div>

        <!-- FITUR FILTER MULTI (JABATAN, BULAN, TAHUN) -->
        <div class="filter-box">
            <form method="GET" id="formFilter" class="filter-left">
                <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Filter Penilaian:</label>
                
                <select name="periode" onchange="document.getElementById('formFilter').submit();" style="width: 180px;">
                    <option value="ALL" <?= $filter_periode == 'ALL' ? 'selected' : '' ?>>Semua Jabatan</option>
                    <?php foreach($list_periode as $p): ?>
                        <option value="<?= htmlspecialchars($p['nama_periode']) ?>" <?= $filter_periode == $p['nama_periode'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_periode']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="bulan" onchange="document.getElementById('formFilter').submit();" style="width: 130px;">
                    <option value="ALL" <?= $filter_bulan == 'ALL' ? 'selected' : '' ?>>Semua Bulan</option>
                    <?php foreach($arr_bulan as $num => $str): ?>
                        <option value="<?= $num ?>" <?= $filter_bulan == $num ? 'selected' : '' ?>><?= $str ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tahun" onchange="document.getElementById('formFilter').submit();" style="width: 120px;">
                    <option value="ALL" <?= $filter_tahun == 'ALL' ? 'selected' : '' ?>>Semua Tahun</option>
                    <?php foreach($arr_tahun as $thn): ?>
                        <option value="<?= $thn ?>" <?= $filter_tahun == $thn ? 'selected' : '' ?>><?= $thn ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            
            <a href="cetak_excel.php?periode=<?= urlencode($filter_periode) ?>&bulan=<?= urlencode($filter_bulan) ?>&tahun=<?= urlencode($filter_tahun) ?>" class="btn-cetak-excel" target="_blank" title="Unduh rekapitulasi nilai ini dalam format Excel">
                <i class="bi bi-file-earmark-excel-fill"></i> Cetak Penilaian
            </a>
        </div>

        <div class="table-card" style="padding: 0;">
            <div style="padding: 25px 25px 15px 25px;">
                <h3>Riwayat Penilaian Pegawai</h3>
                <p>Daftar nilai akhir kompetensi yang telah disahkan oleh Pimpinan.</p>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;">No</th>
                        <th width="20%">Nama Pegawai</th>
                        <th width="30%">Unit Kompetensi</th>
                        <th width="15%" style="text-align: center;">Tgl Disahkan</th>
                        <th width="10%" style="text-align: center;">Skor (100)</th>
                        <th width="10%" style="text-align: center;">Kategori</th>
                        <th width="10%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($list_riwayat)): ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="bi bi-inbox" style="font-size: 32px; display:block; margin-bottom:10px;"></i> Belum ada data penilaian.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach($list_riwayat as $r): 
                            // Sinkronisasi Warna Badge Kategori 4 Status
                            $kat = trim($r['kategori']);
                            if ($kat === "Sangat Kompeten" || $kat === "Kompeten") {
                                $badge_class = 'badge-hijau';
                            } elseif ($kat === "Cukup Kompeten") {
                                $badge_class = 'badge-kuning';
                            } else {
                                $badge_class = 'badge-merah';
                            }
                        ?>
                        <tr class="row-clickable">
                            <td style="text-align: center; color: #64748b; font-weight: 600;" onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'"><?= $no++ ?></td>
                            
                            <td onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'">
                                <b style="color: #0f172a; font-size: 13px;"><?= htmlspecialchars($r['pegawai_nama']) ?></b><br>
                                <span style="font-size: 12px; color: #64748b;"><?= htmlspecialchars($r['jabatan']) ?></span>
                            </td>
                            
                            <td onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'">
                                <span style="font-size: 11px; font-weight: 700; color: #A08348; display: block; margin-bottom: 2px;"></i> <?= htmlspecialchars($r['kode_unit']) ?></span>
                                <span style="color: #334155; line-height: 1.4; font-weight: 500; font-size: 13px;"><?= htmlspecialchars($r['judul_unit']) ?></span>
                            </td>

                            <td style="text-align: center; font-size: 12px; color: #475569; font-weight: 500;" onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'">
                                <?= date('d M Y', strtotime($r['waktu_submit'])) ?>
                            </td>
                            
                            <td style="text-align: center;" onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'">
                                <b style="font-size: 16px; color: #0f172a;"><?= number_format($r['nilai_akhir'], 2) ?></b>
                            </td>
                            
                            <td style="text-align: center;" onclick="window.location.href='detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>'">
                                <span class="badge <?= $badge_class ?>"><?= $r['kategori'] ?></span>
                            </td>
                            
                            <td style="text-align: center; white-space: nowrap;">
                                <a href="detail_hasil_kompetensi.php?id=<?= $r['penilaian_id'] ?>" class="btn-aksi btn-detail" title="Lihat Rincian Skor">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                <a href="?hapus=<?= $r['penilaian_id'] ?>" class="btn-aksi btn-hapus" title="Hapus Penilaian" onclick="return confirm('Yakin ingin menghapus riwayat penilaian ini secara permanen?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
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
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('status') === 'sukses') {
    Swal.fire({ icon: 'success', title: 'Terhapus!', text: 'Riwayat penilaian berhasil dihapus permanen.', timer: 2000, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname);
}
</script>

</body>
</html>