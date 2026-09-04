@extends('layouts.app')
@section('title', 'Aktivitas Penilaian')
@section('page_title', 'Aktivitas Penilaian')
@section('page_subtitle', 'Pantau kelengkapan dokumen (evidence) kompetensi seluruh pegawai')

@push('styles')
    <style>
        .page-card { background: #fff; padding: 32px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .search-box { display: flex; align-items: center; background: #f8fafc; padding: 12px 20px; border-radius: 50px; border: 1px solid #e2e8f0; width: 100%; max-width: 400px; }
        .search-box i { color: #94a3b8; font-size: 16px; margin-right: 12px; }
        .search-box input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #334155; }

        .styled-table { width: 100%; border-collapse: collapse; margin-top: 24px; }
        .styled-table th { color: #64748b; font-size: 11px; font-weight: 800; text-transform: uppercase; padding: 16px; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px; }
        .styled-table td { padding: 18px 16px; font-size: 13px; color: #334155; border-bottom: 1px dashed #e2e8f0; vertical-align: middle; }

        .parent-row { cursor: pointer; transition: 0.2s ease; }
        .parent-row:hover { background: #f8fafc; }
        .parent-row.active { background: #f4f7fe; border-left: 4px solid #3e54a0; }

        .evidence-badge { display: inline-flex; align-items: center; gap: 8px; font-weight: 700; font-size: 14px; }
        .text-target { color: #94a3b8; font-size: 12px; font-weight: 500; }

        .badge { padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-kuning { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }

        .child-container { background: #f8fafc; padding: 24px 40px; border-bottom: 2px solid #e2e8f0; border-left: 4px solid #cbd5e1; }
        .sub-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0;}
        .sub-table th { background: #f1f5f9; padding: 12px 16px; font-size: 10px; }
        .sub-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; }
        .sub-table tr:last-child td { border-bottom: none; }
        .text-id { font-size: 10px; font-weight: 800; color: #3e54a0; background: #e0f2fe; padding: 4px 8px; border-radius: 4px; margin-bottom: 6px; display: inline-block;}

        .kurang-dokumen { font-weight: 800; color: #ef4444; background: #fef2f2; padding: 6px 14px; border-radius: 50px; font-size: 12px; display: inline-block; }
        .lunas-dokumen { font-weight: 800; color: #10b981; background: #ecfdf5; padding: 6px 14px; border-radius: 50px; font-size: 12px; display: inline-block; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div class="search-box">
            <i class="bi bi-search"></i>
            <input type="text" id="searchInput" placeholder="Cari nama atau jabatan pegawai..." onkeyup="filterTable()">
        </div>
    </div>

    <!-- KONTEN TABEL -->
    <div style="overflow-x: auto;">
        <table class="styled-table" id="pegawaiTable">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;"></th>
                    <th width="25%">Nama Pegawai</th>
                    <th width="25%">Jabatan</th>
                    <th width="15%" style="text-align: center;">Dokumen Diupload</th>
                    <th width="15%" style="text-align: center;">Kekurangan Dokumen</th>
                    <th width="15%" style="text-align: center;">Status Penilaian</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data_pegawai as $pid => $peg)
                    @php
                        $semua_selesai = ($peg['uk_dinilai'] == $peg['total_uk'] && $peg['total_uk'] > 0);
                        $progress_color = ($peg['total_terkumpul'] >= $peg['total_target'] && $peg['total_target'] > 0) ? '#10b981' : ($peg['total_terkumpul'] > 0 ? '#f59e0b' : '#94a3b8');

                        // Menghitung jumlah kekurangan evidence
                        $kurang_evidence = $peg['total_target'] - $peg['total_terkumpul'];
                        if($kurang_evidence < 0) $kurang_evidence = 0;
                    @endphp

                    <!-- PARENT ROW -->
                    <tr class="parent-row" onclick="toggleChild('child-{{ $pid }}', this)">
                        <td style="text-align: center;">{!! $peg['total_uk'] > 0 ? '<i class="bi bi-chevron-right icon-toggle" style="color:#94a3b8; font-weight:bold;"></i>' : '<i class="bi bi-dash" style="color: #cbd5e1;"></i>' !!}</td>
                        <td><b style="color: #0f172a; font-size: 13px; display: block;">{{ $peg['nama'] }}</b><span style="font-size: 11px; color: #94a3b8;">ID: {{ $pid }}</span></td>
                        <td><span style="font-size: 13px; color: #475569; font-weight: 500;">{{ $peg['jabatan'] }}</span></td>

                        @if($peg['total_uk'] == 0)
                            <td style="text-align: center;"><span style="color: #cbd5e1;">-</span></td>
                            <td style="text-align: center;"><span style="color: #cbd5e1;">-</span></td>
                            <td style="text-align: center;"><span class="badge badge-merah"><i class="bi bi-exclamation-triangle"></i> Belum Dipetakan</span></td>
                        @else
                            <!-- Keterangan Jumlah Terupload -->
                            <td style="text-align: center;">
                                <div class="evidence-badge">
                                    <i class="bi bi-folder-fill" style="color: {{ $progress_color }};"></i>
                                    <span style="color: #1e293b;">{{ $peg['total_terkumpul'] }}</span>
                                    <span class="text-target">/ {{ $peg['total_target'] }}</span>
                                </div>
                            </td>

                            <!-- Info Kekurangan Dokumen -->
                            <td style="text-align: center;">
                                @if($kurang_evidence > 0)
                                    <span class="kurang-dokumen">Kurang {{ $kurang_evidence }} Dokumen</span>
                                @else
                                    <span class="lunas-dokumen"><i class="bi bi-check-circle-fill"></i> Lengkap</span>
                                @endif
                            </td>

                            <!-- Status Penilaian -->
                            <td style="text-align: center;">
                                @if($semua_selesai)
                                    <span class="badge badge-hijau"><i class="bi bi-check-all"></i> Selesai Dinilai</span>
                                @else
                                    <span class="badge badge-kuning"><i class="bi bi-hourglass-split"></i> {{ $peg['uk_dinilai'] }}/{{ $peg['total_uk'] }} Selesai</span>
                                @endif
                            </td>
                        @endif
                    </tr>

                    <!-- CHILD ROW (Rincian per Unit) -->
                    @if($peg['total_uk'] > 0)
                    <tr class="child-row" id="child-{{ $pid }}" style="display: none;">
                        <td colspan="6" style="padding: 0; border-bottom: 2px solid #cbd5e1;">
                            <div class="child-container">
                                <h4 style="margin: 0 0 12px 0; font-size: 13px; color: #475569;"><i class="bi bi-diagram-3"></i> Rincian Pemenuhan Dokumen per Unit Kompetensi:</h4>
                                <table class="sub-table">
                                    <thead>
                                        <tr>
                                            <th width="5%" style="text-align: center;"></th>
                                            <th width="45%">Kode & Judul Unit</th>
                                            <th width="15%" style="text-align: center;">Dokumen Diupload</th>
                                            <th width="15%" style="text-align: center;">Kekurangan Dokumen</th>
                                            <th width="20%" style="text-align: center;">Status Penilaian</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($peg['uks'] as $index => $uk)
                                            @php
                                                $uk_prog_color = ($uk['terkumpul'] >= $uk['target'] && $uk['target'] > 0) ? '#10b981' : ($uk['terkumpul'] > 0 ? '#f59e0b' : '#94a3b8');
                                                $uk_kurang = $uk['target'] - $uk['terkumpul'];
                                                if($uk_kurang < 0) $uk_kurang = 0;
                                                $detail_id = "detail_" . $pid . "_" . $index;
                                            @endphp
                                            <!-- Row Unit Kompetensi bisa diklik untuk melihat rincian -->
                                            <tr style="cursor: pointer; background: #fff;" onclick="toggleChild('{{ $detail_id }}', this)">
                                                <td style="text-align: center;"><i class="bi bi-chevron-down icon-toggle" style="color:#94a3b8; font-size:11px;"></i></td>
                                                <td><span class="text-id"><i class="bi bi-tag-fill"></i> {{ $uk['kode_unit'] }}</span><span style="color: #334155; font-weight: 500; display:block;">{{ $uk['judul_unit'] }}</span></td>
                                                <td style="text-align: center;"><div class="evidence-badge"><i class="bi bi-folder-fill" style="color: {{ $uk_prog_color }};"></i><span style="color: #1e293b;">{{ $uk['terkumpul'] }}</span><span class="text-target">/ {{ $uk['target'] }}</span></div></td>
                                                <td style="text-align: center;">
                                                    @if($uk_kurang > 0)
                                                        <span style="color: #ef4444; font-weight: 700; font-size: 12px;">- {{ $uk_kurang }} Dokumen</span>
                                                    @else
                                                        <span style="color: #10b981; font-weight: 700; font-size: 12px;"><i class="bi bi-check-lg"></i> Lengkap</span>
                                                    @endif
                                                </td>
                                                <td style="text-align: center;">{!! $uk['is_dinilai'] ? '<span class="badge badge-hijau" style="font-size:9px;">SELESAI DINILAI</span>' : '<span class="badge badge-kuning" style="font-size:9px;">BELUM DINILAI</span>' !!}</td>
                                            </tr>

                                            <!-- Rincian Daftar Dokumen (Disembunyikan default) -->
                                            <tr id="{{ $detail_id }}" style="display: none; background: #f8fafc;">
                                                <td colspan="5" style="padding: 15px 40px; border-bottom: 2px solid #e2e8f0;">
                                                    <div style="display: flex; gap: 40px;">
                                                        <!-- Daftar Kekurangan (Kiri) -->
                                                        <div style="flex: 1;">
                                                            <h5 style="font-size: 11px; font-weight: 800; color: #ef4444; margin-bottom: 10px; text-transform: uppercase;">
                                                                <i class="bi bi-x-circle-fill"></i> Belum Diupload
                                                            </h5>
                                                            <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #475569;">
                                                                @forelse($uk['rincian_belum'] as $belum)
                                                                    <li style="margin-bottom: 6px; display: flex; gap: 8px;"><i class="bi bi-dash" style="color:#cbd5e1;"></i> {{ $belum }}</li>
                                                                @empty
                                                                    <li style="color: #10b981; font-style: italic;">Semua dokumen telah dilengkapi.</li>
                                                                @endforelse
                                                            </ul>
                                                        </div>

                                                        <!-- Daftar Tersedia (Kanan) -->
                                                        <div style="flex: 1;">
                                                            <h5 style="font-size: 11px; font-weight: 800; color: #10b981; margin-bottom: 10px; text-transform: uppercase;">
                                                                <i class="bi bi-check-circle-fill"></i> Sudah Diupload
                                                            </h5>
                                                            <ul style="list-style: none; padding: 0; margin: 0; font-size: 12px; color: #475569;">
                                                                @forelse($uk['rincian_sudah'] as $sudah)
                                                                    <li style="margin-bottom: 6px; display: flex; gap: 8px;"><i class="bi bi-check2" style="color:#10b981;"></i> {{ $sudah }}</li>
                                                                @empty
                                                                    <li style="color: #94a3b8; font-style: italic;">Belum ada dokumen yang diupload.</li>
                                                                @endforelse
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </td>
                    </tr>
                    @endif

                @empty
                    <tr><td colspan="6" style="text-align:center; padding: 40px; color:#64748b;">Belum ada pegawai yang mendaftar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function toggleChild(childId, rowElement) {
        var childRow = document.getElementById(childId);
        var icon = rowElement.querySelector('.icon-toggle');
        if (!childRow) return;

        if (childRow.style.display === 'table-row') {
            childRow.style.display = 'none';
            rowElement.classList.remove('active');
            icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
        } else {
            childRow.style.display = 'table-row';
            rowElement.classList.add('active');
            icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
        }
    }

    function filterTable() {
        var input, filter, table, tr, tdName, tdJabatan, i, txtValueName, txtValueJabatan;
        input = document.getElementById("searchInput");
        filter = input.value.toUpperCase();
        table = document.getElementById("pegawaiTable");
        tr = table.getElementsByClassName("parent-row");

        for (i = 0; i < tr.length; i++) {
            tdName = tr[i].getElementsByTagName("td")[1];
            tdJabatan = tr[i].getElementsByTagName("td")[2];
            if (tdName || tdJabatan) {
                txtValueName = tdName.textContent || tdName.innerText;
                txtValueJabatan = tdJabatan.textContent || tdJabatan.innerText;
                if (txtValueName.toUpperCase().indexOf(filter) > -1 || txtValueJabatan.toUpperCase().indexOf(filter) > -1) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                    // Sembunyikan juga child row-nya saat di-search
                    let childId = tr[i].getAttribute('onclick').match(/'([^']+)'/)[1];
                    let childRow = document.getElementById(childId);
                    if(childRow) childRow.style.display = "none";
                }
            }
        }
    }
</script>
@endpush
