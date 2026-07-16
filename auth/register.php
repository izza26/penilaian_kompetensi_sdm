<?php
session_start();
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    header("Location: ../index.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | Penilaian Kompetensi SDM</title>
    <link rel="stylesheet" href="../assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="login-container">
    <!-- LEFT SIDE -->
    <div class="left-panel">
        <div class="left-content">
            <img src="../assets/img/logo-museum.png" alt="Museum Geologi" class="logo">
            <h1>Bergabung Bersama Kami</h1>
            <p>Museum Geologi Bandung</p>
            <span>Daftarkan diri Anda untuk mengikuti program penilaian kompetensi berbasis SKKNI yang terintegrasi.</span>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="login-box register-box">
            <h2>Daftar Akun Baru</h2>
            <p>Lengkapi formulir di bawah ini dengan data yang valid.</p>

            <form action="proses_register.php" method="POST">
                
                <div class="form-grid">
                    <div class="input-group">
                        <label>NIP / NIK</label>
                        <div class="input-wrapper">
                            <i class="bi bi-credit-card-2-front-fill"></i>
                            <input type="text" name="nip_nik" placeholder="Nomor Induk" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-vcard-fill"></i>
                            <input type="text" name="pegawai_nama" placeholder="Nama sesuai KTP" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" name="email" placeholder="Alamat email aktif" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>No. WhatsApp</label>
                        <div class="input-wrapper">
                            <i class="bi bi-telephone-fill"></i>
                            <input type="text" name="no_hp" placeholder="Contoh: 0812..." required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Daftar Sebagai</label>
                        <div class="input-wrapper">
                            <i class="bi bi-shield-lock-fill"></i>
                            <select name="role" id="roleSelect" onchange="aturJabatan()" required>
                                <option value="" disabled selected>-- Pilih Role --</option>
                                <option value="pegawai">Pegawai Fungsional</option>
                                <option value="pimpinan">Pimpinan / Asesor</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Jabatan Teknis</label>
                        <div class="input-wrapper">
                            <i class="bi bi-briefcase-fill"></i>
                            <select name="jabatan" id="jabatanSelect" required>
                                <option value="" disabled selected>-- Pilih Jabatan --</option>
                                <option value="Register">Register</option>
                                <option value="Kurator">Kurator</option>
                                <option value="Konservator">Konservator</option>
                                <option value="Penata Pameran">Penata Pameran</option>
                                <option value="Edukator">Edukator</option>
                                <option value="Hubungan Masyarakat dan Pemasaran">Hubungan Masyarakat & Pemasaran</option>
                                <!-- Opsi tersembunyi untuk Pimpinan -->
                                <option value="Pimpinan" id="optPimpinan" style="display:none;">Pimpinan Unit</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>Unit Kerja / Instansi</label>
                        <div class="input-wrapper">
                            <i class="bi bi-building-fill"></i>
                            <input type="text" name="unit_kerja" placeholder="Contoh: Museum Geologi" value="Museum Geologi" required>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>Buat Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-key-fill"></i>
                            <input type="password" name="password" placeholder="Minimal 6 karakter" required>
                        </div>
                    </div>
                </div>

                <button type="submit" class="login-btn"><i class="bi bi-person-plus-fill me-2"></i> Buat Akun</button>

                <div class="register-link">
                    Sudah punya akun? <a href="login.php">Masuk di sini</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Logika otomatis untuk menyesuaikan Jabatan berdasarkan Role yang dipilih
function aturJabatan() {
    const role = document.getElementById('roleSelect').value;
    const jabatan = document.getElementById('jabatanSelect');
    const optPimpinan = document.getElementById('optPimpinan');

    if (role === 'pimpinan') {
        optPimpinan.style.display = 'block';
        jabatan.value = 'Pimpinan';
        // Kunci select agar tidak bisa diganti jika dia pimpinan
        jabatan.style.pointerEvents = 'none';
        jabatan.style.background = '#e2e8f0';
    } else {
        optPimpinan.style.display = 'none';
        jabatan.value = '';
        jabatan.style.pointerEvents = 'auto';
        jabatan.style.background = '#f8fafc';
    }
}
</script>

</body>
</html>