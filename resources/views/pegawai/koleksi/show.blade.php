@extends('layouts.app')
@section('title', 'Detail Koleksi')
@section('page_title', 'Rincian Koleksi')
@section('page_subtitle', 'Informasi mendalam mengenai spesifikasi koleksi museum.')
@section('back_url', route('pegawai.koleksi.index'))

@push('styles')
    <style>
        .detail-card { background: #fff; border-radius: 24px; padding: 32px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .detail-header { display: flex; align-items: flex-start; gap: 24px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 24px; margin-bottom: 24px;}
        .icon-large { width: 80px; height: 80px; background: #f4f7fe; color: #3e54a0; border-radius: 20px; display: flex; justify-content: center; align-items: center; font-size: 40px; flex-shrink: 0;}
        .title-area h2 { margin: 0 0 8px 0; font-size: 24px; color: #1e293b; font-weight: 800;}
        .badge { display: inline-block; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}
        .bg-blue { background: #eff6ff; color: #2563eb; }
        .bg-green { background: #ecfdf5; color: #059669; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .info-box { background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0;}
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; display: block;}
        .info-val { font-size: 14px; color: #1e293b; font-weight: 600; line-height: 1.5;}
        
        .desc-box { background: #fff; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; padding: 24px; border-radius: 16px; margin-top: 24px;}
        .desc-box p { margin: 0; font-size: 14px; color: #475569; line-height: 1.6;}
        
        @media(max-width: 768px) { .info-grid { grid-template-columns: 1fr; } .detail-header { flex-direction: column; align-items: center; text-align: center; } }
    </style>
@endpush

@section('content')
<div class="detail-card">
    <div class="detail-header">
        <div class="icon-large"><i class="bi bi-box-seam"></i></div>
        <div class="title-area">
            <h2>{{ $koleksi->nama_koleksi }}</h2>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <span class="badge bg-blue"><i class="bi bi-tag-fill"></i> {{ $koleksi->kategori_koleksi ?? 'Kategori Umum' }}</span>
                <span class="badge bg-green"><i class="bi bi-check-circle-fill"></i> Status: {{ $koleksi->status_koleksi }}</span>
            </div>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <span class="info-label">Nomor Registrasi</span>
            <span class="info-val" style="font-family: monospace; font-size: 16px;"><i class="bi bi-upc-scan"></i> {{ $koleksi->nomor_registrasi }}</span>
        </div>
        <div class="info-box">
            <span class="info-label">Tanggal Masuk Koleksi</span>
            <span class="info-val"><i class="bi bi-calendar3"></i> {{ date('d F Y', strtotime($koleksi->tanggal_masuk)) }}</span>
        </div>
    </div>

    <div class="desc-box">
        <span class="info-label" style="color: #3e54a0; margin-bottom: 12px;"><i class="bi bi-card-text"></i> Deskripsi Lengkap</span>
        <p>{{ $koleksi->deskripsi ?? 'Tidak ada catatan deskripsi untuk koleksi ini.' }}</p>
    </div>
</div>
@endsection