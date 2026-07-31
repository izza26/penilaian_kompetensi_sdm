@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Pantau rekomendasi penempatan Profile Matching Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/hasil_kompetensi.css') }}">
    <style>
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .btn-cetak-excel:hover { background: #059669; transform: translateY(-2px); color: white;}
        .header-title-wrapper { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px;}
        .header-title-wrapper .section-title { margin-bottom: 0; border-bottom: none; padding-bottom: 0;}
        .badge-auto { background: #ecfdf5; color: #059669; font-size: 11px; padding: 4px 10px; border-radius: 20px; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px; }
        .skor-box-cf { background: #eff6ff; color: #3b82f6; font-size: 14px; font-weight: 800; width: 32px; height: 32px; display: inline-flex; justify-content: center; align-items: center; border-radius: 8px; border: 1px solid #bfdbfe; margin: 0 auto;}
        
        /* Style untuk Rekomendasi Bar */
        .summary-grid { grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;}
        .role-bar-container { background: white; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 30px; }
        .role-row { display: flex; align-items: center; gap: 15px; margin-bottom: 12px; }
        .role-name { width: 150px; font-size: 12px; font-weight: 600; color: #475569; }
        .role-bar-bg { flex: 1; background: #f1f5f9; height: 10px; border-radius: 10px; overflow: hidden; }
        .role-bar-fill { height: 100%; border-radius: 10px; }
        .role-score { width: 45px; text-align: right; font-size: 12px; font-weight: 700; color: #0f172a; }
        .highlight-bar { background: #3b82f6; }
        .normal-bar { background: #cbd5e1; }
    </style>
@endpush

@section('content')
<div class="hasil-container">
    <div class="hero-card">
        <div class="hero-left">
            <span class="hero-label"><i class="bi bi-cpu"></i> Profile Matching</span>
            <h1>Rekomendasi Penempatan Posisi</h1>
            <p>Sistem membandingkan skor kompetensi Anda dengan standar kompetensi seluruh bidang di Museum Geologi.</p>
        </div>
        <div class="hero-right">
            <div class="filter-card">
                <span class="filter-title">Periode Penilaian</span>
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
                    <p style="margin-top: 5px;">Rekomendasi sistem berdasarkan hasil pengumpulan evidence.</p>
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

                <div class="summary-card" style="border: 2px solid {{ $is_match ? '#10b981' : '#f59e0b' }};">
                    <div class="summary-icon" style="background: {{ $is_match ? '#d1fae5' : '#fef3c7' }}; color: {{ $is_match ? '#059669' : '#d97706' }};"><i class="bi bi-briefcase-fill"></i></div>
                    <div class="summary-info">
                        <span>Rekomendasi Posisi (Sistem)</span>
                        <h2 style="color: {{ $is_match ? '#059669' : '#d97706' }}; font-size: 18px; margin-top: 5px;">{{ $best_match_role }}</h2>
                        <span style="font-size: 10px; font-weight: bold; background: {{ $is_match ? '#059669' : '#d97706' }}; color: white; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-top: 4px;">
                            {{ $is_match ? 'MATCH (Tepat Sasaran)' : 'MISMATCH (Perlu Rotasi)' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- GRAFIK KESESUAIAN -->
            <div class="role-bar-container">
                <h3 style="font-size: 14px; color: #0f172a; margin-bottom: 15px;"><i class="bi bi-bar-chart-steps"></i> Komparasi Tingkat Kesesuaian Semua Bidang</h3>
                @foreach($match_scores as $role => $score)
                    <div class="role-row">
                        <div class="role-name">{{ $role }} {!! $role == $jabatanPegawai ? '<span style="color:#3b82f6;">(Saat Ini)</span>' : '' !!}</div>
                        <div class="role-bar-bg">
                            <div class="role-bar-fill {{ $role == $best_match_role ? 'highlight-bar' : 'normal-bar' }}" style="width: {{ $score }}%;"></div>
                        </div>
                        <div class="role-score">{{ number_format($score, 2) }}</div>
                    </div>
                @endforeach
            </div>

            <!-- TABEL HISTORIS -->
            <div class="section-title mt-40" id="rincian-penilaian">
                <span><i class="bi bi-list-check"></i> Rincian Skor Historis per Aktivitas (Khusus {{ $jabatanPegawai }})</span>
            </div>

            <div class="table-card" style="padding: 0;">
                <div class="table-responsive">
                    <table class="custom-table" style="margin: 0; width: 100%;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th width="5%" class="text-center" style="font-size: 11px; color: #475569;">NO</th>
                                <th width="15%" style="font-size: 11px; color: #475569;">ID AKTIVITAS</th>
                                <th width="45%" style="font-size: 11px; color: #475569;">DETAIL AKTIVITAS KOMPETENSI</th>
                                <th width="20%" class="text-center" style="font-size: 11px; color: #475569;">KETERANGAN SISTEM</th>
                                <th width="15%" class="text-center" style="font-size: 11px; color: #475569;">NILAI AKTUAL (CF)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hasilDetail as $index => $row)
                                <tr>
                                    <td class="text-center" style="font-weight: 600; color: #64748b; font-size: 13px;">{{ $hasilDetail->firstItem() + $index }}</td>
                                    <td style="font-weight: 700; color: #1e293b; font-size: 13px;">{{ $row->aktivitas_id }}</td>
                                    <td style="font-size: 13px; color: #334155; line-height: 1.5;">{{ $row->detail_aktivitas }}</td>
                                    <td class="text-center"><span class="badge-auto"><i class="bi bi-check-all"></i> Auto-parsed</span></td>
                                    <td class="text-center"><div class="skor-box-cf">{{ (int)$row->skor_final }}</div></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="padding: 20px;">{{ $hasilDetail->appends(['periode_id' => $periode_terpilih])->fragment('rincian-penilaian')->links('pagination::bootstrap-4') }}</div>
            </div>
        @else
            <!-- STATE JIKA BELUM ADA DATA SKORING SAMA SEKALI -->
            <div class="empty-state-card" style="margin-top: 20px; background: white; border: 1px solid #e2e8f0;">
                <img src="https://cdn-icons-png.flaticon.com/512/7486/7486747.png" style="width: 100px; opacity: 0.5; margin-bottom: 15px;">
                <h3 style="color: #0f172a;">Sedang Dalam Proses Evaluasi</h3>
                <p style="color: #64748b;">Sistem belum menerbitkan hasil Profile Matching untuk periode ini.<br>Silakan selesaikan pengunggahan dokumen Anda.</p>
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