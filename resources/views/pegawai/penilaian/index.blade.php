@extends('layouts.app')
@section('title', 'Penilaian')
@section('page_title', 'Status Penilaian Sistem')
@section('page_subtitle', 'Pantau progres analisis dokumen evidence Anda oleh sistem')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/penilaian.css') }}">
    <style>
        .card-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0; }
        .btn-lihat { background-color: #10b981; color: #ffffff !important; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-lihat:hover { background-color: #059669; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); }
        .penilaian-grid { grid-template-columns: 1fr; max-width: 800px; margin: 0 auto; }
        .penilaian-card { padding: 30px; }
    </style>
@endpush

@section('content')
<div class="hero-card">
    <div class="hero-left">
        <div class="hero-label"><i class="bi bi-cpu-fill me-1"></i> Penilaian Berbasis Sistem (Profile Matching)</div>
        <h1>Cek Status Analisis Dokumen</h1>
        <p>Sistem akan melakukan parsing otomatis terhadap dokumen evidence yang Anda unggah untuk menentukan skor kompetensi Core Factor.</p>
    </div>
    <div class="hero-right">
        <div class="periode-card-modern">
            <div class="periode-header">
                <div class="periode-icon"><i class="bi bi-clock-history"></i></div>
                <div class="periode-teks"><span class="label">Periode Aktif</span><span class="value">{{ $periode }}</span></div>
            </div>
            <div class="periode-badges">
                <div class="badge-date"><i class="bi bi-calendar3"></i> {{ $semester }}</div>
                <div class="badge-status"><i class="bi bi-circle-fill"></i> {{ $statusPeriode }}</div>
            </div>
        </div>
    </div>
</div>

<div class="page-card">
    <div class="penilaian-grid">
        <!-- Analisis Sistem -->
        <div class="penilaian-card pimpinan">
            <div class="card-top">
                <div class="card-icon" style="background: #e0f2fe; color: #0284c7;"><i class="bi bi-robot"></i></div>
                <div class="card-progress"><span>{{ $penilaian['sistem']['progress'] }}%</span></div>
            </div>
            <h3>Progres Analisis Sistem & Pengesahan</h3>
            <p>Memantau dokumen yang telah di-parsing oleh sistem dan disahkan oleh Pimpinan.</p>
            <div class="progress-bar"><div class="fill fill-blue" style="width:{{ $penilaian['sistem']['progress'] }}%; background: #0ea5e9;"></div></div>
            <div class="card-footer">
                <span class="status {{ $penilaian['sistem']['warna'] }}"><i class="bi bi-info-circle-fill"></i> {{ $penilaian['sistem']['status'] }}</span>
                <a href="{{ route('pegawai.penilaian.list') }}" class="btn-lihat">Lihat Rincian Analisis <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
</div>
@endsection