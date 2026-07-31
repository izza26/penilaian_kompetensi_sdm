@extends('layouts.app')
@section('title', 'Daftar Evidence Penilaian')
@section('page_title', 'Daftar Evidence & Analisis')
@section('page_subtitle', 'Melihat rincian status parsing dokumen kompetensi')
@section('back_url', route('pegawai.penilaian.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/penilaian_list.css') }}">
@endpush

@section('content')
<div class="custom-hero">
    <div class="custom-hero-icon"><i class="bi bi-cpu"></i></div>
    <div class="custom-hero-text">
        <h2>Daftar Aktivitas & Status Parsing</h2>
        <p>Pantau status seluruh evidence yang telah Anda kumpulkan untuk dianalisis oleh sistem.</p>
        <div class="custom-hero-badge" style="display:inline-block; background:rgba(255,255,255,0.2); padding: 4px 10px; border-radius:6px; font-size:11px; margin-top:8px;">
            <i class="bi bi-robot"></i> Metode: Profile Matching Auto-Parse
        </div>
    </div>
</div>

@if(session('error'))
    <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; border: 1px solid #fecaca;">
        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
    </div>
@endif

<div class="filter-box">
    <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Pilih Unit Kompetensi</label>
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
    <div class="empty-state">
        <i class="bi bi-folder-x"></i><h3>Data Aktivitas Kosong</h3><p>Tidak ditemukan daftar aktivitas untuk unit kompetensi ini.</p>
    </div>
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
                                <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px; font-size: 13px;">
                                    @if(empty($kode_unit_selected)) <span style="color: #A08338; font-size: 11px; font-weight: 700;">{{ $ev->kode_unit }}</span><br> @endif
                                    {{ $ev->detail_aktivitas }}
                                </div>
                            </td>
                            <td style="text-align: center; font-size: 12px; font-weight: 500;">{{ $is_uploaded ? date('d/m/Y', strtotime($ev->tgl_upload)) : '-' }}</td>
                            <td style="text-align: center;">
                                @if($is_uploaded) <span class="badge" style="background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;"><i class="bi bi-check-circle"></i> Uploaded</span>
                                @else <span class="badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;"><i class="bi bi-x-circle"></i> Belum Upload</span> @endif
                            </td>
                            <td style="text-align: center;">
                                @if($is_uploaded) <a href="{{ route('pegawai.penilaian.show', ['aktivitas_id' => $ev->aktivitas_id]) }}" class="btn-upload" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe;"><i class="bi bi-search"></i> Cek Analisis Sistem</a>
                                @else <button class="btn-locked" disabled><i class="bi bi-slash-circle"></i> Kosong</button> @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div> 
        <div style="margin-top: 20px;">
            {{ $paginatedEvidence->appends(['kode_unit' => $kode_unit_selected])->links('pagination::bootstrap-4') }}
        </div>
    </div>
@endif
@endsection