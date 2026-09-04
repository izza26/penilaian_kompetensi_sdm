@extends('layouts.app')
@section('title', 'Isi Bukti Kerja (Per KUK)')
@section('page_title', 'Isi Bukti Kerja')
@section('page_subtitle', 'Lampirkan dokumen bukti kerja Anda untuk setiap Kriteria Unjuk Kerja (KUK).')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mammoth/1.4.21/mammoth.browser.min.js"></script>
    
    <style>
        .toolbar-card { background: #ffffff; border-radius: 24px; padding: 16px 24px; border: none; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; gap: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); flex-wrap: wrap; }
        .toolbar-info { display: flex; align-items: center; gap: 12px; }
        .info-text { font-size: 13px; color: #64748b; line-height: 1.4;}
        .info-text b { color: #1e293b; }
        .badge-status { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px;}
        .bg-green { background: #ecfdf5; color: #059669; }
        .bg-red { background: #fff1f2; color: #e11d48; }

        /* --- CSS FILTER UK BUTTONS BARU (DISEMPURNAKAN) --- */
        .uk-filter-wrapper { margin-bottom: 24px; }
        .uk-filter-label { font-size: 11px; font-weight: 800; color: #64748b; text-transform: uppercase; margin-bottom: 12px; letter-spacing: 0.5px; display: flex; align-items: center; gap: 8px;}
        
        /* Align-items stretch memastikan semua kartu tingginya sama rata */
        .uk-filter-list { display: flex; gap: 16px; overflow-x: auto; padding-bottom: 16px; scroll-behavior: smooth; align-items: stretch; }
        .uk-filter-list::-webkit-scrollbar { height: 8px; }
        .uk-filter-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Lebar kartu ditambah, flex-direction column agar isinya bisa diatur atas-bawah */
        .uk-btn { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; min-width: 280px; max-width: 280px; text-align: left; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 10px rgba(0,0,0,0.02); display: flex; flex-direction: column; gap: 8px;}
        .uk-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.05); border-color: #cbd5e1; }
        .uk-btn.active { border: 2px solid #3e54a0; background: #f8fafc; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.1); }
        
        .uk-btn-title { font-size: 11px; font-weight: 800; color: #3e54a0; }
        
        /* Flex-grow 1 membuat deskripsi memakan ruang kosong, line-clamp membatasi maks 3 baris */
        .uk-btn-desc { font-size: 13px; font-weight: 700; color: #1e293b; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; flex-grow: 1; margin-bottom: 4px;}
        
        /* Margin-top auto memaksa kotak stat selalu berada di bawah berapapun tinggi teksnya */
        .uk-btn-stat { font-size: 11px; font-weight: 700; display: flex; align-items: center; gap: 6px; padding: 8px 12px; border-radius: 8px; margin-top: auto;}

        /* Warna Status Filter */
        .uk-empty { border-left: 4px solid #ef4444; }
        .uk-empty .uk-btn-stat { background: #fef2f2; color: #e11d48; }
        .uk-partial { border-left: 4px solid #f59e0b; }
        .uk-partial .uk-btn-stat { background: #fffbeb; color: #d97706; }
        .uk-complete { border-left: 4px solid #10b981; }
        .uk-complete .uk-btn-stat { background: #ecfdf5; color: #059669; }
        .uk-all { border-left: 4px solid #3e54a0; }
        .uk-all .uk-btn-stat { background: #f4f7fe; color: #3e54a0; }

        /* QUICK NAVIGATION BUTTONS (TRANSPARAN / GHOST BUTTON) */
        .nav-quick-bar { display: none; background: transparent !important; padding: 0 0 16px 0; margin-bottom: 24px; box-shadow: none !important; border: none !important; overflow-x: auto; white-space: nowrap; gap: 10px; align-items: center;}
        
        /* RAHASIA MENGHILANGKAN SCROLLBAR RAKSASA WINDOWS */
        .nav-quick-bar::-webkit-scrollbar { height: 8px; }
        .nav-quick-bar::-webkit-scrollbar-track { background: transparent; }
        .nav-quick-bar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

        .nav-quick-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; border: 1px solid #cbd5e1; background: transparent; color: #64748b; transition: 0.2s; text-decoration: none;}
        .nav-quick-btn:hover { background: #f8fafc; border-color: #94a3b8; color: #1e293b;}
        
        /* Sudah Upload (Hijau Transparan) */
        .nav-quick-btn.nav-done { background: transparent; color: #059669; border-color: #10b981; }
        .nav-quick-btn.nav-done:hover { background: #ecfdf5; }
        
        /* Belum Upload (Merah Transparan) */
        .nav-quick-btn.nav-pending { background: transparent; color: #e11d48; border-color: #fda4af; }
        .nav-quick-btn.nav-pending:hover { background: #fff1f2; }

        .unit-card { background: #ffffff; border-radius: 24px; border: none; margin-bottom: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; }
        .unit-header { background: #3e54a0; padding: 20px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;}
        .unit-header-left h3 { margin: 0; font-size: 16px; color: #ffffff; font-weight: 700; line-height: 1.4;}
        
        .unit-header-stats { display: flex; gap: 10px; flex-wrap: wrap; }
        .stat-pill { background: rgba(255,255,255,0.15); padding: 6px 14px; border-radius: 50px; font-size: 12px; color: white; display: flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,0.2);}
        .stat-pill.success { background: #ecfdf5; color: #059669; border-color: #a7f3d0; font-weight: 700;}
        .stat-pill span { font-size: 10px; color: rgba(255,255,255,0.7); text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px;}
        .stat-pill.success span { color: #10b981; }
        .stat-pill b { font-size: 14px; font-weight: 800;}
        
        .elemen-section { border-bottom: 4px solid #e2e8f0; }
        .elemen-header { padding: 20px 24px; background-color: #f8fafc; }
        .elemen-kode { font-weight: 800; color: #3e54a0; font-size: 13px; margin-bottom: 4px; display: block; }
        .elemen-nama { font-weight: 700; color: #1e293b; font-size: 14px; margin: 0; }
        
        .kuk-item { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-top: 1px dashed #cbd5e1; background-color: #ffffff; transition: 0.2s; gap: 24px; scroll-margin-top: 100px;}
        .kuk-item:hover { background-color: #f8fafc; }
        .kuk-text-container { flex: 1; display: flex; flex-direction: column; gap: 8px;}
        .kuk-text { font-size: 14px; color: #334155; font-weight: 500; line-height: 1.5; }
        .kuk-number { font-weight: 800; color: #3e54a0; margin-right: 4px; font-size: 15px;}
        
        .upload-action-area { display: flex; align-items: center; gap: 12px; min-width: 380px; justify-content: flex-end; }
        .form-upload-inline { display: flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 6px 6px 6px 14px; border-radius: 50px; border: 1px solid #cbd5e1; transition: 0.2s; flex: 1; }
        .form-upload-inline:hover { border-color: #3e54a0; background: #ffffff; box-shadow: 0 4px 15px rgba(62, 84, 160, 0.08); }
        .file-label-text { font-size: 11px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; flex: 1; cursor: pointer;}
        .btn-browse { background: #e2e8f0; color: #1e293b; border: none; padding: 6px 14px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s;}
        .btn-browse:hover { background: #cbd5e1; }
        .btn-submit-cloud { background: #3e54a0; color: white; border: none; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s; display: none; align-items: center; gap: 6px;}
        .btn-submit-cloud:hover { background: #2b3a70; }
        
        .uploaded-state { display: flex; align-items: center; gap: 12px; background: #ecfdf5; border: 1px solid #10b981; padding: 6px 6px 6px 16px; border-radius: 50px; flex: 1; justify-content: space-between;}
        .uploaded-text { font-size: 12px; color: #059669; font-weight: 700; display: flex; align-items: center; gap: 6px;}
        .action-group { display: flex; gap: 6px; }
        .btn-circle { width: 30px; height: 30px; border-radius: 50%; border: none; display: flex; justify-content: center; align-items: center; font-size: 13px; cursor: pointer; transition: 0.2s; }
        
        .btn-preview-local { background: #ffffff; color: #059669; border: 1px solid #a7f3d0; display: none; }
        .btn-preview-local:hover { background: #059669; color: white; border-color: #059669; }

        .btn-preview { background: #ffffff; color: #3e54a0; border: 1px solid #bfdbfe; }
        .btn-preview:hover { background: #3e54a0; color: white; border-color: #3e54a0; }
        .btn-delete { background: #ffffff; color: #e11d48; border: 1px solid #fecdd3; }
        .btn-delete:hover { background: #e11d48; color: white; border-color: #e11d48; }

        .badge-status-sm { padding: 4px 10px; border-radius: 50px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;}
        
        .sub-items-container { background: #f8fafc; border-radius: 12px; padding: 0; border: 1px solid #e2e8f0; margin-top: 15px; overflow: hidden; scroll-margin-top: 100px;}
        .sub-item-header { padding: 12px 20px; background: #e2e8f0; font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase; letter-spacing: 1px;}
        .sub-item-row { display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-top: 1px dashed #cbd5e1; gap: 20px;}
        .sub-item-info { flex: 1; }
        .sub-item-title { color: #1e293b; font-size: 13px; font-weight: 700; margin-bottom: 4px; display: block;}
        .sub-item-req { font-size: 11px; color: #3e54a0; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; background: #f1f5f9; padding: 4px 10px; border-radius: 50px;}

        .swal-wide-popup { border-radius: 24px !important; padding: 24px !important; }
        .excel-preview-table { width: 100%; border-collapse: collapse; font-size: 11px; color: #334155; }
        .excel-preview-table th, .excel-preview-table td { border: 1px solid #cbd5e1; padding: 8px 12px; white-space: nowrap; }
        .excel-preview-table tr:nth-child(even) { background-color: #f8fafc; }
        
        @media(max-width: 992px) { 
            .kuk-item { flex-direction: column; align-items: flex-start; }
            .upload-action-area { width: 100%; justify-content: flex-start; margin-top: 10px; }
            .sub-item-row { flex-direction: column; align-items: flex-start; }
            .toolbar-card { flex-direction: column; align-items: stretch; }
            .unit-header-stats { width: 100%; }
        }
    </style>
@endpush

@section('content')

    <!-- KARTU FILTER UK (PENGGANTI DROPDOWN) -->
    @if(!empty($dataGrouped))
        @php
            $tot_target = 0; $tot_sudah = 0;
            foreach($unitStats as $s) {
                $tot_target += $s['target'];
                $tot_sudah += $s['uploaded'];
            }
            $tot_selisih = $tot_target - $tot_sudah;
            $allClass = ($tot_sudah == 0) ? 'uk-empty' : (($tot_sudah < $tot_target) ? 'uk-partial' : 'uk-complete');
        @endphp
        <div class="uk-filter-wrapper">
            <div class="uk-filter-label"><i class="bi bi-funnel-fill" style="color:#3e54a0; font-size:14px;"></i> PILIH UNIT KOMPETENSI:</div>
            <div class="uk-filter-list">
                <!-- Tombol Tampilkan Semua -->
                <button class="uk-btn uk-all active" id="filter_btn_ALL" onclick="setFilterUnit('ALL')">
                    <div class="uk-btn-title">MODE KESELURUHAN</div>
                    <div class="uk-btn-desc">Tampilkan Semua Unit Kompetensi Anda</div>
                    <div class="uk-btn-stat">
                        <i class="bi bi-bar-chart-fill"></i> Target: {{ $tot_target }} | Sudah: {{ $tot_sudah }} | Selisih: {{ $tot_selisih }}
                    </div>
                </button>
                
                <!-- Tombol Per Unit Kompetensi -->
                @foreach($dataGrouped as $unitKey => $elemenGroup)
                    @php 
                        $parts = explode("|||", $unitKey);
                        $kd_unit = $parts[1]; $jd_unit = $parts[2]; 
                        $stats = $unitStats[$unitKey];
                        $selisih = $stats['target'] - $stats['uploaded'];
                        
                        // Logika Warna
                        if($stats['uploaded'] == 0) { $statusClass = 'uk-empty'; $icon = 'bi-x-circle-fill'; } 
                        elseif($stats['uploaded'] < $stats['target']) { $statusClass = 'uk-partial'; $icon = 'bi-clock-history'; } 
                        else { $statusClass = 'uk-complete'; $icon = 'bi-check-circle-fill'; }
                    @endphp
                    <button class="uk-btn {{ $statusClass }}" id="filter_btn_{{ $kd_unit }}" onclick="setFilterUnit('{{ $kd_unit }}')">
                        <div class="uk-btn-title">[{{ $kd_unit }}]</div>
                        <div class="uk-btn-desc" title="{{ $jd_unit }}">{{ $jd_unit }}</div>
                        <div class="uk-btn-stat">
                            <i class="bi {{ $icon }}"></i> Target: {{ $stats['target'] }} | Sudah: {{ $stats['uploaded'] }}
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- QUICK NAVIGATION BAR -->
    @foreach($unitNavs as $unitKd => $navItems)
        <div class="nav-quick-bar" id="nav_quick_{{ $unitKd }}">
            <span style="font-size: 11px; font-weight: 800; color: #434c5a; margin-right: 8px;">NAVIGASI CEPAT:</span>
            @foreach($navItems as $nav)
                <a href="#kuk_{{ $nav['id'] }}" class="nav-quick-btn {{ $nav['is_uploaded'] ? 'nav-done' : 'nav-pending' }}" title="{{ $nav['tooltip'] }}">
                    @if($nav['is_uploaded'])
                        <i class="bi bi-check-circle-fill"></i>
                    @endif 
                    <!-- Hapus ikon seru, murni teks saja -->
                    {{ $nav['label'] }}
                </a>
            @endforeach
        </div>
    @endforeach

    @if(empty($dataGrouped))
        <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 24px; border: 1px dashed #cbd5e1;">
            <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1; display: block;"></i>
            <h3 style="color: #1e293b; margin-top: 15px; font-size: 18px;">Belum Ada KUK</h3>
            <p style="color: #64748b; font-size: 13px;">Anda belum ditugaskan aktivitas KUK apapun pada periode ini.</p>
        </div>
    @else
        @foreach($dataGrouped as $unitKey => $elemenGroup)
            @php 
                $parts = explode("|||", $unitKey);
                $num = $parts[0]; $kd_unit = $parts[1]; $jd_unit = $parts[2]; 
                $stats = $unitStats[$unitKey];
                $selisih = $stats['target'] - $stats['uploaded'];
            @endphp
            
            <div class="unit-card" data-unit="{{ $kd_unit }}">
                <div class="unit-header">
                    <div class="unit-header-left">
                        <span style="color:#bfdbfe; font-size: 11px; font-weight:700; letter-spacing:1px; display:block; margin-bottom:4px;">UNIT KOMPETENSI KE-{{ $num }}</span>
                        <h3>[{{ $kd_unit }}] {{ $jd_unit }}</h3>
                    </div>
                    <!-- STATISTIK TARGET & REALISASI YANG DIPISAH -->
                    <div class="unit-header-stats">
                        @if($selisih == 0)
                            <div class="stat-pill success">
                                <i class="bi bi-check-circle-fill" style="font-size: 16px;"></i>
                                <div><span>Status</span><br><b>SELESAI</b></div>
                            </div>
                        @else
                            <div class="stat-pill" title="Target Upload">
                                <span>Target:</span> <b>{{ $stats['target'] }}</b>
                            </div>
                            <div class="stat-pill" title="Sudah Diupload" style="background: rgba(16, 185, 129, 0.2); border-color: rgba(16, 185, 129, 0.4);">
                                <span>Sudah:</span> <b style="color: #a7f3d0;">{{ $stats['uploaded'] }}</b>
                            </div>
                            <div class="stat-pill" title="Kekurangan / Selisih" style="background: rgba(225, 29, 72, 0.2); border-color: rgba(225, 29, 72, 0.4);">
                                <span>Selisih:</span> <b style="color: #fecdd3;">{{ $selisih }}</b>
                            </div>
                        @endif
                    </div>
                </div>
                
                <div>
                    @foreach($elemenGroup as $elemenKey => $kukList)
                        @php 
                            $elArr = explode("|||", $elemenKey);
                            $elKode = $elArr[0];
                            $elNama = $elArr[1];
                        @endphp
                        
                        <div class="elemen-section">
                            <div class="elemen-header">
                                <span class="elemen-kode">{{ $elKode }}</span>
                                <h4 class="elemen-nama">{{ $elNama }}</h4>
                            </div>
                            
                            <div>
                                @foreach($kukList as $kuk)
                                    @php 
                                        $badgeClass = 'bg-secondary text-white';
                                        if ($kuk['status_asli'] == 'Kompeten' || $kuk['status_asli'] == 'Sudah Diunggah Lengkap') $badgeClass = 'bg-success text-white';
                                        elseif ($kuk['status_asli'] == 'Revisi' || $kuk['status_asli'] == 'Belum Lengkap') $badgeClass = 'bg-danger text-white';
                                        elseif ($kuk['status_asli'] == 'Menunggu Review') $badgeClass = 'bg-warning text-dark';
                                        $safe_id = \Illuminate\Support\Str::slug($kuk['aktivitas_id']);
                                    @endphp

                                    <div class="kuk-item" id="kuk_{{ $safe_id }}">
                                        <div class="kuk-text-container">
                                            <div class="kuk-text">
                                                <span class="kuk-number">{{ $kuk['kuk_ke'] }}.</span> 
                                                {{ $kuk['teks_kuk'] }}
                                            </div>
                                            @if(!$kuk['is_bercabang'])
                                            <div>
                                                <span style="font-size: 11px; color: #3e54a0; font-weight: 600; background: #f1f5f9; padding: 4px 10px; border-radius: 50px;">
                                                    <i class="bi bi-file-earmark-text"></i> Wajib: {{ $kuk['keterangan_evidence'] }}
                                                </span>
                                            </div>
                                            @endif
                                        </div>

                                        <div class="upload-action-area">
                                            <span class="badge-status-sm {{ $badgeClass }}">{{ $kuk['status_asli'] }}</span>

                                            <!-- === JIKA KUK TUNGGAL (NORMAL) === -->
                                            @if(!$kuk['is_bercabang'])
                                                @php
                                                    $file_url = '';
                                                    if ($kuk['is_uploaded']) {
                                                        $fp = $kuk['file_path'];
                                                        if (file_exists(storage_path('app/public/uploads/evidence/' . $fp))) $file_url = asset('storage/uploads/evidence/' . $fp);
                                                        elseif (file_exists(public_path('uploads/evidence/' . $fp))) $file_url = asset('uploads/evidence/' . $fp);
                                                        else $file_url = env('AWS_URL') . '/uploads/evidence/' . $fp;
                                                    }
                                                    $deleteUrlTemplate = route('pegawai.aktivitas.destroy_bukti', [$kuk['aktivitas_id'], 'BUKTI_ID_PLACEHOLDER']);
                                                @endphp

                                                <div id="action_wrapper_{{ $safe_id }}" style="flex:1;">
                                                    <form id="form_upload_{{ $safe_id }}" action="{{ route('pegawai.aktivitas.upload', $kuk['aktivitas_id']) }}" method="POST" enctype="multipart/form-data" class="form-upload-inline" style="display: {{ $kuk['is_uploaded'] ? 'none' : 'flex' }}" onsubmit="uploadAJAX(event, this, '{{ $safe_id }}')">
                                                        @csrf
                                                        <label for="file_{{ $safe_id }}" class="file-label-text m-0" id="label_{{ $safe_id }}"><i class="bi bi-paperclip"></i> Pilih Dokumen...</label>
                                                        <input type="file" id="file_{{ $safe_id }}" name="file_evidence" style="display:none;" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" onchange="handleFileSelect(this, 'label_{{ $safe_id }}', 'btn_{{ $safe_id }}', 'btn_prev_local_{{ $safe_id }}')">
                                                        <button type="button" class="btn-circle btn-preview-local" id="btn_prev_local_{{ $safe_id }}" title="Pratinjau File" onclick="previewLocalFile('file_{{ $safe_id }}')"><i class="bi bi-eye-fill"></i></button>
                                                        <button type="button" class="btn-browse" onclick="document.getElementById('file_{{ $safe_id }}').click()" id="btn_browse_{{ $safe_id }}">Browse</button>
                                                        <button type="submit" class="btn-submit-cloud" id="btn_{{ $safe_id }}"><i class="bi bi-cloud-arrow-up-fill"></i> Simpan</button>
                                                    </form>

                                                    <div id="uploaded_state_{{ $safe_id }}" class="uploaded-state" style="display: {{ $kuk['is_uploaded'] ? 'flex' : 'none' }}">
                                                        <span class="uploaded-text">
                                                            <i class="bi bi-cloud-check-fill" style="font-size: 16px;"></i> Tersimpan
                                                            @if(!empty($kuk['tgl_upload']))
                                                                <span style="font-size: 10px; color: #059669; font-weight: 600; margin-left: 8px;">
                                                                    • {{ \Carbon\Carbon::parse($kuk['tgl_upload'])->translatedFormat('d M Y, H:i') }} WIB
                                                                </span>
                                                            @endif
                                                        </span>
                                                        <div class="action-group">
                                                            <button type="button" class="btn-circle btn-preview" id="btn_preview_cloud_{{ $safe_id }}" title="Pratinjau" onclick="previewServerFile('{{ $file_url }}', '{{ $kuk['file_path'] }}')"><i class="bi bi-eye-fill"></i></button>
                                                            <form id="form_delete_{{ $safe_id }}" action="{{ $kuk['is_uploaded'] ? route('pegawai.aktivitas.destroy_bukti', [$kuk['aktivitas_id'], $kuk['bukti_id']]) : '#' }}" data-action-template="{{ $deleteUrlTemplate }}" method="POST" style="margin:0;" onsubmit="deleteAJAX(event, this, '{{ $safe_id }}')">
                                                                @csrf @method('DELETE')
                                                                <button type="submit" class="btn-circle btn-delete" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- === JIKA KUK BERCABANG === -->
                                    @if($kuk['is_bercabang'])
                                        <div class="sub-items-container" id="kuk_{{ $safe_id }}">
                                            <div class="sub-item-header"><i class="bi bi-diagram-3-fill" style="margin-right: 6px;"></i> Detail Penugasan Multi-Cabang</div>
                                            
                                            @foreach($kuk['sub_items'] as $sub)
                                                @php 
                                                    $safe_sub_id = \Illuminate\Support\Str::slug($sub['aktivitas_id']); 
                                                    $sub_file_url = '';
                                                    if ($sub['is_uploaded']) {
                                                        $fp = $sub['file_path'];
                                                        if (file_exists(storage_path('app/public/uploads/evidence/' . $fp))) $sub_file_url = asset('storage/uploads/evidence/' . $fp);
                                                        elseif (file_exists(public_path('uploads/evidence/' . $fp))) $sub_file_url = asset('uploads/evidence/' . $fp);
                                                        else $sub_file_url = env('AWS_URL') . '/uploads/evidence/' . $fp;
                                                    }
                                                    $delSubUrlTpl = route('pegawai.aktivitas.destroy_bukti', [$sub['aktivitas_id'], 'BUKTI_ID_PLACEHOLDER']);
                                                @endphp

                                                <div class="sub-item-row">
                                                    <div class="sub-item-info">
                                                        <span class="sub-item-title">{{ $sub['sub_judul'] }}</span>
                                                        <span class="sub-item-req"><i class="bi bi-file-earmark-text"></i> Wajib: {{ $sub['keterangan_evidence'] }}</span>
                                                    </div>
                                                    
                                                    <div class="upload-action-area" id="action_wrapper_{{ $safe_sub_id }}" style="flex:1;">
                                                        <span class="badge-status-sm" style="background:#f1f5f9; color:#475569; margin-right:10px;">{{ $sub['status_asli'] }}</span>
                                                        <form id="form_upload_{{ $safe_sub_id }}" action="{{ route('pegawai.aktivitas.upload', $sub['aktivitas_id']) }}" method="POST" enctype="multipart/form-data" class="form-upload-inline" style="display: {{ $sub['is_uploaded'] ? 'none' : 'flex' }}" onsubmit="uploadAJAX(event, this, '{{ $safe_sub_id }}')">
                                                            @csrf
                                                            <label for="file_{{ $safe_sub_id }}" class="file-label-text m-0" id="label_{{ $safe_sub_id }}"><i class="bi bi-paperclip"></i> Pilih Dokumen...</label>
                                                            <input type="file" id="file_{{ $safe_sub_id }}" name="file_evidence" style="display:none;" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx" onchange="handleFileSelect(this, 'label_{{ $safe_sub_id }}', 'btn_{{ $safe_sub_id }}', 'btn_prev_local_{{ $safe_sub_id }}')">
                                                            <button type="button" class="btn-circle btn-preview-local" id="btn_prev_local_{{ $safe_sub_id }}" title="Pratinjau File Pilihan" onclick="previewLocalFile('file_{{ $safe_sub_id }}')"><i class="bi bi-eye-fill"></i></button>
                                                            <button type="button" class="btn-browse" onclick="document.getElementById('file_{{ $safe_sub_id }}').click()" id="btn_browse_{{ $safe_sub_id }}">Browse</button>
                                                            <button type="submit" class="btn-submit-cloud" id="btn_{{ $safe_sub_id }}"><i class="bi bi-cloud-arrow-up-fill"></i> Simpan</button>
                                                        </form>

                                                        <div id="uploaded_state_{{ $safe_sub_id }}" class="uploaded-state" style="display: {{ $sub['is_uploaded'] ? 'flex' : 'none' }}">
                                                            <span class="uploaded-text">
                                                                <i class="bi bi-cloud-check-fill" style="font-size: 16px;"></i> Tersimpan
                                                                @if(!empty($sub['tgl_upload']))
                                                                    <span style="font-size: 10px; color: #059669; font-weight: 600; margin-left: 8px;">
                                                                        • {{ \Carbon\Carbon::parse($sub['tgl_upload'])->translatedFormat('d M Y, H:i') }} WIB
                                                                    </span>
                                                                @endif
                                                            </span>
                                                            <div class="action-group">
                                                                <button type="button" class="btn-circle btn-preview" id="btn_preview_cloud_{{ $safe_sub_id }}" title="Pratinjau Cloud" onclick="previewServerFile('{{ $sub_file_url }}', '{{ $sub['file_path'] }}')"><i class="bi bi-eye-fill"></i></button>
                                                                <form id="form_delete_{{ $safe_sub_id }}" action="{{ $sub['is_uploaded'] ? route('pegawai.aktivitas.destroy_bukti', [$sub['aktivitas_id'], $sub['bukti_id']]) : '#' }}" data-action-template="{{ $delSubUrlTpl }}" method="POST" style="margin:0;" onsubmit="deleteAJAX(event, this, '{{ $safe_sub_id }}')">
                                                                    @csrf @method('DELETE')
                                                                    <button type="submit" class="btn-circle btn-delete" title="Hapus"><i class="bi bi-trash-fill"></i></button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    @endif
@endsection

@push('scripts')
<script>
    // --- FITUR FILTER TOMBOL ---
    function setFilterUnit(selectedUnit) {
        localStorage.setItem('geotrax_filter_unit', selectedUnit); 

        // Update Class Active pada Tombol Filter
        document.querySelectorAll('.uk-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        let activeBtn = document.getElementById('filter_btn_' + selectedUnit);
        if(activeBtn) activeBtn.classList.add('active');

        // Muncul/Sembunyikan Card Unit Kompetensi
        let cards = document.querySelectorAll('.unit-card');
        cards.forEach(card => {
            if (selectedUnit === 'ALL' || card.getAttribute('data-unit') === selectedUnit) { 
                card.style.display = 'block'; 
            } else { 
                card.style.display = 'none'; 
            }
        });

        // Muncul/Sembunyikan Navigasi Cepat
        let navBars = document.querySelectorAll('.nav-quick-bar');
        navBars.forEach(nav => { nav.style.display = 'none'; }); 
        
        if (selectedUnit !== 'ALL') {
            let activeNav = document.getElementById('nav_quick_' + selectedUnit);
            if (activeNav) activeNav.style.display = 'flex';
        }
    }

    window.onload = function() {
        let savedFilter = localStorage.getItem('geotrax_filter_unit') || 'ALL';
        setFilterUnit(savedFilter); 
    };

    function handleFileSelect(input, labelId, btnSubmitId, btnPrevLocalId) {
        let labelElem = document.getElementById(labelId);
        let btnSubmit = document.getElementById(btnSubmitId);
        let btnPrevLocal = document.getElementById(btnPrevLocalId);
        let btnBrowse = input.nextElementSibling.nextElementSibling; 

        if(input.files && input.files[0]) {
            labelElem.innerHTML = '<span style="color:#059669; font-weight:700;"><i class="bi bi-file-earmark-check"></i> ' + input.files[0].name + '</span>';
            btnBrowse.style.display = 'none'; 
            btnSubmit.style.display = 'inline-flex'; 
            btnPrevLocal.style.display = 'inline-flex'; 
        } else {
            labelElem.innerHTML = '<i class="bi bi-paperclip"></i> Pilih Dokumen...';
            btnBrowse.style.display = 'inline-block';
            btnSubmit.style.display = 'none';
            btnPrevLocal.style.display = 'none';
        }
    }

    async function uploadAJAX(event, form, safe_id) {
        event.preventDefault();
        let formData = new FormData(form);
        
        Swal.fire({ title: 'Mengupload Dokumen...', text: 'Mohon tunggu sebentar', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

        try {
            let res = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
            let result = await res.json();
            
            if(result.success) {
                Swal.fire({icon: 'success', title: 'Sukses', text: 'Dokumen berhasil diunggah. Halaman akan dimuat ulang.', timer: 1500, showConfirmButton: false});
                setTimeout(() => { window.location.reload(); }, 1000);
            } else {
                Swal.fire({icon: 'error', title: 'Gagal', text: result.message});
            }
        } catch (e) {
            Swal.fire({icon: 'error', title: 'Error', text: 'Koneksi ke Cloud terputus. Pastikan file tidak terlalu besar.'});
        }
    }

    async function deleteAJAX(event, form, safe_id) {
        event.preventDefault();
        if(!confirm('Yakin ingin menghapus dokumen ini dari Cloud?')) return;
        
        Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});
        
        try {
            let formData = new FormData(form);
            let res = await fetch(form.action, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: formData });
            let result = await res.json();
            
            if(result.success) {
                Swal.fire({icon: 'success', title: 'Terhapus', text:'Dokumen terhapus. Halaman akan dimuat ulang.', timer: 1000, showConfirmButton: false});
                setTimeout(() => { window.location.reload(); }, 800);
            } else {
                Swal.fire({icon: 'error', title: 'Ditolak', text: result.message});
            }
        } catch (e) {
            Swal.fire({icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.'});
        }
    }

    function previewLocalFile(inputId) {
        const input = document.getElementById(inputId);
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];
        const ext = file.name.split('.').pop().toLowerCase();

        if (['xls', 'xlsx', 'csv'].includes(ext)) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const workbook = XLSX.read(new Uint8Array(e.target.result), {type: 'array'});
                const htmlStr = XLSX.utils.sheet_to_html(workbook.Sheets[workbook.SheetNames[0]]);
                showModalPreview(file.name, htmlStr);
            };
            reader.readAsArrayBuffer(file);
        } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
            showModalPreview(file.name, `<div style="text-align:center;"><img src="${URL.createObjectURL(file)}" style="max-width:100%; max-height:60vh; border-radius:12px; border:1px solid #e2e8f0;"></div>`);
        } else if (ext === 'pdf') {
            showModalPreview(file.name, `<iframe src="${URL.createObjectURL(file)}" style="width:100%; height:70vh; border:none; border-radius:12px; background:#e2e8f0;"></iframe>`);
        } else if (['doc', 'docx'].includes(ext)) {
            const reader = new FileReader();
            reader.onload = function(e) {
                mammoth.convertToHtml({arrayBuffer: e.target.result})
                    .then(function(result) {
                        let htmlContent = result.value || "<p><i>Dokumen kosong atau tidak terbaca.</i></p>";
                        showModalPreview(file.name, `<div style="background:#fff; padding:30px; border:1px solid #e2e8f0; border-radius:12px; color:#000; font-family: 'Times New Roman', serif; line-height: 1.6;">${htmlContent}</div>`);
                    }).catch(function(err) { showModalPreview(file.name, `<div style="padding:40px; text-align:center; color:#e11d48;"><i class="bi bi-exclamation-triangle-fill" style="font-size:32px; display:block;"></i>Gagal membuat preview Word. File mungkin dikunci.</div>`); });
            };
            reader.readAsArrayBuffer(file);
        } else {
            showModalPreview(file.name, `<div style="padding:50px; text-align:center; color:#64748b;"><i class="bi bi-file-earmark-word-fill" style="font-size:48px; color:#3e54a0; display:block; margin-bottom:15px;"></i><b style="font-size:16px;">File siap diunggah</b><br>Format <b>.${ext}</b> tidak mendukung visual.</div>`);
        }
    }

    function previewServerFile(fileUrl, fileName) {
        if (!fileUrl || fileUrl.trim() === '') {
            fileUrl = '{{ env('AWS_URL') }}/uploads/evidence/' + fileName;
        }

        const ext = fileName.split('.').pop().toLowerCase();
        
        Swal.fire({ title: 'Memuat Dokumen...', text: 'Mengambil data dari Cloud', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); }});

        if (['xls', 'xlsx', 'csv'].includes(ext)) {
            fetch(fileUrl).then(res => {
                if(!res.ok) throw new Error('Network response was not ok');
                return res.arrayBuffer();
            }).then(ab => {
                const workbook = XLSX.read(new Uint8Array(ab), {type: 'array'});
                showModalPreview(fileName, XLSX.utils.sheet_to_html(workbook.Sheets[workbook.SheetNames[0]]));
            }).catch(() => showModalPreview(fileName, `<div style="color:#e11d48; text-align:center; padding:30px;"><i class="bi bi-exclamation-triangle-fill" style="font-size:40px; margin-bottom:10px; display:block;"></i> Gagal membaca file dari Cloud.</div>`));
        } else if (['jpg', 'jpeg', 'png'].includes(ext)) {
            Swal.close(); 
            showModalPreview(fileName, `<div style="text-align:center;"><img src="${fileUrl}" style="max-width:100%; max-height:60vh; border-radius:12px; border:1px solid #e2e8f0;"></div>`);
        } else if (ext === 'pdf') {
            Swal.close(); 
            showModalPreview(fileName, `<iframe src="${fileUrl}" style="width:100%; height:70vh; border:none; border-radius:12px; background:#e2e8f0;"></iframe>`);
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
            showModalPreview(fileName, `<div style="padding:50px; text-align:center; color:#64748b;"><i class="bi bi-file-earmark-check-fill" style="font-size:48px; color:#10b981; display:block; margin-bottom:15px;"></i><b style="font-size:16px;">Dokumen telah tersimpan</b><br>Format <b>.${ext}</b> tidak mendukung preview visual.</div>`);
        }
    }

    function showModalPreview(title, contentHTML) {
        const safeTitle = title.length > 50 ? title.substring(0, 50) + "..." : title;
        Swal.fire({
            title: `<span style="font-size:15px; color:#3e54a0; font-weight:bold; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><i class="bi bi-eye-fill"></i> Preview: ${safeTitle}</span>`,
            html: `<div style="max-height: 70vh; overflow: auto; text-align: left;" class="excel-preview-table">${contentHTML}</div>`,
            width: '85%', showCloseButton: true, showConfirmButton: false, customClass: { popup: 'swal-wide-popup' }
        });
    }
    
    // Smooth scroll untuk tombol navigasi
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
</script>
@endpush