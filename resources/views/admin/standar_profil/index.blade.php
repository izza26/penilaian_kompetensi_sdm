@extends('layouts.app')
@section('title', 'Standar Profil Jabatan')
@section('page_title', 'Standar Profil Kompetensi')
@section('page_subtitle', 'Tentukan persentase bobot, target nilai, dan jenis faktor per jabatan.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* KARTU FILTER */
        .filter-card { background: #ffffff; padding: 24px 32px; border-radius: 24px; border: none; margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .filter-group { flex: 1; }
        .filter-group label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group label i { color: #3e54a0 !important; }
        .filter-group select { width: 100%; padding: 0 20px; height: 48px; border-radius: 50px; border: 1px solid #cbd5e1; outline: none; background: #f9fafb; font-size: 13px; cursor: pointer; transition: 0.2s; color: #1e293b; font-family: inherit; }
        .filter-group select:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }

        .btn-tampilkan, .btn-simpan { background: #3e54a0; color: white; border: none; padding: 0 28px; height: 48px; border-radius: 50px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: 0.2s; font-size: 13px; }
        .btn-tampilkan:hover, .btn-simpan:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); }

        /* KONFIGURASI BOBOT */
        .config-card { background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 24px; padding: 24px 32px; margin-bottom: 24px; display: flex; gap: 20px; align-items: center; justify-content: space-between; flex-wrap: wrap; box-shadow: 0 4px 10px rgba(0,0,0,0.01);}
        .config-item { display: flex; align-items: center; gap: 12px; background: white; padding: 10px 20px; border-radius: 50px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .config-item label { font-size: 11px; font-weight: 700; color: #1e293b; margin: 0; text-transform: uppercase;}
        .config-input { width: 60px; height: 36px; padding: 0 10px; border: 1px solid #cbd5e1; border-radius: 50px; text-align: center; font-weight: bold; color: #3e54a0; background: #f4f7fe; outline: none; font-size: 14px; transition: 0.2s; }
        .config-input:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
        .config-separator { font-weight: bold; color: #94a3b8; font-size: 18px; }

        /* KARTU TABEL */
        .table-card { background: #fff; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; padding: 32px; margin-bottom: 24px; }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 15px; font-size: 11px; font-weight: 800; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; letter-spacing: 0.5px;}
        .styled-table td { padding: 20px 15px; border-bottom: 1px dashed #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }

        .input-target { width: 70px; height: 38px; padding: 0 10px; border-radius: 50px; border: 1px solid #cbd5e1; text-align: center; font-weight: 700; color: #0f172a; outline: none; background: #f9fafb; transition: 0.2s; }
        .input-target:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        .faktor-text { font-size: 11.5px; font-weight: 700; color: #3e54a0; display: flex; align-items: center; justify-content: center; gap: 6px; }

        /* NAVIGASI MAIN TAB */
        .tab-nav { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .tab-btn { background: none; border: none; padding: 12px 24px; font-weight: 600; font-size: 14px; color: #64748b; cursor: pointer; border-radius: 12px 12px 0 0; transition: 0.3s; position: relative; display: flex; align-items: center; gap: 8px; }
        .tab-btn:hover { color: #3e54a0; background: #f4f7fe; }
        .tab-btn.active { color: #3e54a0; background: transparent; font-weight: 700; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -12px; left: 0; width: 100%; height: 3px; background: #3e54a0; border-radius: 3px 3px 0 0; }
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active { display: block; }

        /* NAVIGASI SUB-TAB */
        .sub-tab-nav { display: flex; gap: 15px; margin-bottom: 25px; background: #f8fafc; padding: 8px; border-radius: 16px;}
        .sub-tab-btn { flex: 1; padding: 14px; border: none; background: transparent; border-radius: 12px; font-weight: 600; font-size: 13px; color: #64748b; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;}
        .sub-tab-btn:hover { color: #3e54a0; }
        .sub-tab-btn.active { background: #ffffff; color: #3e54a0; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }

        /* Tombol Aksi Superadmin */
        .action-cell { display: flex; gap: 8px; justify-content: center; }
        .btn-act { width: 34px; height: 34px; border: none; border-radius: 8px; display: flex; justify-content: center; align-items: center; cursor: pointer; transition: 0.2s; font-size: 14px; }
        .btn-act-edit { background: #eff6ff; color: #3b82f6; }
        .btn-act-edit:hover { background: #3b82f6; color: #fff; }
        .btn-act-del { background: #fef2f2; color: #ef4444; }
        .btn-act-del:hover { background: #ef4444; color: #fff; }

        /* STYLE BARU: Tombol Tambah Solid Blue (Seperti Master Unit) */
        .btn-tambah-baris { background: #3e54a0; color: #ffffff; font-weight: 600; border: none; border-radius: 50px; padding: 10px 24px; font-size: 13px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
        .btn-tambah-baris:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.3); }

        .input-gap { width: 100%; height: 38px; padding: 0 16px; border-radius: 50px; border: 1px solid #cbd5e1; font-weight: 700; color: #0f172a; outline: none; background: #f9fafb; transition: 0.2s; }
        .input-gap:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }

        /* --- MODAL STYLE --- */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-content { background-color: #fff; margin: 5% auto; padding: 32px; border-radius: 24px; width: 450px; box-shadow: 0 25px 50px rgba(0,0,0,0.15); position: relative; animation: fadeIn 0.3s ease-out; }
        .close-modal { position: absolute; right: 24px; top: 24px; font-size: 24px; font-weight: bold; color: #94a3b8; cursor: pointer; transition: 0.2s; }
        .close-modal:hover { color: #f43f5e; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-input { width: 100%; padding: 12px 16px; border-radius: 12px; border: 1px solid #cbd5e1; font-size: 13px; color: #1e293b; background: #f8fafc; outline: none; transition: 0.2s; box-sizing: border-box;}
        .modal-input:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
        .btn-submit-modal { width: 100%; background: #3e54a0; color: white; border: none; padding: 14px; border-radius: 50px; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; margin-top: 10px;}
        .btn-submit-modal:hover { background: #2b3a70; }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>document.addEventListener("DOMContentLoaded", function() { Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false }); });</script>
    @endif

    <div class="tab-nav">
        <button class="tab-btn active" onclick="bukaTab(event, 'tab-target')"><i class="bi bi-briefcase"></i> Pengaturan per Jabatan</button>
        <button class="tab-btn" onclick="bukaTab(event, 'tab-gap')"><i class="bi bi-bar-chart-steps"></i> Master Bobot Gap</button>
    </div>

    <!-- TAB 1: TARGET JABATAN -->
    <div id="tab-target" class="tab-content active">
        <form action="{{ route('admin.standar_profil.index') }}" method="GET" class="filter-card">
            <input type="hidden" name="active_tab" value="tab-target">
            <div class="filter-group">
                <label><i class="bi bi-briefcase-fill" style="color: #bda572;"></i> Pilih Jabatan untuk Diatur Targetnya:</label>
                <select name="jabatan" onchange="this.form.submit()">
                    <option value="">-- Silakan Pilih Jabatan --</option>
                    @foreach($list_jabatan as $jab)
                        <option value="{{ $jab }}" {{ $jabatan_terpilih == $jab ? 'selected' : '' }}>{{ $jab }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-tampilkan"><i class="bi bi-search"></i> Tampilkan</button>
        </form>

        @if($jabatan_terpilih)
            <div class="table-card">
                @if(count($list_aktivitas) > 0)

                    @php
                        $session_standar = Session::get("standar_profil_{$jabatan_terpilih}", []);
                        $list_softskill = [
                            (object)[ 'id' => 'SF-01', 'judul' => 'Kemampuan Komunikasi', 'deskripsi' => 'Kapasitas dalam menyampaikan informasi koleksi secara jelas dan menarik, baik kepada pengunjung museum maupun koordinasi antar rekan kerja.' ],
                            (object)[ 'id' => 'SF-02', 'judul' => 'Kerja Sama Tim (Kolaborasi)', 'deskripsi' => 'Sinergi lintas divisi, misalnya kolaborasi antara Edukator dan Penata Pameran dalam merancang dan menyukseskan program pameran publik.' ],
                            (object)[ 'id' => 'SF-03', 'judul' => 'Orientasi Pelayanan Publik', 'deskripsi' => 'Tingkat empati, kesabaran, dan ketanggapan staf dalam memandu dan melayani berbagai ragam karakteristik pengunjung museum.' ],
                            (object)[ 'id' => 'SF-04', 'judul' => 'Pemecahan Masalah (Problem Solving)', 'deskripsi' => 'Inisiatif dan ketepatan dalam menangani kendala operasional mendadak, seperti pelaporan kerusakan koleksi atau lonjakan jumlah pengunjung.' ],
                            (object)[ 'id' => 'SF-05', 'judul' => 'Adaptabilitas & Inovasi', 'deskripsi' => 'Kesiapan staf dalam beradaptasi dengan metode kerja baru (seperti digitalisasi sistem atau penggunaan teknologi kecerdasan buatan) serta fleksibilitas saat ada agenda khusus di luar jam operasional.' ],
                            (object)[ 'id' => 'SF-06', 'judul' => 'Manajemen Waktu', 'deskripsi' => 'Kedisiplinan dan efisiensi dalam menyelesaikan target kerja, pengunggahan evidence, dan pelaporan administratif tepat waktu.' ]
                        ];
                    @endphp

                    <form action="{{ route('admin.standar_profil.store') }}" method="POST" id="formStandar">
                        @csrf
                        <input type="hidden" name="jabatan" value="{{ $jabatan_terpilih }}">

                        <!-- PENGATURAN BOBOT NCF & NSF -->
                        <div class="config-card">
                            <div style="flex: 1 1 250px; min-width: 250px;">
                                <h4 style="margin: 0 0 5px 0; font-size: 15px; color: #0f172a;"><i class="bi bi-sliders"></i> Pengaturan Bobot Penilaian (%)</h4>
                                <p style="margin: 0; font-size: 12px; color: #64748b;">Pastikan total Core Factor dan Secondary Factor bernilai tepat 100.</p>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <div class="config-item">
                                    <label>Core Factor (NCF)</label>
                                    <input type="number" name="bobot_ncf" id="val_ncf" class="config-input" min="1" max="99" value="{{ $bobot_ncf ?? 60 }}" oninput="hitungSisaBobot('ncf')">
                                    <span style="font-weight: bold; color: #94a3b8;">%</span>
                                </div>
                                <div class="config-separator">+</div>
                                <div class="config-item">
                                    <label>Secondary Factor (NSF)</label>
                                    <input type="number" name="bobot_nsf" id="val_nsf" class="config-input" min="1" max="99" value="{{ $bobot_nsf ?? 40 }}" oninput="hitungSisaBobot('nsf')">
                                    <span style="font-weight: bold; color: #94a3b8;">%</span>
                                </div>
                                <div class="config-separator">=</div>
                                <div class="config-item" style="background: #ecfdf5; border-color: #a7f3d0;">
                                    <label style="color: #059669;">Total</label>
                                    <span id="val_total" style="font-size: 16px; font-weight: 800; color: #047857; width: 40px; text-align: center;">100</span>
                                    <span style="font-weight: bold; color: #059669;">%</span>
                                </div>
                            </div>
                        </div>

                        <!-- SUB TABS HARDSKILL & SOFTSKILL -->
                        <div class="sub-tab-nav">
                            <button type="button" class="sub-tab-btn active" onclick="switchInnerTab(event, 'sub-core')">
                                <i class="bi bi-tools"></i> Penilaian Hardskill (Core Factor)
                            </button>
                            <button type="button" class="sub-tab-btn" onclick="switchInnerTab(event, 'sub-sec')">
                                <i class="bi bi-people-fill"></i> Penilaian Softskill (Secondary Factor)
                            </button>
                        </div>

                        <!-- TAB: HARDSKILL (CORE FACTOR) -->
                        <div id="sub-core" class="inner-tab-content" style="display: block;">
                            @if(Auth::user()->role == 'superadmin')
                            <div style="text-align: right; margin-bottom: 15px;">
                                <button type="button" class="btn-tambah-baris" onclick="document.getElementById('modalTambahCore').style.display='block'"><i class="bi bi-plus-lg"></i> Tambah KUK Hardskill</button>
                            </div>
                            @endif

                            <table class="styled-table" id="table-hardskill">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;">NO</th>
                                        <th width="15%" style="text-align: center;">KODE UNIT</th>
                                        <th width="45%">AKTIVITAS KUK (HARDSKILL)</th>
                                        <th width="15%" style="text-align: center;">NILAI TARGET</th>
                                        @if(Auth::user()->role == 'superadmin')
                                            <th width="20%" style="text-align: center;">AKSI</th>
                                        @else
                                            <th width="20%" style="text-align: center;">JENIS FAKTOR</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($list_aktivitas as $index => $ak)
                                    <tr class="row-data">
                                        <td style="text-align: center; font-weight: 600; color: #64748b;" class="no-urut">{{ $index + 1 }}</td>
                                        <td style="text-align: center;"><span id="td-kode-{{ $ak->aktivitas_id }}" style="font-size: 11.5px; font-weight: 700; color: #3e54a0; background: #f4f7fe; padding: 4px 12px; border-radius: 50px;">{{ $ak->kode_unit }}</span></td>
                                        <td id="td-teks-{{ $ak->aktivitas_id }}" style="line-height: 1.5; font-weight: 600; color:#1e293b;">{{ $ak->detail_aktivitas }}</td>

                                        <td style="text-align: center;">
                                            <input type="number" name="target[{{ $ak->aktivitas_id }}]" class="input-target" min="1" max="5" value="{{ $ak->target_skor ?? 5 }}" required>
                                            <input type="hidden" name="faktor[{{ $ak->aktivitas_id }}]" value="Core">
                                        </td>

                                        <td style="text-align: center;">
                                            @if(Auth::user()->role == 'superadmin')
                                                <div class="action-cell">
                                                    <button type="button" class="btn-act btn-act-edit" onclick="bukaModalEditCore('{{ $ak->aktivitas_id }}')" title="Edit Data Master"><i class="bi bi-pencil-square"></i></button>
                                                    <button type="button" class="btn-act btn-act-del" onclick="hapusBarisUI(this)" title="Hapus dari Standar"><i class="bi bi-trash"></i></button>
                                                </div>
                                            @else
                                                <span class="faktor-text"><i class="bi bi-star-fill" style="color: #f59e0b;"></i> Core Factor</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- TAB: SOFTSKILL (SECONDARY FACTOR) -->
                        <div id="sub-sec" class="inner-tab-content" style="display: none;">
                            @if(Auth::user()->role == 'superadmin')
                            <div style="text-align: right; margin-bottom: 15px;">
                                <button type="button" class="btn-tambah-baris" style="background:#059669;" onclick="document.getElementById('modalTambahSecondary').style.display='block'" onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'"><i class="bi bi-plus-lg"></i> Tambah Dimensi Softskill</button>
                            </div>
                            @endif

                            <table class="styled-table" id="table-softskill">
                                <thead>
                                    <tr>
                                        <th width="5%" style="text-align: center;">NO</th>
                                        <th width="15%" style="text-align: center;">KODE ID</th>
                                        <th width="45%">DIMENSI SOFT SKILL</th>
                                        <th width="15%" style="text-align: center;">NILAI TARGET</th>
                                        @if(Auth::user()->role == 'superadmin')
                                            <th width="20%" style="text-align: center;">AKSI</th>
                                        @else
                                            <th width="20%" style="text-align: center;">JENIS FAKTOR</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($list_softskill as $index => $sf)
                                    <tr class="row-data">
                                        <td style="text-align: center; font-weight: 600; color: #64748b;" class="no-urut">{{ $index + 1 }}</td>
                                        <td style="text-align: center;"><span id="td-kode-{{ $sf->id }}" style="font-size: 11.5px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 4px 12px; border-radius: 50px;">{{ $sf->id }}</span></td>
                                        <td style="line-height: 1.5;">
                                            <div id="td-teks-{{ $sf->id }}" style="font-weight: 700; color:#1e293b; margin-bottom: 3px;">{{ $sf->judul }}</div>
                                            <div id="td-desc-{{ $sf->id }}" style="font-size: 11.5px; color:#64748b;">{{ $sf->deskripsi }}</div>
                                        </td>

                                        <td style="text-align: center;">
                                            <input type="number" name="target[{{ $sf->id }}]" class="input-target" min="1" max="5" value="{{ $session_standar[$sf->id]['target'] ?? 5 }}" required>
                                            <input type="hidden" name="faktor[{{ $sf->id }}]" value="Secondary">
                                        </td>

                                        <td style="text-align: center;">
                                            @if(Auth::user()->role == 'superadmin')
                                                <div class="action-cell">
                                                    <button type="button" class="btn-act btn-act-edit" onclick="bukaModalEditSecondary('{{ $sf->id }}')" title="Edit Data Master"><i class="bi bi-pencil-square"></i></button>
                                                    <button type="button" class="btn-act btn-act-del" onclick="hapusBarisUI(this)" title="Hapus dari Standar"><i class="bi bi-trash"></i></button>
                                                </div>
                                            @else
                                                <span class="faktor-text" style="color: #059669;"><i class="bi bi-star-half" style="color: #10b981;"></i> Secondary Factor</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- TOMBOL SIMPAN -->
                        <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="btn-simpan" onclick="return validasiTotalBobot()">
                                <i class="bi bi-save-fill"></i> Simpan Standar Jabatan
                            </button>
                        </div>
                    </form>
                @else
                    <div class="empty-state"><i class="bi bi-folder-x"></i><h3 style="margin: 0; color: #0f172a;">Aktivitas Belum Diatur</h3><p style="margin: 5px 0 0 0;">Tidak ada aktivitas kompetensi yang ditemukan untuk jabatan <b>{{ $jabatan_terpilih }}</b>.</p></div>
                @endif
            </div>
        @else
            <div class="table-card empty-state"><i class="bi bi-hand-index-thumb"></i><h3 style="margin: 0; color: #0f172a;">Pilih Jabatan Terlebih Dahulu</h3><p style="margin: 5px 0 0 0;">Silakan pilih jabatan pada filter di atas untuk mengatur standar profilnya.</p></div>
        @endif
    </div>

    <!-- TAB 2: MASTER BOBOT GAP -->
    <div id="tab-gap" class="tab-content">
        <div class="table-card">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b;"></i> Perhatian: Konfigurasi Global</h3>
                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 13px;">Perubahan pada bobot selisih (gap) di bawah ini akan berdampak pada <b>seluruh perhitungan Profile Matching untuk semua jabatan</b> di sistem. Pastikan nilai sudah sesuai dengan pedoman SDM.</p>
            </div>

            <form action="{{ route('admin.standar_profil.gap.store') }}" method="POST">
                @csrf
                <table class="styled-table">
                    <thead>
                        <tr>
                            <th width="10%" style="text-align: center;">Selisih (Gap)</th>
                            <th width="20%" style="text-align: center;">Nilai Bobot</th>
                            <th width="70%">Keterangan Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($list_bobot_gap) && count($list_bobot_gap) > 0)
                            @foreach ($list_bobot_gap as $bg)
                            <tr>
                                <td style="text-align: center; font-weight: 800; font-size: 15px; color: {{ $bg->gap < 0 ? '#dc2626' : ($bg->gap > 0 ? '#059669' : '#0f172a') }};">
                                    {{ $bg->gap > 0 ? '+' : '' }}{{ $bg->gap }}
                                </td>
                                <td style="text-align: center;">
                                    <input type="number" step="0.5" name="bobot[{{ $bg->gap }}]" class="input-gap" value="{{ $bg->bobot }}" required style="text-align: center; width: 80px;">
                                </td>
                                <td style="font-weight: 500;">
                                    <input type="text" name="keterangan[{{ $bg->gap }}]" class="input-gap" value="{{ $bg->keterangan }}" required>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            <tr><td colspan="3" style="text-align: center; padding: 20px;">Data bobot gap belum tersedia.</td></tr>
                        @endif
                    </tbody>
                </table>

                <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <button type="submit" class="btn-simpan"><i class="bi bi-shield-check"></i> Terapkan Bobot Global</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================== -->
    <!-- AREA MODAL SUPERADMIN -->
    <!-- ============================================================== -->
    @if(Auth::user()->role == 'superadmin')
        <!-- MODAL TAMBAH HARDSKILL -->
        <div id="modalTambahCore" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="document.getElementById('modalTambahCore').style.display='none'">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;">Tambah KUK Hardskill</h3>

                <div class="form-group"><label>ID Aktivitas (Wajib Unik)</label><input type="text" id="add_core_id" class="modal-input" placeholder="Misal: AK-101" required></div>
                <div class="form-group"><label>Kode Unit</label><input type="text" id="add_core_kode" class="modal-input" placeholder="Misal: R.91MUS02.xxx" required></div>
                <div class="form-group"><label>Aktivitas KUK (Hardskill)</label><textarea id="add_core_teks" class="modal-input" rows="3" placeholder="Deskripsi aktivitas hardskill..." required></textarea></div>

                <button type="button" class="btn-submit-modal" onclick="tambahBarisCore()">Tambahkan ke Tabel</button>
            </div>
        </div>

        <!-- MODAL EDIT HARDSKILL -->
        <div id="modalEditCore" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="document.getElementById('modalEditCore').style.display='none'">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;">Edit Unit & Aktivitas Hardskill</h3>
                <input type="hidden" id="core_aktivitas_id">

                <div class="form-group"><label>Kode Unit</label><input type="text" id="core_kode" class="modal-input" required></div>
                <div class="form-group"><label>Aktivitas KUK (Hardskill)</label><textarea id="core_teks" class="modal-input" rows="3" required></textarea></div>

                <button type="button" class="btn-submit-modal" onclick="simpanEditCore()">Update Data</button>
            </div>
        </div>

        <!-- MODAL TAMBAH SOFTSKILL -->
        <div id="modalTambahSecondary" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="document.getElementById('modalTambahSecondary').style.display='none'">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;">Tambah Dimensi Softskill</h3>

                <div class="form-group"><label>Kode ID (Wajib Unik)</label><input type="text" id="add_sec_id" class="modal-input" placeholder="Misal: SF-07" required></div>
                <div class="form-group"><label>Dimensi Soft Skill</label><input type="text" id="add_sec_judul" class="modal-input" placeholder="Misal: Kepemimpinan" required></div>
                <div class="form-group"><label>Deskripsi Indikator</label><textarea id="add_sec_desc" class="modal-input" rows="3" placeholder="Kapasitas dalam memandu rekan tim..." required></textarea></div>

                <button type="button" class="btn-submit-modal" onclick="tambahBarisSecondary()">Tambahkan ke Tabel</button>
            </div>
        </div>

        <!-- MODAL EDIT SOFTSKILL -->
        <div id="modalEditSecondary" class="modal">
            <div class="modal-content">
                <span class="close-modal" onclick="document.getElementById('modalEditSecondary').style.display='none'">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; color: #1e293b;">Edit Dimensi Soft Skill</h3>
                <input type="hidden" id="sec_aktivitas_id">

                <div class="form-group"><label>Kode ID</label><input type="text" id="sec_kode" class="modal-input" required></div>
                <div class="form-group"><label>Dimensi Soft Skill</label><input type="text" id="sec_teks" class="modal-input" required></div>
                <div class="form-group"><label>Deskripsi Indikator</label><textarea id="sec_desc" class="modal-input" rows="3" required></textarea></div>

                <button type="button" class="btn-submit-modal" onclick="simpanEditSecondary()">Update Data</button>
            </div>
        </div>
    @endif
@endsection

@push('scripts')
<script>
    function bukaTab(evt, namaTab) {
        let i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) { tabcontent[i].style.display = "none"; tabcontent[i].classList.remove("active"); }
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) { tablinks[i].classList.remove("active"); }
        document.getElementById(namaTab).style.display = "block";
        document.getElementById(namaTab).classList.add("active");
        evt.currentTarget.classList.add("active");
        window.history.pushState(null, null, "#" + namaTab);
    }

    // --- REVISI: Fungsi perpindahan sub-tab Hardskill dan Softskill sudah berfungsi! ---
    function switchInnerTab(evt, sectionId) {
        let contents = document.getElementsByClassName("inner-tab-content");
        for (let i = 0; i < contents.length; i++) { contents[i].style.display = "none"; contents[i].classList.remove("active"); }

        let btns = document.getElementsByClassName("inner-tab-btn");
        for (let i = 0; i < btns.length; i++) { btns[i].classList.remove("active"); }

        document.getElementById(sectionId).style.display = "block";
        document.getElementById(sectionId).classList.add("active");
        evt.currentTarget.classList.add("active");
    }

    document.addEventListener("DOMContentLoaded", function() {
        let hash = window.location.hash;
        if (hash) {
            let targetBtn = document.querySelector(`.tab-btn[onclick*="${hash.substring(1)}"]`);
            if (targetBtn) targetBtn.click();
        }
    });

    function hitungSisaBobot(sumber) {
        let ncfInput = document.getElementById('val_ncf');
        let nsfInput = document.getElementById('val_nsf');
        let totalDisplay = document.getElementById('val_total');
        let ncfVal = parseInt(ncfInput.value) || 0;
        let nsfVal = parseInt(nsfInput.value) || 0;

        if (sumber === 'ncf') { nsfVal = 100 - ncfVal; nsfInput.value = nsfVal; }
        else if (sumber === 'nsf') { ncfVal = 100 - nsfVal; ncfInput.value = ncfVal; }

        let total = ncfVal + nsfVal;
        totalDisplay.innerText = total;

        if (total !== 100) {
            totalDisplay.style.color = '#dc2626'; totalDisplay.parentElement.style.background = '#fef2f2'; totalDisplay.parentElement.style.borderColor = '#fecaca';
        } else {
            totalDisplay.style.color = '#047857'; totalDisplay.parentElement.style.background = '#ecfdf5'; totalDisplay.parentElement.style.borderColor = '#a7f3d0';
        }
    }

    function validasiTotalBobot() {
        let ncfVal = parseInt(document.getElementById('val_ncf').value) || 0;
        let nsfVal = parseInt(document.getElementById('val_nsf').value) || 0;
        if ((ncfVal + nsfVal) !== 100) { Swal.fire({ icon: 'error', title: 'Bobot Tidak Valid', text: 'Total persentase Core Factor dan Secondary Factor wajib 100%!', }); return false; }
        return true;
    }

    // --- FUNGSI UPDATE NOMOR URUT OTOMATIS ---
    function reindexTable(tableId) {
        let tbody = document.querySelector(`#${tableId} tbody`);
        let rows = tbody.querySelectorAll('tr.row-data');
        rows.forEach((row, index) => {
            row.querySelector('.no-urut').innerText = index + 1;
        });
    }

    // --- FUNGSI HAPUS BARIS ---
    function hapusBarisUI(btn) {
        let tr = btn.closest('tr');
        let tbody = tr.closest('tbody');
        let tableId = tr.closest('table').id;

        Swal.fire({
            title: 'Hapus Baris?',
            text: 'Baris ini akan dihilangkan dari tampilan saat Anda menyimpan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: 'Ya, Hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                tr.remove();
                reindexTable(tableId);
            }
        });
    }

    // --- FUNGSI MODAL SUPERADMIN ---
    @if(Auth::user()->role == 'superadmin')

        // ---- HARDSKILL (CORE) ----
        function bukaModalEditCore(id) {
            document.getElementById('core_aktivitas_id').value = id;
            document.getElementById('core_kode').value = document.getElementById('td-kode-' + id).innerText;
            document.getElementById('core_teks').value = document.getElementById('td-teks-' + id).innerText;
            document.getElementById('modalEditCore').style.display = 'block';
        }

        function simpanEditCore() {
            let id = document.getElementById('core_aktivitas_id').value;
            let kode_baru = document.getElementById('core_kode').value;
            let teks_baru = document.getElementById('core_teks').value;

            document.getElementById('td-kode-' + id).innerText = kode_baru;
            document.getElementById('td-teks-' + id).innerText = teks_baru;

            document.getElementById('modalEditCore').style.display = 'none';
        }

        function tambahBarisCore() {
            let id = document.getElementById('add_core_id').value;
            let kode = document.getElementById('add_core_kode').value;
            let teks = document.getElementById('add_core_teks').value;

            if(!id || !kode || !teks) {
                Swal.fire('Oops!', 'Mohon lengkapi semua isian KUK Hardskill.', 'warning');
                return;
            }

            if(document.getElementById('td-kode-' + id)) {
                Swal.fire('Oops!', 'ID Aktivitas tersebut sudah ada di dalam tabel!', 'warning');
                return;
            }

            let html = `
            <tr class="row-data">
                <td style="text-align: center; font-weight: 600; color: #64748b;" class="no-urut">*</td>
                <td style="text-align: center;"><span id="td-kode-${id}" style="font-size: 11.5px; font-weight: 700; color: #3e54a0; background: #f4f7fe; padding: 4px 12px; border-radius: 50px;">${kode}</span></td>
                <td id="td-teks-${id}" style="line-height: 1.5; font-weight: 600; color:#1e293b;">${teks}</td>
                <td style="text-align: center;">
                    <input type="number" name="target[${id}]" class="input-target" min="1" max="5" value="5" required>
                    <input type="hidden" name="faktor[${id}]" value="Core">
                </td>
                <td style="text-align: center;">
                    <div class="action-cell">
                        <button type="button" class="btn-act btn-act-edit" onclick="bukaModalEditCore('${id}')" title="Edit Data"><i class="bi bi-pencil-square"></i></button>
                        <button type="button" class="btn-act btn-act-del" onclick="hapusBarisUI(this)" title="Hapus Data"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`;

            document.querySelector('#table-hardskill tbody').insertAdjacentHTML('beforeend', html);
            reindexTable('table-hardskill');

            document.getElementById('modalTambahCore').style.display = 'none';
            document.getElementById('add_core_id').value = '';
            document.getElementById('add_core_kode').value = '';
            document.getElementById('add_core_teks').value = '';
        }

        // ---- SOFTSKILL (SECONDARY) ----
        function bukaModalEditSecondary(id) {
            document.getElementById('sec_aktivitas_id').value = id;
            document.getElementById('sec_kode').value = document.getElementById('td-kode-' + id).innerText;
            document.getElementById('sec_teks').value = document.getElementById('td-teks-' + id).innerText;
            document.getElementById('sec_desc').value = document.getElementById('td-desc-' + id).innerText;
            document.getElementById('modalEditSecondary').style.display = 'block';
        }

        function simpanEditSecondary() {
            let id = document.getElementById('sec_aktivitas_id').value;
            let kode_baru = document.getElementById('sec_kode').value;
            let teks_baru = document.getElementById('sec_teks').value;
            let desc_baru = document.getElementById('sec_desc').value;

            document.getElementById('td-kode-' + id).innerText = kode_baru;
            document.getElementById('td-teks-' + id).innerText = teks_baru;
            document.getElementById('td-desc-' + id).innerText = desc_baru;

            document.getElementById('modalEditSecondary').style.display = 'none';
        }

        function tambahBarisSecondary() {
            let id = document.getElementById('add_sec_id').value;
            let judul = document.getElementById('add_sec_judul').value;
            let desc = document.getElementById('add_sec_desc').value;

            if(!id || !judul || !desc) {
                Swal.fire('Oops!', 'Mohon lengkapi semua isian Dimensi Softskill.', 'warning');
                return;
            }

            if(document.getElementById('td-kode-' + id)) {
                Swal.fire('Oops!', 'Kode ID tersebut sudah digunakan!', 'warning');
                return;
            }

            let html = `
            <tr class="row-data">
                <td style="text-align: center; font-weight: 600; color: #64748b;" class="no-urut">*</td>
                <td style="text-align: center;"><span id="td-kode-${id}" style="font-size: 11.5px; font-weight: 700; color: #059669; background: #ecfdf5; padding: 4px 12px; border-radius: 50px;">${id}</span></td>
                <td style="line-height: 1.5;">
                    <div id="td-teks-${id}" style="font-weight: 700; color:#1e293b; margin-bottom: 3px;">${judul}</div>
                    <div id="td-desc-${id}" style="font-size: 11.5px; color:#64748b;">${desc}</div>
                </td>
                <td style="text-align: center;">
                    <input type="number" name="target[${id}]" class="input-target" min="1" max="5" value="5" required>
                    <input type="hidden" name="faktor[${id}]" value="Secondary">
                </td>
                <td style="text-align: center;">
                    <div class="action-cell">
                        <button type="button" class="btn-act btn-act-edit" onclick="bukaModalEditSecondary('${id}')" title="Edit Data"><i class="bi bi-pencil-square"></i></button>
                        <button type="button" class="btn-act btn-act-del" onclick="hapusBarisUI(this)" title="Hapus Data"><i class="bi bi-trash"></i></button>
                    </div>
                </td>
            </tr>`;

            document.querySelector('#table-softskill tbody').insertAdjacentHTML('beforeend', html);
            reindexTable('table-softskill');

            document.getElementById('modalTambahSecondary').style.display = 'none';
            document.getElementById('add_sec_id').value = '';
            document.getElementById('add_sec_judul').value = '';
            document.getElementById('add_sec_desc').value = '';
        }

        // Tutup modal jika klik area luar
        window.onclick = function(event) {
            let modals = document.getElementsByClassName('modal');
            for (let i = 0; i < modals.length; i++) {
                if (event.target == modals[i]) {
                    modals[i].style.display = "none";
                }
            }
        }
    @endif
</script>
@endpush
