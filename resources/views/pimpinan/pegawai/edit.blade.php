@extends('layouts.app')
@section('title', 'Edit Pegawai')
@section('page_title', 'Edit Pegawai')
@section('back_url', route('pimpinan.pegawai.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/pegawai.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/edit_pegawai.css') }}">
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <h2>Edit Pegawai: {{ $pegawai->pegawai_nama }}</h2>
    </div>

    <form action="{{ route('pimpinan.pegawai.update', $pegawai->pegawai_id) }}" method="POST">
        @csrf
        @method('PUT') <!-- Wajib di Laravel untuk Update Data -->
        
        <div class="form-grid">
            <div class="form-group">
                <label>NIP / NIK</label>
                <input type="text" name="nip_nik" value="{{ $pegawai->nip_nik }}" required>
            </div>
            <div class="form-group">
                <label>Nama Pegawai</label>
                <input type="text" name="pegawai_nama" value="{{ $pegawai->pegawai_nama }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="{{ $pegawai->email }}">
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="no_hp" value="{{ $pegawai->no_hp }}">
            </div>
            <div class="form-group">
                <label>Jabatan</label>
                <input type="text" name="jabatan" value="{{ $pegawai->jabatan }}" required>
            </div>
            <div class="form-group">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" value="{{ $pegawai->unit_kerja }}" required>
            </div>
            <div class="form-group full-width">
                <label>Status</label>
                <select name="status">
                    <option value="Aktif" {{ $pegawai->status_aktif == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $pegawai->status_aktif == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn-primary" style="padding: 12px 25px; border: none; border-radius: 8px; background: #bda572; color: white;">Simpan Perubahan</button>
        </div>
    </form>
</div>
@endsection