@extends('layouts.app')
@section('title', 'Form Penilaian')
@section('page_title', $is_update ? 'Edit Penilaian' : 'Form Penilaian')
@section('page_subtitle', 'Lakukan penilaian kompetensi pegawai')
@section('back_url', route('admin.penilaian.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_admin/form_penilaian.css') }}">
    <style>
        .score-radio-group { display: flex; gap: 10px; margin-top: 10px; }
        .score-radio-group input[type="radio"] { display: none; }
        .score-label { width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 600; color: #495057; cursor: pointer; transition: 0.2s;}
        .score-radio-group input[type="radio"]:checked + .score-label { background-color: #A15D33; color: white; border-color: #A15D33;}
        .yesno-label { padding: 8px 20px; border: 2px solid #dee2e6; border-radius: 8px; font-weight: 500; color: #495057; cursor: pointer; transition: 0.2s;}
        .score-radio-group input[type="radio"]:checked + .yesno-label { background-color: #A15D33; color: white; border-color: #A15D33;}
        
        .page-card { background: transparent; box-shadow: none; border: none; padding: 0;}
        .detail-card { background: #fff; padding: 25px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 10px rgba(0,0,0,0.02);}
    </style>
@endpush

@section('content')
<form method="POST" action="{{ route('admin.penilaian.store', [$pegawai_id, $kode_unit]) }}">
    @csrf
    <input type="hidden" name="is_update" value="{{ $is_update ? 1 : 0 }}">
    @if($is_update) <input type="hidden" name="penilaian_id_lama" value="{{ $penilaian_lama->penilaian_id }}"> @endif

    <div class="page-card">
        <div class="detail-card">
            <div class="pegawai-summary" style="display: flex; align-items: center; gap: 20px;">
                @php $words = explode(" ", $pegawai->pegawai_nama); $inisial = ""; foreach ($words as $w) { if (!empty($w)) $inisial .= strtoupper($w[0]); } $inisial = substr($inisial, 0, 2); @endphp
                <div class="avatar" style="width: 60px; height: 60px; background: #b56c35; color: white; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 20px; font-weight: bold;">{{ $inisial }}</div>
                <div class="pegawai-info">
                    <h3 style="margin: 0 0 5px 0; font-size: 18px;">{{ $pegawai->pegawai_nama }}</h3>
                    <p style="margin: 0 0 8px 0; color: #64748b;">{{ $pegawai->jabatan }} • {{ $pegawai->unit_kerja ?? 'Museum Geologi' }}</p>
                    <span style="background:{{ $is_update ? '#d1fae5' : '#fff3cd' }}; color:{{ $is_update ? '#065f46' : '#198754' }}; padding:5px 15px; border-radius:20px; font-size:11px; font-weight:bold;">
                        {{ $is_update ? 'Sudah Dinilai' : 'Menunggu Penilaian' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="detail-card">
            <h4 style="margin:0 0 15px 0; font-size: 16px; border-bottom:1px solid #eee; padding-bottom:10px;">Informasi Kompetensi</h4>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div><label style="color:#64748b; font-size:12px;">Unit Kompetensi</label><div style="font-weight:600;">{{ $kode_unit }}</div></div>
                <div><label style="color:#64748b; font-size:12px;">Elemen Kompetensi</label><div style="font-weight:600;">{{ $unit_info->elemen_kompetensi ?? '-' }}</div></div>
                <div><label style="color:#64748b; font-size:12px;">Aktivitas</label><div style="font-weight:600;">Seluruh Aktivitas Terpeta</div></div>
                <div><label style="color:#64748b; font-size:12px;">Jumlah Instrumen</label><div style="font-weight:600;">{{ $daftar_pertanyaan->count() }} Pertanyaan</div></div>
            </div>
        </div>

        @forelse ($daftar_pertanyaan as $index => $q)
            @php 
                $akt_id = $q->aktivitas_id; 
                $default_skor = $is_update && isset($skor_lama_array[$akt_id]) ? $skor_lama_array[$akt_id] : 0;
            @endphp
            <div class="detail-card">
                <h4 style="margin:0 0 10px 0; font-size: 15px;">Pertanyaan {{ $index + 1 }} <small style="color:#aaa; font-weight:normal;">({{ $akt_id }})</small></h4>
                
                <!-- GANTI deskripsi_skor MENJADI kriteria_kompetens -->
                <p style="color:#334155; margin-bottom:15px; line-height: 1.5;">
                    <strong>[{{ $q->detail_aktivitas }}]</strong><br>
                    {{ $q->kriteria_kompetens ?? 'Kriteria KUK belum diatur.' }}
                </p>

                <!-- STANDARISASI KE SKALA 1-5 -->
                <div class="score-radio-group">
                    @for ($i = 1; $i <= 5; $i++)
                        <input type="radio" id="skor_{{ $akt_id }}_{{ $i }}" name="skor[{{ $akt_id }}]" value="{{ $i }}" {{ $default_skor == $i ? 'checked' : '' }} required>
                        <label for="skor_{{ $akt_id }}_{{ $i }}" class="score-label">{{ $i }}</label>
                    @endfor
                </div>
            </div>
        @empty
            <div class="detail-card" style="text-align: center; color: red;">Instrumen untuk unit kompetensi ini belum dibuat.</div>
        @endforelse

        <div class="detail-card">
            <h4 style="margin:0 0 10px 0; font-size: 15px;">Catatan Assessor</h4>
            <textarea name="catatan_assessor" rows="4" placeholder="Tambahkan catatan penilaian..." style="width: 100%; padding:15px; border-radius:10px; border:1px solid #ddd; outline:none; font-family:inherit;">{{ $is_update ? ($penilaian_lama->catatan_umum ?? '') : '' }}</textarea>
        </div>

        @if ($daftar_pertanyaan->count() > 0)
            <div style="display:flex; justify-content:flex-end; gap:15px; padding-bottom:30px;">
                <button type="submit" name="status_simpan" value="Draft" style="background:#f1f3f5; color:#333; padding:12px 25px; border-radius:8px; border:none; cursor:pointer; font-weight:600;">Simpan Draft</button>
                <button type="submit" name="status_simpan" value="Selesai" style="background:#A15D33; color:white; padding:12px 25px; border-radius:8px; border:none; cursor:pointer; font-weight:600;">{{ $is_update ? 'Update Penilaian' : 'Selesaikan Penilaian' }}</button>
            </div>
        @endif
    </div>
</form>
@endsection