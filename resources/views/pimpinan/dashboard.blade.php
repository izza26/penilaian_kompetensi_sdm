@extends('layouts.app')
@section('title', 'Dashboard Pimpinan')
@section('page_title', 'Dashboard Pimpinan')
@section('page_subtitle', 'Pantau antrean penilaian dan perkembangan kompetensi pegawai Anda')

@push('styles')
    <!-- CSS Spesifik hanya untuk halaman Dashboard -->
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/dashboard.css') }}">
@endpush

@section('content')

    <!-- ALERT PERIODE PENILAIAN -->
    @if($periodeAktif)
        <div class="alert-periode alert-{{ $badge_class }}">
            <i class="bi {{ $is_open ? 'bi-calendar-check' : ($badge_class == 'warning' ? 'bi-hourglass-split' : 'bi-calendar-x') }}" style="font-size: 28px;"></i>
            <div>
                <h4 style="margin:0 0 4px 0; font-size: 15px; font-weight: 700;">{{ $periodeAktif->nama_periode }}</h4>
                <p style="margin:0; font-size: 13px; line-height: 1.5;">{!! $pesan_periode !!}</p>
            </div>
        </div>
    @else
        <div class="alert-periode alert-info">
            <i class="bi bi-info-circle" style="font-size: 28px;"></i>
            <div>
                <h4 style="margin:0 0 4px 0; font-size: 15px; font-weight: 700;">Tidak Ada Periode Aktif</h4>
                <p style="margin:0; font-size: 13px; line-height: 1.5;">Sistem penilaian saat ini sedang ditutup atau belum diatur oleh Administrator.</p>
            </div>
        </div>
    @endif

    <!-- WELCOME CARD -->
    <div class="welcome-card">
        <div class="welcome-text">
            <h2>Halo, {{ $pimpinan->pegawai_nama }}! 👋</h2>
            <div class="welcome-date">{{ date('d F Y') }}</div>
            <p>
                @if($tot_dokumen > 0)
                    Terdapat <b>{{ $tot_dokumen }} dokumen</b> evidence yang terkumpul di sistem.
                @else
                    Belum ada dokumen yang diunggah oleh pegawai saat ini.
                @endif
            </p>
        </div>
        <div class="welcome-icon">
            <i class="bi bi-person-workspace"></i>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="pegawai-kpi-grid">
        <div class="pegawai-kpi-card">
            <div class="kpi-header">
                <div class="kpi-label">Total Pegawai</div>
                <div class="kpi-icon blue"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="kpi-body">
                <div class="kpi-value">{{ $tot_pegawai }}</div>
                <div class="kpi-desc">Pegawai yang dinilai</div>
            </div>
        </div>

        <div class="pegawai-kpi-card">
            <div class="kpi-header">
                <div class="kpi-label">Dokumen Terkumpul</div>
                <div class="kpi-icon orange"><i class="bi bi-folder-fill"></i></div>
            </div>
            <div class="kpi-body">
                <div class="kpi-value">{{ $tot_dokumen }}</div>
                <div class="kpi-desc">Evidence diunggah</div>
            </div>
        </div>

        <div class="pegawai-kpi-card">
            <div class="kpi-header">
                <div class="kpi-label">Menunggu Review</div>
                <div class="kpi-icon purple"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="kpi-body">
                <div class="kpi-value">{{ $belum_dinilai }}</div>
                <div class="kpi-desc">Belum divalidasi</div>
            </div>
        </div>

        <div class="pegawai-kpi-card">
            <div class="kpi-header">
                <div class="kpi-label">Selesai Dinilai</div>
                <div class="kpi-icon green"><i class="bi bi-check2-all"></i></div>
            </div>
            <div class="kpi-body">
                <div class="kpi-value">{{ $tot_dinilai }}</div>
                <div class="kpi-desc">Unit tervalidasi</div>
            </div>
        </div>
    </div>

    <!-- BAWAH: STATISTIK & TIMELINE -->
    <div class="dashboard-row">
        <div class="progress-card">
            <div class="card-header">
                <h3>Statistik Kompetensi Pegawai</h3>
                <span>Total Dinilai: {{ $tot_dinilai }}</span>
            </div>
            
            <div class="progress-detail">
                <div class="progress-item">
                    <span>Sangat Kompeten / Kompeten (≥ 70)</span>
                    <strong>{{ $pct_kompeten }}%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bar-kompeten" style="width: {{ $pct_kompeten }}%;"></div>
                </div>

                <div class="progress-item" style="margin-top: 10px;">
                    <span>Cukup Kompeten (55 - 69)</span>
                    <strong>{{ $pct_cukup }}%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bar-cukup" style="width: {{ $pct_cukup }}%;"></div>
                </div>

                <div class="progress-item" style="margin-top: 10px;">
                    <span>Perlu Penguatan (< 55)</span>
                    <strong>{{ $pct_bina }}%</strong>
                </div>
                <div class="progress">
                    <div class="progress-bar bar-bina" style="width: {{ $pct_bina }}%;"></div>
                </div>
            </div>
        </div>

        <div class="timeline-card">
            <div class="card-header" style="margin-bottom: 20px;">
                <h3>Antrean Upload Terbaru</h3>
                <a href="#" style="font-size:12px; color:#3e54a0; text-decoration:none; font-weight:600;">Lihat Semua</a>
            </div>
            <div class="timeline">
                @forelse($timeline as $item)
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-title"><b>{{ $item->pegawai_nama }}</b> mengunggah evidence.</div>
                            <div class="timeline-desc">
                                Unit: {{ $item->judul_unit }}<br>
                                <small><i class="bi bi-clock"></i> {{ date('d M Y, H:i', strtotime($item->tanggal_upload)) }}</small>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style='color:#94a3b8; font-size: 13px; text-align: center; margin-top:20px;'>Belum ada dokumen baru.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection