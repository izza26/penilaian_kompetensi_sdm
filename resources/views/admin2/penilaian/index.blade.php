@extends('layouts.app')
@section('title', 'Penilaian')
@section('page_title', 'Penilaian')
@section('page_subtitle', 'Pantau dan kelola proses penilaian kompetensi seluruh pegawai')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/pegawai.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/penilaian.css') }}">
    <style>
        .table-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; width: 100%;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 15px; border-bottom: 1px dashed #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .parent-row { transition: 0.2s; cursor: pointer; }
        .parent-row:hover { background-color: #f8fafc; }
        .icon-toggle { transition: transform 0.3s ease; color: #94a3b8; font-size: 15px; font-weight: bold;}
        
        .child-container { padding: 20px 25px 20px 50px; background-color: #f8fafc; border-left: 4px solid #3e54a0; }
        .sub-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0; }
        .sub-table th { background: #ffffff; padding: 14px 15px; font-size: 11px; color: #64748b; border-bottom: 1px solid #e2e8f0;}
        .sub-table td { padding: 16px 15px; font-size: 12px; border-bottom: 1px solid #f1f5f9;}
        
        .evidence-badge { display: inline-flex; align-items: center; justify-content: center; gap: 6px; font-size: 14px; font-weight: 700;}
        .text-target { color: #94a3b8; font-size: 11px; font-weight: 600;}
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; text-align: center; letter-spacing: 0.5px;}
        .badge-hijau { background: #ecfdf5; color: #059669; }
        .badge-kuning { background: #fffbeb; color: #b45309; }
        .badge-merah { background: #fef2f2; color: #dc2626; }
        
        /* Tombol Aksi Pill-Shape */
        .btn-nilai-main { 
            background: #3e54a0; color: white; padding: 0 16px; height: 36px; 
            border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 11px; 
            display: inline-flex; align-items: center; gap: 6px; width: 140px; justify-content: center;
            transition: 0.2s;
        }
        .btn-nilai-main:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2); }
        
        .btn-update-main { 
            background: #f4f7fe; color: #3e54a0; padding: 0 16px; height: 36px; 
            border-radius: 50px; text-decoration: none; font-weight: 600; font-size: 11px; 
            display: inline-flex; align-items: center; gap: 6px; width: 140px; justify-content: center;
            transition: 0.2s; border: 1px solid #3e54a0;
        }
        .btn-update-main:hover { background: #3e54a0; color: white; }
        
        .text-id { font-size: 11px; font-weight: 700; color: #3e54a0; margin-bottom: 4px; display: block;}
    </style>
@endpush

@section('content')
<div class="page-card" style="padding: 0; background: transparent; border: none; box-shadow: none;">
    <div class="table-tools" style="background: white; padding: 20px; border-radius: 16px; margin-bottom: 20px; border: 1px solid #e2e8f0;">
        <form method="GET" action="{{ route('admin.penilaian.index') }}">
            <div style="position: relative; max-width: 400px;">
                <i class="bi bi-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                <input type="text" name="cari" class="search-input" style="width: 100%; padding: 12px 15px 12px 40px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none;" placeholder="Cari nama atau jabatan..." value="{{ $cari }}" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    @if(session('success'))
        <div style="background: #d1fae5; color: #065f46; padding: 10px 15px; border-radius: 8px; margin-bottom: 15px;"><i class="bi bi-check-circle"></i> {{ session('success') }}</div>
    @endif

    <div class="table-card">
        <table class="styled-table">
            <thead>
                <tr><th width="5%" style="text-align: center;"></th><th width="20%">Nama Pegawai</th><th width="20%">Jabatan</th><th width="20%" style="text-align: center;">Progress Evidence</th><th width="15%" style="text-align: center;">Status Penilaian</th><th width="20%" style="text-align: center;">Aksi</th></tr>
            </thead>
            <tbody>
                @forelse ($data_pegawai as $pid => $peg)
                    @php 
                        $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk'] && $peg['total_uk'] > 0);
                        $shortcut_kode = $semua_selesai ? ($peg['uks'][0]['kode_unit'] ?? '') : $peg['first_uk_to_score'];
                        $progress_color = ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) ? '#10b981' : ($peg['total_terkumpul'] > 0 ? '#f59e0b' : '#ef4444');
                    @endphp
                    <tr class="parent-row" onclick="toggleChild('child-{{ $pid }}', this)">
                        <td style="text-align: center;">{!! $peg['total_uk'] > 0 ? '<i class="bi bi-chevron-right icon-toggle"></i>' : '<i class="bi bi-dash" style="color: #cbd5e1;"></i>' !!}</td>
                        <td><b style="color: #0f172a; font-size: 13px; display: block;">{{ $peg['nama'] }}</b><span style="font-size: 11px; color: #94a3b8;">ID: {{ $pid }}</span></td>
                        <td><span style="font-size: 13px; color: #475569; font-weight: 500;">{{ $peg['jabatan'] }}</span></td>
                        @if($peg['total_uk'] == 0)
                            <td style="text-align: center;"><span style="color: #cbd5e1;">-</span></td>
                            <td style="text-align: center;"><span class="badge badge-merah"><i class="bi bi-exclamation-triangle"></i> Belum Dipetakan</span></td>
                            <td style="text-align: center;" onclick="event.stopPropagation();"><button class="btn-nilai-main" style="background-color:#e2e8f0; color:#94a3b8; cursor:not-allowed;" disabled><i class="bi bi-lock-fill"></i> Terkunci</button></td>
                        @else
                            <td style="text-align: center;"><div class="evidence-badge"><i class="bi bi-folder-fill" style="color: {{ $progress_color }};"></i><span style="color: #1e293b;">{{ $peg['total_terkumpul'] }}</span><span class="text-target">/ {{ $peg['total_target'] }}</span></div></td>
                            <td style="text-align: center;">@if($semua_selesai)<span class="badge badge-hijau"><i class="bi bi-check-all"></i> Selesai Dinilai</span>@else<span class="badge badge-kuning"><i class="bi bi-hourglass-split"></i> {{ $peg['uk_dinilai'] }}/{{ $peg['total_uk'] }} Selesai</span>@endif</td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                @if($semua_selesai) <a href="{{ route('admin.penilaian.form', [$pid, $shortcut_kode]) }}" class="btn-update-main"><i class="bi bi-eye"></i> Pantau / Ubah</a>
                                @else <a href="{{ route('admin.penilaian.form', [$pid, $shortcut_kode]) }}" class="btn-nilai-main"><i class="bi bi-play-fill"></i> Mulai Penilaian</a> @endif
                            </td>
                        @endif
                    </tr>
                    @if($peg['total_uk'] > 0)
                    <tr class="child-row" id="child-{{ $pid }}" style="display: none;">
                        <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                            <div class="child-container">
                                <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Penilaian per Unit Kompetensi:</h4>
                                <table class="sub-table">
                                    <thead><tr><th width="40%">Kode & Judul Unit</th><th width="20%" style="text-align: center;">Progress Upload</th><th width="15%" style="text-align: center;">Status Penilaian</th><th width="25%" style="text-align: center;">Aksi</th></tr></thead>
                                    <tbody>
                                        @foreach($peg['uks'] as $uk)
                                            @php $uk_prog_color = ($uk['terkumpul'] >= $uk['target'] && $uk['target'] > 0) ? '#10b981' : ($uk['terkumpul'] > 0 ? '#f59e0b' : '#ef4444'); @endphp
                                            <tr>
                                                <td><span class="text-id"><i class="bi bi-tag-fill"></i> {{ $uk['kode_unit'] }}</span><span style="color: #334155; font-weight: 500; display:block;">{{ $uk['judul_unit'] }}</span></td>
                                                <td style="text-align: center;"><div class="evidence-badge"><i class="bi bi-folder-fill" style="color: {{ $uk_prog_color }};"></i><span style="color: #1e293b;">{{ $uk['terkumpul'] }}</span><span class="text-target">/ {{ $uk['target'] }}</span></div></td>
                                                <td style="text-align: center;">{!! $uk['is_dinilai'] ? '<span class="badge badge-hijau" style="font-size:9px;">SELESAI DINILAI</span>' : '<span class="badge badge-kuning" style="font-size:9px;">BELUM DINILAI</span>' !!}</td>
                                                <td style="text-align: center;">
                                                    @if($uk['is_dinilai']) <a href="{{ route('admin.penilaian.form', [$pid, $uk['kode_unit']]) }}" class="btn-update-main" style="width: auto;"><i class="bi bi-eye"></i> Form Penilaian</a>
                                                    @else <a href="{{ route('admin.penilaian.form', [$pid, $uk['kode_unit']]) }}" class="btn-nilai-main" style="width: auto;"><i class="bi bi-ui-checks"></i> Beri Penilaian</a> @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr><td colspan="6" style="text-align: center; padding: 50px;">Tidak ada data pegawai yang ditemukan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleChild(childId, rowElement) {
    var childRow = document.getElementById(childId);
    if(!childRow) return;
    var icon = rowElement.querySelector('.icon-toggle');
    if (childRow.style.display === 'none' || childRow.style.display === '') {
        childRow.style.display = 'table-row'; rowElement.style.backgroundColor = '#f8fafc';
        if(icon) icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    } else {
        childRow.style.display = 'none'; rowElement.style.backgroundColor = 'transparent';
        if(icon) icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    }
}
</script>
@endpush