@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page_title', 'Data Pegawai')
@section('page_subtitle', 'Pantau dan kelola status keaktifan pegawai divisi Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/pegawai.css') }}">
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <form action="{{ route('pimpinan.pegawai.index') }}" method="GET" style="display: inline-block;">
            <input type="text" name="cari" placeholder="Cari nama/NIP..." value="{{ $cari }}" class="search-input">
            <button type="submit" style="display: none;">Cari</button>
        </form>

        @if($cari)
            <a href="{{ route('pimpinan.pegawai.index') }}" style="margin-left: 10px; color: red; text-decoration: none; font-size: 14px;">(X) Reset</a>
        @endif

        <a href="{{ route('pimpinan.pegawai.create') }}" class="btn-primary">+ Tambah Pegawai</a>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <i class="bi bi-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th>NIP</th>
                    <th>Nama Pegawai</th>
                    <th>Jabatan</th>
                    <th>Unit Kerja</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pegawais as $row)
                <tr>
                    <td>{{ $row->nip_nik }}</td>
                    <td>{{ $row->pegawai_nama }}</td>
                    <td>{{ $row->jabatan ?? '-' }}</td> 
                    <td>{{ $row->unit_kerja }}</td>
                    <td><span class="status-badge aktif">{{ $row->status_aktif ?? 'Aktif' }}</span></td> 
                    <td class="action-buttons">
                        <a href="{{ route('pimpinan.pegawai.show', $row->pegawai_id) }}" class="btn-icon view"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('pimpinan.pegawai.edit', $row->pegawai_id) }}" class="btn-icon edit"><i class="bi bi-pencil"></i></a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;">Tidak ada data ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>

        <!-- Fitur Pagination Otomatis dari Laravel! -->
        <div style="margin-top: 20px;">
            {{ $pegawais->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection