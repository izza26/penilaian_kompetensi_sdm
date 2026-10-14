<style>
    /* HEADER UTAMA: MODERN CARD */
    .top-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 16px 28px; min-height: auto;
        background-color: rgba(255, 255, 255, 0.95);
        margin-bottom: 24px;
        border: none;
        border-radius: 24px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
    }

    /* KELAS KHUSUS FLOATING/STICKY (HANYA AKTIF JIKA ADA TOMBOL KEMBALI) */
    .top-header.is-floating {
        position: sticky;
        top: 24px;
        z-index: 999;
        backdrop-filter: blur(10px);
    }

    .header-left, .header-right { display: flex; align-items: center; gap: 16px; height: 100%; }

    /* TOMBOL KEMBALI */
    .btn-back-minimal {
        display: flex; align-items: center; justify-content: center;
        width: 36px; height: 36px; border-radius: 50%;
        background: #f4f7fe; color: #475569; text-decoration: none;
        font-size: 20px; transition: all 0.2s ease; border: none;
    }
    .btn-back-minimal:hover { background: #e0f2fe; color: #3e54a0; transform: translateX(-3px); }

    .header-divider { width: 1px; height: 30px; background-color: #e2e8f0; margin: 0 8px; }
    .header-logo img { max-height: 34px !important; width: auto; }

    .header-title { display: flex; flex-direction: column; justify-content: center; }
    .header-title h1 { font-size: 15px; font-weight: 700; margin: 0 0 2px 0; color: #1e293b; }
    .header-title p { font-size: 11px; font-weight: 500; color: #64748b; margin: 0; }

    .header-icon {
        width: 38px; height: 38px; font-size: 18px; background: #f8fafc;
        border: none; color: #64748b; cursor: pointer; display: flex;
        align-items: center; justify-content: center; border-radius: 50%;
        transition: 0.2s ease;
    }
    .header-icon:hover { color: #3e54a0; background: #e0f2fe; }

    .profile-box { display: flex; align-items: center; gap: 12px; padding: 6px 14px 6px 6px; border-radius: 50px; text-decoration: none; cursor: pointer; transition: 0.2s ease; border: 1px solid transparent;}
    .profile-box:hover { background: #f8fafc; border-color: #e2e8f0;}
    .profile-info { display: flex; flex-direction: column; align-items: flex-end; }
    .profile-name { font-size: 12px; font-weight: 700; color: #1e293b; margin: 0; }
    .profile-role { font-size: 10px; font-weight: 500; color: #64748b; margin-top: 2px;}
    .profile-avatar { width: 34px; height: 34px; font-size: 12px; background-color: #3e54a0; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
</style>

<!-- KONDISI KELAS IS-FLOATING DITAMBAHKAN DI SINI -->
<div class="top-header @hasSection('back_url') is-floating @endif">
    <div class="header-left">
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

        <!-- LOGIKA PERBAIKAN RUTE PROFIL UNTUK SUPERADMIN (ANTI-ERROR) -->
        @php
            // Ambil role dengan format yang bersih
            $roleHeader = strtolower(trim(Auth::user()->role ?? ''));
            // Jika dia kelompok Superadmin, arahkan numpang pakai profil Admin
            if (in_array($roleHeader, ['superadmin', 'super admin', 'sa', 'administrator'])) {
                $roleHeader = 'admin';
            }
        @endphp

        <a href="{{ route($roleHeader . '.profil.index') }}" class="profile-box" title="Lihat Profil Saya">
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