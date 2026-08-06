<style>
    /* HEADER UTAMA: FLOATING CARD MODERN */
    .top-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; min-height: 70px; background-color: #ffffff; margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
    .header-left, .header-right { display: flex; align-items: center; gap: 15px; height: 100%; }
    
    /* TOMBOL KEMBALI MINIMALIS */
    .btn-back-minimal { display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%; background: #f8fafc; color: #475569; text-decoration: none; font-size: 22px; transition: all 0.2s ease; border: 1px solid #e2e8f0; }
    .btn-back-minimal:hover { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; transform: translateX(-3px); }
    .header-divider { width: 1px; height: 30px; background-color: #e2e8f0; margin: 0 5px; }

    /* Mencegah logo membesar */
    .header-logo img { max-height: 38px !important; width: auto; }
    
    .header-title { display: flex; flex-direction: column; justify-content: center; }
    .header-title h1 { font-size: 18px; font-weight: 700; margin: 0 0 2px 0; color: #0f172a; }
    .header-title p { font-size: 11px; font-weight: 500; color: #64748b; margin: 0; }
    
    .header-icon { width: 40px; height: 40px; font-size: 18px; background: transparent; border: none; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    
    .profile-box { display: flex; align-items: center; gap: 12px; padding: 6px 12px; border-radius: 30px; text-decoration: none; cursor: pointer; transition: 0.2s ease; }
    .profile-box:hover { background: #f8fafc; }
    .profile-info { display: flex; flex-direction: column; align-items: flex-end; }
    .profile-name { font-size: 13px; font-weight: 700; color: #0f172a; margin: 0; }
    .profile-role { font-size: 11px; font-weight: 500; color: #64748b; margin-top: 2px;}
    .profile-avatar { width: 38px; height: 38px; font-size: 13px; background-color: #183851; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
</style>

<div class="top-header">
    <div class="header-left">
        
        <!-- LOGIKA TOMBOL KEMBALI DINAMIS -->
        @hasSection('back_url')
            <a href="@yield('back_url')" class="btn-back-minimal" title="Kembali">
                <i class="bi bi-arrow-left-short"></i>
            </a>
            <div class="header-divider"></div>
        @endif

        <div class="header-logo">
            <img src="{{ asset('assets/img/logo-museum.png') }}" alt="Museum Geologi">
        </div>
        <div class="header-title">
            <h1>@yield('page_title', 'Museum Geologi')</h1>
            <p>@yield('page_subtitle', 'Sistem Penilaian Kompetensi SDM')</p>
        </div>
    </div>

    <div class="header-right">
        <button class="header-icon"><i class="bi bi-bell"></i></button>

        <!-- PERUBAHAN ADA DI BARIS href INI -->
        <a href="{{ route(strtolower(Auth::user()->role) . '.profil.index') }}" class="profile-box" title="Lihat Profil Saya">
            <div class="profile-info">
                <span class="profile-name">{{ Auth::user()->pegawai_nama }}</span>
                <span class="profile-role">{{ Auth::user()->jabatan ?? ucfirst(Auth::user()->role) }}</span>
            </div>

            <!-- Inisial Nama Otomatis -->
            @php
                $nama = explode(' ', trim(Auth::user()->pegawai_nama));
                $inisial = count($nama) >= 2 ? strtoupper(substr($nama[0], 0, 1) . substr($nama[1], 0, 1)) : strtoupper(substr($nama[0], 0, 2));
            @endphp

            <div class="profile-avatar">
                {{ $inisial }}
            </div>
        </a>
    </div>
</div>