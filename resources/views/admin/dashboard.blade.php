@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('page_title', 'Dashboard Admin')
@section('page_subtitle', 'Ringkasan sistem penilaian kompetensi Museum Geologi')

<style>
    /* ================= DASHBOARD CLEAN SAAS (RESIZED & NEAT) ================= */

    /* Welcome Card */
    .welcome-card {
        display: flex; align-items: center; justify-content: space-between;
        padding: 28px 36px; border-radius: 24px; background: #ffffff;
        margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        position: relative; overflow: hidden;
    }
    .welcome-text { max-width: 65%; }
    .welcome-text h2 { margin: 0 0 8px 0; font-size: 20px; font-weight: 700; color: #1e293b; } /* Diperkecil */
    .welcome-text p { margin: 0 0 20px 0; color: #64748b; font-size: 13px; line-height: 1.6; } /* Diperkecil */

    /* Tombol Melengkung (Pill) */
    .btn-pill {
        display: inline-block; padding: 10px 24px; background: #f4f7fe; color: #3e54a0;
        border-radius: 50px; font-size: 12px; font-weight: 700; text-decoration: none;
        transition: 0.2s; border: none;
    }
    .btn-pill:hover { background: #3e54a0; color: #ffffff; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(62, 84, 160, 0.2); }
    .welcome-illustration { font-size: 60px; color: #f1f5f9; margin-right: 20px; } /* Icon diperkecil */

    /* Header Seksi KPI */
    .kpi-section-title { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .kpi-section-title h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; } /* Diperkecil */
    .badge-pill { background: #ffffff; padding: 5px 14px; border-radius: 50px; font-size: 11px; color: #64748b; font-weight: 600; box-shadow: 0 2px 10px rgba(0,0,0,0.02); border: 1px solid #f1f5f9; }

    /* KPI Grid */
    .pegawai-kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 24px; }
    .pegawai-kpi-card { background: #ffffff; border-radius: 24px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); display: flex; flex-direction: column; transition: 0.3s; }
    .pegawai-kpi-card:hover { transform: translateY(-4px); box-shadow: 0 15px 35px rgba(62, 84, 160, 0.08); }

    .kpi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
    .kpi-label { font-size: 12px; color: #64748b; font-weight: 600; }
    .kpi-icon { width: 38px; height: 38px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; }

    /* Angka Besar ala Inspo (Disesuaikan proporsinya) */
    .kpi-value { font-size: 28px; font-weight: 700; color: #1e293b; margin-bottom: 4px; line-height: 1; letter-spacing: -0.5px; } /* Diperkecil dari 36px */
    .kpi-desc { font-size: 11px; color: #94a3b8; font-weight: 500; }

    /* Warna Icon */
    .blue { background: #eff6ff; color: #3b82f6; }
    .orange { background: #fff7ed; color: #f97316; }
    .purple { background: #faf5ff; color: #a855f7; }
    .green { background: #ecfdf5; color: #10b981; }

    /* Row Bawah */
    .dashboard-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 24px; }
    .progress-card, .timeline-card { background: #ffffff; border-radius: 24px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }

    .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-header h3, .timeline-card h3 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; } /* Diperkecil */
    .card-header span { font-size: 11px; background: #f4f7fe; padding: 6px 14px; border-radius: 50px; color: #3e54a0; font-weight: 600; }

    /* Progress Bar */
    .progress-detail { margin-top: 10px; display: flex; flex-direction: column; gap: 6px; }
    .progress-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
    .progress-item span { color: #64748b; font-size: 12px; font-weight: 500; } /* Diperkecil */
    .progress-item strong { color: #1e293b; font-size: 13px; font-weight: 700; } /* Diperkecil */

    .progress { width: 100%; height: 10px; background: #f1f5f9; border-radius: 50px; overflow: hidden; margin-bottom: 16px; } /* Ditipiskan dari 14px */
    .progress-bar { height: 100%; border-radius: 50px; }
    .bar-kompeten { background-color: #3e54a0; } 
    .bar-cukup { background-color: #0ea5e9; } 
    .bar-bina { background-color: #f43f5e; } 

    /* Timeline Ala Inspo */
    .timeline { display: flex; flex-direction: column; gap: 14px; margin-top: 16px; }
    .timeline-item { display: flex; gap: 14px; align-items: center; padding-bottom: 14px; border-bottom: 1px dashed #e2e8f0; }
    .timeline-item:last-child { border-bottom: none; padding-bottom: 0; }
    .timeline-icon { width: 38px; height: 38px; border-radius: 50%; background: #f4f7fe; color: #3e54a0; display: flex; justify-content: center; align-items: center; font-size: 16px; flex-shrink: 0;}
    .timeline-content { flex: 1; }
    .timeline-title { font-size: 12px; color: #1e293b; margin-bottom: 2px; font-weight: 500;}
    .timeline-title b { font-weight: 700; }
    .timeline-desc { color: #64748b; font-size: 11px; line-height: 1.4; }

    @media(max-width: 992px){ .dashboard-row { grid-template-columns: 1fr; } .pegawai-kpi-grid { grid-template-columns: repeat(2, 1fr); } }
    @media(max-width: 768px){ .pegawai-kpi-grid { grid-template-columns: 1fr; } }
</style>

@section('content')
<!-- Wecome Card Modern -->
<div class="welcome-card">
    <div class="welcome-text">
        <h2>Halo, {{ $nama_admin }}! 👋</h2>
        <p>Selamat datang di panel pengelola Sistem Penilaian Kompetensi SDM. Pantau aktivitas pegawai, instrumen, dan hasil penilaian dengan mudah.</p>
        <a href="{{ route('admin.penilaian.index') }}" class="btn-pill">Tinjau Penilaian</a>
    </div>
    <div class="welcome-illustration">
        <i class="bi bi-laptop"></i>
    </div>
</div>

<div class="kpi-section-title">
    <h3>Performa & Metrik</h3>
    <span class="badge-pill">{{ $tanggalSekarang }}</span>
</div>

<!-- KPI Grid -->
<div class="pegawai-kpi-grid">
    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Total Pegawai</div>
            <div class="kpi-icon blue"><i class="bi bi-people-fill"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $tot_pegawai }}</div>
            <div class="kpi-desc">Akun terdaftar</div>
        </div>
    </div>

    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Penilaian Masuk</div>
            <div class="kpi-icon orange"><i class="bi bi-inbox-fill"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $tot_masuk }}</div>
            <div class="kpi-desc">Dokumen di sistem</div>
        </div>
    </div>

    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Menunggu Review</div>
            <div class="kpi-icon purple"><i class="bi bi-hourglass-split"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $tot_tunggu }}</div>
            <div class="kpi-desc">Belum dinilai</div>
        </div>
    </div>

    <div class="pegawai-kpi-card">
        <div class="kpi-header">
            <div class="kpi-label">Sudah Dinilai</div>
            <div class="kpi-icon green"><i class="bi bi-shield-check"></i></div>
        </div>
        <div class="kpi-body">
            <div class="kpi-value">{{ $tot_selesai }}</div>
            <div class="kpi-desc">Selesai divalidasi</div>
        </div>
    </div>
</div>

<!-- Row Bawah -->
<div class="dashboard-row">
    <!-- Progress Card -->
    <div class="progress-card">
        <div class="card-header">
            <h3>Statistik Kompetensi</h3>
            <span>Total Elemen: {{ $tot_elemen }}</span>
        </div>
        
        <div class="progress-detail">
            <div class="progress-item">
                <span>Sangat Kompeten / Kompeten (≥ 70)</span>
                <strong>{{ $pct_kompeten }}%</strong>
            </div>
            <div class="progress">
                <div class="progress-bar bar-kompeten" style="width:{{ $pct_kompeten }}%"></div>
            </div>
            
            <div class="progress-item" style="margin-top: 10px;">
                <span>Cukup Kompeten (55 - 69)</span>
                <strong>{{ $pct_cukup }}%</strong>
            </div>
            <div class="progress">
                <div class="progress-bar bar-cukup" style="width:{{ $pct_cukup }}%"></div>
            </div>
            
            <div class="progress-item" style="margin-top: 10px;">
                <span>Perlu Pembinaan (< 55)</span>
                <strong>{{ $pct_bina }}%</strong>
            </div>
            <div class="progress">
                <div class="progress-bar bar-bina" style="width:{{ $pct_bina }}%"></div>
            </div>
        </div>
    </div>

    <!-- Timeline Card -->
    <div class="timeline-card">
        <div class="card-header" style="margin-bottom: 20px;">
            <h3>Aktivitas Terbaru</h3>
            <a href="#" style="font-size:12px; color:#3e54a0; text-decoration:none; font-weight:600;">Lihat Semua</a>
        </div>
        <div class="timeline">
            @forelse ($aktivitas as $row)
                <div class="timeline-item">
                    <div class="timeline-icon"><i class="bi bi-person-badge"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-title">Data baru: <b>{{ $row->pegawai_nama }}</b></div>
                        <div class="timeline-desc">{{ $row->jabatan ?? '-' }} - {{ $row->unit_kerja }}</div>
                    </div>
                </div>
            @empty
                <p style='color:#94a3b8; font-size: 13px; text-align: center; margin-top:20px;'>Belum ada aktivitas baru.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection