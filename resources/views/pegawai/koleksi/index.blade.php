@extends('layouts.app')
@section('title', $view_title)
@section('page_title', $view_title)
@section('page_subtitle', $view_subtitle)

@push('styles')
    <style>
        .toolbar-box { background: #ffffff; border-radius: 50px; padding: 12px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #cbd5e1; box-shadow: 0 10px 30px rgba(0,0,0,0.02); gap: 15px; flex-wrap: wrap;}
        .toolbar-left { display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px;}
        .search-input { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 10px 16px; border-radius: 50px; flex: 1; border: 1px dashed #cbd5e1; transition: 0.2s;}
        .search-input:focus-within { border-color: #3e54a0; background: #fff;}
        .search-input i { color: #64748b; font-size: 14px;}
        .search-input input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #1e293b; font-family: inherit;}
        
        .toolbar-right { display: flex; align-items: center; gap: 10px;}
        .filter-select { padding: 10px 20px; font-size: 13px; font-weight: 600; color: #334155; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 50px; outline: none; cursor: pointer; transition: 0.2s;}
        .filter-select:focus { border-color: #3e54a0; background: #fff;}
        
        .btn-action { background: #3e54a0; color: white; border: none; padding: 10px 24px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; white-space: nowrap;}
        .btn-action:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
        .btn-reset { background: #fff1f2; color: #e11d48; border: none; width: 38px; height: 38px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; text-decoration: none; transition: 0.2s;}
        .btn-reset:hover { background: #e11d48; color: white;}
        
        .total-badge { background: #f4f7fe; color: #3e54a0; font-size: 12px; font-weight: 800; padding: 10px 20px; border-radius: 50px; white-space: nowrap;}

        .table-container { background: #fff; border-radius: 24px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 20px;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 24px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;}
        .styled-table td { padding: 18px 24px; border-bottom: 1px dashed #f1f5f9; vertical-align: top; }
        .styled-table tr:hover { background-color: #f8fafc; }
        .styled-table tr:last-child td { border-bottom: none; }
        
        .koleksi-name { font-weight: 700; color: #1e293b; font-size: 14px; margin-bottom: 6px; display: block;}
        .koleksi-desc { font-size: 12px; color: #64748b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; margin: 0;}
        .reg-no { font-family: monospace; font-size: 13px; color: #3e54a0; font-weight: 700; display: inline-block;}
        
        .badge-jenis { background: #eff6ff; color: #2563eb; font-size: 10px; font-weight: 800; padding: 6px 14px; border-radius: 50px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-status { background: #ecfdf5; color: #059669; font-size: 10px; font-weight: 800; padding: 6px 14px; border-radius: 50px; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-warning { background: #fffbeb; color: #b45309; font-size: 10px; font-weight: 800; padding: 6px 14px; border-radius: 50px;}
        
        .empty-state { text-align: center; padding: 60px 20px; }
        @media(max-width: 768px) { .toolbar-box { flex-direction: column; align-items: stretch;} .toolbar-right { flex-direction: column; align-items: stretch;} .total-badge { text-align: center; } }
    </style>
@endpush

@section('content')

    <form method="GET" action="{{ route('pegawai.koleksi.index') }}" class="toolbar-box">
        <div class="toolbar-left">
            <div class="search-input">
                <i class="bi bi-search"></i>
                <input type="text" name="cari" value="{{ request('cari') }}" placeholder="Ketik kata kunci pencarian...">
            </div>
            <div class="total-badge"><i class="bi bi-box-seam-fill"></i> Total: {{ number_format($total_semua) }} Data</div>
        </div>
        
        <div class="toolbar-right">
            <!-- Filter Dropdown Hanya Muncul Untuk Kurator/Register -->
            @if(!str_contains($jabatan, 'konservator') && !str_contains($jabatan, 'penata pameran') && !str_contains($jabatan, 'edukator') && !str_contains($jabatan, 'humas'))
                <select name="jenis" class="filter-select">
                    <option value="">-- Semua Jenis Koleksi --</option>
                    @foreach($jenis_koleksi as $jk)
                        <option value="{{ $jk->id_jenis }}" {{ request('jenis') == $jk->id_jenis ? 'selected' : '' }}>
                            {{ $jk->kategori_koleksi }}
                        </option>
                    @endforeach
                </select>
            @endif
            
            <button type="submit" class="btn-action">Filter Data</button>
            @if(request()->has('cari') || request()->has('jenis'))
                <a href="{{ route('pegawai.koleksi.index') }}" class="btn-reset" title="Reset Filter"><i class="bi bi-arrow-clockwise"></i></a>
            @endif
        </div>
    </form>

    <div class="table-container">
        <div style="overflow-x: auto;">
            <table class="styled-table">
                
                <!-- TABEL KONSERVATOR -->
                @if(str_contains($jabatan, 'konservator'))
                    <thead>
                        <tr>
                            <th width="15%">No. Registrasi</th>
                            <th width="35%">Gejala Kerusakan Fisik</th>
                            <th width="20%" style="text-align: center;">Tingkat Kerusakan</th>
                            <th width="15%" style="text-align: center;">Tgl Inspeksi</th>
                            <th width="15%" style="text-align: center;">Urgensi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td><span class="reg-no">{{ $item->nomor_registrasi }}</span></td>
                                <td><span class="koleksi-desc">{{ $item->gejala_kerusakan_spesifik ?? '-' }}</span></td>
                                <td style="text-align: center;"><span class="badge-status">{{ $item->status_tingkat_kerusakan }}</span></td>
                                <td style="text-align: center;"><div style="font-size: 12px;"><i class="bi bi-calendar3"></i> {{ $item->tanggal_inspeksi }}</div></td>
                                <td style="text-align: center;"><span class="badge-warning">{{ $item->tingkat_urgensi_penyelamatan }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i><h3>Data Tidak Ditemukan</h3></div></td></tr>
                        @endforelse
                    </tbody>

                <!-- TABEL PENATA PAMERAN -->
                @elseif(str_contains($jabatan, 'penata pameran'))
                    <thead>
                        <tr>
                            <th width="25%">Nama Pameran</th>
                            <th width="25%">Tema & Pesan Utama</th>
                            <th width="15%" style="text-align: center;">Kategori</th>
                            <th width="15%" style="text-align: center;">Status Proyek</th>
                            <th width="20%" style="text-align: center;">Jadwal Pembukaan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td><span class="koleksi-name">{{ $item->nama_pameran }}</span></td>
                                <td><span class="koleksi-desc"><b>{{ $item->tema_pameran }}</b><br>{{ $item->pesan_utama_pameran }}</span></td>
                                <td style="text-align: center;"><span class="badge-jenis">{{ $item->kategori_pameran }}</span></td>
                                <td style="text-align: center;"><span class="badge-status">{{ $item->status_proyek }}</span></td>
                                <td style="text-align: center;"><div style="font-size: 12px;"><i class="bi bi-calendar3"></i> {{ $item->tanggal_pembukaan }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i><h3>Data Tidak Ditemukan</h3></div></td></tr>
                        @endforelse
                    </tbody>

                <!-- TABEL EDUKATOR -->
                @elseif(str_contains($jabatan, 'edukator'))
                    <thead>
                        <tr>
                            <th width="35%">Tujuan Program Publik</th>
                            <th width="30%">Ruang Lingkup</th>
                            <th width="15%" style="text-align: center;">Status Kelayakan</th>
                            <th width="20%" style="text-align: center;">Tanggal Disusun</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td><span class="koleksi-name">{{ $item->tujuan_program }}</span></td>
                                <td><span class="koleksi-desc">{{ $item->ruang_lingkup_program }}</span></td>
                                <td style="text-align: center;"><span class="badge-status">{{ $item->status_kelayakan }}</span></td>
                                <td style="text-align: center;"><div style="font-size: 12px;"><i class="bi bi-calendar3"></i> {{ $item->tanggal_disusun }}</div></td>
                            </tr>
                        @empty
                            <tr><td colspan="4"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i><h3>Data Tidak Ditemukan</h3></div></td></tr>
                        @endforelse
                    </tbody>

                <!-- TABEL HUMAS -->
                @elseif(str_contains($jabatan, 'humas'))
                    <thead>
                        <tr>
                            <th width="35%">Judul Kampanye / Publikasi</th>
                            <th width="25%">Periode Kampanye</th>
                            <th width="40%">Target Audiens</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td><span class="koleksi-name">{{ $item->judul_tema_publikasi }}</span></td>
                                <td><span class="badge-warning">{{ $item->periode_kampanye }}</span></td>
                                <td><span class="koleksi-desc">{{ $item->target_audiens }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i><h3>Data Tidak Ditemukan</h3></div></td></tr>
                        @endforelse
                    </tbody>

                <!-- TABEL DEFAULT (REGISTER / KURATOR) -->
                @else
                    <thead>
                        <tr>
                            <th width="15%">No. Registrasi</th>
                            <th width="40%">Identitas Koleksi</th>
                            <th width="15%" style="text-align: center;">Jenis Koleksi</th>
                            <th width="15%" style="text-align: center;">Tanggal Masuk</th>
                            <th width="15%" style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data as $item)
                            <tr>
                                <td><span class="reg-no">{{ $item->nomor_registrasi }}</span></td>
                                <td>
                                    <span class="koleksi-name">{{ $item->nama_koleksi }}</span>
                                    <p class="koleksi-desc" title="{{ $item->deskripsi }}">{{ $item->deskripsi ?? 'Tidak ada deskripsi rinci.' }}</p>
                                </td>
                                <td style="text-align: center;"><span class="badge-jenis">{{ $item->kategori_koleksi ?? 'Umum' }}</span></td>
                                <td style="text-align: center;"><div style="font-size: 13px;"><i class="bi bi-calendar3"></i> {{ date('d M Y', strtotime($item->tanggal_masuk)) }}</div></td>
                                <td style="text-align: center;"><span class="badge-status">{{ $item->status_koleksi }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state"><i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i><h3>Data Tidak Ditemukan</h3></div></td></tr>
                        @endforelse
                    </tbody>
                @endif

            </table>
        </div>
    </div>

    <!-- PAGINATION -->
    <div style="margin-top: 10px;">
        {{ $data->appends(request()->query())->links('pagination::bootstrap-4') }}
    </div>

@endsection