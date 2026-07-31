@extends('layouts.app')
@section('title', 'Tambah User')
@section('page_title', 'Tambah User')
@section('page_subtitle', 'Tambahkan akun pengguna baru ke sistem')
@section('back_url', route('admin.user.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        /* STRUKTUR FLEX UNTUK MENJAJARKAN JUDUL & TOMBOL KEMBALI */
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 5px 0; font-size: 22px; color: #0f172a; font-weight: 700; }
        .page-title p { margin: 0; font-size: 14px; color: #64748b; }
        
        .btn-kembali { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: 0.2s; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 6px;}
        .btn-kembali:hover { background: #e2e8f0; color: #0f172a; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;}
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 13px; font-weight: 600; color: #1e293b; }
        .form-control { padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; transition: 0.2s; font-family: inherit;}
        .form-control:focus { border-color: #A08348; box-shadow: 0 0 0 3px rgba(160,131,72,0.1); }
        .btn-primary { background: #1B2D46; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px;}
        .btn-primary:hover { background: #29466b; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <div class="page-title">
            <h2>Tambah User</h2>
            <p>Lengkapi informasi akun yang akan digunakan untuk mengakses sistem.</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px;">
            <ul style="margin: 0; padding-left: 15px;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.user.store') }}" autocomplete="off">
        @csrf
        <div class="form-grid">
            <!-- Tambahan autocomplete="off" pada username agar tidak diisi otomatis -->
            <div class="form-group"><label>Username</label><input type="text" name="username" class="form-control" value="{{ old('username') }}" required autocomplete="off"></div>
            
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" class="form-control" value="{{ old('nama_lengkap') }}" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
            <div class="form-group">
                <label>Role</label>
                <select name="role" class="form-control" required>
                    <option value="Admin">Admin</option>
                    <option value="Pimpinan">Pimpinan</option>
                </select>
            </div>
            
            <!-- Tambahan autocomplete="new-password" pada kolom password -->
            <div class="form-group"><label>Password</label><input type="password" name="password" class="form-control" required autocomplete="new-password"></div>
            <div class="form-group"><label>Konfirmasi Password</label><input type="password" name="konfirmasi_password" class="form-control" required autocomplete="new-password"></div>
            
            <div class="form-group full-width">
                <label>Status</label>
                <select name="status" class="form-control" required>
                    <option value="Aktif">Aktif</option>
                    <option value="Nonaktif">Nonaktif</option>
                </select>
            </div>
        </div>
        <div><button type="submit" class="btn-primary">Simpan User</button></div>
    </form>
</div>
@endsection