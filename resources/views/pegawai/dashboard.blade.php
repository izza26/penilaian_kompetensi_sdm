@extends('layouts.app')
@section('title', 'Dashboard Pegawai')
@section('page_title', 'Dashboard Pegawai')
@section('page_subtitle', 'Pantau aktivitas dan perkembangan kompetensi Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/dashboard.css') }}">
@endpush

@section('content')
<!-- WELCOME CARD -->
<div class="welcome-card">
    <div class="welcome-text">
        <h2>Halo, {{ $pegawai->pegawai_nama }}</h2>
        <div class="welcome-date">{{ $tanggalSekarang }}</div>
        <p>{{ $welcomeMessage }}</p>
    </div>
    <div class="welcome-icon"><i class="bi bi-person-workspace"></i></div>
</div>

<!-- KPI CARDS -->
<div class="pegawai-kpi-grid">
    <!-- Evidence Saya -->
    <div class="pegawai-kpi-card blue">
        <div class="pegawai-kpi-top">
            <div class="pegawai-kpi-icon"><i class="bi bi-cloud-upload"></i></div>
            <div class="pegawai-kpi-label">Evidence Saya</div>
        </div>
        <div class="pegawai-kpi-content">
            <div class="pegawai-kpi-value blue-text">{{ $totalUploadSaya }} / {{ $totalEvidenceWajib }}</div>
            <div class="pegawai-kpi-desc">Jumlah evidence yang sudah Anda upload.</div>
        </div>
    </div>

    <!-- Penilaian -->
    <div class="pegawai-kpi-card purple">
        <div class="pegawai-kpi-top">
            <div class="pegawai-kpi-icon"><i class="bi bi-clipboard-check"></i></div>
            <div class="pegawai-kpi-label">Penilaian Saya</div>
        </div>
        <div class="pegawai-kpi-content">
            <div class="pegawai-kpi-value purple-text" style="font-size: 20px;">{{ $statusPenilaian }}</div>
            <div class="pegawai-kpi-desc">Status proses penilaian kompetensi.</div>
        </div>
    </div>

    <!-- Menunggu Review -->
    <div class="pegawai-kpi-card orange">
        <div class="pegawai-kpi-top">
            <div class="pegawai-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="pegawai-kpi-label">Menunggu Review</div>
        </div>
        <div class="pegawai-kpi-content">
            <div class="pegawai-kpi-value orange-text">{{ $sisaEvidence }}</div>
            <div class="pegawai-kpi-desc">Evidence yang belum diupload.</div>
        </div>
    </div>

    <!-- Status Kompetensi -->
    <div class="pegawai-kpi-card green">
        <div class="pegawai-kpi-top">
            <div class="pegawai-kpi-icon"><i class="bi bi-award"></i></div>
            <div class="pegawai-kpi-label">Status Kompetensi</div>
        </div>
        <div class="pegawai-kpi-content">
            <div class="pegawai-kpi-value green-text" style="font-size: 20px;">{{ $statusPenilaian }}</div>
            <div class="pegawai-kpi-desc">Perkembangan kompetensi Anda.</div>
        </div>
    </div>
</div>

<div class="dashboard-row">
    <!-- LEFT: PROGRESS CARD -->
    <div class="progress-card">
        <div class="card-header">
            <h3>Progress Kompetensi</h3>
            <span>{{ $progress }}%</span>
        </div>
        <div class="progress">
            <div class="progress-bar" style="width:{{ $progress }}%"></div>
        </div>
        <div class="progress-detail">
            <div class="progress-item"><span>Status Penilaian</span><strong>{{ $statusPenilaian }}</strong></div>
            <div class="progress-item"><span>Progress Upload</span><strong>{{ $progress }}%</strong></div>
            <div class="progress-item"><span>Evidence Saya</span><strong>{{ $totalUploadSaya }} / {{ $totalEvidenceWajib }}</strong></div>
            <div class="progress-item"><span>Evidence Tersisa</span><strong>{{ $sisaEvidence }}</strong></div>
        </div>
        <a href="{{ $aksiLink }}" class="progress-btn">
            <i class="bi bi-{{ $aksiIcon }}"></i> {{ $aksiText }}
        </a>
    </div>

    <!-- RIGHT: INFORMASI PEGAWAI & TIMELINE -->
    <div style="display: flex; flex-direction: column; gap: 25px; width: 100%;">
        <div class="profile-summary">
            <h3>Informasi Pegawai</h3>
            <table>
                <tr><td width="30%">Nama</td><td width="5%">:</td><td>{{ $pegawai->pegawai_nama }}</td></tr>
                <tr><td>NIP/NIK</td><td>:</td><td>{{ $pegawai->nip_nik }}</td></tr>
                <tr><td>Unit Kerja</td><td>:</td><td>{{ $pegawai->unit_kerja }}</td></tr>
                <tr><td>Jabatan</td><td>:</td><td>{{ $pegawai->jabatan }}</td></tr>
            </table>
        </div>

        <div class="timeline-card">
            <h3>Aktivitas Terbaru Saya</h3>
            <div class="timeline">
                @foreach($aktivitasDashboard as $item)
                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="bi bi-{{ $item['icon'] }}"></i></div>
                        <div>
                            <div class="timeline-title">{{ $item['judul'] }}</div>
                            <div class="timeline-desc">{{ $item['deskripsi'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection