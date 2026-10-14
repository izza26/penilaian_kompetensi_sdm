@extends('layouts.app')
@section('title', 'Master Bank SKKNI')
@section('page_title', 'Master Bank SKKNI')
@section('page_subtitle', 'Direktori referensi hierarki Unit, Elemen, dan Kriteria Unjuk Kerja (KUK) SKKNI.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/master_unit.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .page-card { background: #fff; padding: 32px; border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #64748b; padding: 16px 20px; text-align: left; font-size: 11px; font-weight: 800; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px;}
        .styled-table td { padding: 18px 20px; border-bottom: 1px dashed #f1f5f9; font-size: 13px; color: #334155; }

        .clickable-row { cursor: pointer; transition: 0.2s; }
        .clickable-row:hover { background: #f8fafc; }
        .row-expanded { background: #f8fafc; }

        .sub-table-container { display: none; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
        .elemen-wrapper { padding: 24px 40px; border-left: 4px solid #3e54a0; }

        /* --- Desain Elemen Card --- */
        .elemen-card { background: #fff; border: 1px solid #cbd5e1; border-radius: 16px; margin-bottom: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); overflow: hidden; transition: 0.2s;}
        .elemen-card:hover { border-color: #94a3b8; }

        .elemen-card-header { padding: 18px 24px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: #fff; transition: 0.2s; }
        .elemen-card-header:hover { background: #f8fafc; }

        .elemen-card-body { padding: 0 24px 24px 24px; display: none; border-top: 1px dashed #e2e8f0; margin-top: 4px; padding-top: 20px;}

        /* --- Desain Mode Baca Admin --- */
        .kuk-header { font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 16px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 10px; letter-spacing: 0.5px; }
        .kuk-item { display: flex; gap: 14px; align-items: flex-start; margin-bottom: 14px; }
        .kuk-item:last-child { margin-bottom: 0; }
        .kuk-number { background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 11.5px; padding: 4px 10px; border-radius: 8px; white-space: nowrap; border: 1px solid #bfdbfe; }
        .kuk-detail { font-size: 13px; font-weight: 600; color: #1e293b; margin-bottom: 4px; line-height: 1.5;}
    </style>
@endpush

@section('content')
<div class="page-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <div>
            <h3 style="color: #1e293b; margin-bottom: 5px; font-size: 20px;"><i class="bi bi-journal-bookmark-fill" style="color:#3e54a0; margin-right:8px;"></i> Bank Data SKKNI</h3>
            <p style="font-size: 13px; color: #64748b; margin: 0;">
                @if(Auth::user()->role == 'superadmin') Mode Kelola: Manajemen data master SKKNI @else Mode Baca: Melihat hierarki Unit Kompetensi, Elemen, dan KUK. @endif
            </p>
        </div>

        @if(Auth::user()->role == 'superadmin')
            <a href="{{ route('admin.master_unit.create') }}" class="btn-add-main" style="text-decoration:none;">
                <i class="bi bi-plus-lg"></i> Tambah Unit Kompetensi
            </a>
        @endif
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
                <th width="5%"></th>
                <th width="20%">KODE UNIT</th>
                <th width="55%">JUDUL UNIT KOMPETENSI</th>
                @if(Auth::user()->role == 'superadmin')
                    <th width="20%" style="text-align: center;">AKSI</th>
                @else
                    <th width="20%" style="text-align: right;">JENIS KOMPETENSI</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($data_hirarki as $unit)
            @php $safe_unit_id = "unit_" . Str::slug($unit->kode_unit, '_'); @endphp

            <tr class="clickable-row" id="row-{{ $safe_unit_id }}" onclick="toggleRow('{{ $safe_unit_id }}', this)">
                <td style="text-align: center; color: #94a3b8;"><i class="bi bi-chevron-right icon-{{ $safe_unit_id }}" style="transition: 0.2s;"></i></td>
                <td style="font-size: 13.5px; font-weight: 700; color: #1e293b; font-family: monospace;">{{ $unit->kode_unit }}</td>
                <td>
                    <div style="font-size: 14px; font-weight: 600; color: #334155;">{{ $unit->judul_unit }}</div>
                </td>

                @if(Auth::user()->role == 'superadmin')
                    <td style="text-align: center;" class="no-propagate" onclick="event.stopPropagation();">
                        <button class="btn-action btn-edit" onclick="bukaModalEditUnit('{{ $unit->kode_unit }}', '{{ addslashes($unit->judul_unit) }}', '{{ $unit->jenis_kompetensi }}')"><i class="bi bi-pencil-square"></i></button>
                        <button class="btn-action btn-delete" onclick="hapusData('hapus_unit', '{{ $unit->kode_unit }}')"><i class="bi bi-trash"></i></button>
                    </td>
                @else
                    <td style="text-align: right;">
                        <span style="font-size: 10.5px; color: #3e54a0; font-weight: 700; background: #f4f7fe; padding: 4px 10px; border-radius: 50px;"><i class="bi bi-tags-fill"></i> {{ $unit->jenis_kompetensi }}</span>
                    </td>
                @endif
            </tr>

            <!-- LACI / KONTEN ANAK -->
            <tr class="sub-table-container" id="child-{{ $safe_unit_id }}">
                <td colspan="4" style="padding: 0;">
                    <div class="elemen-wrapper">

                        <!-- TAMPILAN KHUSUS SUPERADMIN (MODE KELOLA) -->
                        @if(Auth::user()->role == 'superadmin')
                            <div class="elemen-title-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
                                <span style="font-weight:700; color:#1e293b;"><i class="bi bi-diagram-3"></i> Manajemen Elemen & Aktivitas</span>
                                <button class="btn-add" onclick="bukaModalElemen('tambah', '{{ $unit->kode_unit }}', '', '', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Elemen</button>
                            </div>

                            @if($unit->elemen->count() > 0)
                                @foreach ($unit->elemen as $elemen)
                                @php $safe_elemen_id = "elemen_" . $elemen->elemen_id; @endphp
                                    <div class="elemen-card" style="border-radius: 12px; box-shadow:none;">
                                        <div class="elemen-card-header" onclick="toggleElemen('{{ $safe_elemen_id }}', this)">
                                            <div>
                                                <h4 style="font-size: 13px; color: #1e293b; margin: 0 0 4px 0; font-weight: 700;">{{ $elemen->kode_elemen_excel }} - {{ $elemen->elemen_kompetensi }}</h4>
                                                <span style="font-size:11px; color:#94a3b8;"><i class="bi bi-arrow-return-right"></i> {{ $elemen->aktivitas->count() }} Aktivitas</span>
                                            </div>
                                            <div class="no-propagate" onclick="event.stopPropagation();">
                                                <button class="btn-action btn-edit" onclick="bukaModalElemen('edit', '{{ $unit->kode_unit }}', '{{ $elemen->elemen_id }}', '{{ addslashes($elemen->kode_elemen_excel) }}', '{{ addslashes($elemen->elemen_kompetensi) }}', '{{ addslashes($elemen->input) }}', '{{ addslashes($elemen->output) }}', '{{ addslashes($elemen->outcome) }}')"><i class="bi bi-pencil-square"></i></button>
                                                <button class="btn-action btn-delete" onclick="hapusData('hapus_elemen', '{{ $elemen->elemen_id }}')"><i class="bi bi-trash"></i></button>
                                                <i class="bi bi-chevron-down icon-{{ $safe_elemen_id }}" style="margin-left: 10px; color:#94a3b8;"></i>
                                            </div>
                                        </div>

                                        <div class="elemen-card-body" id="body-{{ $safe_elemen_id }}">
                                            <div style="text-align: right; margin-bottom: 10px;">
                                                <button class="btn-add" onclick="bukaModalAktivitas('tambah', '{{ $elemen->elemen_id }}', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Aktivitas Baru</button>
                                            </div>
                                            @if($elemen->aktivitas->count() > 0)
                                                <table class="styled-table" style="box-shadow: 0 2px 10px rgba(0,0,0,0.03); border:1px solid #e2e8f0;">
                                                    <thead>
                                                        <tr>
                                                            <th width="15%">Aktivitas ID</th>
                                                            <th width="45%">Detail KUK</th>
                                                            <th width="20%">Evidence Wajib</th>
                                                            <th width="20%" style="text-align: center;">Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($elemen->aktivitas as $aktivitas)
                                                            <tr>
                                                                <td><b>{{ $aktivitas->aktivitas_id }}</b></td>
                                                                <td><span style="display:block; font-weight:600; margin-bottom:4px;">{{ $aktivitas->detail_aktivitas }}</span><span style="font-size:11px; color:#64748b;">{{ $aktivitas->kriteria_kompetens }}</span></td>
                                                                <td><span class="badge-evidence" style="background:#eff6ff; color:#3b82f6; padding:4px 10px; border-radius:50px; font-size:11px; font-weight:700;">{{ $aktivitas->jumlah_evidence_wa }} Dokumen</span></td>
                                                                <td style="text-align: center;">
                                                                    <button class="btn-action btn-edit" onclick="bukaModalAktivitas('edit', '{{ $elemen->elemen_id }}', '{{ $aktivitas->aktivitas_id }}', '{{ addslashes($aktivitas->detail_aktivitas) }}', '{{ addslashes($aktivitas->kriteria_kompetens) }}', '{{ $aktivitas->jumlah_evidence_wa }}')"><i class="bi bi-pencil-square"></i></button>
                                                                    <button class="btn-action btn-delete" onclick="hapusData('hapus_aktivitas', '{{ $aktivitas->aktivitas_id }}')"><i class="bi bi-trash"></i></button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <div style="color: #ef4444; font-size: 12px;"><i class="bi bi-info-circle"></i> Belum ada aktivitas yang ditambahkan.</div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div style="padding: 15px; color: #ef4444; font-size: 13px; text-align:center; border: 1px dashed #fca5a5;">Belum ada elemen kompetensi untuk unit ini.</div>
                            @endif

                        <!-- TAMPILAN KHUSUS ADMIN (MODE BACA CLEAN UX) -->
                        @else
                            @if($unit->elemen->count() > 0)
                                @php
                                    $groupedElements = $unit->elemen->groupBy(function($item) {
                                        $cleanTitle = trim(preg_replace('/^daftar\s+/i', '', $item->elemen_kompetensi));
                                        return ucfirst($cleanTitle);
                                    });
                                    $elemenIndex = 1;
                                @endphp

                                @foreach ($groupedElements as $judulElemen => $elemenGroup)
                                    @php
                                        $safe_elemen_id = "elemen_" . Str::slug($unit->kode_unit) . "_" . $elemenIndex;
                                        $firstElemen = $elemenGroup->first();
                                        $list_kuk = [];
                                        if (!empty($firstElemen->kriteria_unjuk_kerja)) {
                                            $raw_kuks = array_filter(preg_split('/\r\n|\r|\n/', $firstElemen->kriteria_unjuk_kerja));
                                            foreach($raw_kuks as $raw_kuk) {
                                                $clean_kuk = trim(preg_replace('/^\d+\.\s*/', '', $raw_kuk));
                                                if (!empty($clean_kuk)) $list_kuk[] = $clean_kuk;
                                            }
                                        }
                                    @endphp

                                    <div class="elemen-card">
                                        <div class="elemen-card-header" onclick="toggleElemen('{{ $safe_elemen_id }}', this)">
                                            <h4 style="font-size: 13px; color: #1e293b; margin: 0; font-weight: 600;">
                                                <span style="color: #64748b; margin-right: 4px;">{{ $elemenIndex }}.</span> <span style="color: #3e54a0; font-weight: 700;">{{ $judulElemen }}</span>
                                            </h4>
                                            <i class="bi bi-chevron-right icon-{{ $safe_elemen_id }}" style="color: #94a3b8; font-size: 16px; font-weight: bold; transition: 0.2s;"></i>
                                        </div>

                                        <div class="elemen-card-body" id="body-{{ $safe_elemen_id }}">
                                            <div>
                                                <div class="kuk-header">DAFTAR KRITERIA UNJUK KERJA (KUK):</div>
                                                @if(count($list_kuk) > 0)
                                                    <div style="display: flex; flex-direction: column;">
                                                        @foreach ($list_kuk as $index => $kuk_text)
                                                            <div class="kuk-item">
                                                                <div class="kuk-number">{{ $elemenIndex }}.{{ $index + 1 }}</div>
                                                                <div><div class="kuk-detail" style="font-weight: 500; color: #334155; margin-bottom: 0;">{{ $kuk_text }}</div></div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <div style="font-size: 12px; color: #ef4444;"><i class="bi bi-info-circle-fill"></i> Data Kriteria Unjuk Kerja belum tersedia.</div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    @php $elemenIndex++; @endphp
                                @endforeach
                            @else
                                <div style="padding: 15px; color: #ef4444; font-size: 13px; border: 1px dashed #fca5a5; border-radius: 12px; background: #fef2f2; text-align: center;">
                                    <i class="bi bi-exclamation-triangle-fill"></i> Belum ada elemen kompetensi untuk unit ini.
                                </div>
                            @endif
                        @endif

                    </div>
                </td>
            </tr>
            @empty
                <tr><td colspan="4" style="text-align:center; padding: 40px; color:#64748b;">Belum ada data Unit Kompetensi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal & Form Hidden (Hanya di-render untuk Superadmin) -->
@if(Auth::user()->role == 'superadmin')
    <div id="modalUnit" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="tutupModal('modalUnit')">&times;</span>
            <h3 id="unitModalTitle" style="margin-bottom: 20px;">Edit Unit Kompetensi</h3>
            <form method="POST" action="{{ route('admin.master_unit.action') }}">
                @csrf <input type="hidden" name="aksi" value="edit_unit">
                <input type="hidden" name="kode_unit_lama" id="unitKodeLama">
                <div class="form-group"><label>Kode Unit</label><input type="text" id="unitKode" disabled style="background:#f1f5f9; width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></div>
                <div class="form-group"><label>Judul Unit</label><textarea name="judul_unit" id="unitJudul" required rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Jenis Kompetensi</label>
                    <select name="jenis_kompetensi" id="unitJenis" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;">
                        <option value="Manajerial">Manajerial</option><option value="Teknis/Digital">Teknis/Digital</option><option value="Layanan">Layanan</option>
                    </select>
                </div>
                <button type="submit" class="btn-submit" style="background:#3e54a0; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; margin-top:10px;">Update Unit</button>
            </form>
        </div>
    </div>

    <div id="modalElemen" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="tutupModal('modalElemen')">&times;</span>
            <h3 id="elemenModalTitle" style="margin-bottom: 20px;">Tambah Elemen</h3>
            <form method="POST" action="{{ route('admin.master_unit.action') }}">
                @csrf <input type="hidden" name="aksi" id="aksiElemen">
                <input type="hidden" name="kode_unit_parent" id="elemenParentUnit">
                <input type="hidden" name="elemen_id" id="elemenId">
                <div class="form-group"><label>Kode Elemen (Misal: 1A)</label><input type="text" name="kode_elemen_excel" id="elemenKode" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></div>
                <div class="form-group"><label>Judul Elemen</label><textarea name="elemen_kompetensi" id="elemenJudul" required rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Input</label><textarea name="input" id="elemenInput" rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Output</label><textarea name="output" id="elemenOutput" rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Outcome</label><textarea name="outcome" id="elemenOutcome" rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <button type="submit" class="btn-submit" style="background:#3e54a0; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; margin-top:10px;">Simpan Elemen</button>
            </form>
        </div>
    </div>

    <div id="modalAktivitas" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="tutupModal('modalAktivitas')">&times;</span>
            <h3 id="aktModalTitle" style="margin-bottom: 20px;">Tambah Aktivitas</h3>
            <form method="POST" action="{{ route('admin.master_unit.action') }}">
                @csrf <input type="hidden" name="aksi" id="aksiAktivitas">
                <input type="hidden" name="elemen_id_parent" id="aktParentElemen">
                <input type="hidden" name="aktivitas_id_lama" id="aktIdLama">
                <div class="form-group"><label>ID Aktivitas (Unik)</label><input type="text" name="aktivitas_id" id="aktId" required style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></div>
                <div class="form-group"><label>Detail Aktivitas</label><textarea name="detail_aktivitas" id="aktDetail" required rows="3" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Kriteria Kompetensi</label><textarea name="kriteria_kompetens" id="aktKriteria" required rows="2" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></textarea></div>
                <div class="form-group"><label>Jumlah Dokumen Evidence</label><input type="number" name="jumlah_evidence" id="aktEvidence" required min="0" value="1" style="width:100%; padding:10px; border-radius:8px; border:1px solid #cbd5e1;"></div>
                <button type="submit" class="btn-submit" style="background:#3e54a0; color:#fff; border:none; padding:10px 20px; border-radius:8px; cursor:pointer; margin-top:10px;">Simpan Aktivitas</button>
            </form>
        </div>
    </div>

    <form id="formHapus" method="POST" action="{{ route('admin.master_unit.action') }}" style="display:none;">
        @csrf <input type="hidden" name="aksi" id="aksiHapus"> <input type="hidden" name="hapus_id" id="hapusId">
    </form>
@endif
@endsection

@push('scripts')
<script>
function toggleRow(id, rowElement) {
    var childRow = document.getElementById('child-' + id);
    var icon = document.querySelector('.icon-' + id);
    if (!childRow) return;

    if (childRow.style.display === 'table-row') {
        childRow.style.display = 'none';
        rowElement.classList.remove('row-expanded');
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    } else {
        childRow.style.display = 'table-row';
        rowElement.classList.add('row-expanded');
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    }
}

function toggleElemen(id, headerElement) {
    var body = document.getElementById('body-' + id);
    var icon = headerElement.querySelector('.icon-' + id) || headerElement.querySelector('i:last-child');
    if (!body) return;

    if (body.style.display === 'block') {
        body.style.display = 'none';
        if(icon) {
            icon.classList.contains('bi-chevron-down') ? icon.style.transform = 'rotate(0deg)' : icon.classList.replace('bi-dash-circle-fill', 'bi-plus-circle-fill');
        }
    } else {
        body.style.display = 'block';
        if(icon) {
            icon.classList.contains('bi-chevron-down') ? icon.style.transform = 'rotate(-180deg)' : icon.classList.replace('bi-plus-circle-fill', 'bi-dash-circle-fill');
        }
    }
}

@if(Auth::user()->role == 'superadmin')
    function bukaModal(id) { document.getElementById(id).style.display = "block"; }
    function tutupModal(id) { document.getElementById(id).style.display = "none"; }

    function bukaModalEditUnit(kode, judul, jenis) {
        document.getElementById('unitKode').value = kode;
        document.getElementById('unitKodeLama').value = kode;
        document.getElementById('unitJudul').value = judul;
        let sel = document.getElementById('unitJenis');
        if(jenis && !Array.from(sel.options).some(o=>o.value===jenis)) sel.add(new Option(jenis, jenis));
        sel.value = jenis || '';
        bukaModal('modalUnit');
    }

    function bukaModalElemen(mode, pUnit, id, kExcel, jdl, inp, out, outc) {
        document.getElementById('aksiElemen').value = mode === 'edit' ? 'edit_elemen' : 'tambah_elemen';
        document.getElementById('elemenModalTitle').innerText = mode === 'edit' ? 'Edit Elemen' : 'Tambah Elemen';
        document.getElementById('elemenParentUnit').value = pUnit;
        document.getElementById('elemenId').value = id;
        document.getElementById('elemenKode').value = kExcel;
        document.getElementById('elemenJudul').value = jdl;
        document.getElementById('elemenInput').value = inp;
        document.getElementById('elemenOutput').value = out;
        document.getElementById('elemenOutcome').value = outc;
        bukaModal('modalElemen');
    }

    function bukaModalAktivitas(mode, pElemen, aktId, detail, kriteria, evidence) {
        document.getElementById('aksiAktivitas').value = mode === 'edit' ? 'edit_aktivitas' : 'tambah_aktivitas';
        document.getElementById('aktModalTitle').innerText = mode === 'edit' ? 'Edit Aktivitas' : 'Tambah Aktivitas';
        document.getElementById('aktParentElemen').value = pElemen;
        document.getElementById('aktIdLama').value = aktId;
        document.getElementById('aktId').value = aktId;
        document.getElementById('aktDetail').value = detail;
        document.getElementById('aktKriteria').value = kriteria;
        document.getElementById('aktEvidence').value = evidence || 1;
        bukaModal('modalAktivitas');
    }

    function hapusData(aksi, id) {
        let psn = aksi === 'hapus_unit' ? "Hapus Unit ini beserta SELURUH Elemen dan Aktivitas?" :
                 (aksi === 'hapus_elemen' ? "Hapus Elemen beserta SELURUH Aktivitas?" : "Yakin hapus aktivitas ini?");
        Swal.fire({
            title: 'Hapus Data', text: psn, icon: 'warning', showCancelButton: true, confirmButtonColor: '#e74c3c', confirmButtonText: 'Ya, Hapus!'
        }).then((res) => {
            if (res.isConfirmed) {
                document.getElementById('aksiHapus').value = aksi;
                document.getElementById('hapusId').value = id;
                document.getElementById('formHapus').submit();
            }
        });
    }
@endif
</script>
@endpush
