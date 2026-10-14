@extends('layouts.app')
@section('title', 'Beri Nilai')
@section('page_title', 'Hasil Analisis Profile Matching')
@section('page_subtitle', 'Tinjau rekomendasi sistem. Anda dapat mengoreksi nilai jika diperlukan.')
@section('back_url', route('pimpinan.tim_saya.index'))

@push('styles')
    <style>
        .page-header-skoring { background: #ffffff; border-radius: 12px; padding: 20px 25px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; border: 1px solid #e2e8f0; margin-bottom: 25px; }
        .header-left-content h2 { font-size: 16px; font-weight: 600; color: #64748b; margin: 0; }
        .header-left-content h2 span { color: #0f172a; font-weight: 800; }
        .unit-code-text { font-size: 13px; font-weight: 700; color: #A08348; }
        .unit-title-text { font-size: 14px; color: #334155; font-weight: 500; }
        .table-pm { width: 100%; border-collapse: collapse; margin-top: 15px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .table-pm th { background: #f8fafc; padding: 12px; font-size: 12px; color: #475569; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .table-pm td { padding: 12px; font-size: 13px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle; }
        .badge-gap { background: #e0f2fe; color: #0369a1; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: bold; }
        
        .select-skor { padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; outline: none; font-weight: 700; color: #0f172a; background: #fff; cursor: pointer; width: 60px; text-align: center; font-size: 14px; }
        .select-skor:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
        .info-parser { font-size: 10px; color: #64748b; margin-top: 4px; display: block; }
    </style>
@endpush

@section('content')
<div class="page-header-skoring">
    <div class="header-left-content">
        <h2>Review Penilaian Otomatis: <span>{{ $pegawai->pegawai_nama }}</span></h2>
        <div class="unit-code-text">{{ $kode_unit }}</div>
        <div class="unit-title-text">{{ $unit->judul_unit }}</div>
    </div>
</div>

@if(empty($hasil_pm))
    <div style="background:#fff; padding: 40px; text-align:center; border-radius: 12px; border: 1px solid #e2e8f0;">
        <h3 style="color: #0f172a;">Tidak Ada Aktivitas Aktif</h3>
    </div>
@else
<form method="POST" action="{{ route('pimpinan.tim_saya.simpan_nilai', [$pegawai_id, $kode_unit]) }}" id="formPenilaian">
    @csrf
    <input type="hidden" name="is_update" value="{{ $is_update ? 1 : 0 }}">
    @if($is_update) <input type="hidden" name="penilaian_id_lama" value="{{ $penilaian_lama->penilaian_id }}"> @endif

    <div class="score-layout" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
        
        <!-- BAGIAN KIRI: RINCIAN ANALISIS PER AKTIVITAS -->
        <div class="score-main">
            <div class="info-box" style="background: #fff; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                <h4 style="margin:0 0 10px 0; color: #A08348;"><i class="bi bi-cpu"></i> Log PHP Parser (Core Factor) & Admin (Secondary Factor)</h4>
                
                <table class="table-pm">
                    <thead>
                        <tr>
                            <th width="40%">Detail Aktivitas</th>
                            <th width="15%" style="text-align:center;">Evidence</th>
                            <th width="15%" style="text-align:center;">Nilai Aktual</th>
                            <th width="10%" style="text-align:center;">GAP</th>
                            <th width="15%" style="text-align:center;">Bobot PM</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hasil_pm as $item)
                        <tr>
                            <td>{{ $item['detail_aktivitas'] }}
                                @if(!empty($item['file_path'])) 
                                    <br><a href="{{ asset('uploads/evidence/' . $item['file_path']) }}" target="_blank" style="font-size: 11px; color: #3b82f6; text-decoration: none;"><i class="bi bi-file-earmark-pdf"></i> Lihat File Terdeteksi</a>
                                @endif
                            </td>
                            <td style="text-align:center;"><span style="font-weight:bold; color: {{ $item['jml_upload'] > 0 ? '#059669' : '#dc2626' }}">{{ $item['jml_upload'] }}</span> / {{ $item['target_upload'] }}</td>
                            <td style="text-align:center;">
                                <!-- Dropdown agar Pimpinan bisa edit -->
                                <select name="skor[{{ $item['aktivitas_id'] }}]" class="select-skor" 
                                    data-id="{{ $item['aktivitas_id'] }}" 
                                    data-target="{{ $item['target_cf'] }}" 
                                    data-bavg="{{ $item['bobot_sf_avg'] }}">
                                    @for($i=0; $i<=5; $i++)
                                        <option value="{{ $i }}" {{ $item['nilai_profil_cf'] == $i ? 'selected' : '' }}>{{ $i }}</option>
                                    @endfor
                                </select>
                                <span class="info-parser">Saran Parser: {{ $item['nilai_parser'] }}</span>
                            </td>
                            <td style="text-align:center;"><span class="badge-gap" id="gap_{{ $item['aktivitas_id'] }}">{{ $item['gap_cf'] }}</span></td>
                            <td style="text-align:center; color: #A08348; font-weight:bold;" id="bobot_{{ $item['aktivitas_id'] }}">{{ number_format($item['bobot_cf'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- BAGIAN KANAN: HASIL AKHIR & TOMBOL SAHKAN -->
        <aside class="score-side">
            <div class="side-card" style="box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); padding: 20px; background: white; border-radius: 12px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
                <h3 style="margin: 0 0 15px 0; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Perhitungan Profile Matching</h3>
                
                <div style="display:flex; justify-content:space-between; margin-bottom: 10px;">
                    <span style="font-size: 13px; color: #64748b;">NCF (Core - 60%)</span>
                    <b style="font-size: 14px;" id="text_ncf">{{ number_format($ncf, 2) }}</b>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom: 15px;">
                    <span style="font-size: 13px; color: #64748b;">NSF (Secondary - 40%)</span>
                    <b style="font-size: 14px;" id="text_nsf">{{ number_format($nsf, 2) }}</b>
                </div>
                
                <div style="background: #fffef6; border: 1px solid #e1dfcf; padding: 15px; border-radius: 8px; text-align: center;">
                    <span style="display:block; font-size: 12px; color: #64748b; margin-bottom: 5px;">Nilai Akhir (Skala 100)</span>
                    <b style="font-size: 24px; color: #A08348;" id="text_nilai_akhir">{{ number_format($nilai_akhir_100, 2) }}</b>
                </div>

                <div style="margin-top: 15px;">
                    <label style="display:block; font-weight:700; font-size:13px; margin-bottom:8px;">Catatan Tambahan (Opsional):</label>
                    <textarea name="rekomendasi" rows="4" style="width:100%; box-sizing: border-box; padding:10px; border-radius:8px; border:1px solid #cbd5e1; outline: none; font-family: inherit;">{{ $rekomendasi_lama }}</textarea>
                </div>

                <button type="submit" style="width:100%; background: #059669; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: bold; margin-top: 15px; cursor: pointer; transition: 0.2s;" onclick="return confirm('Sahkan penilaian otomatis ini?');">
                    <i class="bi bi-check-circle"></i> {{ $is_update ? 'Update Pengesahan' : 'Sahkan Penilaian Sistem' }}
                </button>
            </div>
        </aside>
    </div>
</form>
@endif
@endsection

@push('scripts')
<script>
    // Tabel Referensi Konversi GAP dari Jurnal Profile Matching
    const bobotTable = {
         "0": 5.0,  "1": 4.5, "-1": 4.0,
         "2": 3.5, "-2": 3.0,  "3": 2.5,
        "-3": 2.0,  "4": 1.5, "-4": 1.0,
         "5": 1.0, "-5": 1.0
    };

    function konversiBobot(gap) {
        let gapStr = gap.toString();
        return bobotTable[gapStr] !== undefined ? bobotTable[gapStr] : 1.0;
    }

    // Fungsi Kalkulasi Real-time Profile Matching
    function calculatePM() {
        let total_bobot_cf = 0;
        let total_bobot_sf = 0;
        let count = 0;

        // Looping semua dropdown skor di tabel
        document.querySelectorAll('.select-skor').forEach(select => {
            let id = select.getAttribute('data-id');
            let target = parseInt(select.getAttribute('data-target'));
            let sf_avg = parseFloat(select.getAttribute('data-bavg'));
            let aktual = parseInt(select.value);

            // 1. Hitung GAP CF
            let gap = aktual - target;
            
            // 2. Konversi Bobot CF
            let bobot_cf = konversiBobot(gap);

            // 3. Update DOM Tabel
            document.getElementById('gap_' + id).innerText = gap;
            document.getElementById('bobot_' + id).innerText = bobot_cf.toFixed(2);

            total_bobot_cf += bobot_cf;
            total_bobot_sf += sf_avg;
            count++;
        });

        if (count > 0) {
            // 4. Kalkulasi NCF, NSF, dan Nilai Akhir
            let ncf = total_bobot_cf / count;
            let nsf = total_bobot_sf / count;
            let ni = (0.6 * ncf) + (0.4 * nsf);
            let nilai_akhir_100 = (ni / 5) * 100;

            // 5. Update DOM Widget Kanan
            document.getElementById('text_ncf').innerText = ncf.toFixed(2);
            document.getElementById('text_nsf').innerText = nsf.toFixed(2);
            document.getElementById('text_nilai_akhir').innerText = nilai_akhir_100.toFixed(2);
        }
    }

    // Pasang event listener pada setiap perubahan dropdown
    document.querySelectorAll('.select-skor').forEach(el => {
        el.addEventListener('change', calculatePM);
    });
</script>
@endpush