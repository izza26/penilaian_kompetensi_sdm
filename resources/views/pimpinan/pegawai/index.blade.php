@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page_title', 'Data Pegawai')
@section('page_subtitle', 'Pantau dan kelola status keaktifan pegawai divisi Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/pegawai.css') }}">
    <style>
        /* --- CUSTOM PAGINATION LARAVEL --- */
        .pagination { display: flex; justify-content: center; align-items: center; list-style: none; padding: 0; margin: 30px 0 10px 0; gap: 8px; }
        .pagination li { margin: 0; padding: 0; list-style: none; }
        .pagination li a, .pagination li span { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 12px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #475569; background: #ffffff; border: 1px solid #cbd5e1; text-decoration: none; transition: all 0.2s ease; }
        .pagination li a:hover { background: #f8fafc; color: #1B2D46; border-color: #94a3b8; transform: translateY(-2px); }
        .pagination li.active span { background: #bda572; color: #ffffff; border-color: #bda572; box-shadow: 0 4px 10px rgba(189, 165, 114, 0.3); }
        .pagination li.disabled span { color: #94a3b8; background: #f1f5f9; border-color: #e2e8f0; cursor: not-allowed; }
    </style>
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
                        <!-- PERUBAHAN CLASS BUTTON AKSI MENJADI WARNA-WARNI KOTAK -->
                        <a href="{{ route('pimpinan.pegawai.show', $row->pegawai_id) }}" class="action-btn view-btn"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('pimpinan.pegawai.edit', $row->pegawai_id) }}" class="action-btn edit-btn"><i class="bi bi-pencil-square"></i></a>
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