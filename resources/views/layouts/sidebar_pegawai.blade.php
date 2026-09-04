<div class="sidebar" id="main-sidebar">
    <!-- Tombol Buka Tutup Sidebar -->
    <button class="sidebar-toggle-btn" id="toggle-sidebar-btn"><i class="bi bi-chevron-left"></i></button>

    <div class="sidebar-header">
        <div class="sidebar-logo">
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
                <a href="{{ route('pegawai.dashboard') }}" class="{{ request()->routeIs('pegawai.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Aktivitas Saya</div>
        <ul>
            <li>
                <a href="{{ route('pegawai.aktivitas.index') }}" class="{{ request()->routeIs('pegawai.aktivitas*') ? 'active' : '' }}">
                    <i class="bi bi-list-check"></i> <span class="menu-text">Isi Bukti Kerja</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pegawai.hasil.index') }}" class="{{ request()->routeIs('pegawai.hasil*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> <span class="menu-text">Hasil Kompetensi</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pegawai.koleksi.index') }}" class="{{ request()->routeIs('pegawai.koleksi.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> <span class="menu-text">Master Koleksi</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Pengaturan</div>
        <ul>
            <li>
                <a href="{{ route('pegawai.profil.index') }}" class="{{ request()->routeIs('pegawai.profil*') ? 'active' : '' }}">
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

<!-- SCRIPT UNTUK TOGGLE SIDEBAR & MENCEGAH LONCAT (FOUC) -->
<script>
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }

    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggle-sidebar-btn');
        const body = document.body;

        if(toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                body.classList.toggle('sidebar-collapsed');
                
                if (body.classList.contains('sidebar-collapsed')) {
                    localStorage.setItem('sidebar-collapsed', 'true');
                } else {
                    localStorage.setItem('sidebar-collapsed', 'false');
                }
            });
        }
    });
</script>