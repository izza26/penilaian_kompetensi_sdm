@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('page_title', 'Dashboard Admin')
@section('page_subtitle', 'Ringkasan sistem penilaian kompetensi Museum Geologi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/dashboard.css') }}">
@endpush

@section('content')
<div class="welcome-card">
    <div class="welcome-text">
        <h2>Halo, {{ $nama_admin }}</h2>
        <div class="welcome-date">{{ $tanggalSekarang }}</div>
        <p>Selamat datang di panel pengelola Sistem Penilaian Kompetensi SDM. Pantau seluruh aktivitas pegawai, instrumen, dan hasil penilaian dari sini.</p>
    </div>
    <div class="welcome-icon"><i class="bi bi-shield-lock-fill"></i></div>
</div>

<div class="pegawai-kpi-grid">
    <div class="pegawai-kpi-card blue">
        <div class="pegawai-kpi-top"><div class="pegawai-kpi-icon"><i class="bi bi-people-fill"></i></div><div class="pegawai-kpi-label">Total Pegawai</div></div>
        <div class="pegawai-kpi-content"><div class="pegawai-kpi-value blue-text">{{ $tot_pegawai }}</div><div class="pegawai-kpi-desc">Akun pegawai terdaftar.</div></div>
    </div>
    <div class="pegawai-kpi-card orange">
        <div class="pegawai-kpi-top"><div class="pegawai-kpi-icon"><i class="bi bi-inbox-fill"></i></div><div class="pegawai-kpi-label">Penilaian Masuk</div></div>
        <div class="pegawai-kpi-content"><div class="pegawai-kpi-value orange-text">{{ $tot_masuk }}</div><div class="pegawai-kpi-desc">Total dokumen pada sistem.</div></div>
    </div>
    <div class="pegawai-kpi-card purple">
        <div class="pegawai-kpi-top"><div class="pegawai-kpi-icon"><i class="bi bi-hourglass-split"></i></div><div class="pegawai-kpi-label">Menunggu Review</div></div>
        <div class="pegawai-kpi-content"><div class="pegawai-kpi-value purple-text">{{ $tot_tunggu }}</div><div class="pegawai-kpi-desc">Dokumen belum dinilai.</div></div>
    </div>
    <div class="pegawai-kpi-card green">
        <div class="pegawai-kpi-top"><div class="pegawai-kpi-icon"><i class="bi bi-shield-check"></i></div><div class="pegawai-kpi-label">Sudah Dinilai</div></div>
        <div class="pegawai-kpi-content"><div class="pegawai-kpi-value green-text">{{ $tot_selesai }}</div><div class="pegawai-kpi-desc">Dokumen selesai divalidasi.</div></div>
    </div>
</div>

<div class="dashboard-row">
    <div class="progress-card">
        <div class="card-header"><h3>Statistik Kompetensi Museum</h3><span>Total Elemen: {{ $tot_elemen }}</span></div>
        <div class="progress-detail" style="margin-top: 10px;"><div class="progress-item"><span>Sangat Kompeten / Kompeten (≥ 70)</span><strong>{{ $pct_kompeten }}%</strong></div></div>
        <div class="progress" style="height: 8px; margin-bottom: 15px;"><div class="progress-bar bar-kompeten" style="width:{{ $pct_kompeten }}%"></div></div>
        <div class="progress-detail"><div class="progress-item"><span>Cukup Kompeten (55 - 69)</span><strong>{{ $pct_cukup }}%</strong></div></div>
        <div class="progress" style="height: 8px; margin-bottom: 15px;"><div class="progress-bar bar-cukup" style="width:{{ $pct_cukup }}%"></div></div>
        <div class="progress-detail"><div class="progress-item"><span>Perlu Pembinaan (< 55)</span><strong>{{ $pct_bina }}%</strong></div></div>
        <div class="progress" style="height: 8px;"><div class="progress-bar bar-bina" style="width:{{ $pct_bina }}%"></div></div>
    </div>

    <div class="timeline-card">
        <h3>Aktivitas Pegawai Terbaru</h3>
        <div class="timeline">
            @forelse ($aktivitas as $row)
                <div class="timeline-item">
                    <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="timeline-title">Data pegawai baru: <b>{{ $row->pegawai_nama }}</b></div>
                        <div class="timeline-desc">Jabatan: {{ $row->jabatan ?? '-' }} <br> Unit: {{ $row->unit_kerja }}</div>
                    </div>
                </div>
            @empty
                <p style='color:#777; font-size: 14px; margin-top:10px;'>Belum ada aktivitas pegawai baru.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection