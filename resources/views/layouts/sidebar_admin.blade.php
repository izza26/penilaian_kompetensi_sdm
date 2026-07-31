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
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('admin.pegawai.index') }}" class="{{ request()->routeIs('admin.pegawai*') ? 'active' : '' }}">
                    <i class="bi bi-people"></i> Data Pegawai
                </a>
            </li>
            <li>
                <a href="{{ route('admin.user.index') }}" class="{{ request()->routeIs('admin.user*') ? 'active' : '' }}">
                    <i class="bi bi-person-gear"></i> User
                </a>
            </li>
        </ul>
        <ul>
            <li>
                <a href="{{ route('admin.master_unit.index') }}" class="{{ request()->routeIs('admin.master_unit*') ? 'active' : '' }}">
                    <i class="bi bi-diagram-3"></i> Master Unit
                </a>
            </li>
            <!-- MENU BARU: STANDAR PROFIL DITAMBAHKAN DI SINI -->
            <li>
                <a href="{{ route('admin.standar_profil.index') }}" class="{{ request()->routeIs('admin.standar_profil*') ? 'active' : '' }}">
                    <i class="bi bi-sliders"></i> Standar Profil
                </a>
            </li>
            <!-- END MENU BARU -->
            <li>
                <a href="{{ route('admin.penilaian.index') }}" class="{{ request()->routeIs('admin.penilaian*') ? 'active' : '' }}">
                    <i class="bi bi-clipboard-check"></i> Penilaian
                </a>
            </li>
            <li>
                <a href="{{ route('admin.hasil_kompetensi.index') }}" class="{{ request()->routeIs('admin.hasil_kompetensi*') ? 'active' : '' }}">
                    <i class="bi bi-award"></i> Hasil Kompetensi
                </a>
            </li>
            <li>
                <a href="{{ route('admin.profil.index') }}" class="{{ request()->routeIs('admin.profil*') ? 'active' : '' }}">
                    <i class="bi bi-person-circle"></i> Profil
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <form action="{{ route('logout') }}" method="POST" id="logout-form" style="display: none;">@csrf</form>
        <a href="#" class="logout-link" onclick="if(confirm('Apakah Anda yakin ingin keluar?')) document.getElementById('logout-form').submit();">
            <i class="bi bi-box-arrow-right"></i><span>Logout</span>
        </a>
    </div>
</div>