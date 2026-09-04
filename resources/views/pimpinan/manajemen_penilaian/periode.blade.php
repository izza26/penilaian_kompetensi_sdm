@extends('layouts.app')
@section('title', 'Manajemen Penilaian')
@section('page_title', 'Manajemen Penilaian')
@section('page_subtitle', 'Atur waktu periode penilaian dan petakan UK spesifik per jabatan.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-card { background: #fff; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: none; padding: 32px; margin-bottom: 24px; }
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .styled-table th { background: transparent; color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 16px 20px; border-bottom: 1px dashed #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .inline-edit-row { transition: 0.2s; }
        .inline-edit-row:hover { background-color: #f8fafc; }
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 700; display: inline-flex; justify-content: center; width: auto; min-width: 120px; text-align: center; border: none; letter-spacing: 0.5px;}
        .badge-hijau { background: #ecfdf5; color: #059669; }
        .badge-kuning { background: #fffbeb; color: #d97706; }
        .badge-merah { background: #fff1f2; color: #e11d48; }
        .badge-abu { background: #f1f5f9; color: #64748b; }
        
        /* Tombol Aksi */
        .btn-aksi-group { display: flex; justify-content: center; gap: 8px;}
        .btn-icon { width: 36px; height: 36px; border-radius: 50%; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; transition: 0.2s;}
        .btn-edit { background: #eff6ff; color: #2563eb; }
        .btn-edit:hover { background: #2563eb; color: white; transform: translateY(-2px);}
        .btn-hapus { background: #fff1f2; color: #e11d48; }
        .btn-hapus:hover { background: #e11d48; color: white; transform: translateY(-2px);}
        
        .td-clickable { cursor: pointer; transition: 0.2s; }
        .td-clickable:hover { background-color: #f4f7fe; }
        .text-click { color: #1e293b; border-bottom: 1px dashed transparent; padding-bottom: 2px; font-weight: 600; font-size: 13px; transition: 0.2s;}
        .td-clickable:hover .text-click { color: #3e54a0; border-bottom-color: #3e54a0; }
        
        .tambah-text { color: #3e54a0; font-weight: 600; border-bottom: 1px dashed #3e54a0; font-size: 13px; cursor: pointer;}
        .tambah-icon { background: #f4f7fe; color: #3e54a0; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; justify-content: center; align-items: center; font-size: 14px;}
        
        .uk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .uk-box { font-size: 11px; background: #f9fafb; color: #3e54a0; border: 1px solid #cbd5e1; padding: 6px 10px; border-radius: 6px; font-weight: 700; text-align: center; position: relative; overflow: hidden; transition: 0.2s;}
        .uk-box::before { content: ''; position: absolute; top: 0; left: 0; width: 0; height: 0; border-style: solid; border-width: 8px 8px 0 0; border-color: #10b981 transparent transparent transparent; }
        .td-clickable:hover .uk-box { background: #fff; border-color: #3e54a0; box-shadow: 0 2px 8px rgba(62, 84, 160, 0.15);}
        
        /* Modal & Form */
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.6); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-box { background: white; padding: 32px; border-radius: 24px; width: 380px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); animation: modalFadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);}
        @keyframes modalFadeIn { from {opacity: 0; transform: translateY(-20px) scale(0.95);} to {opacity: 1; transform: translateY(0) scale(1);} }
        
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .modal-header h3 { margin: 0; font-size: 18px; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px;}
        .close-btn { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; transition: 0.2s;}
        .close-btn:hover { color: #e11d48;}
        
        .form-control { width: 100%; padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; border-radius: 50px; font-size: 13px; box-sizing: border-box; margin-bottom: 20px; font-family: inherit; outline: none; transition: 0.2s; background: #f9fafb;}
        .form-control:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        
        .btn-simpan { background: #3e54a0; color: white; height: 48px; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; width: 100%; transition: 0.2s; font-size: 13px;}
        .btn-simpan:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2);}
    </style>
@endpush

@section('content')
<div class="page-card">
    <div style="margin-bottom: 24px;">
        <h3 style="color: #1e293b; margin: 0 0 8px 0; font-size: 20px; font-weight: 700;">Pemetaan Periode per Jabatan</h3>
        <p style="font-size: 13px; color: #64748b; margin: 0;">Gunakan tombol Edit di sebelah kanan untuk mengubah tanggal dan menutup penilaian.</p>
    </div>

    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif

    <table class="styled-table">
        <thead>
            <tr>
                <th width="5%" style="text-align: center;">No</th>
                <th width="20%">Jabatan Fungsional</th>
                <th width="20%">Periode</th>
                <th width="25%">Unit Kompetensi (Aktif)</th>
                <th width="15%" style="text-align: center;">Status</th>
                <th width="15%" style="text-align: center;">Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr class="inline-edit-row">
                <td style="text-align: center;"><div class="tambah-icon"><i class="bi bi-plus-lg"></i></div></td>
                <td><span class="tambah-text" onclick="bukaModalJabatan()">Pilih Jabatan...</span></td>
                <td><span style="color: #94a3b8; font-style: italic; font-size: 12px;">Pilih jabatan terlebih dahulu</span></td>
                <td><span style="color: #94a3b8; font-style: italic; font-size: 12px;">Pilih jabatan terlebih dahulu</span></td>
                <td style="text-align: center; color: #94a3b8; font-weight: 600;">-</td>
                <td style="text-align: center; color: #94a3b8; font-size: 12px; font-weight: 600;"><i class="bi bi-pencil-fill" style="margin-right: 4px;"></i> Tambah</td>
            </tr>

            @php $no = 1; @endphp
            @foreach($periods as $periode)
                @php
                    $jabatan = $periode->nama_periode;
                    $display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;
                    
                    $list_uk_aktif = [];
                    foreach($all_uks as $uk) {
                        if (!empty($uk->posisi_target) && stripos($uk->posisi_target, $jabatan) !== false) {
                            $parts = explode('.', $uk->kode_unit);
                            if (count($parts) >= 3) {
                                $nomor = (int) preg_replace('/[^0-9]/', '', $parts[2]);
                                $list_uk_aktif[] = str_pad($nomor, 3, '0', STR_PAD_LEFT);
                            }
                        }
                    }
                    sort($list_uk_aktif);

                    $tgl_mulai_db = $periode->tanggal_mulai;
                    $tgl_selesai_db = $periode->tanggal_selesai;
                    
                    $tgl_text = "<span class='tambah-text' onclick=\"bukaModalTanggal('$jabatan', '', '')\"><i class='bi bi-calendar-event'></i> Atur Periode Penilaian</span>";
                    $status_text = "Belum Diatur"; 
                    $status_class = "badge-abu";
                    $js_tgl_m = ''; $js_tgl_s = '';

                    if (!empty($tgl_mulai_db) && strpos($tgl_mulai_db, '1970') === false) {
                        $tgl_mulai = strtotime($tgl_mulai_db);
                        $tgl_selesai = strtotime($tgl_selesai_db . ' 23:59:59');
                        $now = time();
                        
                        $js_tgl_m = date('Y-m-d', $tgl_mulai);
                        $js_tgl_s = date('Y-m-d', $tgl_selesai);
                        
                        $tgl_text = "<b style='color:#1e293b; font-size:13px;'>" . date('d M Y', $tgl_mulai) . "</b> <br>s/d <b style='color:#e11d48; font-size:13px;'>" . date('d M Y', $tgl_selesai) . "</b>";

                        if ($now < $tgl_mulai) { $status_text = "Belum Mulai"; $status_class = "badge-kuning"; }
                        elseif ($now > $tgl_selesai) { $status_text = "Penilaian Berakhir"; $status_class = "badge-merah"; } // DIUBAH MENJADI PENILAIAN BERAKHIR
                        else { $status_text = "Sedang Berlangsung"; $status_class = "badge-hijau"; }
                    }
                @endphp
            <tr class="inline-edit-row">
                <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $no++ }}</td>
                <td><b style="color: #1e293b; font-size: 14px;">{{ $display_jabatan }}</b></td>
                <td><span style="font-weight: normal; line-height: 1.6;">{!! $tgl_text !!}</span></td>
                <td class="td-clickable" onclick="window.location.href='{{ route('pimpinan.manajemen_penilaian.aktivitas', ['jabatan' => $jabatan]) }}'" title="Klik untuk menonaktifkan UK">
                    @if(!empty($list_uk_aktif))
                        <div class="uk-grid">
                            @foreach ($list_uk_aktif as $short_code)
                                <div class="uk-box">{{ $short_code }}</div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-click" style="color: #e11d48; border-bottom-color: #e11d48;"><i class="bi bi-exclamation-triangle"></i> 0 UK Aktif</span>
                    @endif
                </td>
                <td style="text-align: center;"><span class="badge {{ $status_class }}">{{ $status_text }}</span></td>
                <td style="text-align: center;">
                    <!-- TOMBOL EDIT TANGGAL DIMUNCULKAN -->
                    <div class="btn-aksi-group">
                        <button class="btn-icon btn-edit" onclick="bukaModalTanggal('{{ $jabatan }}', '{{ $js_tgl_m }}', '{{ $js_tgl_s }}')" title="Edit Tanggal Penilaian"><i class="bi bi-pencil-square"></i></button>
                        <button class="btn-icon btn-hapus" onclick="hapusPeriode('{{ addslashes($jabatan) }}')" title="Hapus Periode"><i class="bi bi-trash3-fill"></i></button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modals (Tetap Sama) -->
<div class="modal-overlay" id="modalJabatan">
    <div class="modal-box">
        <div class="modal-header"><h3><i class="bi bi-person-badge" style="color: #3e54a0;"></i> Pilih Jabatan</h3><button class="close-btn" type="button" onclick="tutupModal('modalJabatan')"><i class="bi bi-x"></i></button></div>
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}">
            @csrf <input type="hidden" name="action" value="tambah_jabatan_baru">
            <select name="jabatan" class="form-control" onchange="toggleDateInputs(this)" required>
                <option value="">-- Pilih Jabatan --</option>
                <option value="Semua Jabatan" style="font-weight: bold; color: #3e54a0;">-- Pilih Semua 6 Jabatan Museum --</option>
                @foreach($posisi_list as $pos)
                    @php 
                        $periode_ini = $periods->where('nama_periode', $pos)->first();
                        $is_exist = $periode_ini ? true : false;
                        $is_selesai = false;
                        if ($periode_ini && !empty($periode_ini->tanggal_selesai)) {
                            if (time() > strtotime($periode_ini->tanggal_selesai . ' 23:59:59')) { $is_selesai = true; }
                        }
                        $disable_opt = ($is_exist && !$is_selesai);
                    @endphp
                    <option value="{{ $pos }}" {{ $disable_opt ? 'disabled style=color:#cbd5e1;' : '' }}>
                        {{ $pos == 'Hubungan Masyarakat dan Pemasaran' ? 'Humas & Pemasaran' : $pos }} 
                        {{ $disable_opt ? '(Sedang Berjalan)' : ($is_selesai ? '(Periode Baru)' : '') }}
                    </option>
                @endforeach
            </select>
            <div id="serentak-date-fields" style="display: none; margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 20px;">
                <label style="font-size: 11px; font-weight: 700; color:#475569; margin-bottom:6px; display:block; text-transform: uppercase;">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai_serentak" id="tgl_mulai_serentak" class="form-control">
                <label style="font-size: 11px; font-weight: 700; color:#475569; margin-bottom:6px; display:block; text-transform: uppercase;">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai_serentak" id="tgl_selesai_serentak" class="form-control">
            </div>
            <button type="submit" class="btn-simpan">Pilih & Simpan</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalTanggal">
    <div class="modal-box">
        <div class="modal-header"><h3><i class="bi bi-calendar-event" style="color: #3e54a0;"></i> Atur Tanggal</h3><button class="close-btn" type="button" onclick="tutupModal('modalTanggal')"><i class="bi bi-x"></i></button></div>
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}">
            @csrf <input type="hidden" name="action" value="update_tanggal">
            <input type="hidden" name="jabatan" id="tgl_jabatan">
            <label style="font-size: 11px; font-weight: 700; color:#475569; margin-bottom:6px; display:block; text-transform: uppercase;">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" id="tgl_mulai" class="form-control" required>
            <label style="font-size: 11px; font-weight: 700; color:#475569; margin-bottom:6px; display:block; text-transform: uppercase;">Tanggal Selesai (Penutupan)</label>
            <input type="date" name="tanggal_selesai" id="tgl_selesai" class="form-control" required>
            <button type="submit" class="btn-simpan">Simpan Perubahan</button>
        </form>
    </div>
</div>

<form id="formHapus" method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}" style="display:none;">
    @csrf <input type="hidden" name="action" value="hapus_periode"> <input type="hidden" name="jabatan" id="hapusJabatan">
</form>
@endsection

@push('scripts')
<script>
function bukaModalJabatan() { 
    document.getElementById('modalJabatan').style.display = 'flex'; 
    document.body.style.overflow = "hidden";
}
function bukaModalTanggal(jabatan, tglMulai, tglSelesai) {
    document.getElementById('tgl_jabatan').value = jabatan;
    document.getElementById('tgl_mulai').value = tglMulai;
    document.getElementById('tgl_selesai').value = tglSelesai;
    document.getElementById('modalTanggal').style.display = 'flex';
    document.body.style.overflow = "hidden";
}
function hapusPeriode(jabatan) {
    let title = (jabatan === 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : jabatan;
    Swal.fire({
        title: 'Hapus Periode?', text: "Seluruh konfigurasi untuk posisi " + title + " akan dihapus.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#e11d48', confirmButtonText: 'Ya, Hapus!'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('hapusJabatan').value = jabatan; document.getElementById('formHapus').submit();
        }
    });
}
function toggleDateInputs(selectEle) {
    const dateFields = document.getElementById('serentak-date-fields');
    if (selectEle.value === 'Semua Jabatan') { dateFields.style.display = 'block'; } 
    else { dateFields.style.display = 'none'; }
}
function tutupModal(id) { 
    document.getElementById(id).style.display = 'none'; 
    document.body.style.overflow = "auto";
}
window.onclick = function(event) {
    if (event.target.classList.contains('modal-overlay')) {
        event.target.style.display = "none";
        document.body.style.overflow = "auto";
    }
}
</script>
@endpush