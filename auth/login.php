<?php
session_start();
// Jika sudah login, tendang kembali ke dashboard
if (isset($_SESSION['status_login']) && $_SESSION['status_login'] === true) {
    $role = $_SESSION['role'] ?? '';
    if ($role == 'admin') header("Location: ../admin/dashboard.php");
    elseif ($role == 'pegawai') header("Location: ../pegawai/dashboard.php");
    elseif ($role == 'pimpinan') header("Location: ../pimpinan/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Penilaian Kompetensi SDM</title>
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
            <h1>Sistem Penilaian Kompetensi SDM</h1>
            <p>Museum Geologi Bandung</p>
            <span>Platform penilaian kompetensi pegawai berbasis evidence dan standar kompetensi SKKNI.</span>
        </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right-panel">
        <div class="login-box">
            <h2>Selamat Datang</h2>
            <p>Silakan masuk menggunakan akun Anda</p>

            <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'gagal'): ?>
                <div class="alert alert-error"><i class="bi bi-exclamation-octagon-fill"></i> Username atau Password salah!</div>
            <?php elseif(isset($_GET['pesan']) && $_GET['pesan'] == 'register_sukses'): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Registrasi berhasil! Silakan login.</div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" name="username" placeholder="Masukkan NIP atau NIK" required autocomplete="off">
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="password" id="passwordField" class="password-field" placeholder="Masukkan password" required>
                        <!-- ICON MATA DI SINI -->
                        <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword" title="Tampilkan Password"></i>
                    </div>
                </div>

                <a href="#" class="forgot-pass" onclick="alert('Silakan hubungi Administrator IT Museum Geologi untuk mereset password Anda.'); return false;">Lupa Password?</a>

                <button type="submit" class="login-btn">Masuk ke Sistem</button>

                <div class="register-link">
                    Belum punya akun? <a href="register.php">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // JAVASCRIPT UNTUK TOGGLE BUKA/TUTUP PASSWORD
    const togglePassword = document.querySelector('#togglePassword');
    const passwordField = document.querySelector('#passwordField');

    togglePassword.addEventListener('click', function () {
        // Cek tipe input saat ini, lalu ubah
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        // Ubah icon mata dicoret jadi mata terbuka
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
</script>

</body>
</html>