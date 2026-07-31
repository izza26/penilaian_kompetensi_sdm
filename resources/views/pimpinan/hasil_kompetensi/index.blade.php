@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Kelola hasil akhir penilaian kompetensi pegawai')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .widget-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 25px; }
        .widget-box { background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; transition: 0.2s;}
        .widget-box:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(0,0,0,0.04); }
        .widget-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; justify-content: center; align-items: center; font-size: 22px; flex-shrink: 0;}
        .widget-info { display: flex; flex-direction: column; gap: 2px;}
        .widget-info h4 { margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;}
        .widget-info p { margin: 0; font-size: 24px; font-weight: 800; color: #0f172a;}
        .w-sangat .widget-icon { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0;}
        .w-kompeten .widget-icon { background: #f0fdfa; color: #0d9488; border: 1px solid #bbf7d0;}
        .w-cukup .widget-icon { background: #fffbeb; color: #d97706; border: 1px solid #fde68a;}
        .w-belum .widget-icon { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}

        .filter-box { background: #ffffff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center;}
        .filter-left { display: flex; align-items: center; gap: 15px; flex: 1;}
        .filter-left label { font-size: 13px; font-weight: 600; color: #475569;}
        .filter-left select { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; outline: none; cursor: pointer;}
        .btn-cetak-excel { background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px;}
        .btn-cetak-excel:hover { background: #059669; color: white;}

        .table-card { background: #fff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; }
        .table-card h3 { margin: 0 0 5px 0; font-size: 16px; color: #0f172a; }
        .table-card p { margin: 0 0 20px 0; font-size: 13px; color: #64748b; }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; }
        .row-clickable:hover { background-color: #f8fafc; cursor: pointer; }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; width: 120px; text-align: center; text-transform: uppercase; display: inline-block;}
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; } 
        .badge-kuning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; } 
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; } 
        
        .btn-aksi { width: 34px; height: 34px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; text-decoration: none;}
        .btn-detail { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;}
        .btn-hapus { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; margin-left: 5px;}
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

    <div class="widget-container">
        <div class="widget-box w-sangat"><div class="widget-icon"><i class="bi bi-star-fill"></i></div><div class="widget-info"><h4>Sangat Kompeten</h4><p>{{ $stat_sangat_kompeten }}</p></div></div>
        <div class="widget-box w-kompeten"><div class="widget-icon"><i class="bi bi-check-circle-fill"></i></div><div class="widget-info"><h4>Kompeten</h4><p>{{ $stat_kompeten }}</p></div></div>
        <div class="widget-box w-cukup"><div class="widget-icon"><i class="bi bi-exclamation-circle-fill"></i></div><div class="widget-info"><h4>Cukup Kompeten</h4><p>{{ $stat_cukup_kompeten }}</p></div></div>
        <div class="widget-box w-belum"><div class="widget-icon"><i class="bi bi-x-circle-fill"></i></div><div class="widget-info"><h4>Belum Kompeten</h4><p>{{ $stat_belum_kompeten }}</p></div></div>
    </div>

    <div class="filter-box">
        <form method="GET" action="{{ route('pimpinan.hasil_kompetensi.index') }}" id="formFilter" class="filter-left">
            <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Filter Penilaian:</label>
            <select name="periode" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL">Semua Jabatan</option>
                @foreach($list_periode as $p)
                    <option value="{{ $p->nama_periode }}" {{ $filter_periode == $p->nama_periode ? 'selected' : '' }}>{{ $p->nama_periode }}</option>
                @endforeach
            </select>
            <select name="bulan" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL">Semua Bulan</option>
                @foreach($arr_bulan as $num => $str)
                    <option value="{{ $num }}" {{ $filter_bulan == $num ? 'selected' : '' }}>{{ $str }}</option>
                @endforeach
            </select>
            <select name="tahun" onchange="document.getElementById('formFilter').submit();">
                <option value="ALL">Semua Tahun</option>
                @foreach($arr_tahun as $thn)
                    <option value="{{ $thn }}" {{ $filter_tahun == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                @endforeach
            </select>
        </form>
        
        <!-- Link Export menyambung filter yang ada -->
        <a href="{{ route('pimpinan.hasil_kompetensi.export', ['periode' => $filter_periode, 'bulan' => $filter_bulan, 'tahun' => $filter_tahun]) }}" class="btn-cetak-excel" target="_blank">
            <i class="bi bi-file-earmark-excel-fill"></i> Cetak Penilaian
        </a>
    </div>

    <div class="table-card">
        <h3>Riwayat Penilaian Pegawai</h3>
        <p>Daftar nilai akhir kompetensi yang telah disahkan oleh Pimpinan.</p>
        <table class="styled-table">
            <thead>
                <tr>
                    <th width="5%" style="text-align: center;">No</th>
                    <th width="20%">Nama Pegawai</th>
                    <th width="30%">Unit Kompetensi</th>
                    <th width="15%" style="text-align: center;">Tgl Disahkan</th>
                    <th width="10%" style="text-align: center;">Skor (100)</th>
                    <th width="10%" style="text-align: center;">Kategori</th>
                    <th width="10%" style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($list_riwayat as $index => $r)
                    @php
                        $kat = trim($r->kategori);
                        if ($kat === "Sangat Kompeten" || $kat === "Kompeten") $badge_class = 'badge-hijau';
                        elseif ($kat === "Cukup Kompeten") $badge_class = 'badge-kuning';
                        else $badge_class = 'badge-merah';
                    @endphp
                    <tr class="row-clickable" onclick="window.location.href='{{ route('pimpinan.hasil_kompetensi.show', $r->penilaian_id) }}'">
                        <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                        <td>
                            <b style="color: #0f172a; font-size: 13px;">{{ $r->pegawai_nama }}</b><br>
                            <span style="font-size: 12px; color: #64748b;">{{ $r->jabatan }}</span>
                        </td>
                        <td>
                            <span style="font-size: 11px; font-weight: 700; color: #A08348; display: block;">{{ $r->kode_unit }}</span>
                            <span style="color: #334155; font-weight: 500; font-size: 13px;">{{ $r->judul_unit }}</span>
                        </td>
                        <td style="text-align: center; font-size: 12px;">{{ date('d M Y', strtotime($r->waktu_submit)) }}</td>
                        <td style="text-align: center;"><b style="font-size: 16px; color: #0f172a;">{{ number_format($r->nilai_akhir, 2) }}</b></td>
                        <td style="text-align: center;"><span class="badge {{ $badge_class }}">{{ $r->kategori }}</span></td>
                        <td style="text-align: center;" onclick="event.stopPropagation();">
                            <a href="{{ route('pimpinan.hasil_kompetensi.show', $r->penilaian_id) }}" class="btn-aksi btn-detail"><i class="bi bi-eye-fill"></i></a>
                            <form action="{{ route('pimpinan.hasil_kompetensi.destroy', $r->penilaian_id) }}" method="POST" style="display:inline;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-aksi btn-hapus" onclick="return confirm('Yakin ingin menghapus riwayat penilaian ini?')"><i class="bi bi-trash-fill"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">Belum ada data penilaian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection