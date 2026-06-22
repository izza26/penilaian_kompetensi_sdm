<!DOCTYPE html>
<html>

<head>

    <title>Tambah Instrumen</title>

    <link rel="stylesheet"
    href="../assets/css/css_admin/layout.css">

    <link rel="stylesheet"
    href="../assets/css/css_admin/pegawai.css">

    <link rel="stylesheet"
    href="../assets/css/css_admin/tambah_instrumen.css">

    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

</head>

<body>

<div class="app">

    <?php

    include '../layouts/sidebar_admin.php';

    $page_title = "Tambah Instrumen";
    $page_subtitle = "Tambahkan instrumen penilaian kompetensi";

    ?>

    <div class="main-content">

        <?php include '../layouts/header.php'; ?>

        <div class="page-card">

            <div class="page-header">

                <div class="page-title">

                    <h2>Tambah Instrumen</h2>

                    <p>
                        Lengkapi informasi instrumen penilaian kompetensi.
                    </p>

                </div>

                <a href="instrumen.php" class="btn-secondary">
                    Kembali
                </a>

            </div>

            <form>

                <div class="form-grid">

                    <!-- JABATAN -->

                    <div class="form-group">

                        <label>Jabatan</label>

                        <select>

                            <option>Pilih Jabatan</option>

                            <option>Kurator</option>
                            <option>Kurator Senior</option>
                            <option>Asesor</option>
                            <option>Kepala Museum</option>
                            <option>Koordinator Museum</option>

                        </select>

                    </div>

                    <!-- UNIT -->

                    <div class="form-group">

                        <label>Unit Kompetensi</label>

                        <select>

                            <option>
                                R.91MUS02.006.3 - Melakukan Kajian Koleksi untuk Pameran Museum
                            </option>

                        </select>

                    </div>

                    <!-- ELEMEN -->

                    <div class="form-group">

                        <label>Elemen Kompetensi</label>

                        <select>

                            <option>
                                Kajian Makna dan Konteks Koleksi
                            </option>

                            <option>
                                Kajian Koleksi untuk Pameran
                            </option>

                            <option>
                                Evaluasi Rancangan Penyajian
                            </option>

                        </select>

                    </div>

                    <!-- AKTIVITAS -->

                    <div class="form-group">

                        <label>Aktivitas</label>

                        <select>

                            <option>
                                Identifikasi Nilai Koleksi
                            </option>

                            <option>
                                Menentukan Metode Kajian
                            </option>

                            <option>
                                Menyusun Hasil Kajian
                            </option>

                        </select>

                    </div>

                    <!-- PERTANYAAN -->

                    <div class="form-group full-width">

                        <label>
                            Pertanyaan Instrumen
                        </label>

                        <textarea
                        rows="4"
                        placeholder="Masukkan pertanyaan instrumen penilaian"></textarea>

                    </div>

                    <!-- TIPE PENILAIAN -->

                    <div class="form-group">

                        <label>Tipe Penilaian</label>

                        <select>

                            <option>
                                Skala 1 - 5
                            </option>

                            <option>
                                Ya / Tidak
                            </option>

                        </select>

                    </div>

                    <!-- BOBOT -->

                    <div class="form-group">

                        <label>Bobot (%)</label>

                        <input
                        type="number"
                        placeholder="20">

                    </div>

                    <!-- KETERANGAN -->

                    <div class="form-group full-width">

                        <label>
                            Keterangan Penilai
                        </label>

                        <textarea
                        rows="4"
                        placeholder="Masukkan petunjuk atau keterangan penilaian"></textarea>

                    </div>

                    <!-- STATUS -->

                    <div class="form-group full-width">

                        <label>Status</label>

                        <select>

                            <option>Aktif</option>
                            <option>Nonaktif</option>

                        </select>

                    </div>

                </div>

                <div class="form-footer">

                    <button
                    type="button"
                    class="btn-primary">

                        Simpan Instrumen

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>