@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page_title', 'Tambah Pegawai')
@section('page_subtitle', 'Tambahkan data pegawai baru ke sistem')
@section('back_url', route('pimpinan.pegawai.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/tambah_pegawai.css') }}">
@endpush

@section('content')
<div class="page-card">

    <form action="{{ route('pimpinan.pegawai.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <div class="form-group">
                <label>NIP</label>
                <input type="text" name="nip_nik" required>
            </div>
            <div class="form-group">
                <label>Nama Pegawai</label>
                <input type="text" name="pegawai_nama" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email">
            </div>
            <div class="form-group">
                <label>No. HP</label>
                <input type="text" name="no_hp">
            </div>
            <div class="form-group full-width">
                <label>Jabatan Teknis</label>
                <select name="jabatan" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Register">Register</option>
                    <option value="Kurator">Kurator</option>
                    <option value="Konservator">Konservator</option>
                    <option value="Penata Pameran">Penata Pameran</option>
                    <option value="Edukator">Edukator</option>
                    <option value="Hubungan Masyarakat dan Pemasaran">Hubungan Masyarakat & Pemasaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" value="Museum Geologi" required>
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn-primary">Simpan Pegawai</button>
        </div>
    </form>
</div>
@endsection