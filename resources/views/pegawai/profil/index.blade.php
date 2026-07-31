@extends('layouts.app')
@section('title', 'Profil Pegawai')
@section('page_title', 'Profil')
@section('page_subtitle', 'Kelola informasi akun Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/profil.css') }}">
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
                <div class="summary-item"><small>Total Evidence Diunggah</small><strong>{{ $totalEvidence }}</strong></div>
                <div class="summary-item"><small>Kompeten</small><strong>{{ $totalKompeten }}</strong></div>
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
            <div class="info-card"><span>Role</span><h4>{{ ucfirst($pegawai->role) }}</h4></div>
        </div>
    </div>

    <div class="card-section">
        <div class="section-title"><i class="bi bi-shield-lock"></i> Pengaturan Akun</div>
        <div class="setting-grid">
            <a href="#" class="setting-card" onclick="alert('Fitur edit profil akan segera hadir!'); return false;">
                <div class="setting-icon"><i class="bi bi-pencil-square"></i></div>
                <div><h4>Edit Profil</h4><p>Perbarui data diri pegawai.</p></div>
            </a>
            <a href="#" class="setting-card" onclick="alert('Fitur ubah password akan segera hadir!'); return false;">
                <div class="setting-icon"><i class="bi bi-key"></i></div>
                <div><h4>Ubah Password</h4><p>Ganti password akun Anda.</p></div>
            </a>
        </div>
    </div>

</div>
@endsection