@extends('layouts.app')
@section('title', 'Hasil Kompetensi')
@section('page_title', 'Hasil Kompetensi')
@section('page_subtitle', 'Kelola hasil akhir penilaian kompetensi pegawai')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .widget-container { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 24px; }
        .widget-box { background: #fff; padding: 24px; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 16px; transition: 0.3s;}
        .widget-box:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(62, 84, 160, 0.08); }
        .widget-icon { width: 56px; height: 56px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 24px; flex-shrink: 0;}
        .widget-info { display: flex; flex-direction: column; gap: 4px;}
        .widget-info h4 { margin: 0; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;}
        .widget-info p { margin: 0; font-size: 28px; font-weight: 800; color: #1e293b; line-height: 1;}
        
        .w-sangat .widget-icon { background: #ecfdf5; color: #10b981; }
        .w-kompeten .widget-icon { background: #f4f7fe; color: #3e54a0; }
        .w-cukup .widget-icon { background: #fffbeb; color: #d97706; }
        .w-belum .widget-icon { background: #fff1f2; color: #e11d48; }

        .filter-box { background: #ffffff; border-radius: 24px; padding: 20px 32px; border: none; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(0,0,0,0.02); flex-wrap: wrap; gap: 16px;}
        .filter-left { display: flex; align-items: center; gap: 16px; flex: 1; flex-wrap: wrap;}
        .filter-left label { font-size: 13px; font-weight: 700; color: #475569; display: flex; align-items: center; gap: 8px;}
        .filter-left select { padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; border-radius: 50px; font-size: 13px; outline: none; cursor: pointer; background: #f9fafb; transition: 0.2s; color: #1e293b; font-family: inherit;}
        .filter-left select:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        
        .btn-cetak-excel { background: #10b981; color: white; padding: 0 24px; height: 48px; border-radius: 50px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; border: none;}
        .btn-cetak-excel:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(16, 185, 129, 0.2);}

        .table-card { background: #fff; padding: 32px; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .table-card h3 { margin: 0 0 8px 0; font-size: 18px; color: #1e293b; font-weight: 700; }
        .table-card p { margin: 0 0 24px 0; font-size: 13px; color: #64748b; }
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700;}
        .styled-table td { padding: 16px 20px; border-bottom: 1px dashed #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle;}
        .row-clickable { transition: 0.2s; }
        .row-clickable:hover { background-color: #f8fafc; cursor: pointer; }
        
        .badge { padding: 6px 16px; border-radius: 50px; font-size: 10px; font-weight: 700; width: auto; min-width: 110px; text-align: center; text-transform: uppercase; display: inline-block; letter-spacing: 0.5px;}
        .badge-hijau { background: #ecfdf5; color: #059669; border: none; } 
        .badge-kuning { background: #fffbeb; color: #d97706; border: none; } 
        .badge-merah { background: #fff1f2; color: #e11d48; border: none; } 
        
        .btn-aksi { width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; text-decoration: none; transition: 0.2s;}
        .btn-detail { background: #f4f7fe; color: #3e54a0; }
        .btn-detail:hover { background: #3e54a0; color: white; transform: translateY(-2px);}
        .btn-hapus { background: #fff1f2; color: #e11d48; margin-left: 5px;}
        .btn-hapus:hover { background: #e11d48; color: white; transform: translateY(-2px);}

        @media(max-width: 992px) { .widget-container { grid-template-columns: repeat(2, 1fr); } }
        @media(max-width: 768px) { .widget-container { grid-template-columns: 1fr; } .filter-box { flex-direction: column; align-items: stretch;} }
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
            <label><i class="bi bi-funnel-fill" style="color: #3e54a0;"></i> Filter Penilaian:</label>
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
                            <!-- Warna Teks ID Kode Unit diubah ke #3e54a0 -->
                            <span style="font-size: 11px; font-weight: 700; color: #3e54a0; display: block; margin-bottom: 2px;">{{ $r->kode_unit }}</span>
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