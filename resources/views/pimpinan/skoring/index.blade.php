@extends('layouts.app')
@section('title', 'Skoring Bukti Dokumen')
@section('page_title', 'Skoring & Penilaian Evidence')
@section('page_subtitle', 'Kelola dokumen kompetensi pegawai dan berikan penilaian akhir.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .filter-box-top { background: #ffffff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); display: flex; gap: 20px; align-items: center; flex-wrap: wrap;}
        .filter-group { display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px;}
        .filter-group label { font-size: 13px; font-weight: 700; color: #475569; white-space: nowrap; margin: 0; display: flex; align-items: center; gap: 6px;}
        .filter-group select { flex: 1; padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; cursor: pointer; background-color: #f8fafc;}
        .table-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #ffffff; color: #64748b; padding: 12px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .parent-row { transition: 0.2s; cursor: pointer; }
        .parent-row:hover { background-color: #f8fafc; }
        .child-container { padding: 20px 25px 20px 45px; background-color: #f8fafc; border-left: 4px solid #A08348; }
        .sub-table { width: 100%; border-collapse: collapse; background: #ffffff; border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
        .sub-table th { background: #ffffff; padding: 12px 15px; font-size: 11px; color: #64748b; border-bottom: 1px solid #e2e8f0;}
        .sub-table td { padding: 14px 15px; font-size: 13px; border-bottom: 1px solid #f1f5f9; vertical-align: middle;}
        .evidence-badge { display: inline-flex; align-items: center; justify-content: center; gap: 5px; font-size: 14px; font-weight: 700;}
        .text-target { color: #94a3b8; font-size: 12px; font-weight: 500;}
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; text-align: center; white-space: nowrap; border: 1px solid transparent;}
        .badge-hijau { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border-color: #fde68a; }
        .badge-sm { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 700; text-transform: uppercase;}
        .btn-nilai-main { background: #bda572; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: 110px;}
        .btn-update-main { background: #fffdf5; color: #A08348; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid #A08348; width: 110px;}
        .text-id { font-size: 11px; font-weight: 700; color: #A08348; margin-bottom: 2px; display: block;}
        .time-badge { font-size: 11px; font-weight: 600; color: #64748b; display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 8px; border-radius: 6px;}
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif

    <form method="GET" action="{{ route('pimpinan.skoring.index') }}" id="formFilter" class="filter-box-top">
        <div class="filter-group">
            <label><i class="bi bi-person-workspace" style="color: #3b82f6;"></i> Jabatan:</label>
            <select name="jabatan" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL">-- Semua Jabatan --</option>
                @foreach($list_jabatan as $j)
                    <option value="{{ $j }}" {{ $filter_jabatan == $j ? 'selected' : '' }}>{{ $j }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter-group">
            <label><i class="bi bi-ui-checks" style="color: #10b981;"></i> Status:</label>
            <select name="status" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL" {{ $filter_status == 'ALL' ? 'selected' : '' }}>-- Semua Status --</option>
                <option value="belum" {{ $filter_status == 'belum' ? 'selected' : '' }}>Belum Selesai Dinilai</option>
                <option value="selesai" {{ $filter_status == 'selesai' ? 'selected' : '' }}>Selesai Dinilai</option>
            </select>
        </div>
    </form>

    <div class="table-card">
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;"></th>
                    <th width="25%">Nama Pegawai</th>
                    <th width="25%">Jabatan Fungsional</th>
                    <th width="15%" style="text-align: center;">Jumlah Bukti</th>
                    <th width="15%" style="text-align: center;">Status Penilaian</th>
                    <th width="15%" style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data_pegawai as $pid => $peg)
                    @php
                        $progress_color = '#ef4444'; 
                        if ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) $progress_color = '#10b981'; 
                        elseif ($peg['total_terkumpul'] > 0) $progress_color = '#f59e0b'; 
                        
                        $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk']);
                        $shortcut_kode = $semua_selesai ? $peg['uks'][0]['kode_unit'] : $peg['first_uk_to_score'];
                    @endphp
                    <tr class="parent-row" onclick="toggleChild('child-{{ $pid }}', this)">
                        <td style="text-align: center;"><i class="bi bi-chevron-right icon-toggle"></i></td>
                        <td><b style="color: #0f172a; font-size: 13px;">{{ $peg['nama'] }}</b></td>
                        <td><span style="font-size: 13px; color: #475569; font-weight: 500;">{{ $peg['jabatan'] }}</span></td>
                        <td style="text-align: center;">
                            <div class="evidence-badge">
                                <i class="bi bi-folder-fill" style="color: {{ $progress_color }};"></i>
                                <span style="color: #1e293b;">{{ $peg['total_terkumpul'] }}</span>
                                <span class="text-target">/ {{ $peg['total_target'] }}</span>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            @if($semua_selesai) <span class="badge badge-hijau"><i class="bi bi-check-all"></i> Sudah Dinilai</span>
                            @else <span class="badge badge-kuning"><i class="bi bi-hourglass-split"></i> {{ $peg['uk_dinilai'] }}/{{ $peg['total_uk'] }} UK Selesai</span>
                            @endif
                        </td>
                        <td style="text-align: center;" onclick="event.stopPropagation();">
                            @if($semua_selesai) <a href="{{ route('pimpinan.skoring.beri_nilai', [$pid, $shortcut_kode]) }}" class="btn-update-main"><i class="bi bi-pencil-square"></i> Ubah Nilai</a>
                            @else <a href="{{ route('pimpinan.skoring.beri_nilai', [$pid, $shortcut_kode]) }}" class="btn-nilai-main">Beri Nilai</a>
                            @endif
                        </td>
                    </tr>
                    <tr class="child-row" id="child-{{ $pid }}" style="display: none;">
                        <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                            <div class="child-container">
                                <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Unit Kompetensi (UK):</h4>
                                <table class="sub-table">
                                    <thead>
                                        <tr>
                                            <th width="35%">Unit Kompetensi Diujikan</th>
                                            <th width="15%" style="text-align: center;">Bukti Dokumen</th>
                                            <th width="20%" style="text-align: center;">Waktu Upload</th>
                                            <th width="15%" style="text-align: center;">Status</th>
                                            <th width="15%" style="text-align: center;">Aksi Penilaian</th>
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
                                                <td><span class="text-id"><i class="bi bi-tag-fill"></i> {{ $uk['kode_unit'] }}</span><span style="color: #334155; font-weight: 500;">{{ $uk['judul_unit'] }}</span></td>
                                                <td style="text-align: center;">
                                                    <div class="evidence-badge">
                                                        <i class="bi bi-folder-fill" style="color: {{ $uk_prog_color }};"></i>
                                                        <span style="color: #1e293b;">{{ $uk['terkumpul'] }}</span> <span class="text-target">/ {{ $uk['target'] }}</span>
                                                    </div>
                                                </td>
                                                <td style="text-align: center;">
                                                    @if(!empty($uk['waktu_terakhir'])) <span class="time-badge"><i class="bi bi-clock-history"></i> {{ date('d M Y, H:i', strtotime($uk['waktu_terakhir'])) }}</span>
                                                    @else <span style="color: #cbd5e1; font-size: 12px;">-</span> @endif
                                                </td>
                                                <td style="text-align: center;">
                                                    @if($uk['is_dinilai']) <span class="badge-sm badge-hijau">SUDAH DINILAI</span>
                                                    @else <span class="badge-sm badge-kuning">BELUM DINILAI</span> @endif
                                                </td>
                                                <td style="text-align: center;">
                                                    @if($uk['is_dinilai']) <a href="{{ route('pimpinan.skoring.beri_nilai', [$pid, $uk['kode_unit']]) }}" class="btn-update-main" style="width: 100px;"><i class="bi bi-pencil-square"></i> Ubah Nilai</a>
                                                    @else <a href="{{ route('pimpinan.skoring.beri_nilai', [$pid, $uk['kode_unit']]) }}" class="btn-nilai-main" style="width: 100px;"><i class="bi bi-ui-checks"></i> Beri Nilai</a>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; padding: 50px 20px; color: #94a3b8;"><i class="bi bi-funnel" style="font-size: 36px; display:block; margin-bottom:12px; color: #cbd5e1;"></i><span style="font-size: 14px; font-weight: 500;">Tidak ada pegawai yang sesuai filter.</span></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@push('scripts')
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
@endpush