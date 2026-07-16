<?php
session_start();

require_once '../auth/cek_role.php';
cekRole('pegawai');

require_once '../config/koneksi.php';

$page_title = "Hasil Kompetensi";
$page_subtitle = "Pantau hasil evaluasi dan rekomendasi pengembangan Anda";

$idPegawai = $_SESSION['pegawai_id'];

/* ==========================================================
   AMBIL DATA PEGAWAI (UNTUK MENDAPATKAN JABATAN)
========================================================== */
$queryPegawai = pg_query_params($koneksi, "SELECT jabatan FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($queryPegawai);
$jabatanPegawai = !empty($pegawai['jabatan']) ? $pegawai['jabatan'] : 'Belum Ada Jabatan';

$periode_terpilih = isset($_GET['periode_id']) ? $_GET['periode_id'] : null;

// QUERY 1: DATA PERIODE UNTUK DROPDOWN (DIFILTER SESUAI JABATAN)
$queryPeriode = pg_query_params(
    $koneksi,
    "SELECT periode_id, nama_periode, tanggal_selesai
     FROM periode_penilaian
     WHERE nama_periode ILIKE $1
     ORDER BY tanggal_selesai DESC",
    array($jabatanPegawai) 
);
$listPeriode = pg_fetch_all($queryPeriode) ?: [];

$hasilRingkasan = null;
$hasilDetail = [];

/* ==========================================================
   PAGINATION
========================================================== */
$limit = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$totalData = 0;
$totalPage = 1;

if ($periode_terpilih) {
    // QUERY 2: DATA RINGKASAN
    $queryRingkasan = pg_query_params(
        $koneksi,
        "SELECT nilai_akhir, kategori
         FROM penilaian_header
         WHERE pegawai_id = $1
           AND periode_id = $2
         ORDER BY waktu_submit DESC
         LIMIT 1",
        array($idPegawai, $periode_terpilih)
    );
    $hasilRingkasan = $queryRingkasan ? pg_fetch_assoc($queryRingkasan) : null;

    /* ==========================================================
    HITUNG TOTAL DATA
    ========================================================== */
    $qTotal = pg_query_params(
        $koneksi,
        "
        SELECT COUNT(*)
        FROM penilaian_header ph
        JOIN penilaian_detail pd
            ON ph.penilaian_id = pd.penilaian_id
        WHERE
            ph.pegawai_id = $1
            AND ph.periode_id = $2
        ",
        array(
            $idPegawai,
            $periode_terpilih
        )
    );
    $totalData = (int) pg_fetch_result($qTotal, 0, 0);
    $totalPage = max(1, ceil($totalData / $limit));

    /* ==========================================================
    QUERY 3 : RINCIAN HASIL PENILAIAN
    ========================================================== */

    $queryDetail = pg_query_params(
        $koneksi,
        "
        SELECT
            pd.aktivitas_id,
            ak.detail_aktivitas,

            pd.skor_self,
            pd.skor_atasan,
            pd.skor_sejawat,

            pd.skor_final,

            ph.nilai_akhir,
            ph.kategori,
            ph.rekomendasi

        FROM penilaian_header ph

        JOIN penilaian_detail pd
            ON ph.penilaian_id = pd.penilaian_id

        JOIN aktivitas_kompeten ak
            ON pd.aktivitas_id = ak.aktivitas_id

        WHERE
            ph.pegawai_id = $1
            AND ph.periode_id = $2

        ORDER BY
            ak.aktivitas_id

        LIMIT $3
        OFFSET $4
        ",
        array(
            $idPegawai,
            $periode_terpilih,
            $limit,
            $offset
        )
    );

    $hasilDetail = $queryDetail
        ? pg_fetch_all($queryDetail)
        : [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title; ?></title>

    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css"> 
    <link rel="stylesheet" href="../assets/css/css_pegawai/hasil_kompetensi.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* Style untuk tombol Cetak Excel */
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .btn-cetak-excel:hover { background: #059669; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); transform: translateY(-2px); color: white;}
        .header-title-wrapper { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;}
        .header-title-wrapper .section-title { margin-bottom: 0; border-bottom: none; padding-bottom: 0;}
    </style>
</head>

<body>
    <div class="app">
        <?php include "../layouts/sidebar_pegawai.php"; ?>

        <div class="main-content">
            <?php include "../layouts/header.php"; ?>

            <div class="hasil-container">

                <div class="hero-card">
                    <div class="hero-left">
                        <span class="hero-label"><i class="bi bi-stars"></i> Rekapitulasi</span>
                        <h1>Hasil Kompetensi 360°</h1>
                        <p>Pilih periode penilaian pada kolom di samping untuk melihat rincian nilai akhir, kategori, dan rekomendasi pengembangan Anda secara lengkap.</p>
                    </div>

                    <div class="hero-right">
                        <div class="filter-card">
                            <span class="filter-title">Periode Penilaian</span>
                            <form action="" method="GET">
                                <select name="periode_id" class="select-periode" onchange="this.form.submit()">
                                    <?php if (empty($listPeriode)): ?>
                                        <option value="">-- Belum Ada Periode --</option>
                                    <?php else: ?>
                                        <option value="">-- Pilih Periode --</option>
                                        <?php foreach ($listPeriode as $periode): ?>
                                            <option value="<?= $periode['periode_id']; ?>" <?= ($periode_terpilih == $periode['periode_id']) ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($periode['nama_periode']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                </div>

                <?php if ($periode_terpilih): ?>
                    
                    <!-- Wrapper Judul & Tombol Excel -->
                    <div class="header-title-wrapper">
                        <div class="section-title">
                            <span><i class="bi bi-bar-chart-fill"></i> Ringkasan Penilaian</span>
                            <p style="margin-top: 5px;">Nilai akhir dan kategori kompetensi Anda pada periode ini.</p>
                        </div>
                        <a href="cetak_excel.php?periode_id=<?= urlencode($periode_terpilih) ?>" class="btn-cetak-excel" target="_blank" title="Unduh riwayat penilaian Anda ke Excel">
                            <i class="bi bi-file-earmark-excel-fill"></i> Cetak ke Excel
                        </a>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-card">
                            <div class="summary-icon"><i class="bi bi-graph-up-arrow"></i></div>
                            <div class="summary-info">
                                <span>Nilai Akhir Kompetensi</span>
                                <h2><?= $hasilRingkasan && $hasilRingkasan['nilai_akhir'] ? number_format($hasilRingkasan['nilai_akhir'], 2) : '0.00'; ?></h2>
                            </div>
                        </div>
                        <?php
                            $kategoriLabel = $hasilRingkasan && $hasilRingkasan['kategori'] ? $hasilRingkasan['kategori'] : '';
                            $iconClass = 'bi-dash-circle-fill';
                            $iconColor = '';
                            $textColor = 'text-accent';

                            if ($kategoriLabel === 'Kompeten' || $kategoriLabel === 'Sangat Kompeten') {
                                $iconClass = 'bi-patch-check-fill';
                                $iconColor = 'icon-green';
                                $textColor = 'text-green';
                            } elseif ($kategoriLabel === 'Cukup Kompeten') {
                                $iconClass = 'bi-exclamation-circle-fill';
                                $iconColor = 'icon-yellow';
                                $textColor = 'text-yellow';
                            } elseif ($kategoriLabel === 'Belum Kompeten') {
                                $iconClass = 'bi-exclamation-triangle-fill';
                                $iconColor = 'icon-red';
                                $textColor = 'text-red';
                            }
                        ?>
                        <div class="summary-card">
                            <div class="summary-icon <?= $iconColor ?>"><i class="bi <?= $iconClass ?>"></i></div>
                            <div class="summary-info">
                                <span>Kategori</span>
                                <h2 class="<?= $textColor ?>">
                                    <?= $kategoriLabel ? htmlspecialchars($kategoriLabel) : 'Belum Ada Data'; ?>
                                </h2>
                            </div>
                        </div>
                    </div>

                    <div class="section-title mt-40" id="rincian-penilaian">
                        <span><i class="bi bi-table"></i> Rincian Penilaian</span>
                        <p>Rincian skor penilaian berdasarkan unit kompetensi.</p>
                    </div>

                    <div class="table-card">
                        <?php if (count($hasilDetail) > 0): ?>
                            <div class="table-responsive">
                                <table class="custom-table">
                                    <thead>
                                        <tr>
                                            <th>Unit Kompetensi</th>
                                            <th class="text-center">Self</th>
                                            <th class="text-center">Atasan</th>
                                            <th class="text-center">Sejawat</th>
                                            <th class="text-center">Skor Akhir</th>
                                            <th class="text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hasilDetail as $row): ?>
                                            <tr>
                                                <td class="td-keterangan">
                                                    <div class="unit-code">
                                                        <?= htmlspecialchars($row['aktivitas_id']) ?>
                                                    </div>
                                                    <div class="unit-name">
                                                        <?= htmlspecialchars($row['detail_aktivitas']) ?>
                                                    </div>
                                                </td>
                                                <td class="text-center"><?= htmlspecialchars($row['skor_self'] ?? '0'); ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['skor_atasan'] ?? '0'); ?></td>
                                                <td class="text-center"><?= htmlspecialchars($row['skor_sejawat'] ?? '0'); ?></td>
                                                <td class="text-center">
                                                    <?php
                                                        $skor_final = (float)($row['skor_final'] ?? 0);
                                                    ?>
                                                    <span class="score-badge">
                                                        <?= number_format($skor_final, 2) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($skor_final >= 4.0): ?>
                                                        <span class="badge badge-success">Kompeten</span>
                                                    <?php elseif ($skor_final >= 3.0): ?>
                                                        <span class="badge badge-warning">Cukup Kompeten</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">Belum Kompeten</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if($totalPage > 1): ?>
                                <div class="table-footer">

                                    <div class="pagination-info">
                                        Menampilkan
                                        <strong><?= $offset + 1 ?></strong>
                                        –
                                        <strong><?= min($offset + $limit, $totalData) ?></strong>
                                        dari
                                        <strong><?= $totalData ?></strong>
                                        data
                                    </div>

                                    <div class="pagination">

                                        <!-- Previous -->

                                        <?php if($page > 1): ?>
                                            <a href="?periode_id=<?= $periode_terpilih ?>&page=<?= $page-1 ?>#rincian-penilaian" class="page-btn">
                                                <i class="bi bi-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php

                                        $start=max(1,$page-2);
                                        $end=min($totalPage,$page+2);

                                        if($start>1){

                                            ?>

                                            <a href="?periode_id=<?= $periode_terpilih ?>&page=1#rincian-penilaian" class="page-btn">
                                                1
                                            </a>

                                            <?php

                                            if($start>2){

                                                echo '<span class="dots">...</span>';

                                            }

                                        }

                                        ?>

                                        <?php for($i=$start;$i<=$end;$i++): ?>

                                            <a href="?periode_id=<?= $periode_terpilih ?>&page=<?= $i ?>#rincian-penilaian"
                                            class="page-btn <?= $page==$i?'active':'' ?>">
                                                <?= $i ?>
                                            </a>

                                        <?php endfor; ?>

                                        <?php

                                        if($end<$totalPage){

                                            if($end<$totalPage-1){

                                                echo '<span class="dots">...</span>';

                                            }

                                            ?>

                                            <a href="?periode_id=<?= $periode_terpilih ?>&page=<?= $totalPage ?>#rincian-penilaian" class="page-btn">
                                                <?= $totalPage ?>
                                            </a>

                                        <?php } ?>

                                        <?php if($page<$totalPage): ?>

                                            <a href="?periode_id=<?= $periode_terpilih ?>&page=<?= $page+1 ?>#rincian-penilaian" class="page-btn">
                                                <i class="bi bi-chevron-right"></i>
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p class="text-muted">Data rincian belum tersedia untuk periode ini.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="empty-state-card">
                        <i class="bi bi-calendar-check"></i>
                        <h3>Pilih Periode Terlebih Dahulu</h3>
                        <p>Silakan gunakan menu dropdown di atas untuk memilih periode penilaian dan melihat hasilnya.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</body>
</html>