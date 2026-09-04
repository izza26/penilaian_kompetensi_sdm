
<!-- SCRIPT UNTUK TOGGLE SIDEBAR & MENCEGAH LONCAT (FOUC) -->
<script>
    // 1. EKSEKUSI SEGERA (SYNCHRONOUS)
    // Script ini tidak menunggu DOMContentLoaded agar sidebar langsung 
    // tertutup saat pertama kali browser merender halaman (tanpa ada animasi loncat).
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }

    // 2. EVENT LISTENER UNTUK TOMBOL (ASYNCHRONOUS)
    // Menjalankan fungsi tombol ketika halaman sudah siap
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggle-sidebar-btn');
        const body = document.body;

        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                body.classList.toggle('sidebar-collapsed');
                
                // Simpan state saat ini ke local storage
                if (body.classList.contains('sidebar-collapsed')) {
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    localStorage.setItem('sidebar-collapsed', 'false');
                }
            });
        }
    });
</script>

<div class="sidebar" id="main-sidebar">
    <button class="sidebar-toggle-btn" id="toggle-sidebar-btn"><i class="bi bi-chevron-left"></i></button>

    <div class="sidebar-header">
        <div class="sidebar-logo">
            <!-- Ikon Kompas diganti dengan Logo Museum Geologi -->
            <img src="{{ asset('assets/img/logo-museum.png') }}" alt="Logo" style="width: 24px; filter: brightness(0) invert(1);">
        </div>
        <div class="sidebar-title-wrapper">
            <div class="sidebar-title">MUSEUM GEOLOGI</div>
            <div class="sidebar-subtitle">GEOTRAX: Sistem Penilaian SDM</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <div class="menu-section">Utama</div>
        <ul>
            <li>
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Manajemen Akun</div>
        <ul>
            <li>
                <a href="{{ route('admin.pegawai.index') }}" class="{{ request()->routeIs('admin.pegawai*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> <span class="menu-text">Data Pegawai</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.user.index') }}" class="{{ request()->routeIs('admin.user*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i> <span class="menu-text">User</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Aktvitas Penilaian</div>
        <ul>
            <li>
                <a href="{{ route('admin.master_unit.index') }}" class="{{ request()->routeIs('admin.master_unit*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> <span class="menu-text">Master Unit</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.standar_profil.index') }}" class="{{ request()->routeIs('admin.standar_profil*') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i> <span class="menu-text">Standar Profil</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.penilaian.index') }}" class="{{ request()->routeIs('admin.penilaian*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> <span class="menu-text">Penilaian</span>
                </a>
            </li>
            <li>
                <a href="{{ route('admin.hasil_kompetensi.index') }}" class="{{ request()->routeIs('admin.hasil_kompetensi*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> <span class="menu-text">Hasil Kompetensi</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Pengaturan</div>
        <ul>
            <li>
                <a href="{{ route('admin.profil.index') }}" class="{{ request()->routeIs('admin.profil*') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> <span class="menu-text">Profil</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">@csrf</form>
        <a href="#" class="logout-link" onclick="if(confirm('Apakah Anda yakin ingin keluar?')) document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i> <span class="menu-text">Logout</span>
        </a>
    </div>
</div>
