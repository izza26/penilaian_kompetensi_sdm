@extends('layouts.app')
@section('title', 'Beri Nilai')
@section('page_title', 'Hasil Analisis Profile Matching')
@section('page_subtitle', 'Tinjau rekomendasi skoring sistem. Anda dapat mengoreksi nilai jika diperlukan.')
@section('back_url', route('pimpinan.tim_saya.index'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js"></script>

    <style>
        .page-header-skoring { background: #ffffff; border-radius: 24px; padding: 24px 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 24px; }
        .header-left-content h2 { font-size: 18px; font-weight: 600; color: #64748b; margin: 0 0 6px 0; }
        .header-left-content h2 span { color: #1e293b; font-weight: 800; }
        .unit-code-text { font-size: 12px; font-weight: 700; color: #3e54a0; background: #f4f7fe; display: inline-block; padding: 6px 14px; border-radius: 50px; margin-bottom: 10px;}
        .unit-title-text { font-size: 14px; color: #334155; font-weight: 600; }
        
        .nav-quick-bar { background: transparent; padding: 0 0 16px 0; margin-bottom: 24px; overflow-x: auto; white-space: nowrap; display: flex; gap: 10px; align-items: center;}
        .nav-quick-bar::-webkit-scrollbar { height: 8px; }
        .nav-quick-bar::-webkit-scrollbar-track { background: transparent; }
        .nav-quick-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .nav-quick-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; border: 1px solid #cbd5e1; background: transparent; color: #64748b; transition: 0.2s; text-decoration: none;}
        .nav-quick-btn:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b;}
        .nav-quick-btn.nav-done { background: transparent; color: #059669; border-color: #10b981; }
        .nav-quick-btn.nav-done:hover { background: #ecfdf5; }
        .nav-quick-btn.nav-pending { background: transparent; color: #e11d48; border-color: #fda4af; }
        .nav-quick-btn.nav-pending:hover { background: #fff1f2; }

        .unit-card { background: #ffffff; border-radius: 24px; border: none; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; }
        .elemen-header { background: #f8fafc; padding: 20px 24px; border-bottom: 2px solid #e2e8f0; }
        .elemen-kode { font-weight: 800; color: #3e54a0; font-size: 13px; margin-bottom: 4px; display: block; }
        .elemen-nama { font-weight: 700; color: #1e293b; font-size: 14px; margin: 0; }
        
        .table-pm { width: 100%; border-collapse: collapse; background: white; margin: 0; }
        .table-pm th { background: transparent; padding: 14px 20px; font-size: 11px; color: #64748b; text-align: left; border-bottom: 1px solid #e2e8f0; text-transform: uppercase; font-weight: 700;}
        .table-pm td { padding: 20px; font-size: 13px; border-bottom: 1px dashed #cbd5e1; color: #334155; vertical-align: top; line-height: 1.5; }
        
        .badge-gap { background: #f4f7fe; color: #3e54a0; padding: 6px 12px; border-radius: 50px; font-size: 12px; font-weight: bold; display: inline-block; min-width: 30px; text-align: center;}
        
        .select-skor { padding: 8px 16px; border-radius: 50px; border: 1px solid #cbd5e1; outline: none; font-weight: 700; color: #1e293b; background: #f9fafb; cursor: pointer; width: 70px; text-align: center; font-size: 14px; transition: 0.2s;}
        .select-skor:focus { border-color: #3e54a0; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); background: #fff;}
        
        .action-container { background: #f8fafc; padding: 12px 18px; border-radius: 12px; border: 1px dashed #cbd5e1; box-sizing: border-box; }
        .action-row-buttons { display: flex; gap: 8px; flex-wrap: nowrap; flex: 1; justify-content: flex-end; }
        .action-row-status { display: flex; align-items: center; gap: 10px; white-space: nowrap; }
        
        .btn-preview-link { background: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; text-decoration: none; white-space: nowrap;}
        .btn-preview-link:hover { background: #3e54a0; color: white; border-color: #3e54a0;}
        .btn-unduh { color: #059669; border-color: #a7f3d0; background: #ecfdf5;}
        .btn-unduh:hover { background: #10b981; color: white; border-color: #10b981;}
        .ai-btn { color: #d97706; border-color: #fde68a; background: #fffbeb;}
        .ai-btn:hover { background: #f59e0b; color: white; border-color: #f59e0b;}
        
        .status-badge { font-size: 10px; color:#10b981; font-weight:800; background: #ecfdf5; padding: 4px 10px; border-radius: 50px; display: inline-flex; align-items: center; gap: 4px;}
        .date-text { font-size: 10px; color: #64748b; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;}

        .side-card { box-shadow: 0 10px 30px rgba(0,0,0,0.02); padding: 28px; background: white; border-radius: 24px; border: none; position: sticky; top: 20px; }
        .btn-submit-nilai { width: 100%; background: #3e54a0; color: white; border: none; height: 48px; border-radius: 50px; font-weight: 600; margin-top: 20px; cursor: pointer; transition: 0.2s; font-size: 13px; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .btn-submit-nilai:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }

        .swal-wide-popup { border-radius: 24px !important; padding: 24px !important; }
        .excel-preview-table { width: 100%; border-collapse: collapse; font-size: 11px; color: #334155; text-align:left; }
        .spin { display: inline-block; animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }
    </style>
@endpush

@section('content')
<div class="page-header-skoring">
    <div class="header-left-content">
        <h2>Review Penilaian Otomatis: <span>{{ $pegawai->pegawai_nama }}</span></h2>
        <div class="unit-code-text"><i class="bi bi-tag-fill" style="margin-right: 4px;"></i> {{ $kode_unit }}</div>
        <div class="unit-title-text">{{ $unit->judul_unit }}</div>
    </div>
    
    <div class="header-right-content">
        <label for="filter_uk" style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 5px;">PILIH UNIT KOMPETENSI LAIN:</label>
        <select id="filter_uk" class="form-select" style="border-radius: 12px; font-size: 13px; font-weight: 600; color: #3e54a0; border-color: #cbd5e1; padding: 10px 15px; cursor: pointer;" onchange="window.location.href=this.value;">
            @foreach($list_uk_pegawai as $uk_item)
                <option value="{{ route('pimpinan.tim_saya.beri_nilai', [$pegawai_id, $uk_item->kode_unit]) }}" {{ $uk_item->kode_unit == $kode_unit ? 'selected' : '' }}>
                    [{{ $uk_item->kode_unit }}] - {{ \Illuminate\Support\Str::limit($uk_item->judul_unit, 45) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

@if(!empty($dataGrouped))
<div class="nav-quick-bar">
    <span style="font-size: 11px; font-weight: 800; color: #434c5a; margin-right: 8px;">NAVIGASI AKTIVITAS:</span>
    @foreach($dataGrouped as $elemenKode => $elemen)
        @php
            // Cek apakah seluruh KUK di dalam elemen ini sudah diunggah
            $is_all_done = true;
            foreach($elemen['kuks'] as $item) {
                if (empty($item['file_path'])) {
                    $is_all_done = false;
                    break;
                }
            }
            $nav_class = $is_all_done ? 'nav-done' : 'nav-pending';
            $safe_elemen_id = \Illuminate\Support\Str::slug($elemenKode);
            $nomor_aktivitas = preg_replace('/[^0-9]/', '', $elemenKode);
        @endphp
        
        <a href="#aktivitas_{{ $safe_elemen_id }}" class="nav-quick-btn {{ $nav_class }}">
            @if($is_all_done)
                <i class="bi bi-check-square-fill"></i>
            @else
                <i class="bi bi-square"></i>
            @endif
            Aktivitas {{ $nomor_aktivitas }}
        </a>
    @endforeach
</div>
@endif

@if(empty($dataGrouped))
    <div style="background:#fff; padding: 40px; text-align:center; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);">
        <h3 style="color: #0f172a;"><i class="bi bi-folder-x" style="font-size: 32px; color:#cbd5e1; display:block; margin-bottom: 10px;"></i>Tidak Ada Kriteria Unjuk Kerja Aktif</h3>
    </div>
@else
<form method="POST" action="{{ route('pimpinan.tim_saya.simpan_nilai', [$pegawai_id, $kode_unit]) }}" id="formPenilaian">
    @csrf
    <input type="hidden" name="is_update" value="{{ $is_update ? 1 : 0 }}">
    @if($is_update) <input type="hidden" name="penilaian_id_lama" value="{{ $penilaian_lama->penilaian_id }}"> @endif

    <div class="score-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        
        <div class="score-main">
            @foreach($dataGrouped as $elemenKode => $elemen)
            @php $safe_elemen_id = \Illuminate\Support\Str::slug($elemenKode); @endphp
            
            <div class="unit-card" id="aktivitas_{{ $safe_elemen_id }}" style="scroll-margin-top: 100px;">
                <div class="elemen-header">
                    <span class="elemen-kode">{{ $elemenKode }}</span>
                    <h4 class="elemen-nama">{{ $elemen['nama_elemen'] }}</h4>
                </div>
                
                <table class="table-pm">
                    <thead>
                        <tr>
                            <!-- KOLOM 1 DIPERLEBAR MENJADI 50% -->
                            <th width="50%">KRITERIA UNJUK KERJA (KUK)</th>
                            <th width="15%" style="text-align:center;">AKTUAL</th>
                            <th width="10%" style="text-align:center;">TARGET</th>
                            <th width="10%" style="text-align:center;">GAP</th>
                            <th width="15%" style="text-align:center;">BOBOT PM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($elemen['kuks'] as $item)
                        @php $safe_id = \Illuminate\Support\Str::slug($item['aktivitas_id']); @endphp
                        
                        <!-- BARIS 1: INFORMASI KUK SAJA -->
                        <tr style="border-bottom: none;">
                            <td style="padding-bottom: 8px; border-bottom: none;">
                                <div style="margin-bottom: 4px; color: #1e293b; font-size: 14px; font-weight: 700;">
                                    KUK Ke-{{ $item['kuk_ke'] }} <span style="font-size: 10px; color: #94a3b8; font-weight: 600; margin-left: 6px;">[{{ $item['aktivitas_id'] }}]</span>
                                </div>
                                <span style="color: #334155; font-size: 13px; line-height: 1.5; display: block; margin-bottom: 8px;">
                                    {!! $item['detail_aktivitas'] !!}
                                </span>
                                <div style="font-size: 11px; color: #3e54a0; font-weight: 600;">
                                    <i class="bi bi-file-earmark-text" style="margin-right: 4px;"></i> Evidence: {{ $item['keterangan_evidence'] }}
                                </div>
                            </td>
                            <td style="text-align:center; padding-bottom: 8px; border-bottom: none;">
                                <select name="skor[{{ $item['aktivitas_id'] }}]" class="select-skor" id="select_skor_{{ $item['aktivitas_id'] }}" 
                                    data-id="{{ $item['aktivitas_id'] }}" 
                                    data-target="{{ $item['target_cf'] }}" 
                                    data-bavg="{{ $item['bobot_sf_avg'] }}"
                                    @if($item['butuh_panggil_ai']) disabled @endif>
                                    @for($i=0; $i<=5; $i++)
                                        <option value="{{ $i }}" {{ $item['nilai_profil_cf'] == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                                <span style="font-size: 10px; color: #94a3b8; display: block; margin-top: 12px; font-weight: 600;">Rekomendasi:<br>
                                    <b style="color: #3e54a0; font-size:18px;" id="rekomendasi_skor_{{ $item['aktivitas_id'] }}">
                                        @if($item['butuh_panggil_ai']) <i class="bi bi-arrow-repeat spin"></i> @else {{ $item['nilai_parser'] }} @endif
                                    </b>
                                </span>
                                <input type="hidden" name="catatan_sistem[{{ $item['aktivitas_id'] }}]" id="input_catatan_{{ $item['aktivitas_id'] }}" value="{{ $item['catatan_sistem'] }}">
                            </td>
                            <td style="text-align:center; padding-top:20px; padding-bottom: 8px; border-bottom: none;">
                                <span style="font-size: 14px; font-weight: 800; color: #1e293b; background: #f8fafc; padding: 6px 16px; border-radius: 8px; border: 1px solid #cbd5e1;">{{ $item['target_cf'] }}</span>
                            </td>
                            <td style="text-align:center; padding-bottom: 8px; border-bottom: none;">
                                <span class="badge-gap" id="gap_{{ $item['aktivitas_id'] }}">{{ $item['gap_cf'] }}</span>
                            </td>
                            <td style="text-align:center; color: #3e54a0; font-weight:800; padding-bottom: 8px; border-bottom: none;" id="bobot_{{ $item['aktivitas_id'] }}">{{ number_format($item['bobot_cf'], 2) }}</td>
                        </tr>

                        <!-- BARIS KEDUA KHUSUS UNTUK TOMBOL (MEMBENTANG 100% MEMBELAH TABEL) -->
                        <tr style="{{ !empty($item['catatan_sistem']) ? 'border-bottom: none;' : '' }}">
                            <td colspan="5" style="padding: 0; margin: 0; border: none; display: table-cell;">
                                @if(!empty($item['file_path'])) 
                                    @php
                                        $file_path_db = $item['file_path'];
                                        $encoded_file_path = rawurlencode($file_path_db);
                                        $file_url = env('AWS_URL') . '/uploads/evidence/' . $encoded_file_path;
                                    @endphp
                                    
                                    <!-- INLINE STYLE DISINI AKAN MEMAKSA KOTAK TOMBOL MEMBENTANG PENUH -->
                                    <div class="action-container" style="width: 100%; min-width: 100%; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 12px 20px;">
                                        <div class="action-row-status">
                                            <span class="status-badge"><i class="bi bi-check-circle-fill"></i> Diunggah</span>
                                            @if(!empty($item['tanggal_upload']))
                                                <span class="date-text"><i class="bi bi-clock"></i> {{ \Carbon\Carbon::parse($item['tanggal_upload'])->translatedFormat('d M Y, H:i') }} WIB</span>
                                            @endif
                                        </div>
                                        <div class="action-row-buttons">
                                            <button type="button" class="btn-preview-link" onclick='previewServerFile(@json($file_url), @json($item['file_path']))'>
                                                <i class="bi bi-search"></i> Pratinjau
                                            </button>
                                            <a href="{{ $file_url }}" target="_blank" download="{{ $file_path_db }}" class="btn-preview-link btn-unduh">
                                                <i class="bi bi-download"></i> Unduh
                                            </a>
                                            <button type="button" class="btn-preview-link ai-btn" onclick='panggilAIPaksa("{{ $item['aktivitas_id_induk'] }}", "{{ $item['aktivitas_id'] }}", "{{ $item['file_path'] }}")'>
                                                <i></i> Baca Ulang Dokumen
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    <div style="margin: 0 20px 24px 20px;">
                                        <span class="status-badge" style="color:#dc2626; background: #fff1f2; border: 1px solid #fecdd3;"><i class="bi bi-x-circle-fill"></i> Belum Mengunggah</span>
                                    </div>
                                @endif
                            </td>
                        </tr>

                        <!-- BARIS KETIGA KOTAK HASIL ANALISIS SISTEM -->
                        @if(!empty($item['catatan_sistem']))
                        <tr>
                            <td colspan="5" style="padding-top: 0; padding-bottom: 24px; border-bottom: 1px dashed #cbd5e1;">
                                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; padding: 12px 16px; border-radius: 8px; width: 100%; box-sizing: border-box;">
                                    <div style="font-size: 10px; font-weight: 800; color: #64748b; margin-bottom: 6px; letter-spacing: 0.5px;">
                                        <i class="bi bi-cpu" style="margin-right: 4px;"></i> HASIL ANALISIS SISTEM
                                    </div>
                                    <div style="font-size: 12px; color: #334155; line-height: 1.6; font-style: italic;" id="teks_catatan_{{ $item['aktivitas_id'] }}">
                                        "{{ $item['catatan_sistem'] }}"
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endif
                        
                        @if($item['butuh_panggil_ai'])
                            @php
                                $dokumen_target = !empty($item['file_path']) ? $item['file_path'] : (!empty($item['file_parameter']) ? $item['file_parameter'] : '');
                            @endphp
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    daftarPanggilAI("{{ $item['aktivitas_id_induk'] }}", "{{ $item['aktivitas_id'] }}", "{{ $dokumen_target }}");
                                });
                            </script>
                        @endif

                        @endforeach
                    </tbody>
                </table>
            </div>
            @endforeach
        </div>

        <aside class="score-side">
            <div class="side-card">
                <h3 style="margin: 0 0 20px 0; font-size: 16px; border-bottom: 1px dashed #e2e8f0; padding-bottom: 15px; color: #1e293b;">Perhitungan Profile Matching</h3>
                
                <div style="display:flex; justify-content:space-between; margin-bottom: 12px;">
                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">NCF (Core - {{ $persen_ncf }}%)</span>
                    <b style="font-size: 14px; color: #1e293b;" id="text_ncf">{{ number_format($ncf, 2) }}</b>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 20px;">
                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">NSF (Secondary - {{ $persen_nsf }}%)</span>
                    <b style="font-size: 14px; color: #1e293b;" id="text_nsf">{{ number_format($nsf, 2) }}</b>
                </div>
                
                <div style="background: #f4f7fe; border: 1px solid #cbd5e1; padding: 20px; border-radius: 16px; text-align: center;">
                    <span style="display:block; font-size: 11px; color: #64748b; margin-bottom: 6px; font-weight: 700; text-transform: uppercase;">Nilai Akhir (Skala 100)</span>
                    <b style="font-size: 32px; color: #3e54a0; line-height: 1;" id="text_nilai_akhir">{{ number_format($nilai_akhir_100, 2) }}</b>
                </div>

                <div style="margin-top: 20px;">
                    <label style="display:block; font-weight:700; font-size:12px; margin-bottom:8px; color:#475569;">Catatan Tambahan (Opsional):</label>
                    <textarea name="rekomendasi" rows="4" style="width:100%; box-sizing: border-box; padding:15px; border-radius:16px; border:1px solid #cbd5e1; outline: none; font-family: inherit; font-size: 13px; background:#f9fafb;" placeholder="Tuliskan evaluasi Anda di sini...">{{ $rekomendasi_lama }}</textarea>
                </div>

                <button type="submit" class="btn-submit-nilai" onclick="return confirm('Sahkan penilaian otomatis ini? Data akan disimpan sebagai rekam jejak kompetensi pegawai.');">
                    <i class="bi bi-check-circle-fill"></i> {{ $is_update ? 'Update Pengesahan' : 'Sahkan Penilaian Sistem' }}
                </button>
            </div>
        </aside>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
    document.querySelectorAll('.nav-quick-btn').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target) {
                target.scrollIntoView({ behavior: 'smooth' });
                target.style.transition = "background-color 0.5s ease-out";
                target.style.backgroundColor = "#fffbeb"; 
                setTimeout(() => { target.style.backgroundColor = "transparent"; }, 1500);
            }
        });
    });

    const bobotTable = { "0": 5.0, "1": 4.5, "-1": 4.0, "2": 3.5, "-2": 3.0, "3": 2.5, "-3": 2.0, "4": 1.5, "-4": 1.0, "5": 1.0, "-5": 1.0 };
    function konversiBobot(gap) { let gapStr = gap.toString(); return bobotTable[gapStr] !== undefined ? bobotTable[gapStr] : 1.0; }

    function calculatePM() {
        let total_bobot_cf = 0; let total_bobot_sf = 0; let count = 0;
        let persen_ncf = {{ $persen_ncf }} / 100;
        let persen_nsf = {{ $persen_nsf }} / 100;

        document.querySelectorAll('.select-skor').forEach(select => {
            let id = select.getAttribute('data-id');
            let target = parseInt(select.getAttribute('data-target'));
            let sf_avg = parseFloat(select.getAttribute('data-bavg'));
            let aktual = parseInt(select.value);

            let gap = aktual - target;
            let bobot_cf = konversiBobot(gap);

            document.getElementById('gap_' + id).innerText = gap;
            document.getElementById('bobot_' + id).innerText = bobot_cf.toFixed(2);

            total_bobot_cf += bobot_cf;
            total_bobot_sf += sf_avg;
            count++;
        });

        if (count > 0) {
            let ncf = total_bobot_cf / count;
            let nsf = total_bobot_sf / count;
            let ni = (persen_ncf * ncf) + (persen_nsf * nsf);
            let nilai_akhir_100 = (ni / 5) * 100;

            document.getElementById('text_ncf').innerText = ncf.toFixed(2);
            document.getElementById('text_nsf').innerText = nsf.toFixed(2);
            document.getElementById('text_nilai_akhir').innerText = nilai_akhir_100.toFixed(2);
        }
    }
    
    document.querySelectorAll('.select-skor').forEach(el => { el.addEventListener('change', calculatePM); });

    function previewServerFile(url, name) {
        const ext = name.split('.').pop().toLowerCase();
        Swal.fire({ title: 'Memuat Dokumen...', text: 'Mengambil data dari Cloud', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

        if (['xls', 'xlsx', 'csv'].includes(ext)) {
            fetch(url).then(r => r.arrayBuffer()).then(ab => { const w = XLSX.read(new Uint8Array(ab), {type: 'array'}); showModalPreview(name, XLSX.utils.sheet_to_html(w.Sheets[w.SheetNames[0]])); }).catch(() => showModalPreview(name, `<div style="color:#e11d48; text-align:center; padding:30px;"><i class="bi bi-exclamation-triangle-fill" style="font-size:40px; margin-bottom:10px; display:block;"></i> Gagal membaca file dari server.</div>`));
        } else if (['jpg', 'jpeg', 'png'].includes(ext)) { 
            Swal.close();
            showModalPreview(name, `<div style="text-align:center;"><img src="${url}" style="max-width:100%; max-height:60vh; border-radius:12px;"></div>`);
        } else if (ext === 'pdf') { 
            Swal.close();
            showModalPreview(name, `<iframe src="${url}" style="width:100%; height:70vh; border:none; border-radius:12px;"></iframe>`);
        } else if (['doc', 'docx'].includes(ext)) {
            const proxyUrl = "{{ route('proxy.document') }}?url=" + encodeURIComponent(url);
            fetch(proxyUrl).then(res => {
                if(!res.ok) throw new Error('Network response was not ok');
                return res.arrayBuffer();
            }).then(ab => {
                mammoth.convertToHtml({arrayBuffer: ab})
                    .then(function(result) {
                        let htmlContent = result.value || "<p><i>Dokumen kosong atau tidak terbaca.</i></p>";
                        Swal.close();
                        showModalPreview(name, `<div style="background:#fff; padding:30px; border-radius:12px; font-family: 'Times New Roman'; line-height:1.6; border: 1px solid #cbd5e1;">${htmlContent}</div>`);
                    }).catch(function(err) { 
                        showModalPreview(name, `<div style="color:#e11d48; text-align:center; padding:30px;"><i class="bi bi-exclamation-triangle-fill" style="font-size:40px; margin-bottom:10px; display:block;"></i> Gagal membuat preview Word. File mungkin dikunci.</div>`); 
                    });
            }).catch(() => showModalPreview(name, `<div style="color:#e11d48; text-align:center; padding:30px;"><i class="bi bi-exclamation-triangle-fill" style="font-size:40px; margin-bottom:10px; display:block;"></i> Gagal mengambil file Word dari Cloud.</div>`));
        } else { 
            Swal.close();
            showModalPreview(name, `<div style="text-align:center; padding:50px;">Dokumen <b>.${ext}</b> tersimpan. Silakan unduh untuk melihat.</div>`); 
        }
    }

    function showModalPreview(title, html) {
        const safeTitle = title.length > 50 ? title.substring(0, 50) + "..." : title;
        Swal.fire({ title: `<span style="font-size:15px; color:#3e54a0; font-weight:bold; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><i class="bi bi-eye-fill"></i> Preview: ${safeTitle}</span>`, html: `<div style="max-height: 70vh; overflow: auto; text-align: left;" class="excel-preview-table">${html}</div>`, width: '85%', showCloseButton: true, showConfirmButton: false, customClass: { popup: 'swal-wide-popup' } });
    }

    let aiQueue = [];
    let isProcessingQueue = false;

    function daftarPanggilAI(idInduk, idUpload, fileToRead) {
        aiQueue.push({ idInduk, idUpload, fileToRead, forceReload: false });
        if (!isProcessingQueue) mulaiProsesAntrean();
    }

    function panggilAIPaksa(idInduk, idUpload, fileToRead) {
        document.getElementById('rekomendasi_skor_' + idUpload).innerHTML = '<i class="bi bi-arrow-repeat spin"></i>';
        document.getElementById('teks_catatan_' + idUpload).innerText = '"Membaca ulang dan mengevaluasi dokumen dengan teliti..."';
        document.getElementById('select_skor_' + idUpload).disabled = true;
        
        aiQueue.unshift({ idInduk, idUpload, fileToRead, forceReload: true });
        if (!isProcessingQueue) mulaiProsesAntrean();
    }

    async function mulaiProsesAntrean() {
        if (aiQueue.length === 0) {
            isProcessingQueue = false;
            return;
        }

        isProcessingQueue = true; 
        let task = aiQueue.shift(); 
        
        await eksekusiAI(task.idInduk, task.idUpload, task.fileToRead, task.forceReload);
        
        if (aiQueue.length > 0) {
            setTimeout(() => { mulaiProsesAntrean(); }, 3500); 
        } else {
            isProcessingQueue = false; 
        }
    }

    async function eksekusiAI(idInduk, idUpload, fileToRead, forceReload) {
        try {
            let res = await fetch("{{ route('ajax.panggil.ai') }}", {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ id_upload: idUpload, file_to_read: fileToRead, force_reload: forceReload })
            });
            
            let data = await res.json();
            
            let selElem = document.getElementById('select_skor_' + idUpload);
            selElem.value = data.skor;
            selElem.disabled = false;
            
            document.getElementById('rekomendasi_skor_' + idUpload).innerText = data.skor;
            document.getElementById('teks_catatan_' + idUpload).innerText = '"' + data.alasan + '"';
            document.getElementById('input_catatan_' + idUpload).value = data.alasan;
            
            calculatePM();
            
        } catch (error) {
            document.getElementById('rekomendasi_skor_' + idUpload).innerText = "1";
            document.getElementById('teks_catatan_' + idUpload).innerText = '"Gagal terhubung ke server AI. Silakan klik Baca Ulang."';
            document.getElementById('select_skor_' + idUpload).disabled = false;
        }
    }
</script>
@endpush