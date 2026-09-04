@extends('layouts.app')
@section('title', 'Rincian Penilaian')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Rincian nilai aktivitas dan rekomendasi rotasi posisi.')
@section('back_url', route('pimpinan.hasil_kompetensi.index'))

@push('styles')
    <style>
        .summary-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); padding: 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; flex-wrap: wrap;}
        .summary-left { flex: 1; }
        .summary-left h2 { margin: 0 0 8px 0; font-size: 22px; color: #1e293b; display: flex; align-items: center; gap: 10px; font-weight: 700;}
        .summary-left p { margin: 0 0 16px 0; color: #64748b; font-size: 13px; }
        .unit-info { background: #f8fafc; padding: 16px 20px; border-radius: 16px; border: 1px dashed #cbd5e1; }
        .unit-info strong { display: block; color: #3e54a0; font-size: 12px; margin-bottom: 4px; font-weight: 700;}
        .unit-info span { color: #1e293b; font-size: 14px; font-weight: 600; }
        
        .summary-right { display: flex; gap: 16px; flex-wrap: wrap;}
        .box-skor { background: #f8fafc; padding: 24px; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; min-width: 160px; display: flex; flex-direction: column; justify-content: center; align-items: center;}
        .box-skor span.label-skor { display: block; font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px;}
        .box-skor h1 { margin: 0 0 12px 0; font-size: 36px; color: #3e54a0; font-weight: 800; line-height: 1;}
        
        .box-match { padding: 24px; border-radius: 16px; text-align: center; min-width: 180px; max-width: 250px; display: flex; flex-direction: column; justify-content: center;}
        .box-match h3 { margin: 0 0 8px 0; font-size: 15px; font-weight: 700; line-height: 1.4;}
        
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-block; letter-spacing: 0.5px; text-transform: uppercase;}
        .badge-hijau { background: #ecfdf5; color: #059669; }
        .badge-kuning { background: #fffbeb; color: #d97706; }
        .badge-merah { background: #fff1f2; color: #e11d48; }
        
        .table-card { background: #fff; padding: 32px; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .table-card h3 { margin: 0 0 24px 0; font-size: 18px; color: #1e293b; border-bottom: 1px dashed #e2e8f0; padding-bottom: 16px; font-weight: 700;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 20px; border-bottom: 1px dashed #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        @media(max-width: 768px) { .summary-card { flex-direction: column; } .summary-right { width: 100%; flex-direction: column;} .box-match { max-width: 100%; } }
    </style>
@endpush

@section('content')
@php
    $badge_class = 'badge-merah';
    if ($header->nilai_akhir >= 70) $badge_class = 'badge-hijau';
    elseif ($header->nilai_akhir >= 55) $badge_class = 'badge-kuning';

    // Menentukan Tindakan Lanjut Berdasarkan Kategori Kelulusan
    $tindakan_lanjut = "";
    $tindakan_color = "";
    $tindakan_bg = "";
    $tindakan_border = "";

    if (trim($header->kategori) == 'Sangat Kompeten') {
        $tindakan_lanjut = "Siap Promosi / Sertifikasi Asesor";
        $tindakan_color = "#4338ca"; // Indigo
        $tindakan_bg = "#e0e7ff";
        $tindakan_border = "#c7d2fe";
    } elseif (trim($header->kategori) == 'Kompeten') {
        $tindakan_lanjut = "Pertahankan Kinerja / Delegasi Tugas Khusus";
        $tindakan_color = "#059669"; // Green
        $tindakan_bg = "#d1fae5";
        $tindakan_border = "#a7f3d0";
    } elseif (trim($header->kategori) == 'Cukup Kompeten') {
        $tindakan_lanjut = "Mengikuti Seminar / Lokakarya Bidang Terkait";
        $tindakan_color = "#d97706"; // Orange
        $tindakan_bg = "#fef3c7";
        $tindakan_border = "#fde68a";
    } else {
        // Untuk Belum Kompeten / Kurang Kompeten
        $tindakan_lanjut = "Wajib Pelatihan / Evaluasi Rotasi Jabatan";
        $tindakan_color = "#e11d48"; // Red
        $tindakan_bg = "#fff1f2";
        $tindakan_border = "#fecdd3";
    }
@endphp

<div class="summary-card">
    <div class="summary-left">
        <h2><i class="bi bi-person-badge" style="color: #3e54a0;"></i> {{ $header->pegawai_nama }}</h2>
        <p>Jabatan Saat Ini: <b style="color: #1e293b;">{{ $header->jabatan }}</b></p>
        <div class="unit-info">
            <strong>[{{ $header->kode_unit }}]</strong>
            <span>{{ $header->judul_unit }}</span>
        </div>
        @if(!empty($header->rekomendasi))
        <div style="background: #f4f7fe; padding: 16px 20px; border-radius: 16px; border: 1px dashed #bfdbfe; margin-top: 20px;">
            <b style="color: #3e54a0; font-size: 13px; display: block; margin-bottom: 6px;"><i class="bi bi-chat-quote-fill"></i> Catatan Evaluasi Pimpinan:</b>
            <p style="margin: 0; color: #1e293b; font-size: 13px; font-style: italic;">"{{ $header->rekomendasi }}"</p>
        </div>
        @endif
    </div>
    
    <div class="summary-right">
        <!-- Skor Kelulusan -->
        <div class="box-skor">
            <span class="label-skor">Nilai Akhir (Skala 100)</span>
            <h1>{{ number_format($header->nilai_akhir, 2) }}</h1>
            <span class="badge {{ $badge_class }}">{{ $header->kategori }}</span>
            <div style="font-size: 10px; color: #94a3b8; margin-top: 12px;">Disahkan pada:<br><b style="color: #64748b;">{{ date('d M Y, H:i', strtotime($header->waktu_submit)) }}</b></div>
        </div>
        
        <!-- Rekomendasi Tindakan Lanjut -->
        <div class="box-match" style="background: {{ $tindakan_bg }}; border: 1px solid {{ $tindakan_border }};">
            <span style="display: block; font-size: 11px; font-weight: 800; color: {{ $tindakan_color }}; margin-bottom: 8px; letter-spacing: 0.5px; opacity: 0.8;"><i class="bi bi-lightbulb-fill"></i> TINDAKAN LANJUT</span>
            <h3 style="color: {{ $tindakan_color }};">{{ $tindakan_lanjut }}</h3>
            <div style="font-size: 11px; color: {{ $tindakan_color }}; font-weight: 600; margin-top: 8px; opacity: 0.9;">Berdasarkan Gap Kompetensi</div>
        </div>
    </div>
</div>

<div class="table-card">
    <h3><i class="bi bi-bar-chart-fill" style="color: #3e54a0; margin-right: 8px;"></i> Hasil & Ranking Profile Matching</h3>
    
    @if($hasil_pm->isEmpty())
        <div style="padding: 24px; text-align: center; color: #e11d48; background: #fff1f2; border-radius: 16px; border: 1px dashed #fecaca; font-weight: 500; font-size: 13px;">
            <i class="bi bi-exclamation-triangle-fill" style="display: block; font-size: 24px; margin-bottom: 8px; color: #f43f5e;"></i>
            Data Profile Matching belum dihitung oleh sistem. Silakan selesaikan penilaian di menu <b>Tim Saya</b> terlebih dahulu.
        </div>
    @else
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="10%" style="text-align: center;">Peringkat</th>
                    <th width="30%">Jabatan / Posisi</th>
                    <th width="20%" style="text-align: center;">Core Factor (CF)</th>
                    <th width="20%" style="text-align: center;">Secondary Factor (SF)</th>
                    <th width="20%" style="text-align: center;">Nilai Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hasil_pm as $pm)
                    <tr style="{{ $pm->peringkat == 1 ? 'background-color: #f0fdf4; border-left: 4px solid #10b981;' : '' }}">
                        <td style="text-align: center;">
                            @if($pm->peringkat == 1)
                                <span style="background: #10b981; color: white; padding: 6px 12px; border-radius: 50px; font-weight: 700; font-size: 10px; letter-spacing: 0.5px;">#1 TERBAIK</span>
                            @else
                                <b style="color: #64748b; font-size: 14px;">{{ $pm->peringkat }}</b>
                            @endif
                        </td>
                        <td><b style="color: {{ $pm->peringkat == 1 ? '#047857' : '#1e293b' }};">{{ $pm->nama_jabatan }}</b></td>
                        <td style="text-align: center; color: #475569; font-weight: 500;">{{ number_format($pm->nilai_core_factor, 3) }}</td>
                        <td style="text-align: center; color: #475569; font-weight: 500;">{{ number_format($pm->nilai_secondary_factor, 3) }}</td>
                        <td style="text-align: center;"><b style="font-size: 16px; color: {{ $pm->peringkat == 1 ? '#10b981' : '#1e293b' }};">{{ number_format($pm->nilai_total, 3) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection