<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Penilaian Kompetensi SDM</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f4f7fe; color: #1e293b; display: flex; min-height: 100vh; }
        .login-container { display: flex; width: 100%; }
        
        /* 1. Panel Kiri dengan Background Gambar Museum + Overlay Royal Blue */
        .left-panel {
            width: 45%;
            background-color: #3e54a0;
            background-image: url("{{ asset('assets/img/bg-login.jpeg') }}");
            background-size: cover;
            background-position: center;
            background-blend-mode: multiply; 
            color: #ffffff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px 80px;
            position: relative;
        }

        .left-panel::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to right, rgba(62,84,160,0.9) 0%, rgba(62,84,160,0.6) 100%);
            z-index: 1;
        }

        .left-content { position: relative; z-index: 2;}
        .logo { width: 120px; margin-bottom: 30px; filter: brightness(0) invert(1); } 
        .left-content h1 { font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1.3; }
        .left-content p { font-size: 16px; font-weight: 500; margin-bottom: 20px; color: #e0e7ff; }
        .left-content span { font-size: 13px; line-height: 1.6; color: #c7d2fe; max-width: 400px; display: block; }
        
        /* 2. Panel Kanan (Form) */
        .right-panel {
            width: 55%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
            position: relative; /* Kunci utama agar logo bisa dipinggirkan ke ujung */
        }
        
        /* --- CSS SPONSOR (Ditempel pas di pojok kanan atas) --- */
        .sponsor-header {
            position: absolute;
            top: 28px;
            right: 32px; /* Mepet ke ujung layar kanan tanpa jeda besar */
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 16px; 
        }
        .sponsor-text {
            font-size: 10.5px;
            color: #64748b; 
            line-height: 1.5;
            margin: 0;
            font-weight: 500;
            text-align: right; /* Teks Rata Kanan */
        }
        .sponsor-logo {
            height: 36px; 
        }

        /* --- KOTAK LOGIN --- */
        .login-box { 
            width: 100%; 
            max-width: 420px; 
            padding: 20px;
            margin-top: 75px; /* JARAK AMAN! Didorong ke bawah agar tidak bertabrakan dengan logo sponsor di atasnya */
        }
        
        .login-box h2 { font-size: 26px; font-weight: 700; margin-bottom: 8px; color: #1e293b; }
        .login-box p { color: #64748b; font-size: 13px; margin-bottom: 30px; }
        
        /* Alert Message */
        .alert { padding: 12px 16px; border-radius: 12px; font-size: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-weight: 600;}
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .alert-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }

        /* Input Form (Pill-Shape) */
        .input-group { margin-bottom: 20px; position: relative; }
        .input-group label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;}
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 18px; font-size: 16px; color: #94a3b8; }
        
        .input-group input {
            width: 100%; height: 48px; background: #f8fafc; border: 1px solid #cbd5e1;
            border-radius: 50px; padding: 0 20px 0 45px; font-size: 13px; color: #1e293b;
            font-family: inherit; transition: 0.2s ease;
        }
        .input-group input:focus {
            outline: none; border-color: #3e54a0; background: #ffffff;
            box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); 
        }

        .toggle-password { position: absolute; right: 18px; left: auto !important; cursor: pointer; color: #94a3b8; transition: 0.2s;}
        .toggle-password:hover { color: #3e54a0 !important; }
        
        .forgot-pass { display: block; text-align: right; font-size: 12px; color: #3e54a0; font-weight: 600; text-decoration: none; margin-top: -8px; margin-bottom: 24px; }
        .forgot-pass:hover { text-decoration: underline; }
        
        .login-btn {
            width: 100%; height: 48px; border: none; background: #3e54a0; color: #ffffff;
            border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; 
            transition: 0.2s ease; display: flex; justify-content: center; align-items: center;
        }
        .login-btn:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }
        
        .register-link { text-align: center; margin-top: 25px; font-size: 13px; color: #64748b; }
        .register-link a { color: #3e54a0; font-weight: 700; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
        
        .floating-template-btn {
            position: absolute;
            bottom: 40px;
            right: 40px;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: 0.3s;
            z-index: 10;
        }
        .floating-template-btn:hover {
            background: white;
            color: #3e54a0; 
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-decoration: none;
        }

        @media(max-width: 992px) {
            .login-container { flex-direction: column; }
            .left-panel, .right-panel { width: 100%; }
            .left-panel { padding: 40px 30px; }
            .floating-template-btn { bottom: 20px; right: 20px; }
            
            /* Penyesuaian HP */
            .sponsor-header { position: relative; top: 0; right: 0; justify-content: center; flex-direction: column; align-items: center; margin-bottom: 20px; gap: 10px;}
            .sponsor-text { text-align: center; }
            .login-box { margin-top: 0; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="left-panel">
        <div class="left-content">
            <img src="{{ asset('assets/img/logo-museum.png') }}" alt="Museum Geologi" class="logo">
            <h1>Sistem Penilaian<br>Kompetensi SDM</h1>
            <p>Museum Geologi Bandung</p>
            <span>Platform penilaian kompetensi pegawai berbasis evidence dan standar kompetensi SKKNI.</span>
        </div>

        <!-- Tombol Unduh Template -->
        <a href="{{ route('template.dokumen') }}" class="floating-template-btn" title="Unduh Template Dokumen & Evidence">
            <i class="bi bi-folder-symlink-fill"></i> 
        </a>
    </div>

    <div class="right-panel">
        
        <!-- BLOK INFO HIBAH BOPTN (Ditempel persis di pojok kanan atas) -->
        <div class="sponsor-header">
            <!-- Teks di Kiri (Rata Kanan) -->
            <p class="sponsor-text">
                Pengembangan ini didanai oleh Hibah BOPTN Prioritas Skema Ajakan Industri<br>
                Direktorat Pendidikan Tinggi Riset dan Teknologi.<br>
            </p>
            <!-- Logo di Paling Kanan -->
            <img src="{{ asset('assets/img/logo-sponsor.jpeg') }}" alt="Logo Sponsor" class="sponsor-logo">
        </div>

        <div class="login-box">
            <h2>Masuk</h2>
            <p>Silakan masuk menggunakan kredensial Anda</p>

            @if(session('error'))
                <div class="alert alert-error"><i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}</div>
            @endif
            @if(session('success'))
                <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> {{ session('success') }}</div>
            @endif

            <form action="{{ route('login.proses') }}" method="POST">
                @csrf
                <div class="input-group">
                    <label>Username</label>
                    <div class="input-wrapper">
                        <i class="bi bi-person-fill"></i>
                        <input type="text" name="username" placeholder="Masukkan username" required autocomplete="off">
                    </div>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <div class="input-wrapper">
                        <i class="bi bi-lock-fill"></i>
                        <input type="password" name="password" id="passwordField" class="password-field" placeholder="Masukkan password" required>
                        <i class="bi bi-eye-slash-fill toggle-password" id="togglePassword"></i>
                    </div>
                </div>
                <a href="#" class="forgot-pass" onclick="alert('Silakan hubungi Administrator IT Museum Geologi.'); return false;">Lupa Password?</a>
                
                <button type="submit" class="login-btn">Masuk ke Sistem</button>
                
                <div class="register-link">
                    Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
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