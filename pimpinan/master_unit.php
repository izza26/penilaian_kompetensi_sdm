<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

/* ===========================================================
   PROSES CRUD DENGAN HANDLING ERROR
=========================================================== */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    // --- CRUD UNIT KOMPETENSI (Hanya Edit & Hapus, Tambah di page lain) ---
    if ($aksi == 'edit_unit') {
        $kode_unit = $_POST['kode_unit_lama'];
        $judul_unit = $_POST['judul_unit'];
        $jenis_komp = $_POST['jenis_kompetensi'];
        $q = @pg_query_params($koneksi, "UPDATE unit_kompetensi SET judul_unit=$1, jenis_kompetensi=$2 WHERE kode_unit=$3", array($judul_unit, $jenis_komp, $kode_unit));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Unit+berhasil+diperbarui");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+memperbarui+Unit");
        exit;
    } elseif ($aksi == 'hapus_unit') {
        $kode_unit = $_POST['hapus_id'];
        @pg_query_params($koneksi, "DELETE FROM aktivitas_kompeten WHERE elemen_id IN (SELECT elemen_id FROM elemen_kompetensi WHERE kode_unit=$1)", array($kode_unit));
        @pg_query_params($koneksi, "DELETE FROM elemen_kompetensi WHERE kode_unit=$1", array($kode_unit));
        $q = @pg_query_params($koneksi, "DELETE FROM unit_kompetensi WHERE kode_unit=$1", array($kode_unit));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Unit+berhasil+dihapus");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+menghapus+Unit");
        exit;
    }

    // --- CRUD ELEMEN KOMPETENSI ---
    if ($aksi == 'tambah_elemen') {
        $kode_unit = $_POST['kode_unit_parent'];
        $kode_excel = $_POST['kode_elemen_excel'];
        $elemen_komp = $_POST['elemen_kompetensi'];
        $input = $_POST['input'];
        $output = $_POST['output'];
        $outcome = $_POST['outcome'];
        
        $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(elemen_id), 0) + 1 AS new_id FROM elemen_kompetensi");
        $new_id = pg_fetch_assoc($q_id)['new_id'];

        $q = @pg_query_params($koneksi, "INSERT INTO elemen_kompetensi (elemen_id, kode_unit, rekap_elemen_id, kode_elemen_excel, elemen_kompetensi, input, output, outcome, uni_kode_unit) VALUES ($1, $2, 1, $3, $4, $5, $6, $7, $2)", array($new_id, $kode_unit, $kode_excel, $elemen_komp, $input, $output, $outcome));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Elemen+berhasil+ditambahkan");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+menambahkan+Elemen");
        exit;
    } elseif ($aksi == 'edit_elemen') {
        $elemen_id = $_POST['elemen_id'];
        $kode_excel = $_POST['kode_elemen_excel'];
        $elemen_komp = $_POST['elemen_kompetensi'];
        $input = $_POST['input'];
        $output = $_POST['output'];
        $outcome = $_POST['outcome'];
        $q = @pg_query_params($koneksi, "UPDATE elemen_kompetensi SET kode_elemen_excel=$1, elemen_kompetensi=$2, input=$3, output=$4, outcome=$5 WHERE elemen_id=$6", array($kode_excel, $elemen_komp, $input, $output, $outcome, $elemen_id));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Elemen+berhasil+diperbarui");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+memperbarui+Elemen");
        exit;
    } elseif ($aksi == 'hapus_elemen') {
        $elemen_id = $_POST['hapus_id'];
        @pg_query_params($koneksi, "DELETE FROM aktivitas_kompeten WHERE elemen_id=$1", array($elemen_id));
        $q = @pg_query_params($koneksi, "DELETE FROM elemen_kompetensi WHERE elemen_id=$1", array($elemen_id));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Elemen+berhasil+dihapus");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+menghapus+Elemen");
        exit;
    }

    // --- CRUD AKTIVITAS KOMPETENSI ---
    if ($aksi == 'tambah_aktivitas') {
        $elemen_id = $_POST['elemen_id_parent'];
        $akt_id = $_POST['aktivitas_id'];
        $detail = $_POST['detail_aktivitas'];
        $kriteria = $_POST['kriteria_kompetens'];
        $jml_evi = $_POST['jumlah_evidence'];
        $q = @pg_query_params($koneksi, "INSERT INTO aktivitas_kompeten (aktivitas_id, elemen_id, bukti_id, rekap_aktivitas_id, detail_aktivitas, jumlah_evidence_wa, kriteria_kompetens, bobot_aktivitas, ele_elemen_id) VALUES ($1, $2, 0, 0, $3, $4, $5, 0, $2)", array($akt_id, $elemen_id, $detail, $jml_evi, $kriteria));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Aktivitas+berhasil+ditambahkan");
        else header("Location: master_unit.php?status=gagal&pesan=ID+Aktivitas+mungkin+sudah+ada");
        exit;
    } elseif ($aksi == 'edit_aktivitas') {
        $akt_id = $_POST['aktivitas_id_lama'];
        $akt_id_baru = $_POST['aktivitas_id'];
        $detail = $_POST['detail_aktivitas'];
        $kriteria = $_POST['kriteria_kompetens'];
        $jml_evi = $_POST['jumlah_evidence'];
        $q = @pg_query_params($koneksi, "UPDATE aktivitas_kompeten SET aktivitas_id=$1, detail_aktivitas=$2, kriteria_kompetens=$3, jumlah_evidence_wa=$4 WHERE aktivitas_id=$5", array($akt_id_baru, $detail, $kriteria, $jml_evi, $akt_id));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Aktivitas+berhasil+diperbarui");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+memperbarui+Aktivitas");
        exit;
    } elseif ($aksi == 'hapus_aktivitas') {
        $akt_id = $_POST['hapus_id'];
        $q = @pg_query_params($koneksi, "DELETE FROM aktivitas_kompeten WHERE aktivitas_id=$1", array($akt_id));
        if($q) header("Location: master_unit.php?status=sukses&pesan=Aktivitas+berhasil+dihapus");
        else header("Location: master_unit.php?status=gagal&pesan=Gagal+menghapus+Aktivitas");
        exit;
    }
}

/* ===========================================================
   AMBIL DATA HIRARKI (UNIT -> ELEMEN -> AKTIVITAS)
=========================================================== */
$data_hirarki = [];
$q_unit = pg_query($koneksi, "SELECT * FROM unit_kompetensi ORDER BY kode_unit ASC");
while ($row = pg_fetch_assoc($q_unit)) { $row['elemen'] = []; $data_hirarki[$row['kode_unit']] = $row; }

$q_elemen = pg_query($koneksi, "SELECT * FROM elemen_kompetensi ORDER BY elemen_id ASC");
while ($row = pg_fetch_assoc($q_elemen)) {
    if (isset($data_hirarki[$row['kode_unit']])) { $row['aktivitas'] = []; $data_hirarki[$row['kode_unit']]['elemen'][$row['elemen_id']] = $row; }
}

$q_aktivitas = pg_query($koneksi, "SELECT * FROM aktivitas_kompeten ORDER BY aktivitas_id ASC");
while ($row = pg_fetch_assoc($q_aktivitas)) {
    foreach ($data_hirarki as $kode_unit => $unit) {
        if (isset($unit['elemen'][$row['elemen_id']])) {
            $data_hirarki[$kode_unit]['elemen'][$row['elemen_id']]['aktivitas'][] = $row; break; 
        }
    }
}

$page_title = "Master Bank SKKNI";
$page_subtitle = "Kelola database Unit, Elemen, dan Aktivitas Kompetensi SKKNI.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Master SKKNI | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_pimpinan/master_unit.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

<div class="app">
    <?php include "../layouts/sidebar_pimpinan.php"; ?>

    <div class="main-content">
        <?php include "../layouts/header.php"; ?>

        <div class="page-card">
            
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
                <div>
                    <h3 style="color: #2c3e50; margin-bottom: 5px;">Bank Data SKKNI</h3>
                    <p style="font-size: 13px; color: #7f8c8d; margin: 0;">Kelola hierarki master Unit, Elemen, dan Aktivitas.</p>
                </div>
                <!-- TOMBOL PINDAH HALAMAN -->
                <a href="tambah_unit.php" class="btn-add-main" style="text-decoration:none; display:inline-block;">
                    <i class="bi bi-plus-lg"></i> Tambah Unit Kompetensi
                </a>
            </div>

            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%"></th>
                        <th width="20%">KODE UNIT</th>
                        <th width="60%">JUDUL UNIT KOMPETENSI</th>
                        <th width="15%" style="text-align: center;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    foreach ($data_hirarki as $kode_unit => $unit): 
                        $safe_unit_id = "unit_" . str_replace(['.', '-', ' '], '_', $kode_unit);
                    ?>
                    
                    <tr class="clickable-row" id="row-<?= $safe_unit_id ?>" onclick="toggleRow('<?= $safe_unit_id ?>', this)">
                        <td style="text-align: center;"><i class="bi bi-chevron-right icon-<?= $safe_unit_id ?>"></i></td>
                        <td style="text-align: center; font-size: 13px; font-weight: 600; color: #2c3e50;"><?= htmlspecialchars($kode_unit) ?></td>
                        <td style="font-size: 13px; font-weight: 500; color: #34495e;">
                            <?= htmlspecialchars($unit['judul_unit']) ?>
                            <div style="font-size: 11px; color: #94a3b8; font-weight: 400; margin-top: 3px;"><i class="bi bi-folder2"></i> <?= htmlspecialchars($unit['jenis_kompetensi']) ?></div>
                        </td>

                        <td style="text-align: center;" class="no-propagate" onclick="event.stopPropagation();">
                            <button class="btn-action btn-edit" onclick="bukaModalEditUnit('<?= $kode_unit ?>', '<?= htmlspecialchars($unit['judul_unit'], ENT_QUOTES) ?>', '<?= $unit['jenis_kompetensi'] ?>')" title="Edit Judul/Jenis Unit"><i class="bi bi-pencil-square"></i></button>
                            <button class="btn-action btn-delete" onclick="hapusData('hapus_unit', '<?= $kode_unit ?>')" title="Hapus Keseluruhan Unit"><i class="bi bi-trash"></i></button>
                        </td>
                    </tr>

                    <tr class="sub-table-container" id="child-<?= $safe_unit_id ?>">
                        <td colspan="4" style="padding: 0;">
                            <div class="elemen-wrapper">
                                <div class="elemen-title-header">
                                    <span><i class="bi bi-diagram-3"></i> Daftar Elemen & Aktivitas</span>
                                    <button class="btn-add" onclick="bukaModalElemen('tambah', '<?= $kode_unit ?>', '', '', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Elemen</button>
                                </div>

                                <?php if (!empty($unit['elemen'])): ?>
                                    <?php foreach ($unit['elemen'] as $elemen_id => $elemen): 
                                        $safe_elemen_id = "elemen_" . str_replace(['.', '-', ' '], '_', $elemen_id);
                                    ?>
                                        <div class="elemen-card">
                                            <div class="elemen-header" onclick="toggleElemen('<?= $safe_elemen_id ?>', this)">
                                                <i class="bi bi-plus-circle-fill icon-<?= $safe_elemen_id ?>" style="color: #3498db; font-size: 20px; margin-top: 2px;"></i>
                                                <div class="elemen-info">
                                                    <div style="display: flex; justify-content: space-between;">
                                                        <h4 style="font-size: 13px; color: #1e293b; margin: 0 0 8px 0; line-height: 1.4; font-weight: 600;"><?= htmlspecialchars($elemen['kode_elemen_excel']) ?> - <?= htmlspecialchars($elemen['elemen_kompetensi']) ?></h4>
                                                        <div class="no-propagate" onclick="event.stopPropagation();">
                                                            <button class="btn-action btn-edit" onclick="bukaModalElemen('edit', '<?= $kode_unit ?>', '<?= $elemen_id ?>', '<?= htmlspecialchars($elemen['kode_elemen_excel'], ENT_QUOTES) ?>', '<?= htmlspecialchars($elemen['elemen_kompetensi'], ENT_QUOTES) ?>', '<?= htmlspecialchars($elemen['input'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($elemen['output'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($elemen['outcome'] ?? '', ENT_QUOTES) ?>')" title="Edit Elemen"><i class="bi bi-pencil-square"></i></button>
                                                            <button class="btn-action btn-delete" onclick="hapusData('hapus_elemen', '<?= $elemen_id ?>')" title="Hapus Elemen"><i class="bi bi-trash"></i></button>
                                                        </div>
                                                    </div>
                                                    <div style="background: #f8fafc; padding: 10px 12px; border-radius: 6px; margin-top: 8px; border: 1px solid #e2e8f0;">
                                                        <p style="margin-bottom: 4px; font-size: 13px; color: #475569; line-height: 1.4;"><b style="color:#1e293b;">Input:</b> <?= htmlspecialchars($elemen['input'] ?? '-') ?></p>
                                                        <p style="margin-bottom: 4px; font-size: 13px; color: #475569; line-height: 1.4;"><b style="color:#1e293b;">Output:</b> <?= htmlspecialchars($elemen['output'] ?? '-') ?></p>
                                                        <p style="margin-bottom: 0; font-size: 13px; color: #475569; line-height: 1.4;"><b style="color:#1e293b;">Outcome:</b> <?= htmlspecialchars($elemen['outcome'] ?? '-') ?></p>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="aktivitas-container" id="child-<?= $safe_elemen_id ?>">
                                                <div style="text-align: right; margin-bottom: 10px;">
                                                    <button class="btn-add"" onclick="bukaModalAktivitas('tambah', '<?= $elemen_id ?>', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Aktivitas</button>
                                                </div>
                                                
                                                <?php if (!empty($elemen['aktivitas'])): ?>
                                                    <table class="clean-table">
                                                        <thead>
                                                            <tr>
                                                                <th width="15%">Aktivitas ID</th>
                                                                <th width="40%">Detail Aktivitas</th>
                                                                <th width="25%">Kriteria Kompetensi</th>
                                                                <th width="10%" style="text-align: center;">Bukti Dokumen</th>
                                                                <th width="10%" style="text-align: center;">Aksi</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($elemen['aktivitas'] as $aktivitas): ?>
                                                                <tr>
                                                                    <td><b><?= htmlspecialchars($aktivitas['aktivitas_id']) ?></b></td>
                                                                    <td><?= htmlspecialchars($aktivitas['detail_aktivitas']) ?></td>
                                                                    <td><small><?= htmlspecialchars($aktivitas['kriteria_kompetens']) ?></small></td>
                                                                    <td style="text-align: center;"><span class="badge-evidence"><?= $aktivitas['jumlah_evidence_wa'] ?> Dok</span></td>
                                                                    <td style="text-align: center;">
                                                                        <button class="btn-action btn-edit" onclick="bukaModalAktivitas('edit', '<?= $elemen_id ?>', '<?= $aktivitas['aktivitas_id'] ?>', '<?= htmlspecialchars($aktivitas['detail_aktivitas'], ENT_QUOTES) ?>', '<?= htmlspecialchars($aktivitas['kriteria_kompetens'], ENT_QUOTES) ?>', '<?= $aktivitas['jumlah_evidence_wa'] ?>')"><i class="bi bi-pencil-square"></i></button>
                                                                        <button class="btn-action btn-delete" onclick="hapusData('hapus_aktivitas', '<?= $aktivitas['aktivitas_id'] ?>')"><i class="bi bi-trash"></i></button>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                <?php else: ?>
                                                    <div style="color: #e74c3c; font-size: 13px;"><i class="bi bi-info-circle"></i> Belum ada aktivitas di elemen ini.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="padding: 15px; color: #e74c3c; font-size: 13px; text-align:center; border: 1px dashed #f5b7b1;">Belum ada elemen kompetensi untuk unit ini.</div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<!-- Modal HANYA UNTUK EDIT UNIT -->
<div id="modalUnit" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalUnit')">&times;</span>
        <h3 id="unitModalTitle" style="margin-bottom: 20px;">Edit Unit Kompetensi</h3>
        <form method="POST">
            <input type="hidden" name="aksi" value="edit_unit">
            <input type="hidden" name="kode_unit_lama" id="unitKodeLama">
            <div class="form-group">
                <label>Kode Unit (Tidak bisa diubah)</label>
                <input type="text" id="unitKode" disabled style="background:#f1f5f9; cursor:not-allowed;">
            </div>
            <div class="form-group">
                <label>Judul Unit Kompetensi</label>
                <textarea name="judul_unit" id="unitJudul" required rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Jenis Kompetensi</label>
                <select name="jenis_kompetensi" id="unitJenis" required>
                    <option value="Manajerial">Manajerial</option>
                    <option value="Teknis/Digital">Teknis/Digital</option>
                    <option value="Layanan">Layanan</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Update Unit</button>
        </form>
    </div>
</div>

<div id="modalElemen" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalElemen')">&times;</span>
        <h3 id="elemenModalTitle" style="margin-bottom: 20px;">Tambah Elemen Kompetensi</h3>
        <form method="POST">
            <input type="hidden" name="aksi" id="aksiElemen" value="tambah_elemen">
            <input type="hidden" name="kode_unit_parent" id="elemenParentUnit">
            <input type="hidden" name="elemen_id" id="elemenId">
            <div class="form-group">
                <label>Kode/Nomor Elemen (Misal: 1A, 2B)</label>
                <input type="text" name="kode_elemen_excel" id="elemenKode" required>
            </div>
            <div class="form-group">
                <label>Judul Elemen Kompetensi</label>
                <textarea name="elemen_kompetensi" id="elemenJudul" required rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Input</label>
                <textarea name="input" id="elemenInput" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Output</label>
                <textarea name="output" id="elemenOutput" rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Outcome</label>
                <textarea name="outcome" id="elemenOutcome" rows="2"></textarea>
            </div>
            <button type="submit" class="btn-submit">Simpan Elemen</button>
        </form>
    </div>
</div>

<div id="modalAktivitas" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalAktivitas')">&times;</span>
        <h3 id="aktModalTitle" style="margin-bottom: 20px;">Tambah Aktivitas Kompetensi</h3>
        <form method="POST">
            <input type="hidden" name="aksi" id="aksiAktivitas" value="tambah_aktivitas">
            <input type="hidden" name="elemen_id_parent" id="aktParentElemen">
            <input type="hidden" name="aktivitas_id_lama" id="aktIdLama">
            <div class="form-group">
                <label>ID Aktivitas (Unik)</label>
                <input type="text" name="aktivitas_id" id="aktId" required placeholder="Contoh: R91MUS...-01">
            </div>
            <div class="form-group">
                <label>Detail Aktivitas</label>
                <textarea name="detail_aktivitas" id="aktDetail" required rows="3"></textarea>
            </div>
            <div class="form-group">
                <label>Kriteria Kompetensi / Hasil yang Diharapkan</label>
                <textarea name="kriteria_kompetens" id="aktKriteria" required rows="2"></textarea>
            </div>
            <div class="form-group">
                <label>Jumlah Dokumen Evidence (Angka)</label>
                <input type="number" name="jumlah_evidence" id="aktEvidence" required min="0" value="1">
            </div>
            <button type="submit" class="btn-submit">Simpan Aktivitas</button>
        </form>
    </div>
</div>

<form id="formHapus" method="POST" style="display:none;">
    <input type="hidden" name="aksi" id="aksiHapus">
    <input type="hidden" name="hapus_id" id="hapusId">
</form>

<script>
const urlParams = new URLSearchParams(window.location.search);
const status = urlParams.get('status');
const pesan = urlParams.get('pesan');

if (status === 'sukses') {
    const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2000, timerProgressBar: true });
    Toast.fire({ icon: 'success', title: pesan });
    window.history.replaceState(null, null, window.location.pathname);
} else if (status === 'gagal') {
    Swal.fire({ icon: 'error', title: 'Gagal!', text: pesan, confirmButtonColor: '#e74c3c' }).then(() => {
        window.history.replaceState(null, null, window.location.pathname);
    });
}

function toggleRow(id, rowElement) {
    var childRow = document.getElementById('child-' + id);
    var icon = document.querySelector('.icon-' + id);
    if (!childRow) return;
    if (childRow.style.display === 'table-row') {
        childRow.style.display = 'none'; rowElement.classList.remove('row-expanded');
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    } else {
        childRow.style.display = 'table-row'; rowElement.classList.add('row-expanded');
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    }
}

function toggleElemen(id, headerElement) {
    var childContainer = document.getElementById('child-' + id);
    var icon = headerElement.querySelector('.icon-' + id);
    if (!childContainer) return;
    if (childContainer.style.display === 'block') {
        childContainer.style.display = 'none';
        icon.classList.replace('bi-dash-circle-fill', 'bi-plus-circle-fill');
        headerElement.style.borderBottom = 'none';
    } else {
        childContainer.style.display = 'block';
        icon.classList.replace('bi-plus-circle-fill', 'bi-dash-circle-fill');
        headerElement.style.borderBottom = '1px solid #e2e8f0';
    }
}

function bukaModal(idModal) { document.getElementById(idModal).style.display = "block"; }
function tutupModal(idModal) { document.getElementById(idModal).style.display = "none"; }

function bukaModalEditUnit(kode='', judul='', jenis='') {
    document.getElementById('unitKode').value = kode;
    document.getElementById('unitKodeLama').value = kode;
    document.getElementById('unitJudul').value = judul;
    
    let selectJenis = document.getElementById('unitJenis');
    if (jenis) {
        let optionAda = Array.from(selectJenis.options).some(opt => opt.value === jenis);
        if (!optionAda) { selectJenis.add(new Option(jenis, jenis)); }
        selectJenis.value = jenis;
    } else { selectJenis.selectedIndex = 0; }

    bukaModal('modalUnit');
}

function bukaModalElemen(mode, parentUnit, elemenId='', kodeEx='', judul='', input='', output='', outcome='') {
    document.getElementById('aksiElemen').value = mode === 'edit' ? 'edit_elemen' : 'tambah_elemen';
    document.getElementById('elemenModalTitle').innerText = mode === 'edit' ? 'Edit Elemen' : 'Tambah Elemen';
    document.getElementById('elemenParentUnit').value = parentUnit;
    document.getElementById('elemenId').value = elemenId;
    document.getElementById('elemenKode').value = kodeEx;
    document.getElementById('elemenJudul').value = judul;
    document.getElementById('elemenInput').value = input;
    document.getElementById('elemenOutput').value = output;
    document.getElementById('elemenOutcome').value = outcome;
    bukaModal('modalElemen');
}

function bukaModalAktivitas(mode, parentElemen, aktId='', detail='', kriteria='', evidence='1') {
    document.getElementById('aksiAktivitas').value = mode === 'edit' ? 'edit_aktivitas' : 'tambah_aktivitas';
    document.getElementById('aktModalTitle').innerText = mode === 'edit' ? 'Edit Aktivitas' : 'Tambah Aktivitas';
    document.getElementById('aktParentElemen').value = parentElemen;
    document.getElementById('aktIdLama').value = aktId;
    document.getElementById('aktId').value = aktId;
    document.getElementById('aktDetail').value = detail;
    document.getElementById('aktKriteria').value = kriteria;
    document.getElementById('aktEvidence').value = evidence;
    bukaModal('modalAktivitas');
}

function hapusData(aksi, id) {
    let pesannya = aksi === 'hapus_unit' ? "Hapus Unit ini beserta SELURUH Elemen dan Aktivitas di dalamnya?" : 
                  (aksi === 'hapus_elemen' ? "Hapus Elemen ini beserta SELURUH Aktivitas di dalamnya?" : "Yakin hapus aktivitas ini?");
    
    Swal.fire({
        title: 'Peringatan Hapus Data', text: pesannya + " Data yang dihapus tidak dapat dikembalikan!", icon: 'warning',
        showCancelButton: true, confirmButtonColor: '#e74c3c', cancelButtonColor: '#95a5a6', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('aksiHapus').value = aksi;
            document.getElementById('hapusId').value = id;
            document.getElementById('formHapus').submit();
        }
    });
}
</script>

</body>
</html>