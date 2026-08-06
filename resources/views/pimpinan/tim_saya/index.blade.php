@extends('layouts.app')

@section('title', 'Tim Saya')
@section('page_title', 'Tim Saya')
@section('page_subtitle', 'Pantau antrean penilaian dan progres skoring tim Anda')

@section('content')
<style>
    :root {
        --primary: #182A3A;
        --secondary: #425B6F;
        --accent: #A08348;
        --light-accent: #D6BB80;
        --bg: #F7F8FA;
    }

    .page-card { padding: 25px; background: #fff; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px; }
    .page-title-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #E5E7EB; }
    .page-title-header h2 { margin: 0; font-size: 20px; color: var(--primary); font-weight: 700; }
    
    /* BADGE PENILAI */
    .badge-penilai { background-color: #ebdbb6; color: var(--primary); font-weight: 700; padding: 8px 14px; border-radius: 8px; font-size: 13px; box-shadow: 0 2px 5px rgba(160, 131, 72, 0.3); }

    /* TABEL LEVEL 1 (PARENT) */
    .styled-table { width: 100%; border-collapse: collapse; }
    .styled-table th { background: #f8fafc; color: #475569; padding: 14px 15px; border-bottom: 2px solid #e2e8f0; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; }
    .styled-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
    
    .parent-row { cursor: pointer; transition: 0.2s; }
    .parent-row:hover { background-color: #f8fafc; }
    
    /* PERBAIKAN BADGE JABATAN */
    .jabatan-badge { 
        background: #f1f5f9; 
        color: var(--primary); 
        padding: 6px 12px; 
        border-radius: 6px; 
        font-size: 12px; 
        font-weight: 600; 
        border: 1px solid #cbd5e1; 
        display: inline-block; /* KUNCI AGAR TIDAK TERPOTONG */
        line-height: 1.4;
        text-align: center;
    }
    
    /* TABEL LEVEL 2 (CHILD/ACCORDION) */
    .child-container { padding: 20px 25px; background-color: #f8fafc; border-left: 4px solid var(--accent); border-radius: 0 0 8px 0; }
    .sub-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
    .sub-table th { background: #ffffff; padding: 12px 15px; font-size: 11px; color: #64748b; border-bottom: 1px solid #e2e8f0;}
    .sub-table td { padding: 14px 15px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;}
    
    /* INDIKATOR EVIDENCE & STATUS */
    .evidence-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-size: 13px; font-weight: 700;}
    .text-target { color: #94a3b8; font-size: 11px; font-weight: 500;}
    .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 5px;}
    .badge-hijau { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .badge-kuning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge-merah { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    
    /* TOMBOL AKSI */
    .btn-nilai-main { background: #ebdbb6; color: black; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; gap: 6px; width: 110px; justify-content: center; transition: 0.2s;}
    .btn-nilai-main:hover { background: #bda572; color: black; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(160, 131, 72, 0.3); }
    .btn-update-main { background: #fffdf5; color: var(--accent); border: 1px solid var(--accent); padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; gap: 6px; width: 110px; justify-content: center; transition: 0.2s;}
    .btn-update-main:hover { background: var(--accent); color: white; }
</style>

<div class="container-fluid py-4 px-4">
    <div class="page-card">
        <div class="page-title-header">
            <h2><i class="bi bi-people-fill me-2"></i> Tim Saya & Skoring</h2>
            <div class="badge-penilai">
                <i class="bi bi-person-badge me-1"></i> Penilai: {{ $pimpinan->pegawai_nama }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%" style="text-align: center;"></th>
                        <th width="25%">Nama Pegawai</th>
                        <th width="20%" style="text-align: center;">Jabatan Fungsional</th>
                        <th width="15%" style="text-align: center;">Dokumen Bukti</th>
                        <th width="20%" style="text-align: center;">Status Penilaian</th>
                        <th width="15%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data_pegawai as $pid => $peg)
                        @php
                            $progress_color = '#ef4444'; 
                            if ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) $progress_color = '#10b981'; 
                            elseif ($peg['total_terkumpul'] > 0) $progress_color = '#f59e0b'; 
                            
                            $semua_selesai = ($peg['total_uk'] > 0 && $peg['uk_dinilai'] == $peg['total_uk']);
                            $shortcut_kode = $semua_selesai ? $peg['uks'][0]['kode_unit'] : $peg['first_uk_to_score'];
                        @endphp
                        <tr class="parent-row" onclick="toggleChild('child-{{ $pid }}', this)">
                            <td style="text-align: center;"><i class="bi bi-chevron-right icon-toggle" style="color: var(--accent); font-weight: bold;"></i></td>
                            <td>
                                <b style="color: var(--primary); font-size: 14px;">{{ $peg['pegawai_nama'] }}</b><br>
                                <span style="font-size: 11px; color: #94a3b8;">NIP: {{ $peg['nip_nik'] }}</span>
                            </td>
                            <td style="text-align: center;">
                                <span class="jabatan-badge">{{ $peg['jabatan'] }}</span>
                            </td>
                            <td style="text-align: center;">
                                @if($peg['total_terkumpul'] == 0)
                                    <span class="badge-status badge-merah"><i class="bi bi-x-circle-fill"></i> Belum Upload</span>
                                @else
                                    <div class="evidence-badge">
                                        <i class="bi bi-folder-fill" style="color: {{ $progress_color }};"></i>
                                        <span style="color: #1e293b;">{{ $peg['total_terkumpul'] }}</span>
                                        <span class="text-target">/ {{ $peg['total_target'] }}</span>
                                    </div>
                                @endif
                            </td>
                            <td style="text-align: center;">
                                @if($semua_selesai) 
                                    <span class="badge-status badge-hijau"><i class="bi bi-check-circle-fill"></i> Selesai ({{ $peg['uk_dinilai'] }}/{{ $peg['total_uk'] }})</span>
                                @else 
                                    <span class="badge-status badge-kuning"><i class="bi bi-hourglass-split"></i> {{ $peg['uk_dinilai'] }}/{{ $peg['total_uk'] }} UK Dinilai</span>
                                @endif
                            </td>
                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                <a href="{{ route('pimpinan.tim_saya.beri_nilai', [$pid, $shortcut_kode]) }}" class="{{ $semua_selesai ? 'btn-update-main' : 'btn-nilai-main' }}">
                                    <i class="bi {{ $semua_selesai ? 'bi-pencil-square' : 'bi-plus-circle' }}"></i> {{ $semua_selesai ? 'Ubah Nilai' : 'Beri Nilai' }}
                                </a>
                            </td>
                        </tr>
                        
                        <!-- BAGIAN CHILD ROW (RINCIAN UK) -->
                        <tr class="child-row" id="child-{{ $pid }}" style="display: none;">
                            <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                                <div class="child-container">
                                    <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Unit Kompetensi (UK):</h4>
                                    <table class="sub-table">
                                        <thead>
                                            <tr>
                                                <th width="40%">Unit Kompetensi Diujikan</th>
                                                <th width="15%" style="text-align: center;">Bukti Terkumpul</th>
                                                <th width="15%" style="text-align: center;">Upload Terakhir</th>
                                                <th width="15%" style="text-align: center;">Status Penilaian</th>
                                                <th width="15%" style="text-align: center;">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($peg['uks'] as $uk)
                                                @php
                                                    $uk_prog_color = '#ef4444'; 
                                                    if ($uk['terkumpul'] >= $uk['target'] && $uk['target'] > 0) $uk_prog_color = '#10b981';
                                                    elseif ($uk['terkumpul'] > 0) $uk_prog_color = '#f59e0b';
                                                @endphp
                                                <tr>
                                                    <td>
                                                        <span style="font-size: 11px; font-weight: 700; color: var(--accent); display: block; margin-bottom: 2px;"><i class="bi bi-tag-fill"></i> {{ $uk['kode_unit'] }}</span>
                                                        <span style="color: #334155; font-weight: 500; font-size: 12px; line-height: 1.4;">{{ $uk['judul_unit'] }}</span>
                                                    </td>
                                                    <td style="text-align: center;">
                                                        @if($uk['terkumpul'] == 0)
                                                            <span class="badge-status badge-merah"><i class="bi bi-x-circle-fill"></i> 0 Upload</span>
                                                        @else
                                                            <div class="evidence-badge">
                                                                <i class="bi bi-file-earmark-check-fill" style="color: {{ $uk_prog_color }};"></i>
                                                                <span style="color: #1e293b;">{{ $uk['terkumpul'] }}</span> <span class="text-target">/ {{ $uk['target'] }}</span>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td style="text-align: center;">
                                                        @if(!empty($uk['waktu_terakhir'])) 
                                                            <span style="font-size: 11px; font-weight: 600; color: #64748b; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;">
                                                                <i class="bi bi-clock-history"></i> {{ date('d M Y', strtotime($uk['waktu_terakhir'])) }}
                                                            </span>
                                                        @else 
                                                            <span style="color: #cbd5e1; font-size: 12px; font-style: italic;">-</span> 
                                                        @endif
                                                    </td>
                                                    <td style="text-align: center;">
                                                        @if($uk['is_dinilai']) <span class="badge-status badge-hijau" style="font-size:10px;"><i class="bi bi-check-circle-fill"></i> SUDAH DINILAI</span>
                                                        @else <span class="badge-status badge-kuning" style="font-size:10px;"><i class="bi bi-exclamation-circle-fill"></i> BELUM DINILAI</span> @endif
                                                    </td>
                                                    <td style="text-align: center;">
                                                        <a href="{{ route('pimpinan.tim_saya.beri_nilai', [$pid, $uk['kode_unit']]) }}" class="{{ $uk['is_dinilai'] ? 'btn-update-main' : 'btn-nilai-main' }}" style="width: 100px;">
                                                            <i class="bi {{ $uk['is_dinilai'] ? 'bi-pencil-square' : 'bi-ui-checks' }}"></i> Nilai UK
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" style="text-align: center; padding: 50px 20px; color: #94a3b8;"><i class="bi bi-funnel" style="font-size: 36px; display:block; margin-bottom:12px; color: #cbd5e1;"></i><span style="font-size: 14px; font-weight: 500;">Tidak ada bawahan yang ditugaskan.</span></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleChild(childId, rowElement) {
    var childRow = document.getElementById(childId);
    var icon = rowElement.querySelector('.icon-toggle');
    if (childRow.style.display === 'none' || childRow.style.display === '') {
        childRow.style.display = 'table-row';
        rowElement.style.backgroundColor = '#f8fafc';
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    } else {
        childRow.style.display = 'none';
        rowElement.style.backgroundColor = 'transparent';
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    }
}
</script>
@endsection