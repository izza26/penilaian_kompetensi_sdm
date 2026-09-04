@extends('layouts.app')
@section('title', 'Master Bank SKKNI')
@section('page_title', 'Master Bank SKKNI')
@section('page_subtitle', 'Direktori referensi hierarki Unit, Elemen, dan Kriteria Unjuk Kerja (KUK) SKKNI.')

@push('styles')
    <style>
        .page-card { background: #fff; padding: 32px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px;}
        .styled-table td { padding: 18px 20px; border-bottom: 1px dashed #f1f5f9; font-size: 13px; color: #334155; }
        
        .clickable-row { cursor: pointer; transition: 0.2s; }
        .clickable-row:hover { background: #f8fafc; }
        .row-expanded { background: #f8fafc; }
        
        .sub-table-container { display: none; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .elemen-wrapper { padding: 24px 40px; border-left: 4px solid #3e54a0; }
        
        /* --- UPDATE: Desain Elemen Card Menjadi Accordion --- */
        .elemen-card { background: #fff; border: 1px solid #cbd5e1; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); overflow: hidden; transition: 0.2s;}
        .elemen-card:hover { border-color: #94a3b8; }
        
        .elemen-card-header { padding: 18px 24px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #fff; transition: 0.2s; }
        .elemen-card-header:hover { background: #f8fafc; }
        
        .elemen-card-body { padding: 0 24px 24px 24px; display: none; border-top: 1px dashed #e2e8f0; margin-top: 4px; padding-top: 20px;}
        /* -------------------------------------------------- */

        .io-box { background: #f8fafc; padding: 14px 18px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .io-box p { margin-bottom: 6px; font-size: 12.5px; line-height: 1.5; color: #475569;}
        .io-box p:last-child { margin-bottom: 0; }
        .io-box b { color: #3e54a0; font-weight: 700; width: 65px; display: inline-block;}
        
        .kuk-header { font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 16px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px; letter-spacing: 0.5px; }
        .kuk-item { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .kuk-item:last-child { margin-bottom: 0; }
        
        .kuk-number { background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 11.5px; padding: 4px 10px; border-radius: 8px; white-space: nowrap; border: 1px solid #bfdbfe; }
        .kuk-detail { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 4px; line-height: 1.5;}
        .kuk-kriteria { font-size: 12px; color: #64748b; line-height: 1.5;}
    </style>
@endpush

@section('content')
<div class="page-card">
    <div style="margin-bottom: 25px;">
        <h3 style="color: #1e293b; margin-bottom: 5px; font-size: 20px;"><i class="bi bi-journal-bookmark-fill" style="color:#3e54a0; margin-right:8px;"></i> Bank Data SKKNI</h3>
        <p style="font-size: 13px; color: #64748b; margin: 0;">Mode Baca: Melihat hierarki Unit Kompetensi, Aktivitas, dan KUK.</p>
    </div>

    <!-- TABEL UTAMA (Kolom Jenis Kompetensi dipisah ke Kanan) -->
    <table class="styled-table">
        <thead>
            <tr>
                <th width="5%"></th>
                <th width="20%">KODE UNIT</th>
                <th width="55%">JUDUL UNIT KOMPETENSI</th>
                <th width="20%" style="text-align: right;">JENIS KOMPETENSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data_hirarki as $unit)
            @php $safe_unit_id = "unit_" . Str::slug($unit->kode_unit, '_'); @endphp
            
            <tr class="clickable-row" id="row-{{ $safe_unit_id }}" onclick="toggleRow('{{ $safe_unit_id }}', this)">
                <td style="text-align: center; color: #94a3b8;"><i class="bi bi-chevron-right icon-{{ $safe_unit_id }}" style="transition: 0.2s;"></i></td>
                <td style="font-size: 13.5px; font-weight: 700; color: #1e293b; font-family: monospace;">{{ $unit->kode_unit }}</td>
                <td>
                    <div style="font-size: 14px; font-weight: 600; color: #334155;">{{ $unit->judul_unit }}</div>
                </td>
                <td style="text-align: right;">
                    <span style="font-size: 10.5px; color: #3e54a0; font-weight: 700; background: #f4f7fe; padding: 4px 10px; border-radius: 50px;"><i class="bi bi-tags-fill"></i> {{ $unit->jenis_kompetensi }}</span>
                </td>
            </tr>

            <!-- ISI DARI UNIT KOMPETENSI -->
            <tr class="sub-table-container" id="child-{{ $safe_unit_id }}">
                <td colspan="4" style="padding: 0;"> <!-- Colspan disesuaikan jadi 4 -->
                    <div class="elemen-wrapper">
                        
                        @if($unit->elemen->count() > 0)
                            @foreach ($unit->elemen as $elemen)
                                <!-- Membersihkan huruf A dari kode elemen Excel -->
                                @php 
                                    $nomor_elemen_bersih = preg_replace('/[^0-9]/', '', $elemen->kode_elemen_excel); 
                                    $safe_elemen_id = "elemen_" . $elemen->elemen_id;
                                @endphp
                                
                                <div class="elemen-card">
                                    <!-- HEADER ELEMEN YANG BISA DIKLIK (Font Diperkecil) -->
                                    <div class="elemen-card-header" onclick="toggleElemen('{{ $safe_elemen_id }}')">
                                        <h4 style="font-size: 13px; color: #1e293b; margin: 0; font-weight: 600;">
                                            Aktivitas {{ $nomor_elemen_bersih }}: <span style="color: #3e54a0; font-weight: 700;">{{ $elemen->elemen_kompetensi }}</span>
                                        </h4>
                                        <i class="bi bi-chevron-right icon-{{ $safe_elemen_id }}" style="color: #94a3b8; font-size: 16px; font-weight: bold; transition: 0.2s;"></i>
                                    </div>

                                    <!-- KOTAK ISI (Disembunyikan secara default, baru muncul jika Header di atas diklik) -->
                                    <div class="elemen-card-body" id="body-{{ $safe_elemen_id }}">
                                        <!-- KOTAK INPUT/OUTPUT -->
                                        <div class="io-box">
                                            <p><b>Input</b> : {{ $elemen->input ?? '-' }}</p>
                                            <p><b>Output</b> : {{ $elemen->output ?? '-' }}</p>
                                            <p><b>Outcome</b> : {{ $elemen->outcome ?? '-' }}</p>
                                        </div>

                                        <!-- DAFTAR KUK (AKTIVITAS) -->
                                        <div>
                                            <div class="kuk-header">DAFTAR KRITERIA UNJUK KERJA (KUK):</div>
                                            
                                            @if($elemen->aktivitas->count() > 0)
                                                <div style="display: flex; flex-direction: column;">
                                                    @foreach ($elemen->aktivitas as $index => $akt)
                                                        <div class="kuk-item">
                                                            <!-- Penomoran otomatis misal: 1.1, 1.2, 1.3 -->
                                                            <div class="kuk-number">{{ $nomor_elemen_bersih }}.{{ $index + 1 }}</div>
                                                            <div>
                                                                <div class="kuk-detail">{{ $akt->detail_aktivitas }}</div>
                                                                <div class="kuk-kriteria"><b>Kriteria Kompetensi:</b> {{ $akt->kriteria_kompetens }}</div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <div style="font-size: 12px; color: #ef4444;"><i class="bi bi-info-circle-fill"></i> Belum ada aktivitas (KUK) di elemen ini.</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding: 15px; color: #ef4444; font-size: 13px; border: 1px dashed #fca5a5; border-radius: 12px; background: #fef2f2; text-align: center;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Belum ada elemen kompetensi untuk unit ini.
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; padding: 40px; color:#64748b;">Belum ada data Unit Kompetensi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection

@push('scripts')
<script>
// Fungsi Buka-Tutup Baris Unit Kompetensi (Level 1)
function toggleRow(id, rowElement) {
    var childRow = document.getElementById('child-' + id);
    var icon = document.querySelector('.icon-' + id);
    if (!childRow) return;
    
    if (childRow.style.display === 'table-row') {
        childRow.style.display = 'none'; 
        rowElement.classList.remove('row-expanded');
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    } else {
        childRow.style.display = 'table-row'; 
        rowElement.classList.add('row-expanded');
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    }
}

// Fungsi Buka-Tutup Laci Elemen (Level 2)
function toggleElemen(id) {
    var body = document.getElementById('body-' + id);
    var icon = document.querySelector('.icon-' + id);
    if (!body) return;
    
    if (body.style.display === 'block') {
        body.style.display = 'none';
        icon.style.transform = 'rotate(0deg)'; // Panah kembali menunjuk ke kanan
    } else {
        body.style.display = 'block';
        icon.style.transform = 'rotate(90deg)'; // Panah menunjuk ke bawah
    }
}
</script>
@endpush