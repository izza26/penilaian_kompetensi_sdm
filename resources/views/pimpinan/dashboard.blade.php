@extends('layouts.app')

@section('title', 'Dashboard Pimpinan')
@section('page_title', 'Dashboard Pimpinan')
@section('page_subtitle', 'Pantau antrean penilaian dan perkembangan kompetensi pegawai Anda')
@push('styles')
    <!-- CSS Spesifik hanya untuk halaman Dashboard -->
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/dashboard.css') }}">
    
    <!-- Tambahan Inline CSS dari header.php lamamu -->
    <style>
        .top-header { display: flex; justify-content: space-between; align-items: center; padding: 12px 24px; min-height: 70px; background-color: #ffffff; margin-bottom: 25px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .header-left, .header-right { display: flex; align-items: center; gap: 15px; height: 100%; }
        .header-logo img { max-height: 38px; width: auto; }
        .header-title h1 { font-size: 18px; font-weight: 700; margin: 0 0 2px 0; color: #0f172a; }
        .header-title p { font-size: 11px; font-weight: 500; color: #64748b; margin: 0; }
        .header-icon { width: 40px; height: 40px; font-size: 18px; background: transparent; border: none; color: #64748b; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .profile-box { display: flex; align-items: center; gap: 12px; padding: 6px 12px; border-radius: 30px; text-decoration: none; cursor: pointer; }
        .profile-info { display: flex; flex-direction: column; align-items: flex-end; }
        .profile-name { font-size: 13px; font-weight: 700; color: #0f172a; }
        .profile-role { font-size: 11px; font-weight: 500; color: #64748b; margin-top: 2px;}
        .profile-avatar { width: 38px; height: 38px; font-size: 13px; background-color: #1B2D46; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; }
    </style>
@endpush

@section('content')

    <!-- ALERT PERIODE PENILAIAN -->
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
                <p style="margin:0; font-size: 12px;">Sistem penilaian saat ini sedang ditutup atau belum diatur.</p>
            </div>
        </div>
    @endif

    <!-- WELCOME CARD -->
    <div class="welcome-card" style="background: linear-gradient(135deg, #ebdbb6 0%, #D6BB80 100%);">
        <div class="welcome-text">
            <h2>Halo, {{ $pimpinan->pegawai_nama }}</h2>
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
        <div class="pegawai-kpi-card blue">
            <div class="pegawai-kpi-top">
                <div class="pegawai-kpi-icon"><i class="bi bi-people-fill"></i></div>
                <div class="pegawai-kpi-label">Total Pegawai</div>
            </div>
            <div class="pegawai-kpi-content">
                <div class="pegawai-kpi-value blue-text">{{ $tot_pegawai }}</div>
                <div class="pegawai-kpi-desc">Jumlah pegawai yang dinilai.</div>
            </div>
        </div>

        <div class="pegawai-kpi-card orange">
            <div class="pegawai-kpi-top">
                <div class="pegawai-kpi-icon"><i class="bi bi-folder-fill"></i></div>
                <div class="pegawai-kpi-label">Dokumen Terkumpul</div>
            </div>
            <div class="pegawai-kpi-content">
                <div class="pegawai-kpi-value orange-text">{{ $tot_dokumen }}</div>
                <div class="pegawai-kpi-desc">Evidence yang sudah diunggah.</div>
            </div>
        </div>

        <div class="pegawai-kpi-card purple">
            <div class="pegawai-kpi-top">
                <div class="pegawai-kpi-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="pegawai-kpi-label">Menunggu Review</div>
            </div>
            <div class="pegawai-kpi-content">
                <div class="pegawai-kpi-value purple-text">{{ $belum_dinilai }}</div>
                <div class="pegawai-kpi-desc">Evidence belum divalidasi.</div>
            </div>
        </div>

        <div class="pegawai-kpi-card green">
            <div class="pegawai-kpi-top">
                <div class="pegawai-kpi-icon"><i class="bi bi-check2-all"></i></div>
                <div class="pegawai-kpi-label">Selesai Dinilai</div>
            </div>
            <div class="pegawai-kpi-content">
                <div class="pegawai-kpi-value green-text">{{ $tot_dinilai }}</div>
                <div class="pegawai-kpi-desc">Unit kompetensi tervalidasi.</div>
            </div>
        </div>
    </div>

    <!-- BAWAH: STATISTIK & TIMELINE -->
    <div class="dashboard-row">
        <div class="progress-card">
            <div class="card-header">
                <h3>Statistik Kompetensi Pegawai</h3>
                <span>Total Unit Dinilai: {{ $tot_dinilai }}</span>
            </div>
            
            <div class="progress-detail" style="margin-top: 10px;">
                <div class="progress-item">
                    <span>Sangat Kompeten / Kompeten (≥ 70)</span>
                    <strong>{{ $pct_kompeten }}%</strong>
                </div>
            </div>
            <div class="progress" style="height: 8px; margin-bottom: 15px; background: #e2e8f0; border-radius: 10px;">
                <div class="progress-bar bar-kompeten" style="width: {{ $pct_kompeten }}%; background: #10b981; height: 100%; border-radius: 10px;"></div>
            </div>

            <div class="progress-detail">
                <div class="progress-item">
                    <span>Cukup Kompeten (55 - 69)</span>
                    <strong>{{ $pct_cukup }}%</strong>
                </div>
            </div>
            <div class="progress" style="height: 8px; margin-bottom: 15px; background: #e2e8f0; border-radius: 10px;">
                <div class="progress-bar bar-cukup" style="width: {{ $pct_cukup }}%; background: #f59e0b; height: 100%; border-radius: 10px;"></div>
            </div>

            <div class="progress-detail">
                <div class="progress-item">
                    <span>Perlu Penguatan (< 55)</span>
                    <strong>{{ $pct_bina }}%</strong>
                </div>
            </div>
            <div class="progress" style="height: 8px; background: #e2e8f0; border-radius: 10px;">
                <div class="progress-bar bar-bina" style="width: {{ $pct_bina }}%; background: #ef4444; height: 100%; border-radius: 10px;"></div>
            </div>
        </div>

        <div class="timeline-card">
            <h3>Antrean Upload Terbaru</h3>
            <div class="timeline">
                @forelse($timeline as $item)
                    <div class="timeline-item" style="display: flex; gap: 15px; margin-bottom: 15px;">
                        <div class="timeline-icon" style="background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; padding: 10px; border-radius: 50%; width: 40px; height: 40px; text-align: center;">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div>
                            <div class="timeline-title">
                                <b>{{ $item->pegawai_nama }}</b> baru saja mengunggah evidence.
                            </div>
                            <div class="timeline-desc">
                                Unit: {{ $item->judul_unit }}<br>
                                <small style="color: #94a3b8;"><i class="bi bi-clock"></i> {{ date('d M Y, H:i', strtotime($item->tanggal_upload)) }}</small>
                            </div>
                        </div>
                    </div>
                @empty
                    <p style='color:#777; font-size: 14px; margin-top:10px;'>Belum ada pegawai yang mengunggah dokumen baru saat ini.</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection