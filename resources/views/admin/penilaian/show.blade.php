@extends('layouts.app')
@section('title', 'Detail Penilaian')
@section('page_title', 'Detail Penilaian')
@section('page_subtitle', 'Informasi lengkap hasil penilaian kompetensi pegawai.')
@section('back_url', route('admin.penilaian.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/detail_penilaian.css') }}">
    <style>
        .page-card { background: transparent; box-shadow: none; border: none; padding: 0;}
        .detail-card { 
            background: #fff; padding: 32px; border-radius: 24px; margin-bottom: 24px; 
            border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);
        }
        .pegawai-summary { display: flex; align-items: center; gap: 24px; }
        
        /* Avatar Diganti Royal Blue */
        .avatar { 
            width: 80px; height: 80px; background: #3e54a0; color: white; border-radius: 50%; 
            display: flex; justify-content: center; align-items: center; font-size: 28px; font-weight: 700; 
            box-shadow: 0 8px 20px rgba(62, 84, 160, 0.25);
        }
        
        .status-wrapper { display: flex; gap: 10px; margin-top: 12px; }
        .status { background: #ecfdf5; color: #059669; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700;}
        .nilai-badge { background: #f4f7fe; color: #3e54a0; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700;}
        
        .section-title { font-size: 16px; margin: 0 0 20px 0; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; color: #1e293b; font-weight: 700;}
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .detail-item label { font-size: 11px; color: #94a3b8; display: block; margin-bottom: 6px; text-transform: uppercase; font-weight: 600;}
        .detail-item span { font-size: 14px; color: #1e293b; font-weight: 600; }
        
        .hasil-table { width: 100%; border-collapse: collapse; }
        .hasil-table th { background: transparent; padding: 16px; text-align: left; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .hasil-table td { padding: 16px; border-bottom: 1px dashed #f1f5f9; font-size: 13px; color: #334155; }
        
        .note-box { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 16px; color: #475569; font-size: 13px; font-style: italic; line-height: 1.6;}
        .kompeten { color: #059669; font-weight: 700; background: #ecfdf5; padding: 4px 12px; border-radius: 50px;}
        .belum { color: #dc2626; font-weight: 700; background: #fef2f2; padding: 4px 12px; border-radius: 50px;}
    </style>
@endpush

@section('content')
@php
    $words = explode(" ", $data->pegawai_nama); $inisial = ""; foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); } $inisial = substr($inisial, 0, 2);
    $jumlah_instrumen = count($list_skor);
    $total_skor = 0; foreach ($list_skor as $s) { $total_skor += $s->skor_final; }
    $rata_rata = ($jumlah_instrumen > 0) ? round($total_skor / $jumlah_instrumen, 1) : 0;
    
    $status_akhir = $data->status_kompeten ?: 'Belum Terhitung';
    $is_kompeten = in_array($status_akhir, ['K', 'Kompeten']);
    $badge_class = $is_kompeten ? 'kompeten' : 'belum';
@endphp

<div class="page-card">
    <div class="detail-card">
        <div class="pegawai-summary">
            <div class="avatar">{{ $inisial }}</div>
            <div class="pegawai-info">
                <h3 style="margin: 0 0 5px 0; font-size: 20px;">{{ $data->pegawai_nama }}</h3>
                <p style="margin: 0; color: #64748b;">{{ $data->jabatan ?? '-' }} • {{ $data->unit_kerja ?? 'Museum Geologi' }}</p>
                <div class="status-wrapper">
                    <span class="status">{{ $data->status_penilaian ?? 'Sudah Dinilai' }}</span>
                    <span class="nilai-badge">Nilai Akhir : {{ $data->skor_akhir_360 ?? '0' }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="detail-card">
        <h4 class="section-title">Informasi Kompetensi</h4>
        <div class="detail-grid">
            <div class="detail-item"><label>Unit Kompetensi</label><span>{{ $data->kode_unit ?? '-' }}</span></div>
            <div class="detail-item"><label>Nama Unit</label><span>{{ $data->judul_unit ?? '-' }}</span></div>
            <div class="detail-item"><label>Elemen Kompetensi</label><span>{{ $data->nama_elemen ?? '-' }}</span></div>
            <div class="detail-item"><label>Jumlah Instrumen</label><span>{{ $jumlah_instrumen }} Pertanyaan</span></div>
            <div class="detail-item"><label>Tanggal Penilaian</label><span>{{ $data->waktu_submit ?? 'Belum Submit' }}</span></div>
            <div class="detail-item"><label>Assessor</label><span>Tim Asesor / Admin</span></div>
        </div>
    </div>

    <div class="detail-card">
        <h4 class="section-title">Hasil Penilaian</h4>
        <table class="hasil-table">
            <thead><tr><th width="10%">No</th><th width="75%">Kriteria / Indikator Kompeten (KUK)</th><th width="15%">Nilai Perolehan</th></tr></thead>
            <tbody>
                @forelse ($list_skor as $index => $skor)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td><strong>{{ $skor->detail_aktivitas }}</strong><br><small style="color: #6c757d;">{{ $skor->kriteria_kompetens }}</small></td>
                        <td><b style="font-size: 16px;">{{ $skor->skor_final }}</b></td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">Belum ada item penilaian yang terekam.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="detail-card">
        <h4 class="section-title">Catatan Assessor</h4>
        <div class="note-box">{!! nl2br(e($data->catatan_umum ?? 'Tidak ada catatan khusus dari assessor untuk penilaian ini.')) !!}</div>
    </div>

    <div class="detail-card">
        <h4 class="section-title">Ringkasan Hasil</h4>
        <div class="detail-grid">
            <div class="detail-item"><label>Jumlah Instrumen</label><span>{{ $jumlah_instrumen }}</span></div>
            <div class="detail-item"><label>Nilai Rata-rata</label><span>{{ $rata_rata }}</span></div>
            <div class="detail-item"><label>Nilai Akhir (360°)</label><span>{{ $data->skor_akhir_360 ?? '0' }}</span></div>
            <div class="detail-item"><label>Status Kompetensi</label><span class="{{ $badge_class }}">{{ $is_kompeten ? 'Kompeten' : $status_akhir }}</span></div>
        </div>
    </div>
</div>
@endsection