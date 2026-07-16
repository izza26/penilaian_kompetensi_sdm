<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

// =========================================================================
// PROSES PENYIMPANAN KOMPLEKS (UNIT -> ELEMEN -> AKTIVITAS)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_semua'])) {
    
    // Mulai Transaksi Database (Biar kalau gagal di tengah jalan, ter-rollback)
    pg_query($koneksi, "BEGIN");
    $berhasil = true;
    $pesan_gagal = "";

    try {
        // 1. SIMPAN UNIT KOMPETENSI
        $kode_unit = trim($_POST['kode_unit']);
        $judul_unit = trim($_POST['judul_unit']);
        $jenis_komp = trim($_POST['jenis_kompetensi']); // Ambil dari Datalist (ketikan manual atau pilihan)

        // Cek apakah kode_unit sudah ada
        $cek_unit = pg_query_params($koneksi, "SELECT kode_unit FROM unit_kompetensi WHERE kode_unit = $1", array($kode_unit));
        if (pg_num_rows($cek_unit) > 0) {
            throw new Exception("Kode Unit '$kode_unit' sudah ada di database!");
        }

        $ins_unit = pg_query_params($koneksi, "INSERT INTO unit_kompetensi (kode_unit, jabatan_unit_id, rekap_unit_id, judul_unit, jenis_kompetensi, sumber_skkni, aktif) VALUES ($1, 1, 0, $2, $3, '-', 'Y')", array($kode_unit, $judul_unit, $jenis_komp));
        if (!$ins_unit) throw new Exception("Gagal menyimpan Unit Kompetensi.");

        // 2. LOOPING SIMPAN ELEMEN & AKTIVITAS
        $elemen_kodes = $_POST['elemen_kode'] ?? [];
        
        foreach ($elemen_kodes as $i => $kode_el) {
            $kode_el = trim($kode_el);
            if (empty($kode_el)) continue;

            $judul_el = trim($_POST['elemen_judul'][$i]);
            $input_el = trim($_POST['elemen_input'][$i]);
            $output_el = trim($_POST['elemen_output'][$i]);
            $outcome_el = trim($_POST['elemen_outcome'][$i]);

            // Dapatkan ID Elemen Baru
            $q_id = pg_query($koneksi, "SELECT COALESCE(MAX(elemen_id), 0) + 1 AS new_id FROM elemen_kompetensi");
            $new_el_id = pg_fetch_assoc($q_id)['new_id'];

            // Insert Elemen
            $ins_el = pg_query_params($koneksi, "INSERT INTO elemen_kompetensi (elemen_id, kode_unit, rekap_elemen_id, kode_elemen_excel, elemen_kompetensi, input, output, outcome, uni_kode_unit) VALUES ($1, $2, 1, $3, $4, $5, $6, $7, $2)", array($new_el_id, $kode_unit, $kode_el, $judul_el, $input_el, $output_el, $outcome_el));
            if (!$ins_el) throw new Exception("Gagal menyimpan Elemen: $kode_el");

            // Cek apakah ada aktivitas di elemen ini
            // Format name aktivitas: aktivitas_id[index_elemen][index_aktivitas]
            if (isset($_POST['aktivitas_id'][$i]) && is_array($_POST['aktivitas_id'][$i])) {
                
                foreach ($_POST['aktivitas_id'][$i] as $j => $akt_id) {
                    $akt_id = trim($akt_id);
                    if (empty($akt_id)) continue;

                    $akt_detail = trim($_POST['aktivitas_detail'][$i][$j]);
                    $akt_kriteria = trim($_POST['aktivitas_kriteria'][$i][$j]);
                    $akt_evi = (int)$_POST['aktivitas_evidence'][$i][$j];

                    // Insert Aktivitas
                    $ins_akt = pg_query_params($koneksi, "INSERT INTO aktivitas_kompeten (aktivitas_id, elemen_id, bukti_id, rekap_aktivitas_id, detail_aktivitas, jumlah_evidence_wa, kriteria_kompetens, bobot_aktivitas, ele_elemen_id) VALUES ($1, $2, 0, 0, $3, $4, $5, 0, $2)", array($akt_id, $new_el_id, $akt_detail, $akt_evi, $akt_kriteria));
                    if (!$ins_akt) throw new Exception("Gagal menyimpan Aktivitas: $akt_id. Kemungkinan ID ini sudah ada di database.");
                }
            }
        }

        // Jika semua berhasil, COMMIT
        pg_query($koneksi, "COMMIT");

        header("Location: master_unit.php?status=sukses&pesan=Unit+beserta+Elemen+dan+Aktivitas+berhasil+disimpan!");
        exit;

    } catch (Exception $e) {
        pg_query($koneksi, "ROLLBACK");
        $berhasil = false;
        $pesan_gagal = $e->getMessage();
    }
}

// =========================================================================
// TARIK DAFTAR JENIS KOMPETENSI UNTUK DATALIST
// =========================================================================
$q_jenis = pg_query($koneksi, "SELECT DISTINCT jenis_kompetensi FROM unit_kompetensi WHERE jenis_kompetensi IS NOT NULL AND jenis_kompetensi != ''");
$db_jenis = pg_fetch_all($q_jenis) ?: [];
$existing_jenis = array_column($db_jenis, 'jenis_kompetensi');
$default_jenis = ['Manajerial', 'Teknis/Digital', 'Layanan'];
$all_jenis = array_unique(array_merge($default_jenis, $existing_jenis));
sort($all_jenis);

$page_title = "Tambah Unit Kompetensi";
$page_subtitle = "Buat hierarki unit, elemen, dan aktivitas dalam satu kali proses.";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah Unit SKKNI | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-header { background: #fff; padding: 25px 30px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; }
        .page-header h2 { margin: 0 0 5px 0; font-size: 20px; color: #0f172a; }
        .page-header p { margin: 0; font-size: 13px; color: #64748b; }
        .btn-kembali { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; background: #f8fafc; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; border: 1px solid #cbd5e1; transition: 0.2s; }
        .btn-kembali:hover { background: #e2e8f0; color: #0f172a; }

        .form-section { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .section-title { font-size: 16px; color: #1e293b; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: inherit; font-size: 13px; color: #1e293b; box-sizing: border-box; transition: 0.2s; background: #fff;}
        .form-control:focus { border-color: #A08348; box-shadow: 0 0 0 3px rgba(160, 131, 72, 0.15); outline: none; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* Elemen Wrapper */
        .elemen-box { background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 25px; margin-bottom: 20px; position: relative;}
        .btn-hapus-elemen { position: absolute; top: 15px; right: 15px; background: #fef2f2; color: #ef4444; border: none; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-hapus-elemen:hover { background: #ef4444; color: white; }

        /* Aktivitas Wrapper */
        .aktivitas-box { background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #A08348; border-radius: 8px; padding: 20px; margin-top: 15px; position: relative;}
        .btn-hapus-aktivitas { position: absolute; top: 15px; right: 15px; color: #ef4444; background: none; border: none; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .btn-hapus-aktivitas:hover { color: #b91c1c; }

        .btn-tambah-elemen { background: #fffdf5; color: #A08348; border: 1px dashed #A08348; width: 100%; padding: 15px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; margin-bottom: 30px;}
        .btn-tambah-elemen:hover { background: #fef9e8; }

        .btn-tambah-aktivitas { background: #f1f5f9; color: #475569; border: 1px dashed #94a3b8; padding: 10px 15px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; transition: 0.2s; margin-top: 10px;}
        .btn-tambah-aktivitas:hover { background: #e2e8f0; color: #0f172a;}

        /* TOMBOL SIMPAN WARNA EMAS/COKELAT */
        .floating-save { position: fixed; bottom: 30px; right: 30px; background: #A08348; color: white; border: none; padding: 16px 30px; border-radius: 30px; font-size: 16px; font-weight: 700; box-shadow: 0 10px 25px rgba(160, 131, 72, 0.3); cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 10px; z-index: 1000;}
        .floating-save:hover { background: #8e733f; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(160, 131, 72, 0.4);}
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php 
        $btn_kembali = "master_unit.php";
        include '../layouts/header.php'; ?>

        <?php if(!empty($pesan_gagal)): ?>
            <div style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 25px; font-weight: 500;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($pesan_gagal) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="formMaster">
            <!-- TAHAP 1: INFO UNIT KOMPETENSI -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-1-circle-fill" style="color: #A08348;"></i> Identitas Unit Kompetensi</div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Kode Unit (Wajib Unik)</label>
                        <input type="text" name="kode_unit" class="form-control" placeholder="Contoh: R.91MUS02.001.3" required>
                    </div>
                    <div class="form-group">
                        <label>Jenis Kompetensi (Pilih atau ketik jenis baru)</label>
                        <input list="jenis_list" name="jenis_kompetensi" class="form-control" placeholder="Pilih dari daftar / Ketik baru..." required autocomplete="off">
                        <datalist id="jenis_list">
                            <?php foreach($all_jenis as $j): ?>
                                <option value="<?= htmlspecialchars($j) ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label>Judul Unit Kompetensi</label>
                    <textarea name="judul_unit" class="form-control" rows="2" placeholder="Masukkan judul lengkap unit..." required></textarea>
                </div>
            </div>

            <!-- TAHAP 2: DAFTAR ELEMEN & AKTIVITAS -->
            <div class="form-section">
                <div class="section-title"><i class="bi bi-2-circle-fill" style="color: #A08348;"></i> Daftar Elemen & Aktivitas</div>
                
                <div id="elemen_wrapper">
                    <!-- Elemen 1 (Default) -->
                    <div class="elemen-box" data-index="0">
                        <button type="button" class="btn-hapus-elemen" onclick="hapusElemen(this)"><i class="bi bi-trash"></i> Hapus Elemen</button>
                        <h4 style="margin: 0 0 15px 0; color: #0f172a; font-size: 15px;">Elemen Kompetensi Baru</h4>
                        
                        <div class="form-group">
                            <label>Kode/Nomor Elemen (Misal: 1A, 2B)</label>
                            <input type="text" name="elemen_kode[0]" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Judul Elemen</label>
                            <textarea name="elemen_judul[0]" class="form-control" rows="1" required></textarea>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group"><label>Input</label><textarea name="elemen_input[0]" class="form-control" rows="1"></textarea></div>
                            <div class="form-group"><label>Output</label><textarea name="elemen_output[0]" class="form-control" rows="1"></textarea></div>
                        </div>
                        <div class="form-group"><label>Outcome</label><textarea name="elemen_outcome[0]" class="form-control" rows="1"></textarea></div>

                        <!-- Area Aktivitas dalam Elemen -->
                        <div class="aktivitas_wrapper_0">
                            <h5 style="margin: 20px 0 10px 0; color: #475569; font-size: 13px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Rincian Aktivitas Kompetensi:</h5>
                            <!-- Aktivitas 1 (Default) -->
                            <div class="aktivitas-box">
                                <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)" title="Hapus Aktivitas"><i class="bi bi-x-circle-fill"></i></button>
                                <div class="grid-2">
                                    <div class="form-group">
                                        <label>ID Aktivitas</label>
                                        <input type="text" name="aktivitas_id[0][]" class="form-control" placeholder="Contoh: R91...-1A-01" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Target Evidence (Angka)</label>
                                        <input type="number" name="aktivitas_evidence[0][]" class="form-control" value="1" min="0" required>
                                    </div>
                                </div>
                                <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[0][]" class="form-control" rows="2" required></textarea></div>
                                <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[0][]" class="form-control" rows="1" required></textarea></div>
                            </div>
                        </div>
                        <button type="button" class="btn-tambah-aktivitas" onclick="tambahAktivitas(0)"><i class="bi bi-plus"></i> Tambah Aktivitas ke Elemen Ini</button>
                    </div>
                </div>

                <button type="button" class="btn-tambah-elemen" onclick="tambahElemen()"><i class="bi bi-folder-plus"></i> Tambah Elemen Kompetensi Baru</button>

            </div>

            <button type="submit" name="simpan_semua" class="floating-save"><i class="bi bi-save2-fill"></i> Simpan Semua Data</button>
        </form>

    </div>
</div>

<script>
let elemenIndex = 1; // Karena 0 sudah dipakai default

function tambahElemen() {
    let wrapper = document.getElementById('elemen_wrapper');
    let html = `
        <div class="elemen-box" data-index="${elemenIndex}">
            <button type="button" class="btn-hapus-elemen" onclick="hapusElemen(this)"><i class="bi bi-trash"></i> Hapus Elemen</button>
            <h4 style="margin: 0 0 15px 0; color: #0f172a; font-size: 15px;">Elemen Kompetensi Baru</h4>
            
            <div class="form-group">
                <label>Kode/Nomor Elemen (Misal: 1A, 2B)</label>
                <input type="text" name="elemen_kode[${elemenIndex}]" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Judul Elemen</label>
                <textarea name="elemen_judul[${elemenIndex}]" class="form-control" rows="1" required></textarea>
            </div>
            
            <div class="grid-2">
                <div class="form-group"><label>Input</label><textarea name="elemen_input[${elemenIndex}]" class="form-control" rows="1"></textarea></div>
                <div class="form-group"><label>Output</label><textarea name="elemen_output[${elemenIndex}]" class="form-control" rows="1"></textarea></div>
            </div>
            <div class="form-group"><label>Outcome</label><textarea name="elemen_outcome[${elemenIndex}]" class="form-control" rows="1"></textarea></div>

            <div class="aktivitas_wrapper_${elemenIndex}">
                <h5 style="margin: 20px 0 10px 0; color: #475569; font-size: 13px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Rincian Aktivitas Kompetensi:</h5>
                <div class="aktivitas-box">
                    <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)" title="Hapus Aktivitas"><i class="bi bi-x-circle-fill"></i></button>
                    <div class="grid-2">
                        <div class="form-group"><label>ID Aktivitas</label><input type="text" name="aktivitas_id[${elemenIndex}][]" class="form-control" required></div>
                        <div class="form-group"><label>Target Evidence</label><input type="number" name="aktivitas_evidence[${elemenIndex}][]" class="form-control" value="1" min="0" required></div>
                    </div>
                    <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[${elemenIndex}][]" class="form-control" rows="2" required></textarea></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[${elemenIndex}][]" class="form-control" rows="1" required></textarea></div>
                </div>
            </div>
            <button type="button" class="btn-tambah-aktivitas" onclick="tambahAktivitas(${elemenIndex})"><i class="bi bi-plus"></i> Tambah Aktivitas ke Elemen Ini</button>
        </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
    elemenIndex++;
}

function hapusElemen(btn) {
    let box = btn.closest('.elemen-box');
    let wrapper = document.getElementById('elemen_wrapper');
    if (wrapper.children.length > 1) {
        box.remove();
    } else {
        Swal.fire({icon: 'warning', title: 'Perhatian', text: 'Minimal harus ada 1 Elemen Kompetensi.'});
    }
}

function tambahAktivitas(elIndex) {
    let wrapper = document.querySelector('.aktivitas_wrapper_' + elIndex);
    let html = `
        <div class="aktivitas-box">
            <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)" title="Hapus Aktivitas"><i class="bi bi-x-circle-fill"></i></button>
            <div class="grid-2">
                <div class="form-group"><label>ID Aktivitas</label><input type="text" name="aktivitas_id[${elIndex}][]" class="form-control" required></div>
                <div class="form-group"><label>Target Evidence</label><input type="number" name="aktivitas_evidence[${elIndex}][]" class="form-control" value="1" min="0" required></div>
            </div>
            <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[${elIndex}][]" class="form-control" rows="2" required></textarea></div>
            <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[${elIndex}][]" class="form-control" rows="1" required></textarea></div>
        </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
}

function hapusAktivitas(btn) {
    let box = btn.closest('.aktivitas-box');
    let wrapper = box.parentElement;
    // Hitung berapa div yang punya class aktivitas-box di dalam wrapper ini
    let count = wrapper.querySelectorAll('.aktivitas-box').length;
    if (count > 1) {
        box.remove();
    } else {
        Swal.fire({icon: 'warning', title: 'Perhatian', text: 'Minimal harus ada 1 Aktivitas per Elemen.'});
    }
}
</script>

</body>
</html>