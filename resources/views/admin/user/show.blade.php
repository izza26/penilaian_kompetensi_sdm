@extends('layouts.app')
@section('title', 'Detail User')
@section('page_title', 'Detail User')
@section('page_subtitle', 'Informasi lengkap akun pengguna sistem')
@section('back_url', route('admin.user.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 5px 0; font-size: 22px; color: #0f172a; font-weight: 700; }
        .page-title p { margin: 0; font-size: 14px; color: #64748b; }
        .btn-kembali { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: 0.2s; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 6px;}
        .btn-kembali:hover { background: #e2e8f0; color: #0f172a; }

        .profile-card { background: #f8fafc; padding: 30px; border-radius: 12px; text-align: center; margin-bottom: 25px; border: 1px solid #e2e8f0;}
        .user-avatar { width: 80px; height: 80px; background: #bda572; color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 28px; font-weight: 700; margin: 0 auto 15px auto;}
        .profile-card h3 { margin: 0 0 5px 0; color: #0f172a; font-size: 20px;}
        .profile-card p { margin: 0 0 15px 0; color: #64748b; font-size: 14px;}
        .status { background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #a7f3d0;}

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .info-item { display: flex; flex-direction: column; gap: 5px; }
        .info-item label { font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;}
        .info-item span { font-size: 15px; color: #0f172a; font-weight: 600; }
    </style>
@endpush

@section('content')
@php
    $words = explode(" ", $user->nama_lengkap);
    $inisial = "";
    foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); }
    $inisial = substr($inisial, 0, 2);
@endphp

<div class="page-card">
    <div class="page-header">
        <div class="page-title">
            <h2>Detail User</h2>
            <p>Informasi akun yang digunakan untuk mengakses sistem.</p>
        </div>
    </div>

    <div class="profile-card">
        <div class="user-avatar">{{ $inisial }}</div>
        <h3>{{ $user->nama_lengkap }}</h3>
        <p>{{ ucwords($user->role) }}</p>
        <span class="status">{{ $user->status ?? 'Aktif' }}</span>
    </div>

    <div class="info-grid">
        <div class="info-item"><label>Username</label><span>{{ $user->username }}</span></div>
        <div class="info-item"><label>Nama Lengkap</label><span>{{ $user->nama_lengkap }}</span></div>
        <div class="info-item"><label>Email</label><span>{{ $user->email }}</span></div>
        <div class="info-item"><label>Role</label><span>{{ ucwords($user->role) }}</span></div>
        <div class="info-item"><label>Status</label><span>{{ $user->status ?? 'Aktif' }}</span></div>
        <div class="info-item"><label>Tanggal Dibuat</label><span>{{ $user->created_at ? $user->created_at->format('d F Y') : '-' }}</span></div>
    </div>
</div>
@endsection