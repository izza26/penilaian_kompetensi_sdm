<?php
session_start();
require_once '../config/koneksi.php';

$page_title = "Tambah Alur KUK";
$page_subtitle = "Tambahkan alur kompetensi dan instrumen penilaian";

// --- PROSES SIMPAN DATA (MULTI-INSERT) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_alur'])) {
    
    // Tangkap data dari form
    $kode_unit          = $_POST['kode_unit'];
    $elemen_kompetensi  = $_POST['elemen_kompetensi'];
    $kode_elemen        = $_POST['kode_elemen'];
    $input              = $_POST['input'];
    $detail_aktivitas   = $_POST['detail_aktivitas'];
    $kriteria_kompetens = $_POST['kriteria_kompetens'];
    $bobot              = $_POST['bobot'];
    
    // Array dari fitur dynamic input evidence
    $evidences          = isset($_POST['evidence']) ? $_POST['evidence'] : [];
    $jumlah_evidence    = count(array_filter($evidences)); // Hitung yang tidak kosong

    // Kita butuh ID sementara untuk Elemen dan Bukti karena desain Postgre-mu integer biasa
    $q_elemen_id = pg_query($koneksi, "SELECT MAX(elemen_id) as max_el FROM elemen_kompetensi");
    $next_elemen_id = (pg_fetch_assoc($q_elemen_id)['max_el'] ?? 0) + 1;

    $q_bukti_id = pg_query($koneksi, "SELECT MAX(bukti_id) as max_bk FROM bukti_pegawai");
    $next_bukti_id = (pg_fetch_assoc($q_bukti_id)['max_bk'] ?? 0) + 1;

    pg_query($koneksi, "BEGIN"); 
    try {
        $q_cek_elemen = pg_query_params($koneksi, 
            "SELECT elemen_id FROM elemen_kompetensi WHERE elemen_kompetensi = $1 AND kode_unit = $2", 
            array($elemen_kompetensi, $kode_unit)
        );
        
        if (pg_num_rows($q_cek_elemen) > 0) {
            $elemen_id_fix = pg_fetch_assoc($q_cek_elemen)['elemen_id'];
        } else {
            $q_insert_el = "INSERT INTO elemen_kompetensi (elemen_id, kode_unit, rekap_elemen_id, kode_elemen_excel, elemen_kompetensi, input_output_outco, uni_kode_unit) 
                            VALUES ($1, $2, 1, $3, $4, $5, $6)";
            pg_query_params($koneksi, $q_insert_el, array($next_elemen_id, $kode_unit, $kode_elemen, $elemen_kompetensi, $input, $kode_unit));
            $elemen_id_fix = $next_elemen_id;
        }

        pg_query_params($koneksi, "INSERT INTO bukti_pegawai (bukti_id, file_path, status_validasi) VALUES ($1, '-', '-')", array($next_bukti_id));

        $q_count_ak = pg_query_params($koneksi, "SELECT COUNT(*) as tot FROM aktivitas_kompeten WHERE elemen_id = $1", array($elemen_id_fix));
        $urutan_ak = (pg_fetch_assoc($q_count_ak)['tot'] ?? 0) + 1;
        $aktivitas_id_fix = str_replace('.', '', $kode_unit) . '-' . $kode_elemen . '-0' . $urutan_ak;

        $q_insert_ak = "INSERT INTO aktivitas_kompeten (aktivitas_id, elemen_id, bukti_id, rekap_aktivitas_id, detail_aktivitas, jumlah_evidence_wa, kriteria_kompetens, bobot_aktivitas, ele_elemen_id) 
                        VALUES ($1, $2, $3, 1, $4, $5, $6, $7, $8)";
        pg_query_params($koneksi, $q_insert_ak, array($aktivitas_id_fix, $elemen_id_fix, $next_bukti_id, $detail_aktivitas, $jumlah_evidence, $kriteria_kompetens, $bobot, $elemen_id_fix));


        if ($jumlah_evidence > 0) {
            $q_ev_id = pg_query($koneksi, "SELECT MAX(evidence_wajib_id) as max_ev FROM evidence_wajib");
            $next_ev_id = (pg_fetch_assoc($q_ev_id)['max_ev'] ?? 0) + 1;

            foreach ($evidences as $ev_name) {
                if(trim($ev_name) != ''){
                    $q_insert_ev = "INSERT INTO evidence_wajib (evidence_wajib_id, aktivitas_id, bukti_id, nama_evidence, jenis_file_allowed, mandatory, akt_aktivitas_id) 
                                    VALUES ($1, $2, $3, $4, 'PDF,DOC', 'Y', $5)";
                    pg_query_params($koneksi, $q_insert_ev, array($next_ev_id, $aktivitas_id_fix, $next_bukti_id, $ev_name, $aktivitas_id_fix));
                    $next_ev_id++;
                }
            }
        }

        pg_query($koneksi, "COMMIT"); 
        echo "<script>alert('Berhasil! Alur KUK Aktivitas Baru telah ditambahkan.'); window.location.href='alur_kuk.php';</script>";
        exit;

    } catch (Exception $e) {
        pg_query($koneksi, "ROLLBACK"); 
        $error_msg = pg_last_error($koneksi);
        echo "<script>alert('Gagal menyimpan data: " . $error_msg . "');</script>";
    }
}

$q_unit = pg_query($koneksi, "SELECT kode_unit, judul_unit FROM unit_kompetensi WHERE aktif = 'Y'");
$q_jabatan = pg_query($koneksi, "SELECT nama_jabatan FROM jabatan GROUP BY nama_jabatan");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Tambah Alur KUK</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link rel="stylesheet" href="../assets/css/css_admin/alur_kuk.css">
    <link rel="stylesheet" href="../assets/css/css_admin/tambah_alur_kuk.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_admin.php'; ?>
    <div class="main-content">
        <?php include '../layouts/header.php'; ?>
        
        <div class="page-card">
            <div class="page-header">
                <div class="page-title">
                    <h2>Tambah Alur KUK</h2>
                    <p>Lengkapi informasi kompetensi, aktivitas, dan evidence.</p>
                </div>
                <a href="alur_kuk.php" class="btn-secondary">Kembali</a>
            </div>

            <form method="POST" action="">
                <div class="form-grid">

                    <!-- UNIT KOMPETENSI (Dinamis) -->
                    <div class="form-group full-width">
                        <label>Unit Kompetensi (Master)</label>
                        <select name="kode_unit" required>
                            <option value="">-- Pilih Unit Kompetensi --</option>
                            <?php while($row_u = pg_fetch_assoc($q_unit)): ?>
                                <option value="<?= $row_u['kode_unit'] ?>"><?= $row_u['kode_unit'] ?> - <?= $row_u['judul_unit'] ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- ELEMEN -->
                    <div class="form-group full-width">
                        <label>Elemen Kompetensi (Teks)</label>
                        <input type="text" name="elemen_kompetensi" placeholder="Contoh: 1. Menganalisis konsep kebijakan..." required>
                        <small style="color:gray;">*Jika Elemen sudah pernah diinput sebelumnya, ketik persis namanya, sistem akan otomatis menggabungkannya.</small>
                    </div>

                    <div class="form-group">
                        <label>Kode Elemen (Excel)</label>
                        <input type="text" name="kode_elemen" placeholder="Contoh: 1A" required>
                    </div>

                    <div class="form-group">
                        <label>Input Dasar</label>
                        <input type="text" name="input" placeholder="Contoh : 1a. Daftar konsep kebijakan..." required>
                    </div>

                    <!-- AKTIVITAS & KUK -->
                    <div class="form-group">
                        <label>Aktivitas / Proses</label>
                        <input type="text" name="detail_aktivitas" placeholder="Contoh: Identifikasi ruang lingkup..." required>
                    </div>

                    <div class="form-group">
                        <label>Bobot Aktivitas (%)</label>
                        <input type="number" name="bobot" placeholder="Contoh: 30" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Ukuran Kinerja / KUK / Kriteria Kompeten</label>
                        <textarea name="kriteria_kompetens" rows="3" placeholder="Masukkan ukuran kinerja atau kriteria kompeten..." required></textarea>
                    </div>

                    <!-- EVIDENCE DYNAMIC -->
                    <div class="form-group full-width">
                        <label>Evidence Wajib</label>
                        <div id="evidence-wrapper">
                            <div class="evidence-item">
                                <input type="text" name="evidence[]" placeholder="Contoh: Dokumen analisis konsep" required>
                                <button type="button" class="btn-remove"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                        <button type="button" id="btn-add-evidence" class="btn-add-evidence">
                            + Tambah Evidence
                        </button>
                    </div>

                </div>

                <div class="form-footer" style="margin-top:20px;">
                    <button type="submit" name="simpan_alur" class="btn-primary">
                        Simpan Alur KUK Baru
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-add-evidence').addEventListener('click', function(){
    const wrapper = document.getElementById('evidence-wrapper');
    const item = document.createElement('div');
    item.className = 'evidence-item';
    item.innerHTML = `
        <input type="text" name="evidence[]" placeholder="Masukkan evidence pendukung lainnya">
        <button type="button" class="btn-remove"><i class="bi bi-trash"></i></button>
    `;
    wrapper.appendChild(item);
});

document.addEventListener('click', function(e){
    if(e.target.closest('.btn-remove')){
        e.target.closest('.evidence-item').remove();
    }
});
</script>
</body>
</html>