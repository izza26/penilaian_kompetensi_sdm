@extends('layouts.app')
@section('title', 'Rincian Penilaian Pegawai')
@section('page_title', 'Skor Kompetensi Akhir')
@section('page_subtitle', 'Rincian gabungan nilai Hardskill dan Softskill.')
@section('back_url', route('pimpinan.hasil_kompetensi.index'))

@push('styles')
    <style>
        .summary-card { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); padding: 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: stretch; gap: 24px; flex-wrap: wrap;}
        .summary-left { flex: 1; min-width: 280px; display: flex; flex-direction: column; justify-content: center;}
        .summary-left h2 { margin: 0 0 8px 0; font-size: 24px; color: #1e293b; font-weight: 800;}
        .summary-left p { margin: 0; color: #64748b; font-size: 14px; }
        
        .summary-right { display: flex; gap: 16px; flex-wrap: wrap; flex: 2; justify-content: flex-end;}
        .box-skor { background: #f8fafc; padding: 20px 24px; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; display: flex; flex-direction: column; justify-content: center; min-width: 140px;}
        .box-skor h1 { margin: 10px 0; font-size: 38px; color: #3e54a0; font-weight: 800; line-height: 1;}
        
        .box-kalkulasi { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 16px; min-width: 220px; display: flex; flex-direction: column; justify-content: center;}
        .box-match { padding: 20px; border-radius: 16px; text-align: center; min-width: 180px; max-width: 220px; display: flex; flex-direction: column; justify-content: center;}
        
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase;}
        .badge-st { background: #ecfdf5; color: #059669; } 
        .badge-t { background: #e0e7ff; color: #4338ca; } 
        .badge-sd { background: #fef3c7; color: #d97706; } 
        .badge-r { background: #ffedd5; color: #ea580c; } 
        .badge-sr { background: #fff1f2; color: #e11d48; } 

        .grid-tables { display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 24px; margin-bottom: 24px; align-items: start;}
        .table-card { background: #fff; padding: 24px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .table-card h3 { margin: 0 0 20px 0; font-size: 16px; color: #1e293b; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; font-weight: 700;}
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { color: #64748b; padding: 12px 16px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 12px 16px; border-bottom: 1px dashed #f1f5f9; font-size: 12px; color: #334155; }
        
        .action-group { display: flex; justify-content: center; gap: 8px; }
        .btn-icon { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; text-decoration: none; border: none; cursor: pointer; transition: 0.2s; }
        .btn-detail { background: #f4f7fe; color: #3e54a0; }
        .btn-detail:hover { background: #3e54a0; color: white; transform: translateY(-2px); }
        .btn-hapus { background: #fff1f2; color: #e11d48; }
        .btn-hapus:hover { background: #e11d48; color: white; transform: translateY(-2px); }

        @media(max-width: 992px) { .grid-tables { grid-template-columns: 1fr; } .summary-right { justify-content: flex-start; } }
    </style>
@endpush

@section('content')
@php
    $badge_class = 'badge-sr';
    
    // Logika Rekomendasi General (Pelatihan / Coaching)
    if ($kategori_akhir == 'Sangat Tinggi') {
        $badge_class = 'badge-st';
        $tindakan_lanjut = "Rekomendasi Sertifikasi / Mentor Internal";
        $tindakan_color = "#059669"; $tindakan_bg = "#ecfdf5"; $tindakan_border = "#a7f3d0";
    } elseif ($kategori_akhir == 'Tinggi') {
        $badge_class = 'badge-t';
        $tindakan_lanjut = "Delegasi Proyek Khusus & Tambah Tanggung Jawab";
        $tindakan_color = "#4338ca"; $tindakan_bg = "#e0e7ff"; $tindakan_border = "#c7d2fe";
    } elseif ($kategori_akhir == 'Sedang') {
        $badge_class = 'badge-sd';
        $tindakan_lanjut = "Sesi Coaching & Peer-Learning (Tutor Sebaya)";
        $tindakan_color = "#d97706"; $tindakan_bg = "#fef3c7"; $tindakan_border = "#fde68a";
    } elseif ($kategori_akhir == 'Rendah') {
        $badge_class = 'badge-r';
        $tindakan_lanjut = "Disarankan Mengikuti Seminar / Workshop";
        $tindakan_color = "#ea580c"; $tindakan_bg = "#ffedd5"; $tindakan_border = "#fed7aa";
    } else {
        $tindakan_lanjut = "Wajib Mengikuti Pelatihan / Diklat Intensif";
        $tindakan_color = "#e11d48"; $tindakan_bg = "#fff1f2"; $tindakan_border = "#fecdd3";
    }
@endphp

<div class="summary-card">
    <div class="summary-left">
        <h2><i class="bi bi-person-badge" style="color: #3e54a0; margin-right:8px;"></i> {{ $pegawai->pegawai_nama }}</h2>
        <p>Jabatan Saat Ini: <b style="color: #1e293b;">{{ $pegawai->jabatan }}</b></p>
    </div>
    
    <div class="summary-right">
        <!-- Kotak Kalkulasi Raw (Seperti Excel) -->
        <div class="box-kalkulasi">
            <span style="display: block; font-size: 10px; font-weight: 800; color: #475569; margin-bottom: 12px; letter-spacing: 0.5px;"><i class="bi bi-calculator"></i> SKOR MENTAH JABATAN</span>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                <span style="color: #64748b;">Total Kompetensi</span><b style="color: #1e293b;">{{ $jumlah_kompetensi }} Unit</b>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                <span style="color: #64748b;">Total KUK Dinilai</span><b style="color: #1e293b;">{{ $total_kuk_jabatan }} KUK</b>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                <span style="color: #64748b;">Batas Minimum (x1)</span><b style="color: #1e293b;">{{ $min_skor_jabatan }}</b>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                <span style="color: #64748b;">Batas Maksimum (x5)</span><b style="color: #1e293b;">{{ $max_skor_jabatan }}</b>
            </div>
            <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0;">
            <div style="display: flex; justify-content: space-between; font-size: 13px;">
                <span style="color: #3e54a0; font-weight: 700;">Skor Aktual</span><b style="color: #3e54a0; font-size: 16px; font-weight: 800;">{{ $total_skor_raw_jabatan }}</b>
            </div>
        </div>

        <!-- Kotak Skor 100 -->
        <div class="box-skor">
            <span style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Skor Skala (100)</span>
            <h1>{{ number_format($rata_nilai, 2) }}</h1>
            <span class="badge {{ $badge_class }}">{{ $kategori_akhir }}</span>
        </div>

        <!-- Kotak Rekomendasi General -->
        <div class="box-match" style="background: {{ $tindakan_bg }}; border: 1px solid {{ $tindakan_border }};">
            <span style="display: block; font-size: 10px; font-weight: 800; color: {{ $tindakan_color }}; margin-bottom: 8px; letter-spacing: 0.5px; opacity: 0.8;"><i class="bi bi-lightbulb-fill"></i> REKOMENDASI PENGEMBANGAN</span>
            <h3 style="color: {{ $tindakan_color }}; font-size: 13px; margin: 0; line-height: 1.4;">{{ $tindakan_lanjut }}</h3>
        </div>
    </div>
</div>

<div class="grid-tables">
    <!-- TABEL 1: HARDSKILL (SKKNI) -->
    <div class="table-card">
        <h3><i class="bi bi-tools" style="color: #3e54a0; margin-right: 8px;"></i> Break-down Hardskill (Per UK)</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="15%">Kode Unit</th>
                    <th width="40%">Judul Unit</th>
                    <th width="15%" style="text-align: center;">Jumlah KUK</th>
                    <th width="20%" style="text-align: center;">Skor Raw (Min-Max)</th>
                    <th width="10%" style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list_uk as $uk)
                    <tr>
                        <td><b style="color: #3e54a0;">{{ $uk->kode_unit }}</b></td>
                        <td>{{ $uk->judul_unit }}</td>
                        <td style="text-align: center;"><b style="color: #1e293b;">{{ $uk->jumlah_kuk }}</b></td>
                        <td style="text-align: center;">
                            <b style="font-size: 14px; color:#0f172a;">{{ $uk->skor_aktual_raw }}</b>
                            <span style="display:block; font-size: 10px; color:#64748b; margin-top:2px;">(Range: {{ $uk->min_skor }} - {{ $uk->max_skor }})</span>
                        </td>
                        <td style="text-align: center;">
                            <div class="action-group">
                                <!-- Tombol Detail (Mata) -->
                                <a href="{{ route('pimpinan.tim_saya.beri_nilai', [$pegawai->pegawai_id, $uk->kode_unit]) }}" class="btn-icon btn-detail" title="Lihat Rincian KUK">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                                
                                <!-- Tombol Hapus (Tempat Sampah) -->
                                <form action="{{ route('pimpinan.hasil_kompetensi.destroy', $uk->penilaian_id) }}" method="POST" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn-icon btn-hapus" title="Hapus Penilaian" onclick="return confirm('Yakin ingin menghapus penilaian UK ini?')">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align:center;">Belum ada hardskill dinilai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- TABEL 2: SOFTSKILL -->
    <div class="table-card">
        <h3><i class="bi bi-person-hearts" style="color: #d97706; margin-right: 8px;"></i> Break-down Softskill</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="50%">Kriteria Softskill</th>
                    <th width="25%" style="text-align: center;">Nilai Aktual</th>
                    <th width="25%" style="text-align: center;">Bobot PM</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list_softskill as $sf)
                    <tr>
                        <td><b style="color: #1e293b;">{{ $sf->nama_softskill }}</b></td>
                        <td style="text-align: center;"><span style="background:#fef3c7; color:#d97706; padding:4px 12px; border-radius:50px; font-weight:bold; font-size:11px;">{{ $sf->nilai_aktual }}</span></td>
                        <td style="text-align: center; color: #475569; font-weight:bold;">{{ number_format($sf->bobot_gap, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" style="text-align:center;">Belum ada softskill dinilai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- TABEL 3: PROFILE MATCHING -->
<div class="table-card" style="margin-bottom:40px;">
    <h3><i class="bi bi-bar-chart-fill" style="color: #10b981; margin-right: 8px;"></i> Hasil & Ranking Profile Matching</h3>
    @if($hasil_pm->isEmpty())
        <p style="text-align:center; color:#94a3b8;">Sistem belum memproses perankingan Profile Matching.</p>
    @else
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="10%" style="text-align: center;">Peringkat</th>
                    <th width="30%">Jabatan Rekomendasi</th>
                    <th width="20%" style="text-align: center;">Core Factor</th>
                    <th width="20%" style="text-align: center;">Secondary Factor</th>
                    <th width="20%" style="text-align: center;">Nilai Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($hasil_pm as $pm)
                    <tr style="{{ $pm->peringkat == 1 ? 'background-color: #f0fdf4; border-left: 4px solid #10b981;' : '' }}">
                        <td style="text-align: center;">
                            @if($pm->peringkat == 1) <span style="background: #10b981; color: white; padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight:bold; display: inline-block; white-space: nowrap;">#1 TERBAIK</span>
                            @else <b>{{ $pm->peringkat }}</b> @endif
                        </td>
                        <td><b style="color: {{ $pm->peringkat == 1 ? '#047857' : '#1e293b' }};">{{ $pm->nama_jabatan }}</b></td>
                        <td style="text-align: center;">{{ number_format($pm->nilai_core_factor, 3) }}</td>
                        <td style="text-align: center;">{{ number_format($pm->nilai_secondary_factor, 3) }}</td>
                        <td style="text-align: center;"><b style="font-size:14px; color: {{ $pm->peringkat == 1 ? '#10b981' : '#1e293b' }};">{{ number_format($pm->nilai_total, 3) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection