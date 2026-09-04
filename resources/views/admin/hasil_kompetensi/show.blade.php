@extends('layouts.app')
@section('title', 'Detail Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Informasi lengkap hasil kompetensi pegawai')
@section('back_url', route('admin.hasil_kompetensi.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/detail_hasil.css') }}">
    <style>
        .tindakan-card { padding: 20px; border-radius: 12px; margin-top: 15px; border-left: 4px solid; display: flex; align-items: flex-start; gap: 16px;}
        .tindakan-icon { font-size: 24px; line-height: 1; }
        .tindakan-content h4 { margin: 0 0 4px 0; font-size: 15px; font-weight: 700; }
        .tindakan-content p { margin: 0; font-size: 13px; opacity: 0.9; }

        /* Desain Baru untuk Diagnostik Kekurangan */
        .diagnostic-grid { display: grid; grid-template-columns: 1fr; gap: 16px; margin-top: 20px;}
        .diag-box { padding: 20px; border-radius: 16px; border: 1px dashed; }
        .diag-box-red { background: #fff1f2; border-color: #fca5a5; }
        .diag-box-green { background: #f0fdf4; border-color: #86efac; }
        .diag-header { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.05);}
        
        .kuk-item-diag { display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; background: rgba(255,255,255,0.6); padding: 12px 16px; border-radius: 12px; margin-bottom: 8px; border: 1px solid rgba(0,0,0,0.02);}
        .kuk-item-diag:last-child { margin-bottom: 0; }
        .kuk-text { font-size: 13px; color: #334155; line-height: 1.5; font-weight: 500;}
        .kuk-score { font-size: 14px; font-weight: 800; white-space: nowrap; padding: 4px 12px; border-radius: 50px;}
        
        .score-red { background: #fee2e2; color: #e11d48; }
        .score-green { background: #d1fae5; color: #059669; }
    </style>
@endpush

@section('content')
@php
    $jml_instrumen = count($list_nilai);
    $total_skor = 0; foreach($list_nilai as $n) $total_skor += $n->skor_final;
    $rata_rata = ($jml_instrumen > 0) ? round($total_skor / $jml_instrumen, 1) : 0;

    $is_kompeten = in_array($data->status_kompeten, ['K', 'Kompeten', 'Sangat Kompeten']);
    $badge_class = $is_kompeten ? 'kompeten' : 'belum';

    $words = explode(" ", $data->pegawai_nama); $inisial = ""; foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); } $inisial = substr($inisial, 0, 2);

    $tindakan_lanjut = ""; $tindakan_desc = ""; $tindakan_color = ""; $tindakan_bg = ""; $tindakan_border = ""; $tindakan_icon = "";

    if (trim($data->status_kompeten) == 'Sangat Kompeten') {
        $tindakan_lanjut = "Siap Promosi / Sertifikasi Asesor"; $tindakan_desc = "Pegawai ini memiliki skor di atas rata-rata dan siap untuk memegang tanggung jawab lebih besar."; $tindakan_color = "#4338ca"; $tindakan_bg = "#e0e7ff"; $tindakan_border = "#c7d2fe"; $tindakan_icon = "bi-award-fill";
    } elseif (trim($data->status_kompeten) == 'Kompeten') {
        $tindakan_lanjut = "Pertahankan Kinerja / Delegasi Tugas Khusus"; $tindakan_desc = "Pegawai ini telah memenuhi standar kompetensi jabatannya dengan baik."; $tindakan_color = "#059669"; $tindakan_bg = "#d1fae5"; $tindakan_border = "#a7f3d0"; $tindakan_icon = "bi-check-circle-fill";
    } elseif (trim($data->status_kompeten) == 'Cukup Kompeten') {
        $tindakan_lanjut = "Mengikuti Seminar / Lokakarya Bidang Terkait"; $tindakan_desc = "Pegawai ini membutuhkan sedikit peningkatan wawasan untuk mencapai kompetensi ideal."; $tindakan_color = "#d97706"; $tindakan_bg = "#fef3c7"; $tindakan_border = "#fde68a"; $tindakan_icon = "bi-person-workspace";
    } else {
        $tindakan_lanjut = "Wajib Pelatihan Dasar / Evaluasi Rotasi Jabatan"; $tindakan_desc = "Terdapat Gap (celah) kompetensi yang signifikan. Segera jadwalkan pelatihan atau pertimbangkan rotasi."; $tindakan_color = "#e11d48"; $tindakan_bg = "#fff1f2"; $tindakan_border = "#fecdd3"; $tindakan_icon = "bi-shield-exclamation";
    }

    // LOGIKA PINTAR: Memisahkan KUK yang kurang (Skor <= 2) dan yang aman (Skor >= 3)
    $kuk_kurang = [];
    $kuk_aman = [];
    foreach($list_nilai as $n) {
        if ($n->skor_final <= 2) {
            $kuk_kurang[] = $n;
        } else {
            $kuk_aman[] = $n;
        }
    }
@endphp
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    <div class="pegawai-card">
        <div class="pegawai-left">
            <div class="avatar">{{ $inisial }}</div>
            <div>
                <h3>{{ $data->pegawai_nama }}</h3>
                <p>{{ $data->jabatan }} • {{ $data->unit_kerja ?? 'Museum Geologi' }}</p>
                <span class="badge {{ $badge_class }}" style="background: {{ $tindakan_color }}; color: white; border: none; font-weight: 700;">{{ $data->status_kompeten }}</span>
            </div>
        </div>
        <div class="pegawai-right"><small>Nilai Akhir (100)</small><h2>{{ number_format($data->skor_akhir_360 ?? 0, 2) }}</h2></div>
    </div>

    <!-- KOTAK TINDAKAN LANJUT -->
    <div class="content-card">
        <h3>Rekomendasi Tindakan Lanjut</h3>
        <div class="tindakan-card" style="background: {{ $tindakan_bg }}; border-color: {{ $tindakan_color }}; border-top: 1px solid {{ $tindakan_border }}; border-right: 1px solid {{ $tindakan_border }}; border-bottom: 1px solid {{ $tindakan_border }};">
            <div class="tindakan-icon" style="color: {{ $tindakan_color }};"><i class="bi {{ $tindakan_icon }}"></i></div>
            <div class="tindakan-content">
                <h4 style="color: {{ $tindakan_color }};">{{ $tindakan_lanjut }}</h4>
                <p style="color: {{ $tindakan_color }};">{{ $tindakan_desc }}</p>
            </div>
        </div>
    </div>

    <div class="content-card">
        <h3>Informasi Kompetensi</h3>
        <div class="info-grid">
            <div><label>Unit Kompetensi</label><span>{{ $data->kode_unit ?? '-' }}</span></div>
            <div><label>Elemen Kompetensi</label><span>{{ $data->nama_elemen ?? '-' }}</span></div>
            <div><label>Aktivitas</label><span>Seluruh Aktivitas Terkait</span></div>
            <div><label>Tanggal Penilaian</label><span>{{ $data->waktu_submit ? date('d M Y, H:i', strtotime($data->waktu_submit)) : 'Belum ada data' }}</span></div>
            <div><label>Assessor</label><span>Sistem (Profile Matching) & Pimpinan</span></div>
            <div><label>Jumlah Instrumen</label><span>{{ $jml_instrumen }} KUK</span></div>
        </div>
    </div>

    <div class="content-card">
        <h3>Catatan Keseluruhan</h3>
        @if(!$is_kompeten && $data->alasan_sistem != '-')
            <div style="background: #fff1f2; border: 1px dashed #fca5a5; border-left: 4px solid #e11d48; padding: 15px 20px; border-radius: 8px; margin-bottom: 15px;">
                <h5 style="margin: 0 0 8px 0; color: #be123c; font-size: 13px;"><i></i> Ringkasan Kekurangan Dokumen (Sistem)</h5>
                <p style="margin: 0; color: #9f1239; font-size: 13px; font-weight: 500; line-height: 1.5;">{{ $data->alasan_sistem }}</p>
            </div>
        @endif
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; padding: 15px 20px; border-radius: 8px;">
            <h5 style="margin: 0 0 5px 0; color: #1e293b; font-size: 13px;"><i class="bi bi-person-badge"></i> Catatan Pengesahan Pimpinan</h5>
            <p style="margin: 0; color: #475569; font-size: 13px; font-style: italic;">
                {!! $data->catatan_umum ? nl2br(e($data->catatan_umum)) : 'Pimpinan tidak memberikan catatan khusus.' !!}
            </p>
        </div>
    </div>

    <!-- DIAGNOSTIK KEKURANGAN KOMPETENSI -->
    <div class="content-card">
        <h3><i class="bi bi-search" style="color:#3e54a0; margin-right: 6px;"></i> Diagnostik Detail KUK</h3>
        <p style="font-size: 13px; color: #64748b; margin-top: -5px; margin-bottom: 20px;">Rincian Kriteria Unjuk Kerja (KUK) mana saja yang kurang dokumen atau mendapat nilai rendah (Skor 0-2).</p>
        
        <div class="diagnostic-grid">
            <!-- KOTAK MERAH (YANG KURANG) -->
            <div class="diag-box diag-box-red">
                <div class="diag-header" style="color: #e11d48;">
                    <i class="bi bi-exclamation-triangle-fill" style="font-size: 18px;"></i> 
                    Kekurangan Kompetensi / Dokumen Bukti Tidak Valid ({{ count($kuk_kurang) }} KUK)
                </div>
                
                @if(count($kuk_kurang) > 0)
                    @foreach($kuk_kurang as $k)
                        <div class="kuk-item-diag">
                            <div class="kuk-text">{{ $k->detail_aktivitas }}</div>
                            <div class="kuk-score score-red">Skor: {{ $k->skor_final }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="text-align: center; color: #ef4444; font-size: 13px; font-weight: 500; padding: 10px;">Hebat! Tidak ada KUK yang mendapatkan nilai buruk.</div>
                @endif
            </div>

            <!-- KOTAK HIJAU (YANG AMAN) -->
            <div class="diag-box diag-box-green">
                <div class="diag-header" style="color: #059669;">
                    <i class="bi bi-check-circle-fill" style="font-size: 18px;"></i> 
                    Kompetensi Terpenuhi ({{ count($kuk_aman) }} KUK)
                </div>
                
                @if(count($kuk_aman) > 0)
                    @foreach($kuk_aman as $k)
                        <div class="kuk-item-diag">
                            <div class="kuk-text">{{ $k->detail_aktivitas }}</div>
                            <div class="kuk-score score-green">Skor: {{ $k->skor_final }}</div>
                        </div>
                    @endforeach
                @else
                    <div style="text-align: center; color: #059669; font-size: 13px; font-weight: 500; padding: 10px;">Belum ada KUK yang mencapai target nilai.</div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection