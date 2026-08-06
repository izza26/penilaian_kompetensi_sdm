<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><i class="bi bi-compass"></i></div>
        <div class="sidebar-title-wrapper">
            <div class="sidebar-title">MUSEUM GEOLOGI</div>
            <div class="sidebar-subtitle">GEOTRAX: Sistem Informasi Penilaian SDM</div>
        </div>
    </div>

    <div class="sidebar-menu">
        <ul>
            <li>
                <a href="{{ route('pimpinan.dashboard') }}" class="{{ request()->routeIs('pimpinan.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('pimpinan.pegawai.index') }}" class="{{ request()->routeIs('pimpinan.pegawai*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Data Pegawai
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.master_unit.index') }}" class="{{ request()->routeIs('pimpinan.master_unit*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> Master Unit
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('pimpinan.manajemen_penilaian.periode') }}" class="{{ request()->routeIs('pimpinan.manajemen_penilaian*') ? 'active' : '' }}">
                    <i class="bi bi-calendar2-check"></i> Manajemen Penilaian
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.manajemen_evidence.index') }}" class="{{ request()->routeIs('pimpinan.manajemen_evidence*') ? 'active' : '' }}">
                    <i class="bi bi-file-earmark-arrow-up"></i> Kelola Template
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.tim_saya.index') }}" class="{{ request()->routeIs('pimpinan.tim_saya*') ? 'active' : '' }}">
                    <i class="bi bi-people-fill"></i> Tim Saya
                </a>
            </li>
            
            <!-- MENU SKORING SUDAH DIHAPUS DARI SINI -->

            <li>
                <a href="{{ route('pimpinan.hasil_kompetensi.index') }}" class="{{ request()->routeIs('pimpinan.hasil_kompetensi*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> Hasil Kompetensi
                </a>
            </li>
            <li>
                <a href="{{ route('pimpinan.profil.index') }}" class="{{ request()->routeIs('pimpinan.profil*') ? 'active' : '' }}">
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