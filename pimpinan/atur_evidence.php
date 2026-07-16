<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$aktivitas_id = $_GET['id'] ?? '';
if (empty($aktivitas_id)) {
    header("Location: manajemen_evidence.php");
    exit;
}

// 1. Tarik Data Aktivitas
$sql_akt = "
    SELECT 
        ak.*, 
        ek.kode_unit, 
        uk.judul_unit 
    FROM aktivitas_kompeten ak
    JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
    JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
    WHERE ak.aktivitas_id = $1
";
$q_akt = pg_query_params($koneksi, $sql_akt, array($aktivitas_id));
$aktivitas = pg_fetch_assoc($q_akt);

if (!$aktivitas) {
    echo "<script>alert('Aktivitas tidak ditemukan!'); window.location='manajemen_evidence.php';</script>";
    exit;
}

$target_evidence = (int)$aktivitas['jumlah_evidence_wa'];

// 2. Tarik Data Evidence Wajib yang Sudah Ada
$q_ew = pg_query_params($koneksi, "SELECT nama_evidence FROM evidence_wajib WHERE aktivitas_id = $1 ORDER BY evidence_wajib_id ASC", array($aktivitas_id));
$existing_evidences = pg_fetch_all($q_ew) ?: [];

// 3. Proses Simpan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_evidence'])) {
    
    pg_query($koneksi, "BEGIN");
    
    try {
        pg_query_params($koneksi, "DELETE FROM evidence_wajib WHERE aktivitas_id = $1", array($aktivitas_id));
        
        $jumlah_berhasil = 0;
        
        // Loop tiap Grup Evidence
        if (isset($_POST['nama_evidence']) && is_array($_POST['nama_evidence'])) {
            foreach ($_POST['nama_evidence'] as $group_inputs) {
                if (is_array($group_inputs)) {
                    $valid_items = [];
                    // Kumpulkan semua input opsi di dalam grup tersebut
                    foreach ($group_inputs as $item) {
                        $val = trim($item);
                        if ($val !== '') $valid_items[] = $val;
                    }
                    
                    // Gabungkan pakai Newline (\n) dan simpan ke database
                    if (!empty($valid_items)) {
                        $nama_bersih = implode("\n", $valid_items);
                        $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(evidence_wajib_id), 0) + 1 AS new_id FROM evidence_wajib");
                        $new_id = pg_fetch_assoc($q_id)['new_id'];

                        $q_insert = "INSERT INTO evidence_wajib (evidence_wajib_id, aktivitas_id, bukti_id, nama_evidence, jenis_file_allowed, mandatory, akt_aktivitas_id) VALUES ($1, $2, 0, $3, 'PDF, DOC, JPG, PNG', 'Y', $4)";
                        pg_query_params($koneksi, $q_insert, array($new_id, $aktivitas_id, $nama_bersih, $aktivitas_id));
                        $jumlah_berhasil++;
                    }
                }
            }
        }
        
        // Update total target di aktivitas (bahkan jika diset jadi 0)
        pg_query_params($koneksi, "UPDATE aktivitas_kompeten SET jumlah_evidence_wa = $1 WHERE aktivitas_id = $2", array($jumlah_berhasil, $aktivitas_id));

        pg_query($koneksi, "COMMIT");
        header("Location: manajemen_evidence.php?status=sukses");
        exit;
    } catch (Exception $e) {
        pg_query($koneksi, "ROLLBACK");
        $error_msg = "Terjadi kesalahan saat menyimpan!";
    }
}

$page_title = "Atur Rincian Evidence";
$page_subtitle = "Jabarkan opsi dokumen yang diizinkan untuk setiap target evidence.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Atur Evidence | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .detail-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px 30px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; gap: 30px;}
        .detail-left { flex: 1; }
        .detail-right { flex: 1; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; }
        
        .unit-badge { font-size: 12px; font-weight: 700; color: #A08348; background: #fffbeb; padding: 4px 12px; border-radius: 6px; border: 1px solid #fde68a; display: inline-block; margin-bottom: 10px; }
        .detail-card h3 { margin: 0 0 5px 0; font-size: 13px; color: #0f172a; line-height: 1.4; }
        
        .kriteria-title { font-size: 12px; font-weight: 700; color: #182A3A; display: block; margin-bottom: 8px; text-transform: uppercase;}
        .kriteria-text { font-size: 14px; color: #334155; line-height: 1.5;}

        .form-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .form-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;}
        
        /* CARD EVIDENCE UTAMA */
        .evidence-card { background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #A08348; border-radius: 12px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.02);}
        .ev-card-header { background: #f8fafc; padding: 12px 20px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center;}
        .ev-title { font-size: 14px; font-weight: 700; color: #0f172a; }
        .btn-hapus-group { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-hapus-group:hover { background: #ef4444; color: white; }

        .ev-card-body { padding: 20px; }
        .input-row { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
        .input-row:last-child { margin-bottom: 0; }
        
        .form-control { flex: 1; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 13px; color: #1e293b; box-sizing: border-box; transition: 0.2s; }
        .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
        
        .btn-hapus-input { background: transparent; color: #94a3b8; border: none; width: 30px; height: 30px; cursor: pointer; transition: 0.2s; font-size: 18px; display: flex; align-items: center; justify-content: center;}
        .btn-hapus-input:hover { color: #ef4444; }

        .ev-card-footer { padding: 15px 20px; background: #fff; border-top: 1px dashed #e2e8f0; }
        .btn-tambah-opsi { background: #fef9e8; color: #A08348; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; transition: 0.2s; }
        .btn-tambah-opsi:hover { background: #f3eddb; }

        /* TOMBOL BAWAH */
        .btn-tambah-group { background: #fffdf5; color: #A08348; border: 1px dashed #A08348; width: 100%; padding: 15px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; margin-top: 10px; margin-bottom: 30px;}
        .btn-tambah-group:hover { background: #fef9e8; }

        .btn-simpan { background: #bda572; color: white; padding: 14px 30px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; width: 100%; justify-content: center;}
        .btn-simpan:hover { background: #A08348; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(59, 130, 246, 0.2);}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php 
            $btn_kembali = "manajemen_evidence.php";
            include '../layouts/header.php'; 
        ?>

        <!-- IDENTITAS AKTIVITAS & KRITERIA (DIBUAT SEJAJAR KIRI-KANAN) -->
        <div class="detail-card">
            <div class="detail-left">
                <span class="unit-badge">[<?= htmlspecialchars($aktivitas['kode_unit']) ?>] <?= htmlspecialchars($aktivitas['judul_unit']) ?></span>
                <h4 style="color: #A08348; font-size: 12px; margin-bottom: 5px;">ID Aktivitas: <?= htmlspecialchars($aktivitas['aktivitas_id']) ?></h4>
                <h3><?= htmlspecialchars($aktivitas['detail_aktivitas']) ?></h3>
            </div>
            <div class="detail-right">
                <span class="kriteria-title"></i> Kriteria Kompetensi:</span>
                <div class="kriteria-text"><?= htmlspecialchars($aktivitas['kriteria_kompetens']) ?></div>
            </div>
        </div>

        <!-- FORM INPUT EVIDENCE DINAMIS (BERSARANG) -->
        <div class="form-card">
            <div class="form-title">
                <div>
                    <i class="bi bi-folder-plus" style="color: #3b82f6; margin-right: 8px;"></i> Daftar Dokumen Evidence
                    <p style="font-size: 12px; color: #64748b; font-weight: 500; margin: 4px 0 0 28px;">Jabarkan opsi nama dokumen secara spesifik untuk masing-masing Evidence.</p>
                </div>
            </div>

            <form method="POST">
                <div id="wrapper_evidence">
                    <?php 
                    // JIKA SUDAH PERNAH DIISI SEBELUMNYA
                    if (!empty($existing_evidences)) {
                        foreach ($existing_evidences as $index => $ev) {
                            $gIndex = $index + 1;
                            $opsi_dokumen = explode("\n", $ev['nama_evidence']);
                            
                            echo '<div class="evidence-card" data-group="'.$gIndex.'">';
                            echo '  <div class="ev-card-header">';
                            echo '      <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">'.$gIndex.'</span></span>';
                            echo '      <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>';
                            echo '  </div>';
                            echo '  <div class="ev-card-body" id="group_body_'.$gIndex.'">';
                            
                            foreach ($opsi_dokumen as $opsi) {
                                echo '      <div class="input-row">';
                                echo '          <input type="text" name="nama_evidence['.$gIndex.'][]" class="form-control" value="'.htmlspecialchars(trim($opsi)).'" required>';
                                echo '          <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>';
                                echo '      </div>';
                            }
                            
                            echo '  </div>';
                            echo '  <div class="ev-card-footer">';
                            echo '      <button type="button" class="btn-tambah-opsi" onclick="tambahInput('.$gIndex.')"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    } 
                    // JIKA BELUM PERNAH DIISI SAMA SEKALI
                    else {
                        $generate_count = ($target_evidence > 0) ? $target_evidence : 1; 
                        
                        for ($i = 1; $i <= $generate_count; $i++) {
                            echo '<div class="evidence-card" data-group="'.$i.'">';
                            echo '  <div class="ev-card-header">';
                            echo '      <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">'.$i.'</span></span>';
                            echo '      <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>';
                            echo '  </div>';
                            echo '  <div class="ev-card-body" id="group_body_'.$i.'">';
                            echo '      <div class="input-row">';
                            echo '          <input type="text" name="nama_evidence['.$i.'][]" class="form-control" placeholder="Contoh: Dokumen Analisis Konsep..." required>';
                            echo '          <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>';
                            echo '      </div>';
                            echo '  </div>';
                            echo '  <div class="ev-card-footer">';
                            echo '      <button type="button" class="btn-tambah-opsi" onclick="tambahInput('.$i.')"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>';
                            echo '  </div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>

                <button type="button" class="btn-tambah-group" onclick="tambahGroup()"><i class="bi bi-folder-plus"></i> Tambah Target Evidence Baru</button>

                <div style="background: #fffbeb; padding: 15px; border-radius: 10px; border: 1px dashed #fde68a; margin-bottom: 20px;">
                    <p style="margin: 0; font-size: 12px; color: #92400e;"><b>Penting:</b> Menyimpan form ini akan memperbarui target (<code>jumlah_evidence_wa</code>) menyesuaikan dengan jumlah Kotak Evidence di atas. Berbagai opsi di dalam satu kotak dihitung sebagai alternatif dari 1 dokumen.</p>
                </div>

                <button type="submit" name="simpan_evidence" class="btn-simpan"><i class="bi bi-save2-fill"></i> Simpan Daftar Dokumen</button>
            </form>

        </div>

    </div>
</div>

<script>
let groupCounter = <?= empty($existing_evidences) ? (($target_evidence > 0) ? $target_evidence : 1) : count($existing_evidences) ?>;

function renumberGroups() {
    let numbers = document.querySelectorAll('.ev-number');
    numbers.forEach((num, index) => {
        num.innerText = index + 1;
    });
}

// FUNGSI UNTUK MENGHAPUS SATU KOTAK EVIDENCE PENUH
function hapusGroup(btn) {
    let wrapper = document.getElementById('wrapper_evidence');
    if (wrapper.children.length > 1) {
        btn.closest('.evidence-card').remove();
        renumberGroups();
    } else {
        Swal.fire({icon: 'warning', text: 'Minimal harus ada 1 kotak target evidence!'});
    }
}

// FUNGSI UNTUK MENAMBAH KOTAK EVIDENCE BARU
function tambahGroup() {
    groupCounter++;
    let wrapper = document.getElementById('wrapper_evidence');
    let div = document.createElement('div');
    div.className = 'evidence-card';
    div.setAttribute('data-group', groupCounter);
    
    div.innerHTML = `
        <div class="ev-card-header">
            <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">0</span></span>
            <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>
        </div>
        <div class="ev-card-body" id="group_body_${groupCounter}">
            <div class="input-row">
                <input type="text" name="nama_evidence[${groupCounter}][]" class="form-control" placeholder="Ketik nama dokumen..." required>
                <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>
            </div>
        </div>
        <div class="ev-card-footer">
            <button type="button" class="btn-tambah-opsi" onclick="tambahInput(${groupCounter})"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>
        </div>
    `;
    wrapper.appendChild(div);
    renumberGroups();
}

// FUNGSI UNTUK MENAMBAH OPSI INPUT KE DALAM KOTAK EVIDENCE
function tambahInput(groupId) {
    let container = document.getElementById('group_body_' + groupId);
    let div = document.createElement('div');
    div.className = 'input-row';
    div.innerHTML = `
        <input type="text" name="nama_evidence[${groupId}][]" class="form-control" placeholder="Opsi alternatif dokumen..." required>
        <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>
    `;
    container.appendChild(div);
}

// FUNGSI UNTUK MENGHAPUS SALAH SATU OPSI INPUT
function hapusInput(btn) {
    let container = btn.closest('.ev-card-body');
    if (container.querySelectorAll('.input-row').length > 1) {
        btn.closest('.input-row').remove();
    } else {
        Swal.fire({icon: 'warning', text: 'Tinggalkan minimal 1 opsi input. Jika ingin dibatalkan, hapus Kotak Evidence di kanan atas.'});
    }
}
</script>

</body>
</html> 