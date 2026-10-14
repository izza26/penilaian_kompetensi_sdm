@extends('layouts.app')
@section('title', 'Penilaian')
@section('page_title', 'Status Penilaian Sistem')
@section('page_subtitle', 'Pantau progres analisis dokumen evidence Anda oleh sistem')

@push('styles')
    <style>
        .hero-card { background: linear-gradient(135deg, #3e54a0 0%, #2b3a70 100%); border-radius: 24px; padding: 32px 40px; display: flex; justify-content: space-between; align-items: center; gap: 30px; color: white; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(62, 84, 160, 0.2); position: relative; overflow: hidden;}
        .hero-card::after { content: ''; position: absolute; right: -50px; bottom: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .hero-left { z-index: 2; flex: 1; }
        .hero-label { background: rgba(255,255,255,0.15); padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; letter-spacing: 1px; color: #ffffff; display: inline-block; margin-bottom: 16px; border: 1px solid rgba(255, 255, 255, 0.2);}
        .hero-left h1 { margin: 0 0 10px 0; font-size: 28px; font-weight: 800; letter-spacing: -1px; text-shadow: 0 2px 10px rgba(0,0,0,0.2); }
        .hero-left p { font-size: 13px; color: #e0e7ff; margin: 0; line-height: 1.6; max-width: 500px;}
        
        .hero-right { z-index: 2; min-width: 320px; }
        .periode-card-modern { background: #ffffff; border: none; border-radius: 20px; padding: 20px 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; flex-direction: column; gap: 16px; }
        .periode-header { display: flex; align-items: center; gap: 12px; }
        .periode-icon { width: 44px; height: 44px; background: #f4f7fe; color: #3e54a0; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .periode-teks { display: flex; flex-direction: column; }
        .periode-teks .label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 2px; }
        .periode-teks .value { font-size: 15px; font-weight: 800; color: #1e293b; }
        .periode-badges { display: flex; align-items: center; gap: 10px; }
        .badge-date { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #475569; font-weight: 600; background: #f8fafc; padding: 6px 12px; border-radius: 50px; border: 1px solid #cbd5e1; }
        .badge-status { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 6px 12px; border-radius: 50px; }

        .penilaian-grid { grid-template-columns: 1fr; max-width: 800px; margin: 0 auto; }
        .penilaian-card { background: #ffffff; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; flex-direction: column;}
        .card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;}
        .card-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; background: #f4f7fe; color: #3e54a0;}
        .card-progress span { font-size: 24px; font-weight: 800; color: #1e293b;}
        .penilaian-card h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px 0; }
        .penilaian-card p { font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 20px 0; }
        .progress-bar { width: 100%; height: 10px; background: #f1f5f9; border-radius: 50px; overflow: hidden; margin-bottom: 20px;}
        .fill { height: 100%; border-radius: 50px; background: #3e54a0; transition: width 1s ease-in-out;}
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 20px; border-top: 1px dashed #e2e8f0; }
        .status { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; background: #eff6ff; color: #2563eb; display: inline-flex; align-items: center; gap: 6px;}
        
        .btn-lihat { background-color: #3e54a0; color: #ffffff !important; padding: 0 24px; height: 44px; border-radius: 50px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none;}
        .btn-lihat:hover { background-color: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }
        @media(max-width: 768px) { .hero-card { flex-direction: column; align-items: flex-start; } .hero-right { width: 100%; } .card-footer { flex-direction: column; gap: 15px; align-items: flex-start;} .btn-lihat { width: 100%; justify-content: center;}}
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
                <div class="badge-status"><i class="bi bi-check-circle-fill"></i> {{ $statusPeriode }}</div>
            </div>
        </div>
    </div>
</div>

<div class="penilaian-grid">
    <div class="penilaian-card pimpinan">
        <div class="card-top">
            <div class="card-icon"><i class="bi bi-robot"></i></div>
            <div class="card-progress"><span>{{ $penilaian['sistem']['progress'] }}%</span></div>
        </div>
        <h3>Progres Analisis Sistem & Pengesahan</h3>
        <p>Memantau dokumen yang telah di-parsing oleh sistem dan disahkan oleh Pimpinan.</p>
        <div class="progress-bar"><div class="fill fill-blue" style="width:{{ $penilaian['sistem']['progress'] }}%;"></div></div>
        <div class="card-footer">
            <span class="status"><i class="bi bi-info-circle-fill"></i> {{ $penilaian['sistem']['status'] }}</span>
            <a href="{{ route('pegawai.penilaian.list') }}" class="btn-lihat">Lihat Rincian Analisis <i class="bi bi-arrow-right"></i></a>
        </div>
    </div>
</div>
@endsection