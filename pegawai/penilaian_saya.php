<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pegawai');
require_once '../config/koneksi.php';

$page_title = "Penilaian Saya";
$page_subtitle = "Pantau hasil penilaian kompetensi Anda per Unit Kompetensi";

$idPegawai = $_SESSION['pegawai_id'];

/*
|--------------------------------------------------------------------------
| Data Pegawai Login
|--------------------------------------------------------------------------
*/
$queryPegawai = pg_query_params($koneksi, "SELECT * FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($queryPegawai);
$jabatanPegawai = !empty($pegawai['jabatan']) ? $pegawai['jabatan'] : 'Belum Ada Jabatan';

/*
|--------------------------------------------------------------------------
| QUERY DINAMIS TERHUBUNG DENGAN SISTEM SKORING PIMPINAN
|--------------------------------------------------------------------------
*/
$sqlData = "
    WITH Target_UK AS (
        SELECT ek.kode_unit, SUM(ak.jumlah_evidence_wa) AS total_target
        FROM aktivitas_kompeten ak
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        WHERE ak.aktif = 'Y'
        GROUP BY ek.kode_unit
    ),
    Upload_UK AS (
        SELECT ek.kode_unit, COUNT(bp.bukti_id) AS total_upload
        FROM bukti_pegawai bp
        JOIN aktivitas_kompeten ak ON bp.aktivitas_id = ak.aktivitas_id
        JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
        WHERE bp.pegawai_id = $1
        GROUP BY ek.kode_unit
    )
    SELECT 
        uk.kode_unit,
        uk.judul_unit,
        COALESCE(t.total_target, 0) AS target_dokumen,
        COALESCE(u.total_upload, 0) AS terkumpul_dokumen,
        ph.nilai_akhir,
        ph.kategori,
        ph.status,
        ph.penilaian_id
    FROM unit_kompetensi uk
    LEFT JOIN Target_UK t ON uk.kode_unit = t.kode_unit
    LEFT JOIN Upload_UK u ON uk.kode_unit = u.kode_unit
    LEFT JOIN penilaian_header ph ON uk.kode_unit = ph.kode_unit AND ph.pegawai_id = $1
    WHERE uk.posisi_target ILIKE $2
    ORDER BY uk.kode_unit ASC
";

$qPenilaian = pg_query_params($koneksi, $sqlData, array($idPegawai, "%" . $jabatanPegawai . "%"));
$listPenilaian = pg_fetch_all($qPenilaian) ?: [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pegawai/penilaian_saya.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <div class="table-card">
            <div class="table-toolbar">
                <div class="aktivitas-search">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Cari unit kompetensi..." onkeyup="filterTabel()">
                </div>
            </div>

            <div class="table-responsive">
                <table class="aktivitas-table" id="tabelPenilaian">
                    <thead>
                        <tr>
                            <th width="5%">No</th>
                            <th width="45%">Unit Kompetensi Diujikan</th>
                            <th width="15%">Progress Evidence</th>
                            <th width="10%">Nilai</th>
                            <th width="15%">Status Akhir</th>
                            <th width="10%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listPenilaian)): ?>
                            <tr><td colspan="6" style="text-align: center; padding: 30px;">Tidak ada unit kompetensi yang ditugaskan.</td></tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($listPenilaian as $row): 
                                // Kalkulasi Progress
                                $target = (int)$row['target_dokumen'];
                                $upload = (int)$row['terkumpul_dokumen'];
                                $progress = ($target > 0) ? round(($upload / $target) * 100) : 0;
                                if ($progress > 100) $progress = 100;
                                
                                // Deteksi Status dan Nilai
                                $nilai = $row['nilai_akhir'] ? number_format($row['nilai_akhir'], 2) : '-';
                                $kategori = $row['kategori'];
                                
                                if ($row['status'] == 'Selesai') {
                                    if (in_array($kategori, ['Sangat Kompeten', 'Kompeten'])) {
                                        $badge = "success";
                                    } elseif ($kategori == 'Cukup Kompeten') {
                                        $badge = "warning";
                                    } else {
                                        $badge = "danger";
                                    }
                                    $status_text = $kategori;
                                } else {
                                    if ($upload > 0) {
                                        $badge = "warning";
                                        $status_text = "Direview";
                                    } else {
                                        $badge = "secondary";
                                        $status_text = "Belum Dinilai";
                                    }
                                }
                            ?>
                            <tr class="data-row">
                                <td><?= $no++ ?></td>
                                <td>
                                    <strong class="judul-unit"><?= htmlspecialchars($row['judul_unit']) ?></strong><br>
                                    <small style="color:#A08348; font-weight:600;"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($row['kode_unit']) ?></small>
                                </td>
                                <td>
                                    <div class="progress-mini" title="<?= $upload ?> dari <?= $target ?> Dokumen Uploaded">
                                        <div class="progress-track">
                                            <div class="progress-fill" style="width:<?= $progress ?>%; <?= $progress == 100 ? 'background:#10b981;' : '' ?>"></div>
                                        </div>
                                        <div class="progress-text"><?= $progress ?>%</div>
                                    </div>
                                </td>
                                <td><b style="font-size:15px; color:#0f172a;"><?= $nilai ?></b></td>
                                <td><span class="status-badge <?= $badge ?>"><?= $status_text ?></span></td>
                                <td>
                                    <a href="penilaian_list.php?role=pimpinan&kode_unit=<?= urlencode($row['kode_unit']) ?>" class="btn-detail" title="Lihat Rincian Penilaian">
                                        <i class="bi bi-eye"></i>
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
</div>

<script>
// Fungsi Pencarian Ringan
function filterTabel() {
    let input = document.getElementById('searchInput');
    let filter = input.value.toUpperCase();
    let table = document.getElementById('tabelPenilaian');
    let tr = table.getElementsByClassName('data-row');

    for (let i = 0; i < tr.length; i++) {
        let td = tr[i].getElementsByClassName('judul-unit')[0];
        if (td) {
            let txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
</script>
</body>
</html>