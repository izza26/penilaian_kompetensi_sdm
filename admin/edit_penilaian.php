<?php

$page_title = "Edit Penilaian";
$page_subtitle = "Perbarui hasil penilaian kompetensi";

?>

<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Edit Penilaian</title>

    <link rel="stylesheet"
    href="../assets/css/css_admin/layout.css">

    <link rel="stylesheet"
    href="../assets/css/css_admin/edit_penilaian.css">

    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

</head>

<body>

<div class="app">

    <?php include '../layouts/sidebar_admin.php'; ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="page-card">

            <div class="page-header">

                <div class="page-title">

                    <h2>Edit Penilaian</h2>

                    <p>
                        Perbarui hasil penilaian kompetensi pegawai.
                    </p>

                </div>

                <a href="penilaian.php" class="btn-back">

                    <i class="bi bi-arrow-left"></i>
                    Kembali

                </a>

            </div>

            <!-- PROFIL PEGAWAI -->

            <div class="detail-card">

                <div class="pegawai-summary">

                    <div class="avatar">
                        BS
                    </div>

                    <div class="pegawai-info">

                        <h3>Budi Santoso</h3>

                        <p>Kurator • Museum Geologi</p>

                        <span class="status">
                            Sudah Dinilai
                        </span>

                    </div>

                </div>

            </div>

            <!-- INFORMASI -->

            <div class="detail-card">

                <h4 class="section-title">
                    Informasi Kompetensi
                </h4>

                <div class="detail-grid">

                    <div class="detail-item">
                        <label>Unit Kompetensi</label>
                        <span>R.91MUS02.006.3</span>
                    </div>

                    <div class="detail-item">
                        <label>Elemen Kompetensi</label>
                        <span>Kajian Makna dan Konteks Koleksi</span>
                    </div>

                    <div class="detail-item">
                        <label>Aktivitas</label>
                        <span>Identifikasi Nilai Koleksi</span>
                    </div>

                    <div class="detail-item">
                        <label>Jumlah Instrumen</label>
                        <span>5 Pertanyaan</span>
                    </div>

                </div>

            </div>

            <!-- PERTANYAAN 1 -->

            <div class="question-card">

                <h4>Pertanyaan 1</h4>

                <p>
                    Apakah pegawai mampu mengidentifikasi nilai koleksi sesuai standar museum?
                </p>

                <div class="score-group">

                    <label><input type="radio" name="q1"> 1</label>
                    <label><input type="radio" name="q1"> 2</label>
                    <label><input type="radio" name="q1"> 3</label>
                    <label><input type="radio" name="q1" checked> 4</label>
                    <label><input type="radio" name="q1"> 5</label>

                </div>

            </div>

            <!-- PERTANYAAN 2 -->

            <div class="question-card">

                <h4>Pertanyaan 2</h4>

                <p>
                    Apakah metode kajian yang digunakan sudah tepat?
                </p>

                <div class="radio-group">

                    <label>
                        <input type="radio" name="q2" checked>
                        Ya
                    </label>

                    <label>
                        <input type="radio" name="q2">
                        Tidak
                    </label>

                </div>

            </div>

            <!-- PERTANYAAN 3 -->

            <div class="question-card">

                <h4>Pertanyaan 3</h4>

                <p>
                    Apakah hasil kajian disusun secara sistematis?
                </p>

                <div class="score-group">

                    <label><input type="radio" name="q3"> 1</label>
                    <label><input type="radio" name="q3"> 2</label>
                    <label><input type="radio" name="q3"> 3</label>
                    <label><input type="radio" name="q3"> 4</label>
                    <label><input type="radio" name="q3" checked> 5</label>

                </div>

            </div>

            <!-- CATATAN -->

            <div class="detail-card">

                <h4 class="section-title">
                    Catatan Assessor
                </h4>

                <textarea
                class="notes"
                rows="6">Pegawai telah mampu mengidentifikasi nilai koleksi dan menyusun kajian dengan baik.</textarea>

            </div>

            <!-- RINGKASAN -->

            <div class="detail-card">

                <h4 class="section-title">
                    Ringkasan Penilaian
                </h4>

                <div class="detail-grid">

                    <div class="detail-item">
                        <label>Jumlah Instrumen</label>
                        <span>5</span>
                    </div>

                    <div class="detail-item">
                        <label>Instrumen Terisi</label>
                        <span>5</span>
                    </div>

                    <div class="detail-item">
                        <label>Nilai Akhir</label>
                        <span>87</span>
                    </div>

                    <div class="detail-item">
                        <label>Status</label>
                        <span class="kompeten">Kompeten</span>
                    </div>

                </div>

            </div>

            <!-- BUTTON -->

            <div class="form-footer">

                <a href="penilaian.php"
                class="btn-secondary">

                    Batal

                </a>

                <button class="btn-primary">

                    Update Penilaian

                </button>

            </div>

        </div>

    </div>

</div>

</body>
</html>