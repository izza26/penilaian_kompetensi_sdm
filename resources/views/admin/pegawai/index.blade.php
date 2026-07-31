@extends('layouts.app')
@section('title', 'Data Pegawai')
@section('page_title', 'Data Pegawai')
@section('page_subtitle', 'Kelola data pegawai Museum Geologi')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/pegawai.css') }}">
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <form action="{{ route('admin.pegawai.index') }}" method="GET" class="search-form">
            <input type="text" name="cari" placeholder="Cari nama, NIP, atau unit..." value="{{ $cari }}" class="search-input">
            <button type="submit" style="display: none;">Cari</button>
            @if($cari) <a href="{{ route('admin.pegawai.index') }}" class="btn-reset">(X) Reset</a> @endif
        </form>
        <a href="{{ route('admin.pegawai.create') }}" class="btn-primary" style="text-decoration:none;"><i class="bi bi-plus-lg"></i> Tambah Pegawai</a>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>No</th><th>NIP</th><th>Nama Pegawai</th><th>Jabatan</th><th>Unit Kerja</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse($pegawais as $index => $row)
                <tr>
                    <td>{{ $pegawais->firstItem() + $index }}</td>
                    <td>{{ $row->nip_nik }}</td>
                    <td style="font-weight: 500; color: #1e293b;">{{ $row->pegawai_nama }}</td>
                    <td>{{ $row->jabatan ?? '-' }}</td> 
                    <td>{{ $row->unit_kerja }}</td>
                    <td><span class="status-badge aktif">{{ $row->status_aktif ?? 'Aktif' }}</span></td> 
                    <td class="action-buttons">
                        <a href="{{ route('admin.pegawai.show', $row->pegawai_id) }}" class="action-btn view-btn"><i class="bi bi-eye"></i></a>
                        <a href="{{ route('admin.pegawai.edit', $row->pegawai_id) }}" class="action-btn edit-btn"><i class="bi bi-pencil"></i></a>
                        <form action="{{ route('admin.pegawai.destroy', $row->pegawai_id) }}" method="POST" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="action-btn delete-btn" onclick="return confirm('Yakin mau menghapus data ini?');"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" style="text-align:center; padding: 20px; color: #94a3b8;">Data pegawai tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top: 20px;">
            {{ $pegawais->appends(['cari' => $cari])->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection