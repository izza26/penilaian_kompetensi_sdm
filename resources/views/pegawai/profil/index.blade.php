@extends('layouts.app')
@section('title', 'Profil Pegawai')
@section('page_title', 'Profil')
@section('page_subtitle', 'Kelola informasi akun Anda')

@push('styles')
    <style>
        .profil-container { padding: 10px 0; }
        
        /* HERO SECTION BANNERS */
        .hero-profile { 
            background: linear-gradient(135deg, #3e54a0 0%, #2b3a70 100%); /* Gradasi Royal Blue */
            border-radius: 24px; 
            padding: 40px; display: flex; justify-content: space-between; align-items: center; 
            gap: 30px; color: white; margin-bottom: 30px; 
            box-shadow: 0 15px 35px rgba(62, 84, 160, 0.2); 
            position: relative; overflow: hidden; 
        }
        .hero-profile::after { content: ''; position: absolute; right: -50px; bottom: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .hero-left { z-index: 2; flex: 1; }
        
        .hero-label { 
            background: rgba(255,255,255,0.15); padding: 6px 16px; border-radius: 50px; 
            font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #ffffff; 
            display: inline-block; margin-bottom: 16px; border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .hero-profile h1 { margin: 0 0 10px 0; font-size: 32px; font-weight: 800; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .hero-position { font-size: 15px; color: #e0e7ff; margin-bottom: 5px; display: flex; align-items: center; gap: 8px;}
        .hero-unit { font-size: 13px; color: #c7d2fe; display: flex; align-items: center; gap: 8px;}
        
        /* Badge Kontak (Pill-shape) */
        .hero-contact { display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; }
        .hero-contact div { 
            background: rgba(255,255,255,0.1); padding: 8px 18px; border-radius: 50px; 
            font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; 
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.1);
        }

        /* SUMMARY CARD DI KANAN */
        .hero-right { 
            background: #ffffff; padding: 30px; border-radius: 24px; 
            width: 320px; flex-shrink: 0; box-sizing: border-box; z-index: 2; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        .summary-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .summary-top span { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px;}
        .status-active { 
            background: #ecfdf5; color: #059669; padding: 6px 14px; 
            border-radius: 50px; font-size: 11px; font-weight: 700; 
            display: flex; align-items: center; gap: 6px;
        }
        
        .summary-divider { height: 1px; border-top: 1px dashed #e2e8f0; margin-bottom: 20px; }
        .summary-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .summary-item small { font-size: 13px; color: #64748b; font-weight: 500; }
        .summary-item strong { font-size: 16px; color: #1e293b; font-weight: 800; }

        /* KARTU INFORMASI BAWAH */
        .card-section { 
            margin-bottom: 24px; background: #fff; border-radius: 24px; 
            padding: 32px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .section-title { 
            font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 24px; 
            display: flex; align-items: center; gap: 10px; padding-bottom: 16px; border-bottom: 1px dashed #e2e8f0; 
        }
        .section-title i { color: #3e54a0; }
        
        .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
        .info-card { background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px dashed #cbd5e1; }
        .info-card span { display: block; font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-bottom: 6px; letter-spacing: 0.5px;}
        .info-card h4 { margin: 0; font-size: 14px; color: #1e293b; font-weight: 600; line-height: 1.4; }

        /* MENU PENGATURAN */
        .setting-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .setting-card { 
            display: flex; align-items: center; gap: 20px; padding: 24px; 
            background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; 
            text-decoration: none; transition: 0.3s;
        }
        .setting-card:hover { border-color: #3e54a0; transform: translateY(-3px); box-shadow: 0 10px 25px rgba(62, 84, 160, 0.08);}
        .setting-icon { 
            width: 50px; height: 50px; border-radius: 50%; 
            display: flex; justify-content: center; align-items: center; font-size: 20px; flex-shrink: 0;
        }
        .setting-card h4 { margin: 0 0 6px 0; font-size: 15px; color: #1e293b; font-weight: 700; }
        .setting-card p { margin: 0; font-size: 12px; color: #64748b; line-height: 1.5; }

        @media(max-width: 992px) {
            .hero-profile { flex-direction: column; align-items: stretch; }
            .hero-right { width: 100%; }
            .info-grid { grid-template-columns: 1fr 1fr; }
            .setting-grid { grid-template-columns: 1fr; }
        }
        @media(max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="profil-container">

    <div class="hero-profile">
        <div class="hero-left">
            <span class="hero-label">PROFIL PEGAWAI</span>
            <h1>{{ $pegawai->pegawai_nama }}</h1>
            <div class="hero-position"><i class="bi bi-briefcase"></i> {{ $pegawai->jabatan }}</div>
            <div class="hero-unit"><i class="bi bi-buildings"></i> {{ $pegawai->unit_kerja }}</div>
            
            <div class="hero-contact">
                <div><i class="bi bi-person-vcard"></i> {{ $pegawai->nip_nik }}</div>
                <div><i class="bi bi-envelope"></i> {{ $pegawai->email }}</div>
                <div><i class="bi bi-telephone"></i> {{ $pegawai->no_hp }}</div>
            </div>
        </div>

        <div class="hero-right">
            <div class="profile-summary">
                <div class="summary-top">
                    <span>Status Akun</span>
                    <div class="status-active"><i class="bi bi-patch-check-fill"></i> Aktif</div>
                </div>
                <div class="summary-divider"></div>
                <div class="summary-item"><small>Total Kompetensi</small><strong>{{ $totalKompetensi }}</strong></div>
                <div class="summary-item"><small>Total Evidence Diunggah</small><strong style="color: #3e54a0;">{{ $totalEvidence }}</strong></div>
                <div class="summary-item" style="margin-bottom:0;"><small>Kompeten</small><strong style="color: #059669;">{{ $totalKompeten }}</strong></div>
            </div>
        </div>
    </div>

    <div class="card-section">
        <div class="section-title"><i class="bi bi-person-vcard"></i> Informasi Pribadi</div>
        <div class="info-grid">
            <div class="info-card"><span>Nama Lengkap</span><h4>{{ $pegawai->pegawai_nama }}</h4></div>
            <div class="info-card"><span>NIP / NIK</span><h4>{{ $pegawai->nip_nik }}</h4></div>
            <div class="info-card"><span>Email</span><h4>{{ $pegawai->email }}</h4></div>
            <div class="info-card"><span>Nomor HP</span><h4>{{ $pegawai->no_hp }}</h4></div>
            <div class="info-card"><span>Jabatan</span><h4>{{ $pegawai->jabatan }}</h4></div>
            <div class="info-card"><span>Unit Kerja</span><h4>{{ $pegawai->unit_kerja }}</h4></div>
            <div class="info-card"><span>Role</span><h4><span style="color:#3e54a0; font-weight:700;"><i class="bi bi-person-fill"></i> {{ ucfirst($pegawai->role) }}</span></h4></div>
        </div>
    </div>

    <div class="card-section" style="margin-bottom: 0;">
        <div class="section-title"><i class="bi bi-shield-lock"></i> Pengaturan Akun</div>
        <div class="setting-grid">
            <a href="#" class="setting-card" onclick="alert('Fitur edit profil akan segera hadir!'); return false;">
                <div class="setting-icon" style="background:#f4f7fe; color:#3e54a0;"><i class="bi bi-pencil-square"></i></div>
                <div><h4>Edit Profil</h4><p>Perbarui data diri pegawai.</p></div>
            </a>
            <a href="#" class="setting-card" onclick="alert('Fitur ubah password akan segera hadir!'); return false;">
                <div class="setting-icon" style="background:#fff1f2; color:#e11d48;"><i class="bi bi-key"></i></div>
                <div><h4>Ubah Password</h4><p>Ganti password akun Anda.</p></div>
            </a>
        </div>
    </div>

</div>
@endsection