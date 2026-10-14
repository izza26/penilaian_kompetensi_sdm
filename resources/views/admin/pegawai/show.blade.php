@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page_title', 'Detail Pegawai')
@section('page_subtitle', 'Informasi lengkap pegawai Museum Geologi')
@section('back_url', route('admin.pegawai.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 6px 0; font-size: 20px; color: #1e293b; font-weight: 700; }
        .page-title p { margin: 0; font-size: 13px; color: #64748b; }
        
        .detail-card { background: #f8fafc; border-radius: 24px; padding: 32px; border: 1px solid #f1f5f9; }
        
        .pegawai-summary { display: flex; align-items: center; gap: 24px; margin-bottom: 30px; }
        
        /* Avatar Baru: Bulat & Royal Blue */
        .avatar { 
            width: 80px; height: 80px; background: #3e54a0; color: white; 
            border-radius: 50%; display: flex; justify-content: center; align-items: center; 
            font-size: 28px; font-weight: 700; box-shadow: 0 8px 20px rgba(62, 84, 160, 0.25);
        }
        
        .pegawai-info h3 { margin: 0 0 6px 0; font-size: 20px; color: #1e293b; font-weight: 700;}
        .pegawai-info p { margin: 0 0 12px 0; font-size: 14px; color: #64748b; font-weight: 500;}
        .status-badge { background: #ecfdf5; color: #059669; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block;}
        
        .detail-divider { height: 1px; border-top: 1px dashed #cbd5e1; margin-bottom: 30px; }
        
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .detail-item { display: flex; flex-direction: column; gap: 6px; }
        .detail-item label { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .detail-item span { font-size: 14px; color: #1e293b; font-weight: 600; }
    </style>
@endpush

@section('content')
@php
    $kata_nama = explode(' ', trim($pegawai->pegawai_nama));
    $inisial = count($kata_nama) >= 2 ? strtoupper(substr($kata_nama[0], 0, 1) . substr($kata_nama[1], 0, 1)) : strtoupper(substr($kata_nama[0], 0, 2));
@endphp
<div class="page-card">
    <div class="page-header">
        <div class="page-title">
            <h2>Detail Pegawai</h2>
            <p>Informasi lengkap pegawai yang terlibat dalam proses penilaian kompetensi.</p>
        </div>
    </div>

    <div class="detail-card">
        <div class="pegawai-summary">
            <div class="avatar">{{ $inisial }}</div>
            <div class="pegawai-info">
                <h3>{{ $pegawai->pegawai_nama }}</h3>
                <p>{{ $pegawai->jabatan ?? '-' }}</p>
                <span class="status-badge">{{ $pegawai->status_aktif ?? 'Aktif' }}</span>
            </div>
        </div>

        <div class="detail-divider"></div>

        <div class="detail-grid">
            <div class="detail-item"><label>NIP</label><span>{{ $pegawai->nip_nik }}</span></div>
            <div class="detail-item"><label>Nama Pegawai</label><span>{{ $pegawai->pegawai_nama }}</span></div>
            <div class="detail-item"><label>Email</label><span>{{ $pegawai->email ?? '-' }}</span></div>
            <div class="detail-item"><label>No. WhatsApp</label><span>{{ $pegawai->no_hp ?? '-' }}</span></div>
            <div class="detail-item"><label>Jabatan Teknis</label><span>{{ $pegawai->jabatan ?? '-' }}</span></div>
            <div class="detail-item"><label>Unit Kerja</label><span>{{ $pegawai->unit_kerja }}</span></div>
        </div>
    </div>
</div>
@endsection