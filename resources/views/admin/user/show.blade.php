@extends('layouts.app')
@section('title', 'Detail User')
@section('page_title', 'Detail User')
@section('page_subtitle', 'Informasi lengkap akun pengguna sistem')
@section('back_url', route('admin.user.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 24px; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 6px 0; font-size: 20px; color: #1e293b; font-weight: 700; }
        .page-title p { margin: 0; font-size: 13px; color: #64748b; }

        .profile-card { background: #f8fafc; padding: 32px; border-radius: 24px; text-align: center; margin-bottom: 30px; border: 1px solid #f1f5f9;}
        
        /* Avatar Royal Blue Mewah */
        .user-avatar { 
            width: 80px; height: 80px; background: #3e54a0; color: white; 
            border-radius: 50%; display: flex; justify-content: center; align-items: center; 
            font-size: 28px; font-weight: 700; margin: 0 auto 16px auto; 
            box-shadow: 0 8px 20px rgba(62, 84, 160, 0.25);
        }
        
        .profile-card h3 { margin: 0 0 6px 0; color: #1e293b; font-size: 20px; font-weight: 700;}
        .profile-card p { margin: 0 0 16px 0; color: #64748b; font-size: 14px; font-weight: 500;}
        .status { background: #ecfdf5; color: #059669; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block;}

        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .info-item { display: flex; flex-direction: column; gap: 6px; }
        .info-item label { font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;}
        .info-item span { font-size: 14px; color: #1e293b; font-weight: 600; }
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