@extends('layouts.app')
@section('title', 'Detail Analisis Sistem')
@section('page_title', 'Detail Analisis Evidence')
@section('page_subtitle', 'Rincian evidence dan hasil evaluasi sistem (Profile Matching)')
@section('back_url', route('pegawai.penilaian.list'))

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap-grid.min.css" rel="stylesheet">
    <style>
        .detail-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); padding: 32px; margin-bottom: 24px; }
        .detail-title { font-size: 16px; font-weight: 700; color: #1e293b; margin-bottom: 24px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .info-group { margin-bottom: 20px; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
        .info-value { font-size: 13px; color: #334155; font-weight: 500; line-height: 1.6; }
        
        .file-preview-box { background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 40px 20px; text-align: center; transition: 0.3s; }
        .file-preview-box:hover { border-color: #3e54a0; background: #f4f7fe; }
        .file-icon { font-size: 56px; color: #3e54a0; margin-bottom: 15px; }
        .btn-download { background: #3e54a0; color: #fff; padding: 0 24px; height: 44px; border-radius: 50px; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; transition: 0.2s; }
        .btn-download:hover { background: #2b3a70; color: #fff; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
        
        .score-box { text-align: center; padding: 10px 0 20px 0; }
        .score-circle { width: 140px; height: 140px; border: 6px solid #3e54a0; border-radius: 50%; margin: 0 auto 20px; display: flex; justify-content: center; align-items: center; background: #fff; box-shadow: 0 10px 20px rgba(62, 84, 160, 0.1);}
        .score-number { font-size: 40px; font-weight: 800; color: #3e54a0; line-height: 1; display: flex; align-items: baseline; gap: 4px;}
        
        .feedback-box { background: #f4f7fe; border-left: 4px solid #3e54a0; padding: 16px 20px; border-radius: 0 12px 12px 0; font-size: 13px; color: #1e293b; line-height: 1.6; font-style: italic; font-weight: 500;}
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}
    </style>
@endpush

@section('content')
@php
    $skor_evidence = $data->skor_tampil ? (int)$data->skor_tampil : null;
    $teks_skor = [1 => "Sangat Kurang", 2 => "Kurang", 3 => "Cukup", 4 => "Kompeten", 5 => "Sangat Kompeten"];
    $kategori = $skor_evidence ? ($teks_skor[$skor_evidence] ?? "Dinilai") : '-';
    $catatan_penilai = !empty($data->rekomendasi) ? $data->rekomendasi : "Telah diverifikasi dan disahkan oleh Pimpinan.";
    
    $bg_color = '#f1f5f9'; $text_color = '#64748b';
    if ($skor_evidence >= 4) { $bg_color = '#ecfdf5'; $text_color = '#059669'; } 
    elseif ($skor_evidence == 3) { $bg_color = '#fffbeb'; $text_color = '#d97706'; } 
    elseif ($skor_evidence > 0) { $bg_color = '#fff1f2'; $text_color = '#e11d48'; }
@endphp

<div class="row g-4">
    <!-- KIRI (FILE EVIDENCE) -->
    <div class="col-lg-7">
        <div class="detail-card">
            <div class="detail-title"><i class="bi bi-info-circle-fill"></i> Informasi Kompetensi</div>
            <div class="info-group"><div class="info-label">Unit Kompetensi</div><div class="info-value"><span class="badge" style="background: #f4f7fe; color: #3e54a0; margin-bottom: 8px;">{{ $data->kode_unit }}</span><br>{{ $data->judul_unit }}</div></div>
            <div class="info-group mb-0"><div class="info-label">Terkait Aktivitas Penilaian</div><div class="info-value" style="color: #1e293b; font-weight: 700;">{{ $data->detail_aktivitas }}</div></div>
        </div>

        <div class="detail-card">
            <div class="detail-title"><i class="bi bi-paperclip"></i> File yang Diupload</div>
            <div class="file-preview-box">
                <div class="file-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                <h4 style="margin: 0 0 6px 0; font-size: 16px; color: #1e293b; font-weight: 700;">Dokumen Evidence Terlampir</h4>
                <p style="margin: 0 0 20px 0; font-size: 12px; color: #64748b; font-weight: 500;">Terakhir diunggah pada: {{ date('d M Y, H:i', strtotime($data->tgl_upload)) }}</p>
                <a href="{{ asset('uploads/evidence/' . $data->file_path) }}" target="_blank" class="btn-download"><i class="bi bi-cloud-arrow-down-fill"></i> Pratinjau Dokumen</a>
            </div>
        </div>
    </div>

    <!-- KANAN (HASIL SKOR) -->
    <div class="col-lg-5">
        <div class="detail-card" style="height: 100%;">
            <div class="detail-title"><i class="bi bi-cpu-fill"></i> Hasil Analisis Sistem (CF)</div>
            @if (empty($skor_evidence))
                <div style="text-align: center; padding: 40px 20px;">
                    <i class="bi bi-hourglass-split" style="font-size: 48px; color: #cbd5e1; display: block; margin-bottom: 16px;"></i>
                    <h4 style="margin: 0 0 8px 0; font-size: 16px; color: #1e293b; font-weight: 700;">Sedang Dalam Proses Parsing</h4>
                    <p style="font-size: 13px; color: #64748b; line-height: 1.5;">Sistem belum selesai mengalkulasi nilai CF dokumen ini.</p>
                </div>
            @else
                <div class="score-box">
                    <div class="score-circle">
                        <div class="score-number">{{ $skor_evidence }} <span style="font-size: 18px; color:#94a3b8;">/ 5</span></div>
                    </div>
                    <span class="badge" style="background: {{ $bg_color }}; color: {{ $text_color }}; font-size: 12px; margin-bottom: 8px;">{{ $kategori }}</span>
                    <div style="margin-top: 12px;"><span style="font-size: 11px; background: #ecfdf5; color: #059669; padding: 6px 14px; border-radius: 50px; font-weight: 700;"><i class="bi bi-check-all"></i> Auto-parsed successfully</span></div>
                </div>
                <div class="info-group mt-5"><div class="info-label"><i class="bi bi-chat-quote-fill me-1"></i> Pengesahan Pimpinan</div><div class="feedback-box">"{{ $catatan_penilai }}"</div></div>
            @endif
        </div>
    </div>
</div>
@endsection