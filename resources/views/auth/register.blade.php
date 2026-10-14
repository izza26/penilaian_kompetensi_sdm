<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | Penilaian Kompetensi SDM</title>
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
            background-blend-mode: multiply; /* Efek biru menyatu dengan foto */
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

        .left-content { position: relative; z-index: 2; }
        .logo { width: 120px; margin-bottom: 30px; filter: brightness(0) invert(1); }
        .left-content h1 { font-size: 32px; font-weight: 700; margin-bottom: 12px; line-height: 1.3; }
        .left-content p { font-size: 16px; font-weight: 500; margin-bottom: 20px; color: #e0e7ff; }
        .left-content span { font-size: 13px; line-height: 1.6; color: #c7d2fe; max-width: 400px; display: block; }
        
        /* 2. Panel Kanan (Form Register) */
        .right-panel {
            width: 55%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #ffffff;
        }
        .login-box { width: 100%; max-width: 650px; padding: 40px 50px; } /* Diperlebar untuk grid */
        .login-box h2 { font-size: 26px; font-weight: 700; margin-bottom: 8px; color: #1e293b; }
        .login-box p { color: #64748b; font-size: 13px; margin-bottom: 30px; }
        
        /* Alert Message */
        .alert { padding: 12px 16px; border-radius: 12px; font-size: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; font-weight: 600;}
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

        /* Grid Form Layout */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0 20px; }
        .input-group { margin-bottom: 20px; position: relative; }
        .input-group.full-width { grid-column: span 2; }
        
        .input-group label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;}
        .input-wrapper { position: relative; display: flex; align-items: center; }
        .input-wrapper i { position: absolute; left: 18px; font-size: 16px; color: #94a3b8; z-index: 2;}
        
        /* Input & Select (Pill-Shape) */
        .input-group input, .input-group select {
            width: 100%; height: 48px; background: #f8fafc; border: 1px solid #cbd5e1;
            border-radius: 50px; padding: 0 20px 0 45px; font-size: 13px; color: #1e293b;
            font-family: inherit; transition: 0.2s ease; appearance: none;
        }
        .input-group input:focus, .input-group select:focus {
            outline: none; border-color: #3e54a0; background: #ffffff;
            box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); 
        }

        /* Custom Dropdown Arrow */
        .input-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2364748b' viewBox='0 0 16 16'%3E%3Cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 18px center;
        }
        
        /* Tombol Daftar Royal Blue */
        .login-btn {
            width: 100%; height: 48px; border: none; background: #3e54a0; color: #ffffff;
            border-radius: 50px; font-size: 14px; font-weight: 600; cursor: pointer; 
            transition: 0.2s ease; display: flex; justify-content: center; align-items: center; margin-top: 10px;
        }
        .login-btn:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }
        
        .register-link { text-align: center; margin-top: 25px; font-size: 13px; color: #64748b; }
        .register-link a { color: #3e54a0; font-weight: 700; text-decoration: none; }
        .register-link a:hover { text-decoration: underline; }
        
        @media(max-width: 992px) {
            .login-container { flex-direction: column; }
            .left-panel, .right-panel { width: 100%; }
            .left-panel { padding: 40px 30px; }
            .form-grid { grid-template-columns: 1fr; gap: 0; }
            .input-group.full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="left-panel">
        <div class="left-content">
            <img src="{{ asset('assets/img/logo-museum.png') }}" alt="Museum Geologi" class="logo">
            <h1>Bergabung Bersama Kami</h1>
            <p>Museum Geologi Bandung</p>
            <span>Daftarkan diri Anda untuk mengikuti program penilaian kompetensi berbasis SKKNI yang terintegrasi.</span>
        </div>
    </div>

    <div class="right-panel">
        <div class="login-box register-box">
            <h2>Daftar Akun Baru</h2>
            <p>Lengkapi formulir di bawah ini dengan data yang valid.</p>

            @if ($errors->any())
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('register.proses') }}" method="POST">
                @csrf
                
                <div class="form-grid">
                    <div class="input-group">
                        <label>NIP</label>
                        <div class="input-wrapper">
                            <i class="bi bi-credit-card-2-front-fill"></i>
                            <input type="text" name="nip_nik" placeholder="Nomor Induk" value="{{ old('nip_nik') }}" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-vcard-fill"></i>
                            <input type="text" name="pegawai_nama" placeholder="Nama Lengkap" value="{{ old('pegawai_nama') }}" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Email</label>
                        <div class="input-wrapper">
                            <i class="bi bi-envelope-fill"></i>
                            <input type="email" name="email" placeholder="Alamat email aktif" value="{{ old('email') }}" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>No. WhatsApp</label>
                        <div class="input-wrapper">
                            <i class="bi bi-telephone-fill"></i>
                            <input type="text" name="no_hp" placeholder="Contoh: 0812..." value="{{ old('no_hp') }}" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Daftar Sebagai</label>
                        <div class="input-wrapper">
                            <i class="bi bi-shield-lock-fill"></i>
                            <select name="role" id="roleSelect" onchange="aturJabatan()" required>
                                <option value="" disabled selected>-- Pilih Role --</option>
                                <option value="pegawai" {{ old('role') == 'pegawai' ? 'selected' : '' }}>Pegawai Fungsional</option>
                                <option value="pimpinan" {{ old('role') == 'pimpinan' ? 'selected' : '' }}>Pimpinan / Asesor</option>
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
                                <option value="Pimpinan" id="optPimpinan" style="display:none;">Pimpinan Unit</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>Unit Kerja / Instansi</label>
                        <div class="input-wrapper">
                            <i class="bi bi-building-fill"></i>
                            <input type="text" name="unit_kerja" placeholder="Contoh: Museum Geologi" value="{{ old('unit_kerja', 'Museum Geologi') }}" required>
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

                <button type="submit" class="login-btn"><i class="bi bi-person-plus-fill me-2"> </i> Buat Akun</button>

                <div class="register-link">
                    Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function aturJabatan() {
    const role = document.getElementById('roleSelect').value;
    const jabatan = document.getElementById('jabatanSelect');
    const optPimpinan = document.getElementById('optPimpinan');

    if (role === 'pimpinan') {
        optPimpinan.style.display = 'block';
        jabatan.value = 'Pimpinan';
        jabatan.style.pointerEvents = 'none';
        jabatan.style.background = '#e2e8f0';
    } else {
        optPimpinan.style.display = 'none';
        jabatan.value = '';
        jabatan.style.pointerEvents = 'auto';
        jabatan.style.background = '#f8fafc';
    }
}

window.onload = function() {
    if(document.getElementById('roleSelect').value !== "") {
        aturJabatan();
    }
};
</script>

</body>
</html>