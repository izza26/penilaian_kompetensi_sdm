@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Pantau rekomendasi penempatan Profile Matching Anda')

@push('styles')
    <!-- INI YANG BIKIN TIDAK POLOSAN LAGI -->
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/hasil_kompetensi.css') }}">
@endpush

@section('content')
<div class="hasil-container">
    <div class="hero-card">
        <div class="hero-left">
            <span class="hero-label"><i class="bi bi-cpu"></i> Profile Matching</span>
            <h1>Rekomendasi</h1>
            <p>Sistem membandingkan skor kompetensi Anda dengan standar kompetensi seluruh bidang di Museum Geologi.</p>
        </div>
        <div class="hero-right">
            <div class="filter-card">
                <span class="filter-title">Pilih Periode Penilaian</span>
                <form action="{{ route('pegawai.hasil.index') }}" method="GET">
                    <select name="periode_id" class="select-periode" onchange="this.form.submit()">
                        @if ($listPeriode->isEmpty())
                            <option value="">-- Belum Ada Periode --</option>
                        @else
                            <option value="">-- Pilih Periode --</option>
                            @foreach ($listPeriode as $periode)
                                <option value="{{ $periode->periode_id }}" {{ ($periode_terpilih == $periode->periode_id) ? 'selected' : '' }}>
                                    {{ $periode->nama_periode }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </form>
            </div>
        </div>
    </div>

    @if ($periode_terpilih)
        @if ($hasilRingkasan)
            <div class="header-title-wrapper">
                <div class="section-title">
                    <span><i class="bi bi-person-check-fill"></i> Hasil Analisis Kesesuaian Jabatan</span>
                    <p>Rekomendasi sistem berdasarkan hasil pengumpulan evidence.</p>
                </div>
            </div>

            <!-- TIGA WIDGET ATAS -->
            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-icon" style="background:#e0f2fe; color:#0284c7;"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="summary-info">
                        <span>Nilai Akhir Skala 100</span>
                        <h2>{{ number_format($hasilRingkasan->nilai_akhir, 2) }}</h2>
                    </div>
                </div>
                
                @php
                    $iconClass = 'bi-patch-check-fill'; $iconColor = 'icon-green'; $textColor = 'text-green';
                    if (!in_array($hasilRingkasan->kategori, ['Kompeten', 'Sangat Kompeten'])) {
                        $iconClass = 'bi-exclamation-triangle-fill'; $iconColor = 'icon-red'; $textColor = 'text-red';
                    }
                @endphp
                <div class="summary-card">
                    <div class="summary-icon {{ $iconColor }}"><i class="bi {{ $iconClass }}"></i></div>
                    <div class="summary-info">
                        <span>Kategori Kelulusan</span>
                        <h2 class="{{ $textColor }}">{{ $hasilRingkasan->kategori }}</h2>
                    </div>
                </div>

                @php
                    // Menentukan Tindakan Lanjut Berdasarkan Kategori Kelulusan
                    $tindakan_lanjut = "";
                    $tindakan_color = "";
                    $tindakan_bg = "";
                    $tindakan_icon = "";

                    if ($hasilRingkasan->kategori == 'Sangat Kompeten') {
                        $tindakan_lanjut = "Siap untuk Promosi Jabatan / Sertifikasi Asesor";
                        $tindakan_color = "#4338ca"; // Indigo
                        $tindakan_bg = "#e0e7ff";
                        $tindakan_icon = "bi-award-fill";
                    } elseif ($hasilRingkasan->kategori == 'Kompeten') {
                        $tindakan_lanjut = "Pertahankan Kinerja / Delegasi Tugas Khusus";
                        $tindakan_color = "#059669"; // Green
                        $tindakan_bg = "#d1fae5";
                        $tindakan_icon = "bi-check-circle-fill";
                    } elseif ($hasilRingkasan->kategori == 'Cukup Kompeten') {
                        $tindakan_lanjut = "Mengikuti Seminar / Lokakarya Bidang Terkait";
                        $tindakan_color = "#d97706"; // Orange
                        $tindakan_bg = "#fef3c7";
                        $tindakan_icon = "bi-person-workspace";
                    } else {
                        // Untuk Belum Kompeten / Kurang Kompeten
                        $tindakan_lanjut = "Wajib Mengikuti Pelatihan Dasar / Evaluasi Rotasi Jabatan";
                        $tindakan_color = "#e11d48"; // Red
                        $tindakan_bg = "#ffe4e6";
                        $tindakan_icon = "bi-shield-exclamation";
                    }
                @endphp

                <div class="summary-card" style="border: 1px solid {{ $tindakan_color }}; background: {{ $tindakan_bg }};">
                    <div class="summary-icon" style="background: rgba(255,255,255,0.7); color: {{ $tindakan_color }};"><i class="bi {{ $tindakan_icon }}"></i></div>
                    <div class="summary-info">
                        <span>Rekomendasi Tindakan Lanjut</span>
                        <h2 style="color: {{ $tindakan_color }}; font-size: 15px; margin-top: 5px; line-height: 1.4;">{{ $tindakan_lanjut }}</h2>
                        <span style="font-size: 10px; font-weight: 700; background: {{ $tindakan_color }}; color: white; padding: 4px 10px; border-radius: 50px; display: inline-block; margin-top: 6px; letter-spacing: 0.5px;">
                            Berdasarkan Gap Kompetensi
                        </span>
                    </div>
                </div>
            </div>

            <!-- GRAFIK KESESUAIAN -->
            <div class="role-bar-container">
                <h3 style="font-size: 16px; color: #1e293b; font-weight: 700; margin: 0 0 24px 0;"><i class="bi bi-bar-chart-steps" style="color:#3e54a0; margin-right: 6px;"></i> Komparasi Tingkat Kesesuaian Semua Bidang</h3>
                @foreach($match_scores as $role => $data)
                    <div class="role-row">
                        <div class="role-name">{{ $role }} {!! $role == $jabatanPegawai ? '<br><span style="color:#3e54a0; font-size:11px;">(Posisi Saat Ini)</span>' : '' !!}</div>
                        <div class="role-bar-bg">
                            <div class="role-bar-fill {{ $role == $best_match_role ? 'highlight-bar' : 'normal-bar' }}" style="width: {{ $data['persen'] }}%;"></div>
                        </div>
                        <div class="role-score">{{ number_format($data['skor_asli'], 3) }}</div>
                    </div>
                @endforeach
            </div>

            <!-- TABEL HISTORIS -->
            <div class="section-title mt-40" id="rincian-penilaian" style="margin-bottom: 20px;">
                <span><i class="bi bi-list-check"></i> Rincian Skor Historis per Aktivitas (Khusus {{ $jabatanPegawai }})</span>
            </div>

            <div class="table-card" style="padding: 0; overflow: hidden;">
                <div class="table-responsive">
                    <table class="custom-table" style="margin: 0; width: 100%;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th width="5%" class="text-center">NO</th>
                                <th width="15%">ID AKTIVITAS</th>
                                <th width="45%">DETAIL AKTIVITAS KOMPETENSI</th>
                                <th width="20%" class="text-center">KETERANGAN SISTEM</th>
                                <th width="15%" class="text-center">NILAI AKTUAL (CF)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hasilDetail as $index => $row)
                                <tr>
                                    <td class="text-center" style="font-weight: 600; color: #64748b; font-size: 13px;">{{ $hasilDetail->firstItem() + $index }}</td>
                                    <td style="font-weight: 700; color: #3e54a0; font-size: 13px;">{{ $row->aktivitas_id }}</td>
                                    <td style="font-size: 13px; color: #334155; line-height: 1.6; font-weight: 500;">{{ $row->detail_aktivitas }}</td>
                                    <td class="text-center"><span class="badge-auto"><i class="bi bi-check-all"></i> Auto-parsed</span></td>
                                    <td class="text-center"><div class="skor-box-cf">{{ (int)$row->skor_final }}</div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding: 20px; background: #fff;">{{ $hasilDetail->appends(['periode_id' => $periode_terpilih])->fragment('rincian-penilaian')->links('pagination::bootstrap-4') }}</div>
            </div>
        @else
            <!-- STATE JIKA BELUM ADA DATA SKORING SAMA SEKALI -->
            <div class="empty-state-card">
                <i class="bi bi-hourglass-split"></i>
                <h3>Sedang Dalam Proses Evaluasi</h3>
                <p>Sistem belum menerbitkan hasil Profile Matching untuk periode ini.<br>Silakan selesaikan pengunggahan dokumen Anda jika belum selesai.</p>
            </div>
        @endif
    @else
        <div class="empty-state-card">
            <i class="bi bi-cpu"></i>
            <h3>Pilih Periode Terlebih Dahulu</h3>
            <p>Silakan gunakan menu dropdown di atas untuk memilih periode penilaian dan melihat hasil rekomendasi sistem.</p>
        </div>
    @endif
</div>
@endsection