@extends('layouts.app') 
@section('title', 'Standar Profil Jabatan')
@section('page_title', 'Standar Profil Kompetensi')
@section('page_subtitle', 'Tentukan persentase bobot, target nilai, dan jenis faktor per jabatan.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* --- STYLE BARU (ROYAL BLUE & PILL SHAPE) --- */
        
        /* KARTU FILTER */
        .filter-card { 
            background: #ffffff; padding: 24px 32px; border-radius: 24px; border: none; 
            margin-bottom: 24px; display: flex; gap: 16px; align-items: flex-end; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); 
        }
        .filter-group { flex: 1; }
        .filter-group label { display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group label i { color: #3e54a0 !important; } /* Mengubah ikon koper jadi biru */
        
        /* INPUT & SELECT PILL-SHAPE */
        .filter-group select { 
            width: 100%; padding: 0 20px; height: 48px; border-radius: 50px; 
            border: 1px solid #cbd5e1; outline: none; background: #f9fafb; 
            font-size: 13px; cursor: pointer; transition: 0.2s; color: #1e293b; font-family: inherit;
        }
        .filter-group select:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
        
        /* TOMBOL UTAMA */
        .btn-tampilkan, .btn-simpan { 
            background: #3e54a0; color: white; border: none; padding: 0 28px; height: 48px; 
            border-radius: 50px; font-weight: 600; cursor: pointer; display: inline-flex; 
            align-items: center; justify-content: center; gap: 8px; transition: 0.2s; font-size: 13px; 
        }
        .btn-tampilkan:hover, .btn-simpan:hover { 
            background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2); 
        }
        
        /* KONFIGURASI BOBOT (NCF & NSF) */
        .config-card { 
            background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 24px; 
            padding: 24px 32px; margin-bottom: 24px; display: flex; gap: 20px; 
            align-items: center; justify-content: space-between; flex-wrap: wrap;
        }
        .config-item { 
            display: flex; align-items: center; gap: 12px; background: white; 
            padding: 10px 20px; border-radius: 50px; border: 1px solid #e2e8f0; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.02); 
        }
        .config-item label { font-size: 11px; font-weight: 700; color: #1e293b; margin: 0; text-transform: uppercase;}
        
        .config-input { 
            width: 60px; height: 36px; padding: 0 10px; border: 1px solid #cbd5e1; 
            border-radius: 50px; text-align: center; font-weight: bold; color: #3e54a0; 
            background: #f4f7fe; outline: none; font-size: 14px; transition: 0.2s;
        }
        .config-input:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
        .config-separator { font-weight: bold; color: #94a3b8; font-size: 18px; }

        /* TABEL STANDAR PROFIL */
        .table-card { 
            background: #fff; border-radius: 24px; border: none; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.02); overflow: hidden; padding: 32px; margin-bottom: 24px;
        }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 15px; border-bottom: 1px dashed #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        .input-target { 
            width: 70px; height: 38px; padding: 0 10px; border-radius: 50px; 
            border: 1px solid #cbd5e1; text-align: center; font-weight: 700; color: #0f172a; 
            outline: none; background: #f9fafb; transition: 0.2s;
        }
        .input-target:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        
        .select-faktor { 
            height: 38px; padding: 0 16px; border-radius: 50px; border: 1px solid #cbd5e1; 
            font-size: 12px; outline: none; font-weight: 600; cursor: pointer; 
            background: #f9fafb; transition: 0.2s; color: #1e293b;
        }
        .select-faktor:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        
        .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
        .empty-state i { font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block; }

        /* --- NAVIGASI TAB BUKA-TUTUP --- */
        .tab-nav { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .tab-btn { 
            background: none; border: none; padding: 12px 24px; font-weight: 600; 
            font-size: 14px; color: #64748b; cursor: pointer; border-radius: 12px 12px 0 0; 
            transition: 0.3s; position: relative; display: flex; align-items: center; gap: 8px;
        }
        .tab-btn:hover { color: #3e54a0; background: #f4f7fe; }
        .tab-btn.active { color: #3e54a0; background: transparent; font-weight: 700; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -12px; left: 0; width: 100%; height: 3px; background: #3e54a0; border-radius: 3px 3px 0 0; }
        
        .tab-content { display: none; animation: fadeIn 0.3s ease-in-out; }
        .tab-content.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .input-gap { 
            width: 100%; height: 38px; padding: 0 16px; border-radius: 50px; 
            border: 1px solid #cbd5e1; font-weight: 700; color: #0f172a; outline: none; 
            background: #f9fafb; transition: 0.2s;
        }
        .input-gap:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
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

    <!-- Menu Navigasi Tab -->
    <div class="tab-nav">
        <button class="tab-btn active" onclick="bukaTab(event, 'tab-target')"><i class="bi bi-briefcase"></i> Pengaturan per Jabatan</button>
        <button class="tab-btn" onclick="bukaTab(event, 'tab-gap')"><i class="bi bi-bar-chart-steps"></i> Master Bobot Gap</button>
    </div>

    <!-- ============================================== -->
    <!-- TAB 1: TARGET JABATAN (Yang sudah kamu buat) -->
    <!-- ============================================== -->
    <div id="tab-target" class="tab-content active">
        <!-- Filter Jabatan -->
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
                    <form action="{{ route('admin.standar_profil.store') }}" method="POST" id="formStandar">
                        @csrf
                        <input type="hidden" name="jabatan" value="{{ $jabatan_terpilih }}">
                        
                        <!-- PENGATURAN BOBOT NCF & NSF (Sudah dirapikan UI-nya) -->
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
                        
                        <table class="styled-table">
                            <thead>
                                <tr>
                                    <th width="5%" style="text-align: center;">No</th>
                                    <th width="15%">Kode Unit</th>
                                    <th width="45%">Aktivitas</th>
                                    <th width="15%" style="text-align: center;">Nilai Target</th>
                                    <th width="20%" style="text-align: center;">Jenis Faktor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($list_aktivitas as $index => $ak)
                                <tr>
                                    <td style="text-align: center; font-weight: 600; color: #64748b;">{{ $index + 1 }}</td>
                                    <td><span style="font-size: 11px; font-weight: 800; color: #3e54a0; background: #f4f7fe; padding: 6px 12px; border-radius: 50px;">{{ $ak->kode_unit }}</span></td>
                                    <td style="line-height: 1.5; font-weight: 500;">{{ $ak->detail_aktivitas }}</td>
                                    <td style="text-align: center;">
                                        <input type="number" name="target[{{ $ak->aktivitas_id }}]" class="input-target" min="1" max="5" value="{{ $ak->target_skor ?? 4 }}" required>
                                    </td>
                                    <td style="text-align: center;">
                                        <select name="faktor[{{ $ak->aktivitas_id }}]" class="select-faktor">
                                            <option value="Core" {{ ($ak->jenis_faktor ?? 'Core') == 'Core' ? 'selected' : '' }}>Core Factor</option>
                                            <option value="Secondary" {{ ($ak->jenis_faktor ?? '') == 'Secondary' ? 'selected' : '' }}>Secondary Factor</option>
                                        </select>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>

                        <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="btn-simpan" onclick="return validasiTotalBobot()">
                                <i class="bi bi-save-fill"></i> Simpan Standar Jabatan
                            </button>
                        </div>
                    </form>
                @else
                    <div class="empty-state">
                        <i class="bi bi-folder-x"></i>
                        <h3 style="margin: 0; color: #0f172a;">Aktivitas Belum Diatur</h3>
                        <p style="margin: 5px 0 0 0;">Tidak ada aktivitas kompetensi yang ditemukan untuk jabatan <b>{{ $jabatan_terpilih }}</b>.</p>
                    </div>
                @endif
            </div>
        @else
            <div class="table-card empty-state">
                <i class="bi bi-hand-index-thumb"></i>
                <h3 style="margin: 0; color: #0f172a;">Pilih Jabatan Terlebih Dahulu</h3>
                <p style="margin: 5px 0 0 0;">Silakan pilih jabatan pada filter di atas untuk mengatur standar profilnya.</p>
            </div>
        @endif
    </div>

    <!-- ============================================== -->
    <!-- TAB 2: MASTER BOBOT GAP -->
    <!-- ============================================== -->
    <div id="tab-gap" class="tab-content">
        <div class="table-card">
            <div style="margin-bottom: 20px;">
                <h3 style="margin: 0; color: #0f172a;"><i class="bi bi-exclamation-triangle-fill" style="color: #f59e0b;"></i> Perhatian: Konfigurasi Global</h3>
                <p style="margin: 5px 0 0 0; color: #64748b; font-size: 13px;">Perubahan pada bobot selisih (gap) di bawah ini akan berdampak pada <b>seluruh perhitungan Profile Matching untuk semua jabatan</b> di sistem. Pastikan nilai sudah sesuai dengan pedoman SDM.</p>
            </div>

            <!-- Ganti action dengan route simpan bobot gap kamu nantinya -->
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
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 20px;">Data bobot gap belum tersedia.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                    <button type="submit" class="btn-simpan">
                        <i class="bi bi-shield-check"></i> Terapkan Bobot Global
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // --- FITUR GANTI TAB ---
    function bukaTab(evt, namaTab) {
        let i, tabcontent, tablinks;
        
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
            tabcontent[i].classList.remove("active");
        }
        
        tablinks = document.getElementsByClassName("tab-btn");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }
        
        document.getElementById(namaTab).style.display = "block";
        document.getElementById(namaTab).classList.add("active");
        evt.currentTarget.classList.add("active");

        // Simpan state tab ke URL agar saat reload tidak hilang
        window.history.pushState(null, null, "#" + namaTab);
    }

    // Auto-buka tab berdasarkan URL hash (#tab-gap)
    document.addEventListener("DOMContentLoaded", function() {
        let hash = window.location.hash;
        if (hash) {
            let targetBtn = document.querySelector(`.tab-btn[onclick*="${hash.substring(1)}"]`);
            if (targetBtn) targetBtn.click();
        }
    });

    // --- FITUR DYNAMIC CALCULATOR ---
    function hitungSisaBobot(sumber) {
        let ncfInput = document.getElementById('val_ncf');
        let nsfInput = document.getElementById('val_nsf');
        let totalDisplay = document.getElementById('val_total');
        
        let ncfVal = parseInt(ncfInput.value) || 0;
        let nsfVal = parseInt(nsfInput.value) || 0;

        if (sumber === 'ncf') {
            nsfVal = 100 - ncfVal;
            nsfInput.value = nsfVal;
        } else if (sumber === 'nsf') {
            ncfVal = 100 - nsfVal;
            ncfInput.value = ncfVal;
        }

        let total = ncfVal + nsfVal;
        totalDisplay.innerText = total;

        if (total !== 100) {
            totalDisplay.style.color = '#dc2626';
            totalDisplay.parentElement.style.background = '#fef2f2';
            totalDisplay.parentElement.style.borderColor = '#fecaca';
        } else {
            totalDisplay.style.color = '#047857';
            totalDisplay.parentElement.style.background = '#ecfdf5';
            totalDisplay.parentElement.style.borderColor = '#a7f3d0';
        }
    }

    function validasiTotalBobot() {
        let ncfVal = parseInt(document.getElementById('val_ncf').value) || 0;
        let nsfVal = parseInt(document.getElementById('val_nsf').value) || 0;
        
        if ((ncfVal + nsfVal) !== 100) {
            Swal.fire({
                icon: 'error',
                title: 'Bobot Tidak Valid',
                text: 'Total persentase Core Factor dan Secondary Factor wajib 100%!',
            });
            return false;
        }
        return true;
    }
</script>
@endpush