<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="bi bi-compass"></i></div>
        <div class="sidebar-title-wrapper">
            <div class="sidebar-title">MUSEUM GEOLOGI</div>
            <div class="sidebar-subtitle">GEOTRAX: Sistem Penilaian SDM</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <ul>
            <li>
                <a href="{{ route('pegawai.dashboard') }}" class="{{ request()->routeIs('pegawai.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('pegawai.aktivitas.index') }}" class="{{ request()->routeIs('pegawai.aktivitas*') ? 'active' : '' }}">
                    <i class="bi bi-list-check"></i> Aktivitas Saya
                </a>
            </li>
            <li>
                <a href="{{ route('pegawai.penilaian.index') }}" class="{{ request()->routeIs('pegawai.penilaian*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> Penilaian
                </a>
            </li>
            <li>
                <a href="{{ route('pegawai.hasil.index') }}" class="{{ request()->routeIs('pegawai.hasil*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> Hasil Kompetensi
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('pegawai.profil.index') }}" class="{{ request()->routeIs('pegawai.profil*') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> Profil
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">@csrf</form>
        <a href="#" class="logout-link" onclick="if(confirm('Apakah Anda yakin ingin keluar?')) document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</div>