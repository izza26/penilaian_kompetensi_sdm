@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Kelola hasil akhir penilaian kompetensi pegawai')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/hasil_kompetensi.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Status Badge (Pill-shape) */
        .status { padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; text-align: center; display: inline-block; white-space: nowrap; letter-spacing: 0.5px;}
        .status.kompeten { background-color: #ecfdf5; color: #059669; }
        .status.cukup { background-color: #fffbeb; color: #b45309; }
        .status.belum { background-color: #fff1f2; color: #f43f5e; }

        /* Tombol Cetak (Pill-shape) */
        .btn-cetak-excel {
            background: #3e54a0; color: white; padding: 0 24px; height: 44px;
            border-radius: 50px; border: none; font-weight: 600; display: inline-flex;
            align-items: center; gap: 8px; font-size: 13px; text-decoration: none;
            transition: 0.2s; white-space: nowrap;
        }
        .btn-cetak-excel:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }

        /* Aksi */
        .action-cell { white-space: nowrap; }
        .action-btn { transition: 0.2s; border: none; cursor: pointer; }
        .action-btn:hover { transform: translateY(-2px); filter: brightness(0.9); }
    </style>
@endpush

@section('content')
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">

    @if(session('success'))
        <script>document.addEventListener("DOMContentLoaded", function() { Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2500, showConfirmButton: false }); });</script>
    @endif
    @if(session('error'))
        <script>document.addEventListener("DOMContentLoaded", function() { Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" }); });</script>
    @endif

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
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding:12px;">No</th>
                        <th>Nama Pegawai</th>
                        <th>Jabatan</th>
                        <th>Unit Kompetensi</th>
                        <th>Nilai Akhir</th>
                        <th>Status</th>
                        <th width="20%">Alasan / Catatan Sistem</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
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

                            <td>
                                @if($row->alasan_sistem != '-')
                                    <span style="font-size: 11px; color: #dc2626; line-height: 1.4; display: block; background: #fef2f2; padding: 6px 10px; border-radius: 6px; border: 1px solid #fecaca;">
                                        <i class="bi bi-info-circle-fill"></i> {{ $row->alasan_sistem }}
                                    </span>
                                @else
                                    <span style="color: #cbd5e1; font-weight: bold;">-</span>
                                @endif
                            </td>

                            <td class="action-cell" style="text-align: center;">
                                <!-- Tombol Lihat Detail (Semua Role Bisa) -->
                                <a href="{{ url('/admin/hasil-kompetensi/detail/' . $row->penilaian_id) }}" class="action-btn view-btn" style="background:#eff6ff; color:#3b82f6; padding:6px 10px; border-radius:6px; display:inline-block;" title="Lihat Detail"><i class="bi bi-eye"></i></a>

                                <!-- Tombol Edit & Hapus (Hanya Superadmin) -->
                                @if(Auth::user()->role == 'superadmin')
                                    <!-- Edit dilempar ke route Takeover Pimpinan (agar ngitung ulang PM) -->
                                    <a href="{{ route('admin.tim_saya.beri_nilai', [$row->pegawai_id ?? 0, $row->kode_unit]) }}" class="action-btn edit-btn" style="background:#fffbeb; color:#d97706; padding:6px 10px; border-radius:6px; display:inline-block; margin-left: 4px;" title="Revisi Skor"><i class="bi bi-pencil-square"></i></a>

                                    <button type="button" class="action-btn delete-btn" style="background:#fef2f2; color:#ef4444; border:none; padding:6px 10px; border-radius:6px; cursor:pointer; margin-left: 4px;" onclick="hapusHasil('{{ $row->penilaian_id }}')" title="Hapus Riwayat"><i class="bi bi-trash"></i></button>

                                    <!-- Form Hapus Tak Terlihat -->
                                    <form id="form-hapus-{{ $row->penilaian_id }}" action="{{ route('admin.hasil_kompetensi.destroy', $row->penilaian_id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center; padding: 30px;">Belum ada data hasil penilaian.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div style="margin-top:20px;">{{ $list_hasil->links('pagination::bootstrap-4') }}</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function hapusHasil(id) {
        Swal.fire({
            title: 'Hapus Hasil Penilaian?',
            text: "Data skor dan riwayat penilaian pegawai ini akan dihapus permanen! Pegawai harus dinilai ulang dari awal.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            cancelButtonColor: '#94a3b8',
            confirmButtonText: 'Ya, Hapus Data!'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('form-hapus-' + id).submit();
            }
        });
    }
</script>
@endpush
