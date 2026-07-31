@extends('layouts.app')
@section('title', 'Data User')
@section('page_title', 'Data User')
@section('page_subtitle', 'Kelola akun pengguna sistem')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/pegawai.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/user.css') }}">
    <style>
        .role { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block;}
        .admin-role { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
        .assessor-role { background: #fef9e8; color: #b45309; border: 1px solid #fde68a; }
        .pimpinan-role { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <form method="GET" action="{{ route('admin.user.index') }}" class="search-form">
            <input type="text" name="cari" class="search-input" placeholder="Cari user..." value="{{ $cari }}" onchange="this.form.submit()">
            @if($cari) <a href="{{ route('admin.user.index') }}" class="btn-reset">(X) Reset</a> @endif
        </form>
        <a href="{{ route('admin.user.create') }}" class="btn-primary" style="text-decoration:none;">+ Tambah User</a>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px; border-radius: 5px; margin-bottom: 15px;"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr><th>No</th><th>Username</th><th>Nama Lengkap</th><th>Role</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($users as $index => $user)
                    @php
                        $role_class = '';
                        $role_db = strtolower($user->role);
                        if (str_contains($role_db, 'admin')) $role_class = 'admin-role';
                        elseif (str_contains($role_db, 'assessor')) $role_class = 'assessor-role';
                        elseif (str_contains($role_db, 'pimpinan')) $role_class = 'pimpinan-role';
                    @endphp
                    <tr>
                        <td>{{ $users->firstItem() + $index }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->nama_lengkap }}</td>
                        <td><span class="role {{ $role_class }}">{{ ucwords($user->role) }}</span></td>
                        <td><span class="status-badge aktif">{{ $user->status ?? 'Aktif' }}</span></td>
                        <td class="action-buttons">
                            <a href="{{ route('admin.user.show', $user->id) }}" class="action-btn view-btn"><i class="bi bi-eye"></i></a>
                            <a href="{{ route('admin.user.edit', $user->id) }}" class="action-btn edit-btn"><i class="bi bi-pencil-square"></i></a>
                            <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="action-btn delete-btn" onclick="return confirm('Yakin ingin menghapus user {{ $user->username }}?');"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center; padding: 20px;">Data user tidak ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div style="margin-top: 20px;">
            {{ $users->appends(['cari' => $cari])->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection