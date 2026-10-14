@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi Evaluasi')
@section('page_subtitle', 'Pantau nilai akhir SKKNI dan Softskill Anda yang telah disahkan Pimpinan')

@push('styles')
    <style>
        .summary-card { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); padding: 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: stretch; gap: 24px; flex-wrap: wrap;}
        .summary-left { flex: 1; min-width: 280px; display: flex; flex-direction: column; justify-content: center;}
        .summary-left h2 { margin: 0 0 8px 0; font-size: 20px; color: #1e293b; font-weight: 800;}
        .summary-left p { margin: 0; color: #64748b; font-size: 13px; line-height: 1.5; }
        
        .summary-right { display: flex; gap: 16px; flex-wrap: wrap; flex: 2; justify-content: flex-end;}
        
        .box-kalkulasi { background: #f8fafc; border: 1px dashed #cbd5e1; padding: 20px; border-radius: 16px; min-width: 220px; display: flex; flex-direction: column; justify-content: center;}
        .box-skor { background: #f8fafc; padding: 20px 24px; border-radius: 16px; border: 1px dashed #cbd5e1; text-align: center; display: flex; flex-direction: column; justify-content: center; min-width: 140px;}
        .box-skor h1 { margin: 10px 0; font-size: 38px; color: #3e54a0; font-weight: 800; line-height: 1;}
        
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-transform: uppercase; display: inline-block;}
        .badge-st { background: #ecfdf5; color: #059669; border: none; } 
        .badge-t { background: #e0e7ff; color: #4338ca; border: none; } 
        .badge-sd { background: #fef3c7; color: #d97706; border: none; } 
        .badge-r { background: #ffedd5; color: #ea580c; border: none; } 
        .badge-sr { background: #fff1f2; color: #e11d48; border: none; } 

        .table-card { background: #fff; padding: 24px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 24px;}
        .table-card h3 { margin: 0 0 20px 0; font-size: 16px; color: #1e293b; border-bottom: 1px dashed #e2e8f0; padding-bottom: 12px; font-weight: 700;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { color: #64748b; padding: 12px 16px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 12px 16px; border-bottom: 1px dashed #f1f5f9; font-size: 12px; color: #334155; }
        
        .filter-period-box { background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between;}
        .filter-period-box label { font-size: 13px; font-weight: 700; color: #475569; margin: 0;}
        .filter-period-box select { padding: 10px 20px; border-radius: 50px; border: 1px solid #cbd5e1; font-size: 13px; outline: none; cursor: pointer; color: #1e293b; font-weight: 600; min-width: 250px;}

        .btn-detail-kuk { background: #f4f7fe; color: #3e54a0; border: none; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; }
        .btn-detail-kuk:hover { background: #3e54a0; color: #fff; }

        .skor-box-sf { background: #fef3c7; color: #d97706; font-weight: 800; padding: 6px 12px; border-radius: 8px; display: inline-block; min-width: 35px; text-align: center; border: 1px solid #fde68a;}
        .gap-box-sf { font-weight: 800; color: #ef4444; }
        .gap-box-sf.positive { color: #10b981; }
        .gap-box-sf.zero { color: #64748b; }
        
        @media(max-width: 992px) { .summary-right { justify-content: flex-start; } .grid-3-col { grid-template-columns: 1fr !important; } }
    </style>
@endpush

@section('content')
<div class="hasil-container">
    
    <div class="filter-period-box">
        <label><i class="bi bi-calendar-check-fill" style="color: #3e54a0; margin-right: 8px;"></i> Filter Periode Penilaian:</label>
        <form action="{{ route('pegawai.hasil.index') }}" method="GET" style="margin: 0;">
            <select name="periode_id" onchange="this.form.submit()">
                @if ($listPeriode->isEmpty())
                    <option value="">-- Belum Ada Penilaian Disahkan --</option>
                @else
                    <option value="">-- Pilih Periode --</option>
                    @foreach ($listPeriode as $periode)
                        @php
                            // Mengambil tahun secara otomatis dari database
                            $tahun = !empty($periode->tanggal_mulai) ? date('Y', strtotime($periode->tanggal_mulai)) : '2026';
                        @endphp
                        <option value="{{ $periode->periode_id }}" {{ ($periode_terpilih == $periode->periode_id) ? 'selected' : '' }}>
                            Tahun {{ $tahun }} ({{ $periode->nama_periode }})
                        </option>
                    @endforeach
                @endif
            </select>
        </form>
    </div>

    @if ($periode_terpilih)
        @if ($sudah_dinilai)
            @php
                $badge_class = 'badge-sr';
                if ($kategori_raw == 'Sangat Tinggi') {
                    $badge_class = 'badge-st';
                    $tindakan_lanjut = "Rekomendasi Sertifikasi / Mentor Internal";
                    $tindakan_color = "#059669"; $tindakan_bg = "#ecfdf5"; $tindakan_border = "#a7f3d0";
                } elseif ($kategori_raw == 'Tinggi') {
                    $badge_class = 'badge-t';
                    $tindakan_lanjut = "Delegasi Proyek Khusus";
                    $tindakan_color = "#4338ca"; $tindakan_bg = "#e0e7ff"; $tindakan_border = "#c7d2fe";
                } elseif ($kategori_raw == 'Sedang') {
                    $badge_class = 'badge-sd';
                    $tindakan_lanjut = "Sesi Coaching & Tutor Sebaya";
                    $tindakan_color = "#d97706"; $tindakan_bg = "#fef3c7"; $tindakan_border = "#fde68a";
                } elseif ($kategori_raw == 'Rendah') {
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
                    <h2><i class="bi bi-person-badge" style="color: #3e54a0; margin-right:8px;"></i> Evaluasi Jabatan: {{ $jabatanPegawai }}</h2>
                    <p>Berikut adalah hasil akhir dari perhitungan <i>Core Factor</i> (SKKNI) dan <i>Secondary Factor</i> (Softskill) yang telah disahkan oleh Pimpinan Anda.</p>
                </div>
                
                <div class="summary-right">
                    <div class="box-kalkulasi">
                        <span style="display: block; font-size: 10px; font-weight: 800; color: #475569; margin-bottom: 12px; letter-spacing: 0.5px;"><i class="bi bi-calculator"></i> SKOR MENTAH JABATAN</span>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                            <span style="color: #64748b;">Total Kompetensi</span><b style="color: #1e293b;">{{ $jumlah_kompetensi }} Unit</b>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                            <span style="color: #64748b;">Total KUK Dinilai</span><b style="color: #1e293b;">{{ $total_kuk_jabatan }} KUK</b>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                            <span style="color: #64748b;">Batas Minimum (x1)</span><b style="color: #1e293b;">{{ $nilai_minimum }}</b>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 11px;">
                            <span style="color: #64748b;">Batas Maksimum (x5)</span><b style="color: #1e293b;">{{ $nilai_maksimum }}</b>
                        </div>
                        <hr style="border: none; border-top: 1px dashed #cbd5e1; margin: 8px 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 13px;">
                            <span style="color: #3e54a0; font-weight: 700;">Skor Aktual</span><b style="color: #3e54a0; font-size: 16px; font-weight: 800;">{{ $skor_aktual_total }}</b>
                        </div>
                    </div>

                    <div class="box-skor">
                        <span style="font-size: 10px; color: #64748b; font-weight: 700; text-transform: uppercase;">Skor Aktual (Raw)</span>
                        <h1>{{ $skor_aktual_total }}</h1>
                        <span class="badge {{ $badge_class }}">{{ $kategori_raw }}</span>
                    </div>
                </div>
            </div>

            <div class="grid-3-col" style="display: grid; grid-template-columns: 1fr 1fr 1.5fr; gap: 24px; margin-bottom: 24px;">
                <div style="background: #fff; padding: 24px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; border-left: 4px solid #3e54a0;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><i class="bi bi-tools" style="margin-right: 4px;"></i> Nilai NCF</span>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8;">Core Factor ({{ $persen_ncf }}%)</p>
                    </div>
                    <h2 style="margin: 0; font-size: 28px; color: #3e54a0; font-weight: 800;">{{ number_format($ncf, 2) }}</h2>
                </div>

                <div style="background: #fff; padding: 24px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; border-left: 4px solid #d97706;">
                    <div>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;"><i class="bi bi-person-hearts" style="margin-right: 4px;"></i> Nilai NSF</span>
                        <p style="margin: 4px 0 0 0; font-size: 12px; color: #94a3b8;">Secondary Factor ({{ $persen_nsf }}%)</p>
                    </div>
                    <h2 style="margin: 0; font-size: 28px; color: #d97706; font-weight: 800;">{{ number_format($nsf, 2) }}</h2>
                </div>

                <div style="background: {{ $tindakan_bg }}; padding: 24px; border-radius: 24px; border: 1px solid {{ $tindakan_border }}; display: flex; align-items: center; gap: 16px;">
                    <div style="width: 48px; height: 48px; border-radius: 50%; background: white; display: flex; justify-content: center; align-items: center; font-size: 20px; color: {{ $tindakan_color }}; flex-shrink: 0;">
                        <i class="bi bi-lightbulb-fill"></i>
                    </div>
                    <div>
                        <span style="font-size: 10px; font-weight: 800; color: {{ $tindakan_color }}; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.8;">Rekomendasi Pengembangan</span>
                        <h3 style="margin: 4px 0 0 0; font-size: 14px; color: {{ $tindakan_color }}; line-height: 1.4; font-weight: 700;">{{ $tindakan_lanjut }}</h3>
                    </div>
                </div>
            </div>

            <!-- TABEL HISTORIS SKKNI (CORE FACTOR) YANG BISA DIKLIK DETAIL -->
            @if($list_uk && $list_uk->count() > 0)
            <div class="table-card" style="padding: 0; overflow: hidden;">
                <h3 style="padding: 24px 24px 0 24px;"><i class="bi bi-tools" style="color: #3e54a0; margin-right: 8px;"></i> Break-down Hardskill NCF (Per Unit Kompetensi)</h3>
                <div class="table-responsive">
                    <table class="styled-table" style="margin: 0; width: 100%;">
                        <thead style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                            <tr>
                                <th width="15%">Kode Unit</th>
                                <th width="35%">Judul Unit</th>
                                <th width="15%" class="text-center">Skor (100)</th>
                                <th width="20%" class="text-center">Skor Raw (Min-Max)</th>
                                <th width="15%" class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list_uk as $uk)
                                <tr>
                                    <td><b style="color: #3e54a0;">{{ $uk->kode_unit }}</b></td>
                                    <td><div style="font-weight: 600; color: #1e293b;">{{ $uk->judul_unit }}</div></td>
                                    <td class="text-center"><b style="font-size: 14px; color:#0f172a;">{{ number_format($uk->nilai_akhir, 2) }}</b></td>
                                    <td class="text-center">
                                        <b style="font-size: 14px; color:#0f172a;">{{ $uk->skor_aktual_raw }}</b>
                                        <span style="display:block; font-size: 10px; color:#64748b; margin-top:2px;">(Range: {{ $uk->min_skor }} - {{ $uk->max_skor }})</span>
                                    </td>
                                    <td class="text-center">
                                        <!-- TOMBOL UNTUK MEMBUKA DETAIL KUK -->
                                        <button type="button" class="btn-detail-kuk" onclick="toggleDetail('{{ $uk->kode_unit }}')">
                                            <i class="bi bi-eye-fill"></i> Detail KUK
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- BARIS TERSEMBUNYI UNTUK DETAIL KUK -->
                                <tr id="detail-{{ $uk->kode_unit }}" style="display: none; background: #f8fafc;">
                                    <td colspan="5" style="padding: 20px 30px; border-bottom: 2px solid #cbd5e1;">
                                        <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                                            <table style="width: 100%; border-collapse: collapse;">
                                                <thead style="background: #f1f5f9;">
                                                    <tr>
                                                        <th style="padding: 10px 15px; font-size: 10px; color: #475569; text-transform: uppercase;">Aktivitas (KUK)</th>
                                                        <th class="text-center" style="padding: 10px 15px; font-size: 10px; color: #475569; text-transform: uppercase;">Aktual</th>
                                                        <th class="text-center" style="padding: 10px 15px; font-size: 10px; color: #475569; text-transform: uppercase;">Target</th>
                                                        <th class="text-center" style="padding: 10px 15px; font-size: 10px; color: #475569; text-transform: uppercase;">Gap</th>
                                                        <th class="text-center" style="padding: 10px 15px; font-size: 10px; color: #475569; text-transform: uppercase;">Bobot</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @if(isset($detail_per_uk[$uk->kode_unit]))
                                                        @foreach($detail_per_uk[$uk->kode_unit] as $row)
                                                        <tr style="border-bottom: 1px dashed #e2e8f0;">
                                                            <td style="padding: 12px 15px; font-size: 12px; color: #334155;">{!! $row->detail_aktivitas !!}</td>
                                                            <td class="text-center" style="padding: 12px 15px; font-size: 12px; font-weight: bold; color: #0284c7;">{{ (int)$row->skor_final }}</td>
                                                            <td class="text-center" style="padding: 12px 15px; font-size: 12px; font-weight: bold;">5</td>
                                                            <td class="text-center" style="padding: 12px 15px; font-size: 12px;">
                                                                @php
                                                                    $gapClass = 'zero';
                                                                    if($row->gap > 0) $gapClass = 'positive';
                                                                    elseif($row->gap < 0) $gapClass = '';
                                                                @endphp
                                                                <span class="gap-box-sf {{ $gapClass }}" style="background: transparent; border:none;">{{ $row->gap > 0 ? '+'.$row->gap : $row->gap }}</span>
                                                            </td>
                                                            <td class="text-center" style="padding: 12px 15px; font-size: 12px; font-weight: bold; color: #3e54a0;">{{ number_format($row->bobot, 2) }}</td>
                                                        </tr>
                                                        @if(!empty($row->catatan_penilai) && $row->catatan_penilai != 'Penilaian PM (Dikoreksi Pimpinan)')
                                                        <tr>
                                                            <td colspan="5" style="padding: 0 15px 12px 15px; border-bottom: 1px dashed #e2e8f0;">
                                                                <div style="background: #fef3c7; border-left: 3px solid #d97706; padding: 8px 12px; font-size: 11px; color: #92400e; font-style: italic;">
                                                                    <i class="bi bi-chat-left-text-fill"></i> "{{ $row->catatan_penilai }}"
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        @endif
                                                        @endforeach
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- TABEL HISTORIS SOFTSKILL (SECONDARY FACTOR) -->
            @if($hasilSoftskill && $hasilSoftskill->count() > 0)
            <div class="table-card" style="padding: 0; overflow: hidden; border: 1px solid #fde68a;">
                <h3 style="padding: 24px 24px 0 24px; color: #92400e; border-bottom: none;"><i class="bi bi-person-hearts" style="color: #d97706; margin-right: 8px;"></i> Rincian Skor Perilaku NSF (Secondary Factor - {{ $persen_nsf }}%)</h3>
                <div class="table-responsive" style="background: white;">
                    <table class="styled-table" style="margin: 0; width: 100%;">
                        <thead style="background: #fdf8e6; border-bottom: 2px solid #fde68a;">
                            <tr>
                                <th width="5%" class="text-center" style="color: #92400e;">NO</th>
                                <th width="40%" style="color: #92400e;">KRITERIA SOFTSKILL</th>
                                <th width="15%" class="text-center" style="color: #92400e;">TARGET</th>
                                <th width="15%" class="text-center" style="color: #92400e;">NILAI AKTUAL</th>
                                <th width="10%" class="text-center" style="color: #92400e;">GAP</th>
                                <th width="15%" class="text-center" style="color: #92400e;">BOBOT PM</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($hasilSoftskill as $index => $sf)
                                <tr>
                                    <td class="text-center" style="font-weight: 600; color: #b45309;">{{ $index + 1 }}</td>
                                    <td>
                                        <div style="font-weight: 700; color: #78350f; font-size: 13px;">{{ $sf->nama_softskill }}</div>
                                        <div style="font-size: 11px; color: #92400e; margin-top: 4px; line-height: 1.4;">{{ $sf->deskripsi }}</div>
                                    </td>
                                    <td class="text-center"><span style="font-weight: 800; color: #94a3b8; font-size: 13px;">{{ $sf->target_skor }}</span></td>
                                    <td class="text-center"><div class="skor-box-sf">{{ $sf->nilai_aktual }}</div></td>
                                    <td class="text-center">
                                        @php
                                            $gapClass = 'zero';
                                            if($sf->gap > 0) $gapClass = 'positive';
                                            elseif($sf->gap < 0) $gapClass = '';
                                        @endphp
                                        <span class="gap-box-sf {{ $gapClass }}">{{ $sf->gap > 0 ? '+'.$sf->gap : $sf->gap }}</span>
                                    </td>
                                    <td class="text-center" style="font-weight: 800; color: #b45309;">{{ number_format($sf->bobot_gap, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        @else
            <div style="background:#fff; padding: 40px; text-align:center; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
                <i class="bi bi-hourglass-split" style="font-size: 40px; color:#cbd5e1; display:block; margin-bottom: 10px;"></i>
                <h3 style="color: #0f172a; margin:0;">Sedang Dalam Proses Evaluasi</h3>
                <p style="color: #64748b; font-size: 13px; margin-top: 8px;">Pimpinan belum menyelesaikan dan mengesahkan penilaian kompetensi Anda untuk periode ini.</p>
            </div>
        @endif
    @else
        <div style="background:#fff; padding: 40px; text-align:center; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
            <i class="bi bi-cpu" style="font-size: 40px; color:#cbd5e1; display:block; margin-bottom: 10px;"></i>
            <h3 style="color: #0f172a; margin:0;">Pilih Periode Terlebih Dahulu</h3>
            <p style="color: #64748b; font-size: 13px; margin-top: 8px;">Silakan gunakan dropdown di atas untuk memilih periode penilaian dan melihat hasil skoring Anda.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function toggleDetail(kodeUnit) {
        var el = document.getElementById('detail-' + kodeUnit);
        if (el.style.display === 'none') {
            el.style.display = 'table-row';
        } else {
            el.style.display = 'none';
        }
    }
</script>
@endpush