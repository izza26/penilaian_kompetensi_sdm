@extends('layouts.app')
@section('title', 'Edit User')
@section('page_title', 'Edit User')
@section('page_subtitle', 'Perbarui informasi akun pengguna')
@section('back_url', route('admin.user.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 6px 0; font-size: 20px; color: #1e293b; font-weight: 700; }
        .page-title p { margin: 0; font-size: 13px; color: #64748b; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 25px;}
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 12px; font-weight: 600; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;}

        .form-control {
            padding: 0 20px; height: 48px; border: 1px solid #e2e8f0;
            border-radius: 50px; font-size: 13px; outline: none; transition: 0.2s;
            font-family: inherit; background: #f9fafb; color: #1e293b;
        }
        .form-control:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }

        /* Tombol Simpan Biru Royal */
        .btn-primary {
            background: #3e54a0; color: white; border: none; padding: 0 28px; height: 48px;
            border-radius: 50px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 13px;
            display: inline-flex; align-items: center; gap: 8px; justify-content: center;
        }
        .btn-primary:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }

        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper .form-control { width: 100%; padding-right: 45px; box-sizing: border-box; }
        .toggle-password { position: absolute; right: 16px; background: none; border: none; color: #94a3b8; cursor: pointer; padding: 5px; outline: none; font-size: 16px; transition: 0.2s; }
        .toggle-password:hover { color: #3e54a0; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <div class="page-title">
            <h2>Edit User</h2>
            <p>Perbarui informasi akun yang digunakan untuk mengakses sistem.</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px;">
            <ul style="margin: 0; padding-left: 15px;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.user.update', $user->id) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" class="form-control" value="{{ $user->username }}" required autocomplete="off">
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control" value="{{ $user->nama_lengkap }}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="Admin" {{ $user->role == 'Admin' ? 'selected' : '' }}>Admin</option>
                    <option value="Pimpinan" {{ $user->role == 'Pimpinan' ? 'selected' : '' }}>Pimpinan</option>
                </select>
            </div>

            <div class="form-group full-width">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    <option value="Aktif" {{ $user->status == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $user->status == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
@endsection
