@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Pilih pegawai untuk melihat rincian akhir penilaian (Hardskill & Softskill)')

@push('styles')
    <style>
        .widget-container { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; margin-bottom: 24px; }
        .widget-box { background: #fff; padding: 20px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 14px;}
        .widget-icon { width: 48px; height: 48px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px;}
        .widget-info h4 { margin: 0; font-size: 10px; color: #64748b; text-transform: uppercase; font-weight: 700;}
        .widget-info p { margin: 0; font-size: 24px; font-weight: 800; color: #1e293b; line-height: 1;}
        
        .w-st { .widget-icon { background: #ecfdf5; color: #10b981; } }
        .w-t { .widget-icon { background: #e0e7ff; color: #4338ca; } }
        .w-sd { .widget-icon { background: #fef3c7; color: #d97706; } }
        .w-r { .widget-icon { background: #ffedd5; color: #ea580c; } }
        .w-sr { .widget-icon { background: #fff1f2; color: #e11d48; } }

        .filter-box { background: #ffffff; border-radius: 24px; padding: 20px 32px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .filter-left select { padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; border-radius: 50px; font-size: 13px; outline: none; cursor: pointer; background: #f9fafb; color: #1e293b;}
        
        .table-card { background: #fff; padding: 32px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700;}
        .styled-table td { padding: 16px 20px; border-bottom: 1px dashed #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle;}
        
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 10px; font-weight: 700; width: auto; min-width: 100px; text-align: center; text-transform: uppercase; display: inline-block;}
        .badge-st { background: #ecfdf5; color: #059669; } 
        .badge-t { background: #e0e7ff; color: #4338ca; } 
        .badge-sd { background: #fef3c7; color: #d97706; } 
        .badge-r { background: #ffedd5; color: #ea580c; } 
        .badge-sr { background: #fff1f2; color: #e11d48; } 
        
        .btn-aksi { background: #3e54a0; color: white; padding: 8px 18px; border-radius: 50px; text-decoration: none; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px; white-space: nowrap; transition: 0.2s;}
        .btn-aksi:hover { background: #2b3a70; transform: translateY(-2px);}
    </style>
@endpush

@section('content')
    <div class="widget-container">
        <div class="widget-box w-st"><div class="widget-icon"><i class="bi bi-star-fill"></i></div><div class="widget-info"><h4>Sangat Tinggi</h4><p>{{ $stat_sangat_tinggi }}</p></div></div>
        <div class="widget-box w-t"><div class="widget-icon" style="background:#e0e7ff; color:#4338ca;"><i class="bi bi-graph-up-arrow"></i></div><div class="widget-info"><h4>Tinggi</h4><p>{{ $stat_tinggi }}</p></div></div>
        <div class="widget-box w-sd"><div class="widget-icon" style="background:#fef3c7; color:#d97706;"><i class="bi bi-dash-circle-fill"></i></div><div class="widget-info"><h4>Sedang</h4><p>{{ $stat_sedang }}</p></div></div>
        <div class="widget-box w-r"><div class="widget-icon" style="background:#ffedd5; color:#ea580c;"><i class="bi bi-exclamation-triangle-fill"></i></div><div class="widget-info"><h4>Rendah</h4><p>{{ $stat_rendah }}</p></div></div>
        <div class="widget-box w-sr"><div class="widget-icon" style="background:#fff1f2; color:#e11d48;"><i class="bi bi-x-circle-fill"></i></div><div class="widget-info"><h4>Sangat Rendah</h4><p>{{ $stat_sangat_rendah }}</p></div></div>
    </div>

    <div class="filter-box">
        <form method="GET" action="{{ route('pimpinan.hasil_kompetensi.index') }}" id="formFilter" class="filter-left">
            <label style="font-size: 13px; font-weight: 700; margin-right:10px;"><i class="bi bi-funnel-fill" style="color: #3e54a0;"></i> Filter Periode / Jabatan:</label>
            <select name="periode" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL">Semua Jabatan</option>
                @foreach($list_periode as $p)
                    <option value="{{ $p->nama_periode }}" {{ $filter_periode == $p->nama_periode ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="table-card">
        <h3 style="margin:0 0 20px 0; font-size: 18px; color:#1e293b;">Daftar Pegawai yang Telah Dinilai</h3>
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;">No</th>
                    <th width="25%">Nama Pegawai</th>
                    <th width="20%">Jabatan Saat Ini</th>
                    <th width="15%" style="text-align: center;">Skor Aktual (Raw)</th>  
                    <th width="15%" style="text-align: center;">Predikat</th>
                    <th width="20%" style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list_riwayat as $index => $r)
                    @php
                        $text_kat = $r->kategori_baru;
                        if ($text_kat == 'Sangat Tinggi') $badge_class = 'badge-st';
                        elseif ($text_kat == 'Tinggi') $badge_class = 'badge-t';
                        elseif ($text_kat == 'Sedang') $badge_class = 'badge-sd';
                        elseif ($text_kat == 'Rendah') $badge_class = 'badge-r';
                        else $badge_class = 'badge-sr';
                    @endphp
                    <tr>
                        <td style="text-align: center; color: #64748b;">{{ $index + 1 }}</td>
                        <td><b style="color: #0f172a; font-size: 14px;">{{ $r->pegawai_nama }}</b></td>
                        <td>{{ $r->jabatan }}</td>
                        <td style="text-align: center;">
                            <b style="font-size: 16px; color: #3e54a0;">{{ $r->skor_aktual_raw }}</b>
                            <span style="display:block; font-size: 10px; color:#64748b;">(Max: {{ $r->total_kuk_raw * 5 }})</span>
                        </td>
                        <td style="text-align: center;"><span class="badge {{ $badge_class }}">{{ $text_kat }}</span></td>
                        <td style="text-align: center;">
                            <a href="{{ route('pimpinan.hasil_kompetensi.show', $r->pegawai_id) }}" class="btn-aksi"><i class="bi bi-eye-fill"></i> Lihat Nilai</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; padding: 40px;">Belum ada data penilaian pegawai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection