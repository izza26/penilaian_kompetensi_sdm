@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page_title', 'Detail Pegawai')
@section('back_url', route('pimpinan.pegawai.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/detail_pegawai.css') }}">
@endpush

@section('content')
@php
    $kata_nama = explode(' ', trim($pegawai->pegawai_nama));
    $inisial = count($kata_nama) >= 2 ? strtoupper(substr($kata_nama[0], 0, 1) . substr($kata_nama[1], 0, 1)) : strtoupper(substr($kata_nama[0], 0, 2));
@endphp

<div class="page-card">
    <div class="detail-card">
        <div class="pegawai-summary">
            <div class="avatar" style="width: 80px; height: 80px; background: #182A3A; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 30px; font-weight: bold;">
                {{ $inisial }}
            </div>
            <div class="pegawai-info">
                <h3>{{ $pegawai->pegawai_nama }}</h3>
                <p>{{ $pegawai->jabatan ?? '-' }}</p>
                <span class="status">{{ $pegawai->status_aktif ?? 'Aktif' }}</span>
            </div>
        </div>

        <div class="detail-divider" style="margin: 20px 0; border-bottom: 1px solid #e2e8f0;"></div>

        <div class="detail-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="detail-item">
                <label style="color: #64748b; font-size: 12px;">NIP/NIK</label>
                <div style="font-weight: 600;">{{ $pegawai->nip_nik }}</div>
            </div>
            <div class="detail-item">
                <label style="color: #64748b; font-size: 12px;">Email</label>
                <div style="font-weight: 600;">{{ $pegawai->email ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <label style="color: #64748b; font-size: 12px;">No. HP</label>
                <div style="font-weight: 600;">{{ $pegawai->no_hp ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <label style="color: #64748b; font-size: 12px;">Unit Kerja</label>
                <div style="font-weight: 600;">{{ $pegawai->unit_kerja }}</div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="{{ route('pimpinan.pegawai.index') }}" class="btn-secondary" style="padding: 10px 20px; background: #e2e8f0; text-decoration: none; border-radius: 8px; color: black;">Kembali</a>
        </div>
    </div>
</div>
@endsection