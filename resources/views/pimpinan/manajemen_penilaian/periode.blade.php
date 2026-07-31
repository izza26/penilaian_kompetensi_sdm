@extends('layouts.app')
@section('title', 'Manajemen Penilaian')
@section('page_title', 'Manajemen Penilaian')
@section('page_subtitle', 'Atur waktu periode penilaian dan petakan UK spesifik per jabatan.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; }
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .styled-table th { background: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-size: 12px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; display: inline-flex; justify-content: center; width: 130px; text-align: center; }
        .badge-hijau { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
        .badge-kuning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
        .badge-merah { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-abu { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .btn-hapus { width: 34px; height: 34px; border-radius: 8px; border: none; cursor: pointer; display: inline-flex; justify-content: center; align-items: center; font-size: 15px; background: #fef2f2; color: #ef4444; }
        .btn-hapus:hover { background: #ef4444; color: white; }
        .inline-edit-row:hover { background-color: #f8fafc; }
        .td-clickable { cursor: pointer; transition: 0.2s; }
        .td-clickable:hover { background-color: #eff6ff; }
        .text-click { color: #0f172a; border-bottom: 1px dashed transparent; padding-bottom: 2px; font-weight: 600; font-size: 13px;}
        .td-clickable:hover .text-click { color: #3b82f6; border-bottom-color: #3b82f6; }
        .tambah-text { color: #3b82f6; font-weight: 600; border-bottom: 1px dashed #3b82f6; }
        .tambah-icon { background: #eff6ff; color: #3b82f6; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; justify-content: center; align-items: center; font-size: 14px;}
        .uk-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .uk-box { font-size: 12px; background: #ffffff; color: #1e3a8a; border: 1px solid #cbd5e1; padding: 4px 8px; border-radius: 4px; font-weight: 600; text-align: center; position: relative; overflow: hidden; transition: 0.2s;}
        .uk-box::before { content: ''; position: absolute; top: 0; left: 0; width: 0; height: 0; border-style: solid; border-width: 6px 6px 0 0; border-color: #059669 transparent transparent transparent; }
        .td-clickable:hover .uk-box { background: #eff6ff; border-color: #3b82f6; }
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15,23,42,0.5); z-index: 2000; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
        .modal-box { background: white; padding: 25px; border-radius: 16px; width: 350px;}
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .close-btn { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; box-sizing: border-box; margin-bottom: 15px;}
        .btn-simpan { background: #182A3A; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer; width: 100%; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div style="margin-bottom: 20px;">
        <h3 style="color: #0f172a; margin: 0 0 5px 0; font-size: 20px;">Pemetaan Periode per Jabatan</h3>
        <p style="font-size: 13px; color: #64748b; margin: 0;">Klik langsung pada kolom tabel untuk menambahkan atau mengedit datanya.</p>
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
                <td class="td-clickable" onclick="bukaModalJabatan()"><span class="tambah-text">Pilih Jabatan...</span></td>
                <td><span style="color: #cbd5e1; font-style: italic;">Pilih jabatan terlebih dahulu</span></td>
                <td><span style="color: #cbd5e1; font-style: italic;">Pilih jabatan terlebih dahulu</span></td>
                <td style="text-align: center; color: #cbd5e1; font-weight: 600;">-</td>
                <td style="text-align: center; color: #cbd5e1; font-size: 12px; font-weight: 500;"><i class="bi bi-pencil-fill"></i> Tambah</td>
            </tr>

            @php $no = 1; @endphp
            @foreach($periods as $periode)
                @php
                    $jabatan = $periode->nama_periode;
                    $display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;
                    
                    // Filter UK Aktif menggunakan Collection Laravel
                    $list_uk_aktif = [];
                    foreach($all_uks as $uk) {
                        if (stripos($uk->posisi_target, $jabatan) !== false && $uk->aktif == 'Y') {
                            $parts = explode('.', $uk->kode_unit);
                            $list_uk_aktif[] = count($parts) >= 3 ? $parts[2] : $uk->kode_unit;
                        }
                    }

                    $tgl_mulai_db = $periode->tanggal_mulai;
                    $tgl_selesai_db = $periode->tanggal_selesai;
                    
                    $tgl_text = "<span class='tambah-text'><i class='bi bi-calendar-event'></i> Atur Periode Penilaian</span>";
                    $status_text = "Belum Diatur"; 
                    $status_class = "badge-abu";
                    $js_tgl_m = ''; $js_tgl_s = '';

                    if (!empty($tgl_mulai_db) && strpos($tgl_mulai_db, '1970') === false) {
                        $tgl_mulai = strtotime($tgl_mulai_db);
                        $tgl_selesai = strtotime($tgl_selesai_db . ' 23:59:59');
                        $now = time();
                        
                        $js_tgl_m = date('Y-m-d', $tgl_mulai);
                        $js_tgl_s = date('Y-m-d', $tgl_selesai);
                        
                        $tgl_text = "<b style='color:#1e293b; font-size:13px;'>" . date('d M Y', $tgl_mulai) . "</b> <br>s/d <b style='color:#e74c3c; font-size:13px;'>" . date('d M Y', $tgl_selesai) . "</b>";

                        if ($now < $tgl_mulai) { $status_text = "Belum Mulai"; $status_class = "badge-kuning"; }
                        elseif ($now > $tgl_selesai) { $status_text = "Selesai"; $status_class = "badge-merah"; }
                        else { $status_text = "Sedang Berlangsung"; $status_class = "badge-hijau"; }
                    }
                @endphp
            <tr class="inline-edit-row">
                <td style="text-align: center; color: #64748b; font-weight: 600;">{{ $no++ }}</td>
                <td><b style="color: #0f172a; font-size: 14px;">{{ $display_jabatan }}</b></td>
                <td class="td-clickable" onclick="bukaModalTanggal('{{ $jabatan }}', '{{ $js_tgl_m }}', '{{ $js_tgl_s }}')">
                    <span class="text-click" style="font-weight: normal; line-height: 1.6;">{!! $tgl_text !!}</span>
                </td>
                <td class="td-clickable" onclick="window.location.href='{{ route('pimpinan.manajemen_penilaian.aktivitas', ['jabatan' => $jabatan]) }}'" title="Klik untuk mengatur aktivitas kompetensi">
                    @if(!empty($list_uk_aktif))
                        <div class="uk-grid">
                            @foreach ($list_uk_aktif as $short_code)
                                <div class="uk-box">{{ $short_code }}</div>
                            @endforeach
                        </div>
                    @else
                        <span class="text-click" style="color: #dc2626; border-bottom-color: #dc2626;"><i class="bi bi-exclamation-triangle"></i> 0 UK Aktif</span>
                    @endif
                </td>
                <td style="text-align: center;"><span class="badge {{ $status_class }}">{{ $status_text }}</span></td>
                <td style="text-align: center;">
                    <button class="btn-hapus" onclick="hapusPeriode('{{ addslashes($jabatan) }}')" title="Hapus Periode"><i class="bi bi-trash3-fill"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modals -->
<div class="modal-overlay" id="modalJabatan">
    <div class="modal-box">
        <div class="modal-header"><h3><i class="bi bi-person-badge"></i> Pilih Jabatan</h3><button class="close-btn" type="button" onclick="tutupModal('modalJabatan')"><i class="bi bi-x"></i></button></div>
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}">
            @csrf <input type="hidden" name="action" value="tambah_jabatan_baru">
            <select name="jabatan" class="form-control" onchange="toggleDateInputs(this)" required>
                <option value="">-- Pilih Jabatan --</option>
                <option value="Semua Jabatan" style="font-weight: bold; color: #3b82f6;">-- Pilih Semua Jabatan --</option>
                @foreach($posisi_list as $pos)
                    @php $is_exist = $periods->contains('nama_periode', $pos); @endphp
                    <option value="{{ $pos }}" {{ $is_exist ? 'disabled style=color:#cbd5e1;' : '' }}>{{ $pos == 'Hubungan Masyarakat dan Pemasaran' ? 'Humas & Pemasaran' : $pos }} {{ $is_exist ? '(Sudah Ada)' : '' }}</option>
                @endforeach
            </select>
            <div id="serentak-date-fields" style="display: none; margin-top: 10px; border-top: 1px dashed #cbd5e1; padding-top: 15px;">
                <label style="font-size: 11px; font-weight: 600; color:#64748b; margin-bottom:5px; display:block;">Tanggal Mulai</label>
                <input type="date" name="tgl_mulai_serentak" id="tgl_mulai_serentak" class="form-control">
                <label style="font-size: 11px; font-weight: 600; color:#64748b; margin-bottom:5px; display:block;">Tanggal Selesai</label>
                <input type="date" name="tgl_selesai_serentak" id="tgl_selesai_serentak" class="form-control">
            </div>
            <button type="submit" class="btn-simpan">Pilih & Simpan</button>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalTanggal">
    <div class="modal-box">
        <div class="modal-header"><h3><i class="bi bi-calendar-event"></i> Atur Tanggal</h3><button class="close-btn" type="button" onclick="tutupModal('modalTanggal')"><i class="bi bi-x"></i></button></div>
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}">
            @csrf <input type="hidden" name="action" value="update_tanggal">
            <input type="hidden" name="jabatan" id="tgl_jabatan">
            <label style="font-size: 11px; font-weight: 600; color:#64748b; margin-bottom:5px; display:block;">Tanggal Mulai</label><input type="date" name="tanggal_mulai" id="tgl_mulai" class="form-control" required>
            <label style="font-size: 11px; font-weight: 600; color:#64748b; margin-bottom:5px; display:block;">Tanggal Selesai</label><input type="date" name="tanggal_selesai" id="tgl_selesai" class="form-control" required>
            <button type="submit" class="btn-simpan">Simpan Tanggal</button>
        </form>
    </div>
</div>

<form id="formHapus" method="POST" action="{{ route('pimpinan.manajemen_penilaian.action') }}" style="display:none;">
    @csrf <input type="hidden" name="action" value="hapus_periode"> <input type="hidden" name="jabatan" id="hapusJabatan">
</form>

@endsection

@push('scripts')
<script>
function bukaModalJabatan() { document.getElementById('modalJabatan').style.display = 'flex'; }
function bukaModalTanggal(jabatan, tglMulai, tglSelesai) {
    document.getElementById('tgl_jabatan').value = jabatan;
    document.getElementById('tgl_mulai').value = tglMulai;
    document.getElementById('tgl_selesai').value = tglSelesai;
    document.getElementById('modalTanggal').style.display = 'flex';
}
function hapusPeriode(jabatan) {
    let title = (jabatan === 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : jabatan;
    Swal.fire({
        title: 'Hapus Periode?', text: "Seluruh konfigurasi untuk posisi " + title + " akan dihapus.", icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Ya, Hapus!'
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
function tutupModal(id) { document.getElementById(id).style.display = 'none'; }
</script>
@endpush