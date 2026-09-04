@extends('layouts.app')
@section('title', 'Detail Hasil Kompetensi')
@section('page_title', 'Detail Hasil Kompetensi')
@section('page_subtitle', 'Informasi lengkap hasil kompetensi pegawai')
@section('back_url', route('admin.hasil_kompetensi.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/detail_hasil.css') }}">
@endpush

@section('content')
@php
    $jml_instrumen = count($list_nilai);
    $total_skor = 0; foreach($list_nilai as $n) $total_skor += $n->skor_final;
    $rata_rata = ($jml_instrumen > 0) ? round($total_skor / $jml_instrumen, 1) : 0;
    
    $is_kompeten = in_array($data->status_kompeten, ['K', 'Kompeten']);
    $status_text = $is_kompeten ? 'Kompeten' : 'Belum Kompeten';
    $badge_class = $is_kompeten ? 'kompeten' : 'belum';
    
    $words = explode(" ", $data->pegawai_nama); $inisial = ""; foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); } $inisial = substr($inisial, 0, 2);
@endphp
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    <div class="pegawai-card">
        <div class="pegawai-left">
            <div class="avatar">{{ $inisial }}</div>
            <div>
                <h3>{{ $data->pegawai_nama }}</h3>
                <p>{{ $data->jabatan }} • {{ $data->unit_kerja ?? 'Museum Geologi' }}</p>
                <span class="badge {{ $badge_class }}">{{ $status_text }}</span>
            </div>
        </div>
        <div class="pegawai-right"><small>Nilai Akhir</small><h2>{{ $data->skor_akhir_360 ?? '0' }}</h2></div>
    </div>

    <div class="content-card">
        <h3>Informasi Kompetensi</h3>
        <div class="info-grid">
            <div><label>Unit Kompetensi</label><span>{{ $data->kode_unit ?? '-' }}</span></div>
            <div><label>Elemen Kompetensi</label><span>{{ $data->nama_elemen ?? '-' }}</span></div>
            <div><label>Aktivitas</label><span>Seluruh Aktivitas Terkait</span></div>
            <div><label>Tanggal Penilaian</label><span>{{ $data->waktu_submit ? date('d M Y, H:i', strtotime($data->waktu_submit)) : 'Belum ada data' }}</span></div>
            <div><label>Assessor</label><span>Tim Asesor / Admin</span></div>
            <div><label>Jumlah Instrumen</label><span>{{ $jml_instrumen }} Pertanyaan</span></div>
        </div>
    </div>

    <div class="content-card">
        <h3>Ringkasan Nilai</h3>
        <div class="summary-grid">
            <div class="summary-item"><label>Nilai Rata-rata</label><h4>{{ $rata_rata }}</h4></div>
            <div class="summary-item"><label>Nilai Akhir</label><h4>{{ $data->skor_akhir_360 ?? '0' }}</h4></div>
            <div class="summary-item"><label>Status</label><span class="badge {{ $badge_class }}">{{ $status_text }}</span></div>
        </div>
    </div>

    <div class="content-card">
        <h3>Detail Perolehan Nilai</h3>
        <div class="score-list">
            @forelse ($list_nilai as $n)
                @php $persen = ($n->skor_final / 5) * 100; @endphp
                <div class="score-item">
                    <div class="score-header"><span>{{ $n->detail_aktivitas }}</span><strong>{{ $n->skor_final }} / 5</strong></div>
                    <div class="progress"><div class="progress-fill" style="width: {{ $persen }}%;"></div></div>
                </div>
            @empty
                <p style="text-align: center; color: #888; padding: 20px;">Belum ada detail nilai yang diinputkan oleh assessor.</p>
            @endforelse
        </div>
    </div>

    <div class="content-card">
        <h3>Catatan Assessor</h3>
        <div class="note-box">{!! nl2br(e($data->catatan_umum ?? 'Tidak ada catatan khusus dari assessor.')) !!}</div>
    </div>
</div>
@endsection