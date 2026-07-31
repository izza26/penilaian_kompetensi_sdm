@extends('layouts.app')
@section('title', 'Rincian Penilaian')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Rincian nilai aktivitas dan rekomendasi rotasi posisi.')
@section('back_url', route('pimpinan.hasil_kompetensi.index'))

@push('styles')
    <style>
        .summary-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; flex-wrap: wrap;}
        .summary-left { flex: 1; }
        .summary-left h2 { margin: 0 0 5px 0; font-size: 20px; color: #0f172a; display: flex; align-items: center; gap: 8px;}
        .summary-left p { margin: 0 0 15px 0; color: #64748b; font-size: 13px; }
        .unit-info { background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .unit-info strong { display: block; color: #3b82f6; font-size: 12px; margin-bottom: 2px; }
        .unit-info span { color: #1e293b; font-size: 14px; font-weight: 500; }
        
        .summary-right { display: flex; gap: 15px; }
        .box-skor { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; text-align: center; min-width: 150px;}
        .box-skor span { display: block; font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .box-skor h1 { margin: 0 0 10px 0; font-size: 32px; color: #A08348; }
        
        .box-match { padding: 20px; border-radius: 12px; text-align: center; min-width: 180px;}
        .box-match h3 { margin: 0 0 5px 0; font-size: 16px; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-block; }
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        
        .table-card { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; }
        .table-card h3 { margin: 0 0 20px 0; font-size: 16px; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .skor-box { background: #eff6ff; color: #3b82f6; font-size: 16px; font-weight: 700; width: 36px; height: 36px; display: inline-flex; justify-content: center; align-items: center; border-radius: 8px; border: 1px solid #bfdbfe; }
        .badge-auto { background: #f1f5f9; color: #64748b; font-size: 10px; padding: 4px 8px; border-radius: 6px; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; gap: 4px; }
    </style>
@endpush

@section('content')
@php
    $badge_class = 'badge-merah';
    if ($header->nilai_akhir >= 70) $badge_class = 'badge-hijau';
    elseif ($header->nilai_akhir >= 55) $badge_class = 'badge-kuning';
@endphp

<div class="summary-card">
    <div class="summary-left">
        <h2><i class="bi bi-person-badge" style="color: #3b82f6;"></i> {{ $header->pegawai_nama }}</h2>
        <p>Jabatan Saat Ini: <b style="color: #0f172a;">{{ $header->jabatan }}</b></p>
        <div class="unit-info">
            <strong>[{{ $header->kode_unit }}]</strong>
            <span>{{ $header->judul_unit }}</span>
        </div>
        @if(!empty($header->rekomendasi))
        <div style="background: #fffbeb; padding: 15px; border-radius: 10px; border: 1px dashed #fcd34d; margin-top: 20px;">
            <b style="color: #b45309; font-size: 13px; display: block; margin-bottom: 5px;"><i class="bi bi-chat-quote-fill"></i> Catatan Anda:</b>
            <p style="margin: 0; color: #92400e; font-size: 13px; font-style: italic;">"{{ $header->rekomendasi }}"</p>
        </div>
        @endif
    </div>
    
    <div class="summary-right">
        <!-- Skor Kelulusan -->
        <div class="box-skor">
            <span>Nilai Akhir (Skala 100)</span>
            <h1>{{ number_format($header->nilai_akhir, 2) }}</h1>
            <span class="badge {{ $badge_class }}">{{ $header->kategori }}</span>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 10px;">Disahkan pada:<br>{{ date('d M Y, H:i', strtotime($header->waktu_submit)) }}</div>
        </div>
        
        <!-- Rekomendasi Match Profile -->
        <div class="box-match" style="background: {{ $is_match ? '#ecfdf5' : '#fffbeb' }}; border: 1px solid {{ $is_match ? '#a7f3d0' : '#fde68a' }};">
            <span style="display: block; font-size: 11px; font-weight: 700; color: {{ $is_match ? '#059669' : '#d97706' }}; margin-bottom: 5px;">REKOMENDASI SISTEM</span>
            <h3 style="color: {{ $is_match ? '#047857' : '#b45309' }};">{{ $best_match_role }}</h3>
            @if($is_match)
                <div style="font-size: 12px; color: #059669; font-weight: 600;"><i class="bi bi-check-circle-fill"></i> MATCH (Tepat)</div>
            @else
                <div style="font-size: 12px; color: #b45309; font-weight: 600;"><i class="bi bi-exclamation-triangle-fill"></i> MISMATCH (Rotasi)</div>
            @endif
        </div>
    </div>
</div>

<div class="table-card">
    <h3><i class="bi bi-list-check" style="color: #3b82f6; margin-right: 8px;"></i> Rincian Skor Historis per Aktivitas</h3>
    <table class="styled-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">No</th>
                <th width="15%">ID Aktivitas</th>
                <th width="45%">Detail Aktivitas Kompetensi</th>
                <th width="20%" style="text-align: center;">Keterangan Sistem</th>
                <th width="15%" style="text-align: center;">Nilai Aktual (CF)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $index => $d)
                <tr>
                    <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                    <td><b style="color: #1e293b;">{{ $d->aktivitas_id }}</b></td>
                    <td style="line-height: 1.5;">{{ $d->detail_aktivitas }}</td>
                    <td style="text-align: center;"><span class="badge-auto"><i class="bi bi-check-all"></i> Auto-parsed</span></td>
                    <td style="text-align: center;"><div class="skor-box">{{ $d->skor_final }}</div></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection