<div class="sidebar" id="main-sidebar">
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
                <a href="{{ route('pimpinan.dashboard') }}" class="{{ request()->routeIs('pimpinan.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> <span class="menu-text">Dashboard</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Manajemen</div>
        <ul>
            <li>
                <a href="{{ route('pimpinan.pegawai.index') }}" class="{{ request()->routeIs('pimpinan.pegawai*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> <span class="menu-text">Data Pegawai</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.master_unit.index') }}" class="{{ request()->routeIs('pimpinan.master_unit*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> <span class="menu-text">Master Unit</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.koleksi.index') }}" class="{{ request()->routeIs('pimpinan.koleksi.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i> <span class="menu-text">Master Koleksi</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.tim_saya.index') }}" class="{{ request()->routeIs('pimpinan.tim_saya*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> <span class="menu-text">Tim Saya</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Penilaian SKKNI</div>
        <ul>
            <li>
                <a href="{{ route('pimpinan.manajemen_penilaian.periode') }}" class="{{ request()->routeIs('pimpinan.manajemen_penilaian*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check"></i> <span class="menu-text">Manajemen Penilaian</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.manajemen_evidence.index') }}" class="{{ request()->routeIs('pimpinan.manajemen_evidence*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-arrow-up"></i> <span class="menu-text">Tampilan Upload Kinerja</span>
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.hasil_kompetensi.index') }}" class="{{ request()->routeIs('pimpinan.hasil_kompetensi*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> <span class="menu-text">Hasil Kompetensi</span>
                </a>
            </li>
        </ul>
        
        <div class="menu-section">Pengaturan</div>
        <ul>
            <li>
                <a href="{{ route('pimpinan.profil.index') }}" class="{{ request()->routeIs('pimpinan.profil*') ? 'active' : '' }}">
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