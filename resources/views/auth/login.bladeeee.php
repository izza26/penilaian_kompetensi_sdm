<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Penilaian Kompetensi SDM</title>
    <!-- Gunakan helper asset() bawaan Laravel -->
    <link rel="stylesheet" href="{{ asset('assets/css/login.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<div class="login-container">
    <!-- LEFT SIDE -->
    <div class="left-panel">
        <div class="left-content">
            <img src="{{ asset('assets/img/logo-museum.png') }}" alt="Museum Geologi" class="logo">
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

            <!-- Menangkap pesan error dari Controller Laravel -->
            @if(session('error'))
                <div class="alert alert-error" style="color: red; margin-bottom: 15px;">
                    <i class="bi bi-exclamation-octagon-fill"></i> {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success" style="color: green; margin-bottom: 15px;">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                </div>
            @endif

            <!-- Form Action menggunakan Route Laravel -->
            <form action="{{ route('login.proses') }}" method="POST">
                @csrf <!-- Wajib ada di Laravel untuk mencegah serangan CSRF -->

                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" name="username" placeholder="Masukkan Username atau NIP" required autocomplete="off">
                    </div>
                </div>

                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="password" id="passwordField" class="password-field" placeholder="Masukkan password" required>
                        <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword" title="Tampilkan Password"></i>
                    </div>
                </div>

                <a href="#" class="forgot-pass" onclick="alert('Silakan hubungi Administrator IT Museum Geologi untuk mereset password Anda.'); return false;">Lupa Password?</a>

                <button type="submit" class="login-btn">Masuk ke Sistem</button>

                <div class="register-link">
                    Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
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
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        
        this.classList.toggle('bi-eye-fill');
        this.classList.toggle('bi-eye-slash-fill');
    });
</script>

</body>
</html>