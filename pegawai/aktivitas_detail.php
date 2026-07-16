<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pegawai');
require_once '../config/koneksi.php';

$idPegawai = $_SESSION['pegawai_id'];
$aktivitas_id = $_GET['id'] ?? '';

if (empty($aktivitas_id)) {
    echo "<script>alert('Aktivitas tidak ditemukan!'); window.location='aktivitas_saya.php';</script>";
    exit;
}

// Ambil Jabatan Pegawai
$qPegawai = pg_query_params($koneksi, "SELECT jabatan FROM pegawai WHERE pegawai_id = $1", array($idPegawai));
$pegawai = pg_fetch_assoc($qPegawai);
$jabatanPegawai = !empty($pegawai['jabatan']) ? $pegawai['jabatan'] : 'Belum Ada Jabatan';

/* ==========================================================
   AMBIL DATA DETAIL AKTIVITAS (TERMASUK TEMPLATE FILE)
========================================================== */
$sqlDetail = "
SELECT 
    ak.*, 
    ek.elemen_kompetensi, ek.kode_elemen_excel, 
    uk.judul_unit, uk.kode_unit 
FROM aktivitas_kompeten ak
LEFT JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
WHERE ak.aktivitas_id = $1 AND uk.posisi_target ILIKE $2
";
$queryDetail = pg_query_params($koneksi, $sqlDetail, array($aktivitas_id, "%" . $jabatanPegawai . "%"));
$detail = pg_fetch_assoc($queryDetail);

if (!$detail) {
    echo "<script>alert('Aktivitas ini tidak ditugaskan untuk posisi Anda ($jabatanPegawai).'); window.location='aktivitas_saya.php';</script>";
    exit;
}

$target_evidence = (int)$detail['jumlah_evidence_wa'];

/* ==========================================================
   CEK PERIODE PENILAIAN (Keamanan Auto-Lock)
========================================================== */
$qPeriode = pg_query($koneksi, "SELECT * FROM periode_penilaian WHERE status_aktif = 'Y' LIMIT 1");
$periodeAktif = pg_fetch_assoc($qPeriode);
$is_open = false;

if ($periodeAktif) {
    $tgl_mulai = strtotime($periodeAktif['tanggal_mulai']);
    $tgl_selesai = strtotime($periodeAktif['tanggal_selesai'] . ' 23:59:59');
    $sekarang = time();
    if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) { $is_open = true; }
}

/* ==========================================================
   PROSES UPLOAD FILE PER SLOT
========================================================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file_evidence'])) {
    if (!$is_open) {
        echo "<script>alert('Gagal! Periode ditutup.'); window.location='aktivitas_detail.php?id=$aktivitas_id';</script>"; exit;
    }

    $evidence_wajib_id = (int)$_POST['evidence_wajib_id'];
    $file = $_FILES['file_evidence'];
    $nama_file = $file['name'];
    $tmp_file = $file['tmp_name'];
    $ukuran_file = $file['size'];

    $ekstensi_diperbolehkan = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx');
    $x = explode('.', $nama_file);
    $ekstensi = strtolower(end($x));

    if (in_array($ekstensi, $ekstensi_diperbolehkan) === true) {
        if ($ukuran_file < 5242880) { // 5 MB
            $nama_file_baru = "EVI_" . $idPegawai . "_" . time() . "_" . rand(10,99) . "." . $ekstensi;
            $direktori = '../uploads/evidence/';
            if (!is_dir($direktori)) { mkdir($direktori, 0777, true); }

            if (move_uploaded_file($tmp_file, $direktori . $nama_file_baru)) {
                $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(bukti_id), 0) + 1 AS new_id FROM bukti_pegawai");
                $new_id = pg_fetch_assoc($q_id)['new_id'];

                $q_insert = "INSERT INTO bukti_pegawai (bukti_id, file_path, status_validasi, pegawai_id, evidence_wajib_id, tanggal_upload, aktivitas_id) 
                             VALUES ($1, $2, 'Menunggu Review', $3, $4, NOW(), $5)";
                pg_query_params($koneksi, $q_insert, array($new_id, $nama_file_baru, $idPegawai, $evidence_wajib_id, $aktivitas_id));
                
                header("Location: aktivitas_detail.php?id=$aktivitas_id&sukses=1"); exit;
            } else { $pesan_error = "Gagal mengunggah file ke server."; }
        } else { $pesan_error = "Ukuran file terlalu besar! Maksimal 5 MB."; }
    } else { $pesan_error = "Ekstensi file tidak diperbolehkan! Gunakan PDF, JPG, PNG, DOC."; }
}

/* ==========================================================
   PROSES HAPUS FILE
========================================================== */
if (isset($_GET['hapus_bukti'])) {
    $bukti_id = (int)$_GET['hapus_bukti'];
    $q_cek = pg_query_params($koneksi, "SELECT file_path, status_validasi FROM bukti_pegawai WHERE bukti_id = $1 AND pegawai_id = $2", array($bukti_id, $idPegawai));
    $file_hapus = pg_fetch_assoc($q_cek);

    if ($file_hapus && in_array($file_hapus['status_validasi'], ['Menunggu Review', 'Revisi'])) {
        @unlink('../uploads/evidence/' . $file_hapus['file_path']);
        pg_query_params($koneksi, "DELETE FROM bukti_pegawai WHERE bukti_id = $1", array($bukti_id));
        header("Location: aktivitas_detail.php?id=$aktivitas_id&hapus=1"); exit;
    }
}

/* ==========================================================
   LOGIKA GENERATE SLOT UPLOAD (SUDAH DIPERBAIKI)
========================================================== */
// 1. Ambil daftar Evidence Wajib dari Pimpinan
$qEW = pg_query_params($koneksi, "SELECT * FROM evidence_wajib WHERE aktivitas_id = $1 ORDER BY evidence_wajib_id ASC", array($aktivitas_id));
$listEW = pg_fetch_all($qEW) ?: [];

// 2. Ambil bukti yang sudah diupload Pegawai
$qBukti = pg_query_params($koneksi, "SELECT * FROM bukti_pegawai WHERE aktivitas_id = $1 AND pegawai_id = $2 ORDER BY tanggal_upload ASC", array($aktivitas_id, $idPegawai));
$listBukti = pg_fetch_all($qBukti) ?: [];

// 3. Mapping bukti berdasarkan evidence_wajib_id
$buktiMapped = [];
$buktiGeneric = [];
foreach($listBukti as $b) {
    if (!empty($b['evidence_wajib_id']) && $b['evidence_wajib_id'] != 0) {
        $buktiMapped[$b['evidence_wajib_id']] = $b;
    } else {
        $buktiGeneric[] = $b; 
    }
}

// 4. Susun Slot (Prioritaskan yang disetting Pimpinan)
$slots = [];

// Jika Pimpinan sudah setting evidence wajib
if (count($listEW) > 0) {
    foreach($listEW as $ew) {
        $slots[] = [
            'id' => $ew['evidence_wajib_id'],
            'nama' => $ew['nama_evidence'],
            'format' => $ew['jenis_file_allowed'],
            'data' => $buktiMapped[$ew['evidence_wajib_id']] ?? null
        ];
    }
} 
// Jika Pimpinan belum setting, gunakan default generik sesuai "target evidence"
else {
    for ($i = 0; $i < $target_evidence; $i++) {
        $slots[] = [
            'id' => 0,
            'nama' => 'Dokumen Evidence ' . ($i + 1),
            'format' => 'PDF, DOC, JPG, PNG',
            'data' => $buktiGeneric[$i] ?? null
        ];
    }
}

// Sisa bukti generik yang terlanjur terupload dimasukkan ke slot tambahan
$sisa_generic_terupload = count($buktiGeneric) - ($target_evidence > count($listEW) ? $target_evidence : 0);
if ($sisa_generic_terupload > 0) {
    $startIndex = ($target_evidence > count($listEW)) ? $target_evidence : count($listEW);
    for ($j = 0; $j < count($buktiGeneric); $j++) {
        if ($j >= $startIndex || count($listEW) > 0) { // Hanya render jika benar-benar sisa
            $slots[] = [
                'id' => 0,
                'nama' => 'Dokumen Ekstra (Opsional)',
                'format' => 'Telah diunggah',
                'data' => $buktiGeneric[$j]
            ];
        }
    }
}

function getBadgeClass($status) {
    if ($status == 'Kompeten') return 'success';
    if ($status == 'Revisi') return 'danger';
    return 'warning';
}

$page_title = "Detail Aktivitas";
$page_subtitle = "Unggah dokumen evidence untuk aktivitas kompetensi Anda";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail & Upload | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_pegawai/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .detail-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card-box { background: #ffffff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .card-title { font-size: 16px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;}
        .card-title i { color: #3b82f6; }
        
        .info-row { margin-bottom: 15px; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 0.5px;}
        .info-value { font-size: 13px; color: #1e293b; font-weight: 500; line-height: 1.5; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        
        .info-value.highlight-blue { background: #eff6ff; border-color: #bfdbfe; }
        .info-value.highlight-orange { background: #fffbeb; color: #b45309; border-color: #fde68a; font-weight: 600;}

        .slot-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px; background: #fff; box-shadow: 0 2px 10px rgba(0,0,0,0.02); transition: 0.2s;}
        .slot-box:hover { border-color: #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.04);}
        .slot-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 15px; border-bottom: 1px dashed #e2e8f0;}
        .slot-header h5 { margin: 0; font-size: 14px; color: #0f172a; font-weight: 700;}
        .format-badge { font-size: 11px; background: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #e2e8f0;}
        
        .upload-form { display: flex; gap: 10px; align-items: center; }
        .file-input-wrapper { flex: 1; display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px dashed #cbd5e1; }
        .btn-pilih-file { background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; transition: 0.2s; white-space: nowrap;}
        .btn-pilih-file:hover { background: #cbd5e1; color: #0f172a;}
        .file-name-text { font-size: 12px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
        
        .btn-upload-small { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; transition: 0.2s; cursor: pointer;}
        .btn-upload-small:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 2px 5px rgba(59,130,246,.3);}
        
        .uploaded-file { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;}
        .file-info-group { display: flex; align-items: center; gap: 12px; }
        .file-info-group i { font-size: 24px; color: #ef4444; }
        .file-details a { font-size: 13px; font-weight: 600; color: #1e293b; text-decoration: none; display: block; margin-bottom: 2px;}
        .file-details a:hover { color: #3b82f6; text-decoration: underline; }
        .file-details small { font-size: 11px; color: #64748b; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;}
        .badge-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a;}
        .badge-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;}
        .badge-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}
        
        .btn-delete { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 6px 10px; border-radius: 6px; font-size: 13px; cursor: pointer; transition: 0.2s; }
        .btn-delete:hover { background: #dc2626; color: white; border-color: #dc2626;}

        .btn-kembali { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; background: #ffffff; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; border: 1px solid #e2e8f0; margin-bottom: 20px;}
        .btn-kembali:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1;}

        @media(max-width: 992px) {
            .detail-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pegawai.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php";
        $btn_kembali = "aktivitas_saya.php";
         ?>

        <a href="aktivitas_saya.php" class="btn-kembali">
            <i class="bi bi-arrow-left" style="margin-right: 6px;"></i> Kembali ke Daftar Aktivitas
        </a>

        <?php if(isset($pesan_error)): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px; font-weight: 500;">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $pesan_error ?>
            </div>
        <?php endif; ?>

        <div class="detail-container">
            <div class="card-box">
                <div class="card-title"><i class="bi bi-info-circle-fill"></i> Detail Aktivitas Kompetensi</div>
                
                <div class="info-row">
                    <span class="info-label">Unit Kompetensi</span>
                    <div class="info-value">
                        <b>[<?= htmlspecialchars($detail['kode_unit']) ?>]</b><br><?= htmlspecialchars($detail['judul_unit']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Elemen Kompetensi</span>
                    <div class="info-value">
                        <b><?= htmlspecialchars($detail['kode_elemen_excel']) ?></b> - <?= htmlspecialchars($detail['elemen_kompetensi']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Aktivitas yang Harus Dilakukan</span>
                    <div class="info-value highlight-blue">
                        <b style="color: #1e3a8a;">[ <?= htmlspecialchars($detail['aktivitas_id']) ?> ]</b><br><?= htmlspecialchars($detail['detail_aktivitas']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Kriteria Kompetensi (Hasil Diharapkan)</span>
                    <div class="info-value">
                        <?= htmlspecialchars($detail['kriteria_kompetens']) ?>
                    </div>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Target Evidence yang Dibutuhkan</span>
                    <div class="info-value highlight-orange">
                        <i class="bi bi-file-earmark-check"></i> Minimal <b><?= $detail['jumlah_evidence_wa'] ?> Dokumen</b>
                    </div>
                </div>
            </div>

            <div class="card-box">
                <div class="card-title"><i class="bi bi-cloud-arrow-up-fill"></i> Daftar Upload Evidence</div>
                
                <?php if (!empty($detail['template_file'])): ?>
                    <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 16px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(59, 130, 246, 0.05);">
                        <div>
                            <b style="color: #1e3a8a; font-size: 13px; display: block; margin-bottom: 4px;"><i class="bi bi-info-circle-fill"></i> Template Tersedia</b>
                            <span style="color: #3b82f6; font-size: 12px;">Gunakan template ini untuk membuat dokumen evidence.</span>
                        </div>
                        <a href="../uploads/templates/<?= htmlspecialchars($detail['template_file']) ?>" target="_blank" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; white-space: nowrap;">
                            <i class="bi bi-download"></i> Unduh Format
                        </a>
                    </div>
                <?php endif; ?>
                
                <!-- LOOPING SLOT UPLOAD -->
                <?php foreach($slots as $index => $slot): ?>
                    <div class="slot-box">
                        <div class="slot-header">
                            <!-- NAMA SLOT OTOMATIS BACA DARI DATABASE -->
                            <h5><i class="bi bi-file-earmark-text" style="color:#3b82f6; margin-right:5px;"></i> <?= htmlspecialchars($slot['nama']) ?></h5>
                            <span class="format-badge"><?= htmlspecialchars($slot['format']) ?></span>
                        </div>
                        
                        <?php if ($slot['data']): ?>
                            <div class="uploaded-file">
                                <div class="file-info-group">
                                    <i class="bi bi-file-earmark-pdf-fill"></i>
                                    <div class="file-details">
                                        <a href="../uploads/evidence/<?= $slot['data']['file_path'] ?>" target="_blank" title="<?= htmlspecialchars($slot['data']['file_path']) ?>">
                                            <?= (strlen($slot['data']['file_path']) > 25) ? substr($slot['data']['file_path'], 0, 25) . '...' : htmlspecialchars($slot['data']['file_path']) ?>
                                        </a>
                                        <small><?= date('d M Y, H:i', strtotime($slot['data']['tanggal_upload'])) ?> WIB</small>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="badge badge-<?= getBadgeClass($slot['data']['status_validasi']) ?>"><?= $slot['data']['status_validasi'] ?></span>
                                    
                                    <?php if ($is_open && in_array($slot['data']['status_validasi'], ['Menunggu Review', 'Revisi'])): ?>
                                        <a href="?id=<?= $aktivitas_id ?>&hapus_bukti=<?= $slot['data']['bukti_id'] ?>" class="btn-delete" title="Hapus File" onclick="return confirm('Yakin ingin menghapus file ini?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($is_open): ?>
                                <form method="POST" enctype="multipart/form-data" class="upload-form">
                                    <input type="hidden" name="evidence_wajib_id" value="<?= $slot['id'] ?>">
                                    <div class="file-input-wrapper">
                                        <input type="file" name="file_evidence" id="file_<?= $index ?>" style="display:none;" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="showFileName(this, 'name_<?= $index ?>')">
                                        <label for="file_<?= $index ?>" class="btn-pilih-file"><i class="bi bi-folder2-open"></i> Pilih File</label>
                                        <span id="name_<?= $index ?>" class="file-name-text">Belum ada file</span>
                                    </div>
                                    <button type="submit" class="btn-upload-small"><i class="bi bi-send-fill"></i> Unggah</button>
                                </form>
                            <?php else: ?>
                                <div style="background:#fef2f2; padding:10px; text-align:center; border-radius:8px; border: 1px solid #fecaca; color:#dc2626; font-size:12px; font-weight:600;">
                                    <i class="bi bi-lock-fill"></i> Terkunci (Periode Ditutup)
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showFileName(input, textId) {
    var textElement = document.getElementById(textId);
    if (input.files && input.files.length > 0) {
        textElement.innerHTML = '<b style="color:#059669;"><i class="bi bi-check-circle-fill"></i> ' + input.files[0].name + '</b>';
    } else { 
        textElement.innerHTML = 'Belum ada file'; 
    }
}

const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('sukses')) {
    Swal.fire({ icon: 'success', title: 'Berhasil!', text: 'Dokumen berhasil diunggah.', timer: 2000, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname + '?id=<?= $aktivitas_id ?>');
}
if (urlParams.get('hapus')) {
    Swal.fire({ icon: 'success', title: 'Terhapus!', text: 'Dokumen berhasil dihapus.', timer: 2000, showConfirmButton: false });
    window.history.replaceState(null, null, window.location.pathname + '?id=<?= $aktivitas_id ?>');
}
</script>

</body>
</html>