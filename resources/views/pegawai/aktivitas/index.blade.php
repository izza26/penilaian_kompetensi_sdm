@extends('layouts.app')
@section('title', 'Aktivitas Saya')
@section('page_title', 'Aktivitas Saya')
@section('page_subtitle', 'Pantau seluruh aktivitas kompetensi Anda')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pegawai/aktivitas_saya.css') }}">
@endpush

@section('content')
    <!-- ALERT PERIODE -->
    @if($periodeAktif)
        <div class="alert-periode alert-{{ $badge_class }}">
            <i class="bi {{ $is_open ? 'bi-calendar-check' : ($badge_class == 'warning' ? 'bi-hourglass-split' : 'bi-calendar-x') }}" style="font-size: 22px;"></i>
            <div>
                <h4 style="margin:0 0 2px 0; font-size: 14px;">{{ $periodeAktif->nama_periode }}</h4>
                <p style="margin:0; font-size: 12px;">{!! $pesan_periode !!}</p>
            </div>
        </div>
    @else
        <div class="alert-periode alert-info">
            <i class="bi bi-info-circle" style="font-size: 22px;"></i>
            <div>
                <h4 style="margin:0 0 2px 0; font-size: 14px;">Tidak Ada Periode Aktif</h4>
                <p style="margin:0; font-size: 12px;">Pimpinan belum membuka periode penilaian kompetensi. Harap menunggu informasi selanjutnya.</p>
            </div>
        </div>
    @endif

    @if(empty($dataGrouped))
        <div style="padding: 60px 0; text-align: center; background: #fff; border-radius: 12px; border: 1px dashed #cbd5e1; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
            <i class="bi bi-person-workspace" style="font-size: 48px; color: #94a3b8; display: block; margin-bottom: 10px;"></i>
            <h3 style="margin: 0; color: #1e293b; font-size: 16px;">Tidak Ada Aktivitas Ditugaskan</h3>
            <p style="color: #64748b; font-size: 13px; margin-top: 5px;">Pimpinan belum menugaskan Unit Kompetensi apapun untuk posisi <b>{{ $jabatan }}</b>.</p>
        </div>
    @else
        <div class="filter-box">
            <label><i class="bi bi-funnel-fill" style="color: #A08348;"></i> Pilih Unit Kompetensi</label>
            <select id="filterUnit" onchange="filterUnit()">
                <option value="ALL" style="font-weight: bold;">-- Tampilkan Semua Unit Kompetensi --</option>
                @foreach($dataGrouped as $unitKey => $aktivitasList)
                    @php $kd_unit = explode("|||", $unitKey)[0]; $jd_unit = explode("|||", $unitKey)[1]; @endphp
                    <option value="{{ $kd_unit }}">[{{ $kd_unit }}] - {{ $jd_unit }}</option>
                @endforeach
            </select>
        </div>

        @foreach($dataGrouped as $unitKey => $aktivitasList)
            @php $kd_unit = explode("|||", $unitKey)[0]; $jd_unit = explode("|||", $unitKey)[1]; @endphp
            <div id="{{ $kd_unit }}" class="unit-card" data-unit="{{ $kd_unit }}">
                <div class="unit-header">
                    <span><i class="bi bi-tags-fill"></i> {{ $kd_unit }}</span>
                    <h3>{{ $jd_unit }}</h3>
                </div>
                <div style="overflow-x: auto;">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th width="5%" style="text-align: center;">No</th>
                                <th width="45%">Detail Aktivitas & Elemen</th>
                                <th width="15%" style="text-align: center;">Evidence Wajib</th>
                                <th width="15%" style="text-align: center;">Status</th>
                                <th width="20%" style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($aktivitasList as $index => $akt)
                            <tr>
                                <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $index + 1 }}</td>
                                <td>
                                    <div style="font-weight: 600; color: #0f172a; margin-bottom: 4px;">{{ $akt->detail_aktivitas }}</div>
                                    <div style="font-size: 11px; color: #64748b;"><i class="bi bi-arrow-return-right"></i> {{ $akt->elemen_kompetensi }}</div>
                                </td>
                                <td style="text-align: center;"><span class="badge badge-doc"><i class="bi bi-file-earmark-text"></i> {{ $akt->jumlah_evidence_wa }} Dokumen</span></td>
                                <td style="text-align: center;"><span class="badge badge-{{ $akt->classStatus }}">{{ $akt->status_asli }}</span></td>
                                <td style="text-align: center;">
                                    @if ($is_open)
                                        @if ($akt->is_uploaded > 0)
                                            <a href="{{ route('pegawai.aktivitas.show', $akt->aktivitas_id) }}" class="btn-detail"><i class="bi bi-eye-fill"></i> Lihat Detail</a>
                                        @else
                                            <a href="{{ route('pegawai.aktivitas.show', $akt->aktivitas_id) }}" class="btn-upload"><i class="bi bi-cloud-arrow-up-fill"></i> Upload Bukti</a>
                                        @endif
                                    @else
                                        <a href="#" class="btn-locked" onclick="alert('Pengisian Tidak Tersedia.'); return false;"><i class="bi bi-lock-fill"></i> Terkunci</a>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('scripts')
<script>
function filterUnit() {
    let selectedUnit = document.getElementById('filterUnit').value;
    let cards = document.querySelectorAll('.unit-card');
    cards.forEach(card => {
        if (selectedUnit === 'ALL' || card.getAttribute('data-unit') === selectedUnit) card.style.display = 'block';
        else card.style.display = 'none';
    });
}
window.onload = function() {
    let filterSelect = document.getElementById('filterUnit');
    if (filterSelect) { filterSelect.value = 'ALL'; filterUnit(); }
};
</script>
@endpush