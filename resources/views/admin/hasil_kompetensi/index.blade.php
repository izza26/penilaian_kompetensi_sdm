@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Kelola hasil akhir penilaian kompetensi pegawai')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/hasil_kompetensi.css') }}">
    <style>
        .status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-align: center; display: inline-block; white-space: nowrap;}
        .status.kompeten { background-color: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;}
        .status.cukup { background-color: #fffbeb; color: #b45309; border: 1px solid #fde68a;}
        .status.belum { background-color: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; text-decoration: none; transition: 0.2s; white-space: nowrap; }
        .btn-cetak-excel:hover { background: #059669; transform: translateY(-2px); color: white;}
    </style>
@endpush

@section('content')
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon brown"><i class="bi bi-people"></i></div><div><h3>{{ $total_pegawai }}</h3><span>Total Pegawai</span></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-patch-check"></i></div><div><h3>{{ $jml_kompeten }}</h3><span>Kompeten (≥70)</span></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-exclamation-circle"></i></div><div><h3>{{ $jml_belum }}</h3><span>Belum Kompeten</span></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="bi bi-bar-chart"></i></div><div><h3>{{ $rata_rata }}</h3><span>Rata-rata Nilai</span></div></div>
    </div>

    <div class="result-card" style="padding: 25px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-top: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div><h3 style="margin: 0 0 5px 0; font-size: 16px; color: #0f172a;">Riwayat Penilaian Pegawai</h3><p style="margin: 0; font-size: 13px; color: #64748b;">Daftar nilai akhir kompetensi dari seluruh pegawai.</p></div>
            <a href="{{ route('admin.hasil_kompetensi.export') }}" class="btn-cetak-excel" target="_blank"><i class="bi bi-file-earmark-excel-fill"></i> Cetak Penilaian</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;"><th style="padding:12px;">No</th><th>Nama Pegawai</th><th>Jabatan</th><th>Unit Kompetensi</th><th>Nilai Akhir</th><th>Status</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($list_hasil as $index => $row)
                        @php 
                            $kategori = trim($row->kategori);
                            if (in_array($kategori, ['Sangat Kompeten', 'Kompeten'])) $class_status = 'kompeten';
                            elseif ($kategori === 'Cukup Kompeten') $class_status = 'cukup';
                            else $class_status = 'belum';
                        @endphp
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding:15px;">{{ $list_hasil->firstItem() + $index }}</td>
                            <td><b>{{ $row->pegawai_nama }}</b></td>
                            <td>{{ $row->jabatan }}</td>
                            <td><span style="font-size: 11px; font-weight: 700; color: #1e3a8a; display: block;">{{ $row->kode_unit }}</span><span style="font-size: 12px;">{{ $row->judul_unit }}</span></td>
                            <td class="nilai"><b>{{ number_format($row->nilai_akhir, 2) }}</b></td>
                            <td><span class="status {{ $class_status }}">{{ $kategori }}</span></td>
                            <td class="action-cell">
                                <a href="{{ route('admin.hasil_kompetensi.show', $row->penilaian_id) }}" class="action-btn view-btn" style="background:#eff6ff; color:#3b82f6; padding:6px 10px; border-radius:6px; display:inline-block;"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center; padding: 30px;">Belum ada data hasil penilaian.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div style="margin-top:20px;">{{ $list_hasil->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection 