@extends('layouts.app')
@section('title', 'Detail Penilaian')
@section('page_title', 'Detail Penilaian')
@section('page_subtitle', 'Informasi lengkap hasil penilaian kompetensi pegawai.')
@section('back_url', route('admin.penilaian.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/detail_penilaian.css') }}">
    <style>
        .page-card { background: transparent; box-shadow: none; border: none; padding: 0;}
        .detail-card { background: #fff; padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02);}
        .pegawai-summary { display: flex; align-items: center; gap: 20px; }
        .avatar { width: 70px; height: 70px; background: #b56c35; color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; font-weight: bold; }
        .status-wrapper { display: flex; gap: 10px; margin-top: 10px; }
        .status { background: #ecfdf5; color: #059669; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #a7f3d0;}
        .nilai-badge { background: #eff6ff; color: #3b82f6; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; border: 1px solid #bfdbfe;}
        .section-title { font-size: 16px; margin: 0 0 15px 0; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; color: #0f172a;}
        .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .detail-item label { font-size: 12px; color: #64748b; display: block; margin-bottom: 4px; }
        .detail-item span { font-size: 14px; color: #0f172a; font-weight: 600; }
        .hasil-table { width: 100%; border-collapse: collapse; }
        .hasil-table th { background: #f8fafc; padding: 12px; text-align: left; font-size: 12px; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .hasil-table td { padding: 15px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
        .note-box { background: #fffbeb; border: 1px dashed #fcd34d; padding: 15px; border-radius: 10px; color: #92400e; font-size: 13px; font-style: italic; }
        .kompeten { color: #059669; font-weight: 700;}
        .belum { color: #dc2626; font-weight: 700;}
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