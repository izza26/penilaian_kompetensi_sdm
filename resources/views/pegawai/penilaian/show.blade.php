@extends('layouts.app')
@section('title', 'Detail Analisis Sistem')
@section('page_title', 'Detail Analisis Evidence')
@section('page_subtitle', 'Rincian evidence dan hasil evaluasi sistem (Profile Matching)')
@section('back_url', route('pegawai.penilaian.list'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/penilaian_detail.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
@endpush

@section('content')
@php
    $skor_evidence = $data->skor_tampil ? (int)$data->skor_tampil : null;
    $teks_skor = [1 => "Sangat Kurang", 2 => "Kurang", 3 => "Cukup", 4 => "Kompeten", 5 => "Sangat Kompeten"];
    $kategori = $skor_evidence ? ($teks_skor[$skor_evidence] ?? "Dinilai") : '-';
    $catatan_penilai = !empty($data->rekomendasi) ? $data->rekomendasi : "Telah diverifikasi dan disahkan oleh Pimpinan.";
    
    $bg_color = '#f1f5f9'; $text_color = '#64748b';
    if ($skor_evidence >= 4) { $bg_color = '#e9f8ee'; $text_color = '#16a34a'; } 
    elseif ($skor_evidence == 3) { $bg_color = '#fffbeb'; $text_color = '#d97706'; } 
    elseif ($skor_evidence > 0) { $bg_color = '#fee2e2'; $text_color = '#dc2626'; }
@endphp

<div class="row g-4">
    <!-- KIRI (FILE EVIDENCE) -->
    <div class="col-lg-7">
        <div class="detail-card">
            <div class="detail-title"><i class="bi bi-info-circle" style="color: #0284c7;"></i> Informasi Kompetensi</div>
            <div class="info-group"><div class="info-label">Unit Kompetensi</div><div class="info-value"><span class="badge" style="background: #e2e8f0; color: #475569; margin-bottom: 5px;">{{ $data->kode_unit }}</span><br>{{ $data->judul_unit }}</div></div>
            <div class="info-group mb-0"><div class="info-label">Terkait Aktivitas Penilaian</div><div class="info-value" style="color: #0284c7; font-weight: 600;">{{ $data->detail_aktivitas }}</div></div>
        </div>

        <div class="detail-card">
            <div class="detail-title"><i class="bi bi-paperclip" style="color: #0284c7;"></i> File yang Diupload</div>
            <div class="file-preview-box">
                <div class="file-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                <h4 style="margin: 0 0 5px 0; font-size: 15px; color: #1e293b;">Dokumen Evidence Terlampir</h4>
                <p style="margin: 0 0 15px 0; font-size: 12px; color: #64748b;">Terakhir diunggah pada: {{ date('d M Y, H:i', strtotime($data->tgl_upload)) }}</p>
                <a href="{{ asset('uploads/evidence/' . $data->file_path) }}" target="_blank" class="btn-download" style="background: #f1f5f9; color: #3b82f6; border: 1px solid #cbd5e1;"><i class="bi bi-cloud-arrow-down-fill"></i> Pratinjau Dokumen</a>
            </div>
        </div>
    </div>

    <!-- KANAN (HASIL SKOR) -->
    <div class="col-lg-5">
        <div class="detail-card" style="height: 100%;">
            <div class="detail-title"><i class="bi bi-cpu-fill" style="color: #0284c7;"></i> Hasil Analisis Sistem (CF)</div>
            @if (empty($skor_evidence))
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="bi bi-hourglass-split" style="font-size: 40px; color: #cbd5e1;"></i>
                    <h4 style="margin: 15px 0 5px 0; font-size: 15px; color: #475569;">Sedang Dalam Proses Parsing</h4>
                    <p style="font-size: 13px; color: #64748b;">Sistem belum selesai mengalkulasi nilai CF dokumen ini.</p>
                </div>
            @else
                <div class="score-box">
                    <div class="score-circle" style="background: #f0f9ff; color: #0284c7; border-color: #bae6fd;"><div class="score-number" style="font-size: 32px;">{{ $skor_evidence }} <span style="font-size: 16px; color:#94a3b8;">/ 5</span></div></div>
                    <span class="badge" style="background: {{ $bg_color }}; color: {{ $text_color }}; font-size: 13px; margin-bottom: 5px;">{{ $kategori }}</span>
                    <div style="margin-top: 10px;"><span style="font-size: 11px; background: #ecfdf5; color: #059669; padding: 4px 8px; border-radius: 6px; border: 1px solid #a7f3d0;"><i class="bi bi-check-all"></i> Auto-parsed successfully</span></div>
                </div>
                <div class="info-group mt-4"><div class="info-label"><i class="bi bi-chat-quote-fill me-1"></i> Pengesahan Pimpinan</div><div class="feedback-box">"{{ $catatan_penilai }}"</div></div>
            @endif
        </div>
    </div>
</div>
@endsection