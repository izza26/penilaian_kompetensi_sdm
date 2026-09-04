@extends('layouts.app')
@section('title', 'Dashboard Pegawai')
@section('page_title', 'Dashboard Pegawai')
@section('page_subtitle', 'Pantau aktivitas dan perkembangan kompetensi Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/dashboard.css') }}">
    <style>
        /* PERBAIKAN FONT AGAR PROPORSIONAL & TIDAK SESAK */
        .pegawai-kpi-card { padding: 20px; } 
        .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }
        
        /* Angka Utama */
        .kpi-value { font-size: 26px; font-weight: 800; color: #1e293b; margin-top: 10px; margin-bottom: 4px; line-height: 1; }
        .kpi-value span { font-size: 13px !important; font-weight: 600; color: #94a3b8; }
        
        /* Teks Status (Sedang Berjalan, dll) */
        .kpi-value-text { font-size: 16px; font-weight: 700; color: #1e293b; margin-top: 14px; margin-bottom: 8px; line-height: 1.2; }
        
        .kpi-desc { font-size: 11px; color: #94a3b8; font-weight: 500; line-height: 1.4; }
        
        .progress-item span { font-size: 11px; color: #64748b; }
        .progress-item strong { font-size: 12px; color: #1e293b; }
        
        .card-header h3 { font-size: 16px; }
        .card-header span { font-size: 14px; }
    </style>
@endpush

@section('content')
<!-- WELCOME CARD -->
<div class="welcome-card">
    <div class="welcome-text">
        <h2>Halo, {{ $pegawai->pegawai_nama }} 👋</h2>
        <div class="welcome-date">{{ $tanggalSekarang }}</div>
        <p>{{ $welcomeMessage }}</p>
    </div>
    <div class="welcome-icon"><i class="bi bi-person-workspace"></i></div>
</div>

<!-- KPI CARDS -->
<div class="pegawai-kpi-grid">
    <!-- Dokumen Bukti (Diperbarui dari Evidence Saya) -->
    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Dokumen Bukti</div>
            <div class="kpi-icon blue"><i class="bi bi-cloud-upload"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $totalUploadSaya }} <span>/ {{ $totalEvidenceWajib }}</span></div>
            <div class="kpi-desc">Dokumen yang telah diunggah</div>
        </div>
    </div>

    <!-- Penilaian Saya -->
    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Penilaian Saya</div>
            <div class="kpi-icon purple"><i class="bi bi-clipboard-check"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value-text">{{ $statusPenilaian }}</div>
            <div class="kpi-desc">Status proses evaluasi saat ini</div>
        </div>
    </div>

    <!-- Kekurangan Bukti (Diperbarui dari Menunggu Review) -->
    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Kekurangan Bukti</div>
            <div class="kpi-icon orange"><i class="bi bi-hourglass-split"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $sisaEvidence }}</div>
            <div class="kpi-desc">Dokumen yang belum diunggah</div>
        </div>
    </div>

    <!-- Status Kompetensi -->
    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Status Kompetensi</div>
            <div class="kpi-icon green"><i class="bi bi-award"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value-text">{{ $statusPenilaian }}</div>
            <div class="kpi-desc">Perkembangan akhir kompetensi</div>
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
            <div class="progress-item"><span>Dokumen Bukti Saya</span><strong>{{ $totalUploadSaya }} / {{ $totalEvidenceWajib }}</strong></div>
            <div class="progress-item"><span>Dokumen Tersisa</span><strong>{{ $sisaEvidence }}</strong></div>
        </div>
        <a href="{{ $aksiLink }}" class="progress-btn">
            <i class="bi bi-{{ $aksiIcon }}"></i> {{ $aksiText }}
        </a>
    </div>

    <!-- RIGHT: INFORMASI PEGAWAI & TIMELINE -->
    <div style="display: flex; flex-direction: column; gap: 24px; width: 100%;">
        <div class="profile-summary">
            <h3>Informasi Pegawai</h3>
            <table>
                <tr><td width="30%">Nama</td><td width="5%">:</td><td><b>{{ $pegawai->pegawai_nama }}</b></td></tr>
                <tr><td>NIP/NIK</td><td>:</td><td>{{ $pegawai->nip_nik }}</td></tr>
                <tr><td>Unit Kerja</td><td>:</td><td>{{ $pegawai->unit_kerja }}</td></tr>
                <tr><td>Jabatan</td><td>:</td><td>{{ $pegawai->jabatan }}</td></tr>
            </table>
        </div>

        <div class="timeline-card">
            <h3>Aktivitas Terbaru Saya</h3>
            <div class="timeline">
                @forelse($aktivitasDashboard as $item)
                    <div class="timeline-item">
                        <div class="timeline-icon"><i class="bi bi-{{ $item['icon'] }}"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-title">{{ $item['judul'] }}</div>
                            <div class="timeline-desc">{{ $item['deskripsi'] }}</div>
                        </div>
                    </div>
                @empty
                    <p style='color:#94a3b8; font-size: 13px; text-align: center; margin-top:20px;'><i class="bi bi-clock-history" style="display:block; font-size: 24px; margin-bottom: 8px;"></i> Belum ada aktivitas.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection