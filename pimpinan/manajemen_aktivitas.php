<?php
session_start();
require_once '../auth/cek_role.php';
cekRole('pimpinan');
require_once '../config/koneksi.php';

$jabatan = $_GET['jabatan'] ?? '';
if (empty($jabatan)) {
    header("Location: manajemen_periode.php");
    exit;
}

$display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;

// ==========================================================
// PROSES SIMPAN AKTIVITAS YANG DICENTANG
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_aktivitas'])) {
    $aktivitas_terpilih = $_POST['aktivitas_aktif'] ?? []; // Array ID aktivitas yang dicentang

    // 1. Matikan (Set 'N') SEMUA aktivitas yang ada di bawah Jabatan ini
    $qReset = "
        UPDATE aktivitas_kompeten 
        SET aktif = 'N' 
        WHERE elemen_id IN (
            SELECT ek.elemen_id 
            FROM elemen_kompetensi ek 
            JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit 
            WHERE uk.posisi_target ILIKE $1
        )
    ";
    pg_query_params($koneksi, $qReset, array("%".$jabatan."%"));

    // 2. Hidupkan (Set 'Y') HANYA aktivitas yang dicentang oleh Pimpinan
    if (!empty($aktivitas_terpilih)) {
        foreach ($aktivitas_terpilih as $id_akt) {
            pg_query_params($koneksi, "UPDATE aktivitas_kompeten SET aktif = 'Y' WHERE aktivitas_id = $1", array($id_akt));
        }
    }

    header("Location: manajemen_aktivitas.php?jabatan=" . urlencode($jabatan) . "&status=sukses");
    exit;
}

// ==========================================================
// AMBIL DATA HIERARKI (UK -> ELEMEN -> AKTIVITAS)
// ==========================================================
// Hanya ambil UK yang status aktifnya = 'Y' pada jabatan ini
$sqlData = "
    SELECT 
        uk.kode_unit, uk.judul_unit,
        ek.elemen_id, ek.kode_elemen_excel, ek.elemen_kompetensi,
        ak.aktivitas_id, ak.detail_aktivitas, ak.aktif
    FROM unit_kompetensi uk
    JOIN elemen_kompetensi ek ON uk.kode_unit = ek.kode_unit
    JOIN aktivitas_kompeten ak ON ek.elemen_id = ak.elemen_id
    WHERE uk.posisi_target ILIKE $1 AND uk.aktif = 'Y'
    ORDER BY uk.kode_unit ASC, ek.kode_elemen_excel ASC, ak.aktivitas_id ASC
";
$qData = pg_query_params($koneksi, $sqlData, array("%".$jabatan."%"));

// Kelompokkan Data Agar Rapi di UI
$hirarki = [];
while ($row = pg_fetch_assoc($qData)) {
    $uk_key = $row['kode_unit'] . "|||" . $row['judul_unit'];
    $ek_key = $row['kode_elemen_excel'] . "|||" . $row['elemen_kompetensi'];
    
    $hirarki[$uk_key][$ek_key][] = [
        'id' => $row['aktivitas_id'],
        'detail' => $row['detail_aktivitas'],
        'aktif' => $row['aktif']
    ];
}

$page_title = "Manajemen Penilaian";
$page_subtitle = "Kustomisasi aktivitas kompetensi untuk jabatan " . $display_jabatan;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kustomisasi Aktivitas | Museum Geologi</title>
    <link rel="stylesheet" href="../assets/css/css_admin/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .btn-kembali { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; background: #ffffff; color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; transition: 0.2s; border: 1px solid #e2e8f0; margin-bottom: 20px;}
        .btn-kembali:hover { background: #f8fafc; color: #0f172a; border-color: #cbd5e1;}

        .header-jabatan { background: linear-gradient(135deg, #1B2D46 0%, #425B6F 100%); color: white; padding: 20px 25px; border-radius: 16px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(27, 45, 70, 0.15);}
        .header-jabatan h2 { margin: 0 0 5px 0; font-size: 22px; font-weight: 700; }
        .header-jabatan p { margin: 0; font-size: 13px; opacity: 0.9; }

        .uk-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); overflow: hidden; }
        .uk-header { background: #f8fafc; padding: 18px 25px; border-bottom: 1px solid #e2e8f0; }
        .uk-header h3 { margin: 0 0 5px 0; font-size: 15px; color: #0f172a; }
        .uk-header span { font-size: 14px; font-weight: 700; color: #A08348; padding: 4px 10px; border-radius: 6px; }

        .elemen-group { padding: 0 25px; margin-bottom: 10px; }
        .elemen-title { font-size: 13px; font-weight: 700; color: #475569; margin: 20px 0 10px 0; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;}
        
        .aktivitas-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .aktivitas-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0; transition: 0.2s; cursor: pointer; }
        .aktivitas-item:hover { background: #f8fafc; border-color: #cbd5e1; }
        .aktivitas-item input[type="checkbox"] { margin-top: 3px; transform: scale(1.2); cursor: pointer; }
        .aktivitas-item.checked { background: #fffcf7; border-color: #bda572; }
        .aktivitas-item.checked:hover { background: #dbeafe; }
        
        .akt-id { font-size: 13px; color: #A08348; font-weight: 700; display: block; margin-bottom: 2px;}
        .akt-desc { font-size: 13px; color: #334155; line-height: 1.4; }

        /* Floating Save Bar */
        .floating-save-bar { position: sticky; bottom: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 15px 25px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 -4px 20px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; z-index: 100;}
        .btn-simpan { background: #A08348; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; font-size: 14px; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px;}
        .btn-simpan:hover { background: #1B2D46; transform: translateY(-2px); }
    </style>
</head>
<body>

<div class="app">
    <?php include '../layouts/sidebar_pimpinan.php'; ?>
    <div class="main-content">
        <?php
        $btn_kembali = "manajemen_periode.php"; 
        include '../layouts/header.php'; ?>

        <div class="header-jabatan">
            <div>
                <h2>Kustomisasi Aktivitas: <?= htmlspecialchars($display_jabatan) ?></h2>
                <p>Pimpinan dapat menonaktifkan aktivitas yang dirasa tidak perlu dievaluasi pada periode ini.</p>
            </div>
            <i class="bi bi-ui-checks" style="font-size: 40px; opacity: 0.2;"></i>
        </div>

        <?php if (empty($hirarki)): ?>
            <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
                <i class="bi bi-inbox" style="font-size: 40px; color: #94a3b8; margin-bottom: 15px; display: block;"></i>
                <h3 style="color: #0f172a; margin: 0 0 5px 0;">Belum Ada Unit Kompetensi yang Aktif</h3>
                <p style="color: #64748b; font-size: 13px; margin: 0;">Silakan aktifkan Unit Kompetensi untuk jabatan ini di halaman sebelumnya terlebih dahulu.</p>
            </div>
        <?php else: ?>

            <form method="POST">
                <?php foreach ($hirarki as $uk_key => $elemen_list): 
                    $exUK = explode("|||", $uk_key);
                    $kodeUK = $exUK[0];
                    $judulUK = $exUK[1];
                ?>
                <div class="uk-card">
                    <div class="uk-header">
                        <span><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($kodeUK) ?></span>
                        <h3 style="margin-top: 10px;"><?= htmlspecialchars($judulUK) ?></h3>
                    </div>
                    
                    <?php foreach ($elemen_list as $ek_key => $aktivitas_list): 
                        $exEK = explode("|||", $ek_key);
                        $kodeEK = $exEK[0];
                        $judulEK = $exEK[1];
                    ?>
                    <div class="elemen-group">
                        <div class="elemen-title">
                            Elemen <?= htmlspecialchars($kodeEK) ?>: <?= htmlspecialchars($judulEK) ?>
                        </div>
                        <div class="aktivitas-list">
                            <?php foreach ($aktivitas_list as $akt): 
                                $isChecked = ($akt['aktif'] == 'Y') ? 'checked' : '';
                            ?>
                                <label class="aktivitas-item <?= $isChecked ? 'checked' : '' ?>">
                                    <input type="checkbox" name="aktivitas_aktif[]" value="<?= htmlspecialchars($akt['id']) ?>" <?= $isChecked ?> onclick="toggleActive(this)">
                                    <div>
                                        <span class="akt-id">ID: <?= htmlspecialchars($akt['id']) ?></span>
                                        <span class="akt-desc"><?= htmlspecialchars($akt['detail']) ?></span>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>

                <!-- Sticky Footer Save Button -->
                <div class="floating-save-bar">
                    <div style="font-size: 12px; color: #64748b;">
                        Pastikan untuk menyimpan perubahan setelah mencentang aktivitas.
                    </div>
                    <button type="submit" name="simpan_aktivitas" class="btn-simpan">
                        <i class="bi bi-save"></i> Simpan Kustomisasi Aktivitas
                    </button>
                </div>
            </form>

        <?php endif; ?>
    </div>
</div>

<script>
// Fungsi kecil untuk menambahkan class background biru ketika dicentang
function toggleActive(checkbox) {
    if (checkbox.checked) {
        checkbox.closest('.aktivitas-item').classList.add('checked');
    } else {
        checkbox.closest('.aktivitas-item').classList.remove('checked');
    }
}

// SweetAlert Notifikasi Sukses
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('status') === 'sukses') {
    Swal.fire({ 
        icon: 'success', 
        title: 'Berhasil Disimpan!', 
        text: 'Kustomisasi aktivitas untuk jabatan ini telah diperbarui.', 
        timer: 2000, 
        showConfirmButton: false 
    });
    // Hapus parameter url agar kalau direfresh tidak muncul terus
    window.history.replaceState(null, null, window.location.pathname + "?jabatan=<?= urlencode($jabatan) ?>");
}
</script>

</body>
</html>