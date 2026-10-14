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

    $is_kompeten = in_array($data->status_kompeten, ['K', 'Kompeten', 'Sangat Kompeten']);
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
                <span class="badge {{ $badge_class }}">{{ $data->status_kompeten }}</span>
            </div>
        </div>
        <div class="pegawai-right"><small>Nilai Akhir</small><h2>{{ number_format($data->skor_akhir_360 ?? 0, 2) }}</h2></div>
    </div>

    <div class="content-card">
        <h3>Informasi Kompetensi</h3>
        <div class="info-grid">
            <div><label>Unit Kompetensi</label><span>{{ $data->kode_unit ?? '-' }}</span></div>
            <div><label>Elemen Kompetensi</label><span>{{ $data->nama_elemen ?? '-' }}</span></div>
            <div><label>Aktivitas</label><span>Seluruh Aktivitas Terkait</span></div>
            <div><label>Tanggal Penilaian</label><span>{{ $data->waktu_submit ? date('d M Y, H:i', strtotime($data->waktu_submit)) : 'Belum ada data' }}</span></div>
            <div><label>Assessor</label><span>Tim Asesor / Pimpinan</span></div>
            <div><label>Jumlah Instrumen</label><span>{{ $jml_instrumen }} KUK</span></div>
        </div>
    </div>

    <div class="content-card">
        <h3>Ringkasan Nilai</h3>
        <div class="summary-grid">
            <div class="summary-item"><label>Nilai Rata-rata</label><h4>{{ number_format($rata_rata, 1) }}</h4></div>
            <div class="summary-item"><label>Nilai Akhir</label><h4>{{ number_format($data->skor_akhir_360 ?? 0, 2) }}</h4></div>
            <div class="summary-item"><label>Status</label><span class="badge {{ $badge_class }}">{{ $data->status_kompeten }}</span></div>
        </div>
    </div>

    <!-- DIPINDAHKAN KE SINI: CATATAN SISTEM & ASESOR -->
    <div class="content-card">
        <h3>Catatan & Evaluasi</h3>

        @if(!$is_kompeten && $data->alasan_sistem != '-')
            <!-- Catatan Otomatis dari Sistem (Ditampilkan jika belum kompeten) -->
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; padding: 15px 20px; border-radius: 8px; margin-bottom: 15px;">
                <h5 style="margin: 0 0 5px 0; color: #b91c1c; font-size: 13px;"><i class="bi bi-robot"></i> Catatan Sistem</h5>
                <p style="margin: 0; color: #7f1d1d; font-size: 13px;">{{ $data->alasan_sistem }}</p>
            </div>
        @endif

        <!-- Catatan Manual dari Asesor -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; padding: 15px 20px; border-radius: 8px;">
            <h5 style="margin: 0 0 5px 0; color: #1e293b; font-size: 13px;"><i class="bi bi-person-badge"></i> Catatan Evaluasi Asesor</h5>
            <p style="margin: 0; color: #475569; font-size: 13px;">
                {!! $data->catatan_umum ? nl2br(e($data->catatan_umum)) : '<i>Asesor tidak memberikan catatan khusus.</i>' !!}
            </p>
        </div>
    </div>

    <!-- DETAIL PEROLEHAN NILAI DI BAWAH CATATAN -->
    <div class="content-card">
        <h3>Detail Perolehan Nilai</h3>
        <div class="score-list">
            @forelse ($list_nilai as $n)
                @php $persen = ($n->skor_final / 5) * 100; @endphp
                <div class="score-item">
                    <div class="score-header"><span style="font-weight: 500;">{{ $n->detail_aktivitas }}</span><strong>{{ $n->skor_final }} / 5</strong></div>
                    <div class="progress"><div class="progress-fill" style="width: {{ $persen }}%; background-color: #3e54a0;"></div></div>
                </div>
            @empty
                <p style="text-align: center; color: #888; padding: 20px;">Belum ada detail nilai yang diinputkan oleh assessor.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
