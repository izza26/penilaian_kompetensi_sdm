@extends('layouts.app')
@section('title', 'Detail Pegawai')
@section('page_title', 'Detail Pegawai')
@section('back_url', route('pimpinan.pegawai.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/detail_pegawai.css') }}">
@endpush

@section('content')
@php
    $kata_nama = explode(' ', trim($pegawai->pegawai_nama));
    $inisial = count($kata_nama) >= 2 ? strtoupper(substr($kata_nama[0], 0, 1) . substr($kata_nama[1], 0, 1)) : strtoupper(substr($kata_nama[0], 0, 2));
@endphp

<div class="page-card">
    <div class="detail-card">
        <div class="pegawai-summary">
            <div class="avatar">
                {{ $inisial }}
            </div>
            <div class="pegawai-info">
                <h3>{{ $pegawai->pegawai_nama }}</h3>
                <p>{{ $pegawai->jabatan ?? '-' }}</p>
                <span class="status">{{ $pegawai->status_aktif ?? 'Aktif' }}</span>
            </div>
        </div>

        <div class="detail-divider"></div>

        <div class="detail-grid">
            <div class="detail-item">
                <label>NIP</label>
                <div>{{ $pegawai->nip_nik }}</div>
            </div>
            <div class="detail-item">
                <label>Email</label>
                <div>{{ $pegawai->email ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <label>No. HP</label>
                <div>{{ $pegawai->no_hp ?? '-' }}</div>
            </div>
            <div class="detail-item">
                <label>Unit Kerja</label>
                <div>{{ $pegawai->unit_kerja }}</div>
            </div>
        </div>
    </div>
</div>
@endsection