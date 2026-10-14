@extends('layouts.app')
@section('title', 'Daftar Evidence Penilaian')
@section('page_title', 'Daftar Evidence & Analisis')
@section('page_subtitle', 'Melihat rincian status parsing dokumen kompetensi')
@section('back_url', route('pegawai.penilaian.index'))

@push('styles')
    <style>
        .custom-hero { background: #ffffff; border-radius: 24px; padding: 24px 32px; display: flex; align-items: center; gap: 20px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02); margin-bottom: 24px; }
        .custom-hero-icon { width: 64px; height: 64px; background: #f4f7fe; color: #3e54a0; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 32px; flex-shrink: 0; }
        .custom-hero-text flex: 1;
        .custom-hero-text h2 { margin: 0 0 6px 0; font-size: 20px; font-weight: 700; color: #1e293b; }
        .custom-hero-text p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }
        .custom-hero-badge { display: inline-flex; align-items: center; gap: 6px; background: #f4f7fe; color: #3e54a0; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; margin-top: 12px; }
        
        .filter-box { background: #ffffff; border-radius: 24px; padding: 20px 32px; border: none; margin-bottom: 24px; display: flex; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); box-sizing: border-box; width: 100%;}
        .filter-box label { font-weight: 700; color: #475569; font-size: 13px; white-space: nowrap; display: flex; align-items: center; gap: 8px;}
        .filter-box select { flex: 1; min-width: 0; padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; border-radius: 50px; font-size: 13px; outline: none; cursor: pointer; color: #1e293b; background-color: #f9fafb; font-family: inherit; transition: 0.2s; box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;}
        .filter-box select:focus { border-color: #3e54a0; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}

        .unit-card { background: #fff; border-radius: 24px; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; overflow: hidden; }
        .unit-header { background: #ffffff; padding: 24px 32px; border-bottom: 1px dashed #e2e8f0; display: flex; flex-direction: column; gap: 8px; }
        .unit-header h3 { margin: 0; font-size: 16px; color: #1e293b; font-weight: 700; line-height: 1.4; }
        .unit-header span { font-size: 11px; font-weight: 700; color: #3e54a0; display: inline-flex; align-items: center; gap: 6px; background: #f4f7fe; padding: 6px 14px; border-radius: 50px; align-self: flex-start; }
        
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background-color: transparent; color: #64748b; padding: 16px 24px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700; letter-spacing: 0.5px; }
        .styled-table td { padding: 20px 24px; border-bottom: 1px dashed #e2e8f0; vertical-align: middle; font-size: 13px; color: #334155; }
        .styled-table tr:hover { background-color: #f8fafc; }
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 700; white-space: nowrap; display: inline-flex; align-items: center; gap: 4px; text-transform: uppercase; border: none; letter-spacing: 0.5px;}
        
        .btn-upload { background-color: #3e54a0 !important; color: white !important; padding: 0 16px; height: 36px; border-radius: 50px; text-decoration: none; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 6px; transition: 0.2s; white-space: nowrap; border: none; }
        .btn-upload:hover { background-color: #2b3a70 !important; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62,84,160,.2); }
        .btn-locked { background: #f1f5f9; color: #94a3b8; padding: 0 16px; height: 36px; border-radius: 50px; font-size: 11px; font-weight: 700; display: inline-flex; justify-content: center; align-items: center; gap: 6px; cursor: not-allowed; white-space: nowrap; border: none; }
        .empty-state { padding: 60px 0; text-align: center; background: #fff; border-radius: 24px; border: 1px dashed #cbd5e1; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .empty-state i { font-size: 50px; color: #cbd5e1; }
        .empty-state h3 { margin-top: 15px; color: #1e293b; font-size: 18px; font-weight: 700; margin-bottom: 8px;}
        .empty-state p { color: #64748b; font-size: 13px; margin: 0;}
    </style>
@endpush

@section('content')
<div class="custom-hero">
    <div class="custom-hero-icon"><i class="bi bi-cpu"></i></div>
    <div class="custom-hero-text">
        <h2>Daftar Aktivitas & Status Parsing</h2>
        <p>Pantau status seluruh evidence yang telah Anda kumpulkan untuk dianalisis oleh sistem.</p>
        <div class="custom-hero-badge"><i class="bi bi-robot"></i> Metode: Profile Matching Auto-Parse</div>
    </div>
</div>

<div class="filter-box">
    <label><i class="bi bi-funnel-fill" style="color: #3e54a0;"></i> Pilih Unit Kompetensi</label>
    <form method="GET" action="{{ route('pegawai.penilaian.list') }}" style="flex: 1; display: flex;">
        <select name="kode_unit" onchange="this.form.submit()">
            <option value="" style="font-weight: bold;" {{ empty($kode_unit_selected) ? 'selected' : '' }}>-- Tampilkan Semua Unit --</option>
            @foreach($units as $u)
                <option value="{{ $u->kode_unit }}" {{ ($kode_unit_selected == $u->kode_unit) ? 'selected' : '' }}>[{{ $u->kode_unit }}] - {{ $u->judul_unit }}</option>
            @endforeach
        </select>
    </form>
</div>

@if($paginatedEvidence->isEmpty())
    <div class="empty-state"><i class="bi bi-folder-x"></i><h3>Data Aktivitas Kosong</h3><p>Tidak ditemukan daftar aktivitas untuk unit kompetensi ini.</p></div>
@else
    <div class="unit-card" style="display: block;">
        <div class="unit-header">
            <span><i class="bi bi-tags-fill"></i> {{ empty($kode_unit_selected) ? 'Semua Unit Kompetensi' : $kode_unit_selected }}</span>
            <h3>Daftar Aktivitas & Evidence</h3>
        </div>
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr><th width="5%" style="text-align: center;">No</th><th width="45%">Rincian Aktivitas</th><th width="15%" style="text-align: center;">Upload Terakhir</th><th width="15%" style="text-align: center;">Status Upload</th><th width="20%" style="text-align: center;">Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($paginatedEvidence as $index => $ev)
                        @php $is_uploaded = $ev->is_uploaded > 0; @endphp
                        <tr>
                            <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $paginatedEvidence->firstItem() + $index }}</td>
                            <td>
                                <div style="font-weight: 600; color: #1e293b; margin-bottom: 4px; font-size: 13px;">
                                    @if(empty($kode_unit_selected)) <span style="color: #3e54a0; font-size: 11px; font-weight: 700;">{{ $ev->kode_unit }}</span><br> @endif
                                    {{ $ev->detail_aktivitas }}
                                </div>
                            </td>
                            <td style="text-align: center; font-size: 12px; font-weight: 500;">{{ $is_uploaded ? date('d/m/Y', strtotime($ev->tgl_upload)) : '-' }}</td>
                            <td style="text-align: center;">
                                @if($is_uploaded) <span class="badge" style="background: #ecfdf5; color: #059669;"><i class="bi bi-check-circle-fill"></i> Uploaded</span>
                                @else <span class="badge" style="background: #fff1f2; color: #e11d48;"><i class="bi bi-x-circle-fill"></i> Belum Upload</span> @endif
                            </td>
                            <td style="text-align: center;">
                                @if($is_uploaded) <a href="{{ route('pegawai.penilaian.show', ['aktivitas_id' => $ev->aktivitas_id]) }}" class="btn-upload"><i class="bi bi-search"></i> Cek Analisis Sistem</a>
                                @else <button class="btn-locked" disabled><i class="bi bi-slash-circle"></i> Kosong</button> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div> 
        <div style="padding: 24px; background: white;">
            {{ $paginatedEvidence->appends(['kode_unit' => $kode_unit_selected])->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endif
@endsection