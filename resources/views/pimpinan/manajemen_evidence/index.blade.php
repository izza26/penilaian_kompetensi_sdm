@extends('layouts.app')
@section('title', 'Manajemen Kebutuhan Evidence')
@section('page_title', 'Manajemen Evidence')
@section('page_subtitle', 'Tetapkan rincian dokumen apa saja yang harus diunggah pegawai per aktivitas.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Filter Box Atas */
        .filter-box { 
            background: #ffffff; border-radius: 24px; padding: 20px 32px; border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 24px; 
            display: flex; align-items: center; gap: 20px; 
            box-sizing: border-box; width: 100%; /* Pastikan kontainer full dan tidak bocor */
        }
        .filter-box label { font-size: 13px; font-weight: 700; color: #475569; white-space: nowrap; margin: 0; display: flex; align-items: center; gap: 8px;}
        
        /* Input Select Pill-Shape */
        .filter-box select { 
            flex: 1; 
            min-width: 0; /* KUNCI ANTI-JEBOL FLEXBOX */
            padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; 
            border-radius: 50px; font-size: 13px; color: #1e293b; outline: none; cursor: pointer; 
            background-color: #f9fafb; font-family: inherit; transition: 0.2s;
            box-sizing: border-box; text-overflow: ellipsis; white-space: nowrap; overflow: hidden; /* Teks kepanjangan jadi titik-titik */
        }
        .filter-box select:focus { border-color: #3e54a0; background-color: #ffffff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }

        /* Kartu Unit Kompetensi */
        .unit-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 24px; overflow: hidden; }
        .unit-card-header { background: #ffffff; padding: 24px 32px; border-bottom: 1px dashed #cbd5e1; }
        .unit-card-header h3 { margin: 0; font-size: 18px; color: #1e293b; font-weight: 700;}
        .unit-card-header span { 
            font-size: 11px; font-weight: 700; color: #3e54a0; background: #f4f7fe; 
            padding: 6px 14px; border-radius: 50px; border: none; display: inline-block; margin-bottom: 10px; letter-spacing: 0.5px;
        }

        /* Tabel */
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 24px; text-align: left; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; font-weight: 700; }
        .styled-table td { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: top; }
        
        .inline-edit-row { transition: 0.2s; cursor: pointer; }
        .inline-edit-row:hover { background-color: #f8fafc; }
        
        .text-id { font-size: 11px; font-weight: 700; color: #3e54a0; margin-bottom: 6px; display: block;}
        .text-detail { font-size: 14px; font-weight: 600; color: #1e293b; line-height: 1.5; display: block;}
        .text-kriteria { font-size: 13px; color: #475569; line-height: 1.6;}
        
        /* Indikator Evidence */
        .badge-evidence { background: #f4f7fe; color: #3e54a0; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; border: none; display: inline-block; margin-bottom: 12px;}
        .ev-group { margin-bottom: 16px; background: #f8fafc; padding: 12px 16px; border-radius: 12px; border: 1px dashed #cbd5e1;}
        .ev-group-title { font-size: 11px; font-weight: 700; color: #1e293b; margin-bottom: 8px; text-transform: uppercase;}
        .ev-list { padding-left: 20px; margin: 0; color: #475569; font-size: 13px; font-weight: 500;}
        .ev-list li { margin-bottom: 4px; }
        .ev-list li::marker { color: #3e54a0; }
        .badge-kosong { background: #fff1f2; color: #e11d48; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 600; border: none; display: inline-block; }
        
        /* Tombol Atur Pill-Shape */
        .btn-atur { 
            display: inline-flex; align-items: center; justify-content: center; gap: 6px; 
            color: white; text-decoration: none; font-size: 12px; font-weight: 600; 
            background: #3e54a0; padding: 0 20px; height: 36px; border-radius: 50px; border: none; transition: 0.2s;
        }
        .btn-atur:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
    </style>
@endpush

@section('content')
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    
    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif
    @if(session('error'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'error', title: 'Gagal!', text: "{{ session('error') }}" });
            });
        </script>
    @endif

    @if (empty($dataGrouped))
        <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <i class="bi bi-inbox" style="font-size: 40px; color: #94a3b8; display: block;"></i>
            <h3 style="color: #0f172a; margin-top: 15px;">Belum Ada Aktivitas Aktif</h3>
            <p style="color: #64748b; font-size: 13px;">Silakan atur Periode Penilaian terlebih dahulu.</p>
        </div>
    @else
        <div class="filter-box">
            <label><i class="bi bi-funnel-fill" style="color: #3b82f6;"></i> Pilih Unit Kompetensi</label>
            <select id="filterUK" onchange="applyFilterUK()">
                <option value="ALL" style="font-weight: bold;">-- Tampilkan Semua Unit --</option>
                @foreach ($dataGrouped as $unitKey => $aktivitas)
                    @php $kode_unit = explode("|||", $unitKey)[0]; $judul_unit = explode("|||", $unitKey)[1]; @endphp
                    <option value="{{ $kode_unit }}">[{{ $kode_unit }}] - {{ $judul_unit }}</option>
                @endforeach
            </select>
        </div>

        @foreach ($dataGrouped as $unitKey => $aktivitas)
        @php $kode_unit = explode("|||", $unitKey)[0]; $judul_unit = explode("|||", $unitKey)[1]; @endphp
        <div class="unit-card" data-kode="{{ $kode_unit }}">
            <div class="unit-card-header">
                <span><i class="bi bi-tag-fill"></i> {{ $kode_unit }}</span>
                <h3 style="margin-top: 8px;">{{ $judul_unit }}</h3>
            </div>
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="30%">Detail Aktivitas</th>
                        <th width="30%">Kriteria Kompetensi</th>
                        <th width="30%">Daftar Evidence yang Diharapkan</th>
                        <th width="10%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($aktivitas as $akt)
                    @php $my_evidences = $ew_data[$akt->aktivitas_id] ?? []; @endphp
                    <tr class="inline-edit-row" onclick="window.location.href='{{ route('pimpinan.manajemen_evidence.edit', $akt->aktivitas_id) }}'">
                        <td>
                            <span class="text-id">{{ $akt->aktivitas_id }}</span>
                            <span class="text-detail">{{ $akt->detail_aktivitas }}</span>
                        </td>
                        <td><div class="text-kriteria">{{ $akt->kriteria_kompetens ?? '-' }}</div></td>
                        <td>
                            @if (!empty($my_evidences))
                                <span class="badge-evidence"><i class="bi bi-folder-check"></i> Target: {{ count($my_evidences) }} Dokumen</span>
                                @php $evCount = 1; @endphp
                                @foreach($my_evidences as $ev)
                                    <div class="ev-group">
                                        <div class="ev-group-title"><i class="bi bi-file-earmark-text"></i> Evidence {{ $evCount++ }}</div>
                                        <ul class="ev-list">
                                            @foreach(explode("\n", $ev) as $opsi)
                                                <li>{{ trim($opsi) }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endforeach
                            @else
                                <span class="badge-evidence"><i class="bi bi-folder-check"></i> Target: {{ $akt->jumlah_evidence_wa }} Dokumen</span><br>
                                <span class="badge-kosong"><i class="bi bi-exclamation-triangle"></i> Daftar belum dirincikan</span>
                            @endif
                        </td>
                        <td style="text-align: center; vertical-align: middle;">
                            <a href="{{ route('pimpinan.manajemen_evidence.edit', $akt->aktivitas_id) }}" class="btn-atur"><i class="bi bi-pencil-square"></i> Atur</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
    @endif
</div>
@endsection

@push('scripts')
<script>
function applyFilterUK() {
    let selectedUK = document.getElementById('filterUK').value;
    let cards = document.querySelectorAll('.unit-card');
    cards.forEach(card => {
        if (selectedUK === 'ALL' || card.getAttribute('data-kode') === selectedUK) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
window.onload = function() {
    let selectBox = document.getElementById('filterUK');
    if (selectBox && selectBox.options.length > 2) {
        selectBox.selectedIndex = 1; applyFilterUK();
    }
}
</script>
@endpush