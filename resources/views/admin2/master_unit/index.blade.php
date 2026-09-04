@extends('layouts.app')
@section('title', 'Master Bank SKKNI')
@section('page_title', 'Master Bank SKKNI')
@section('page_subtitle', 'Kelola database Unit, Elemen, dan Aktivitas Kompetensi SKKNI.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/master_unit.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="page-card">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 25px;">
        <div>
            <h3 style="color: #2c3e50; margin-bottom: 5px;">Bank Data SKKNI</h3>
            <p style="font-size: 13px; color: #7f8c8d; margin: 0;">Kelola hierarki master Unit, Elemen, dan Aktivitas.</p>
        </div>
        <a href="{{ route('admin.master_unit.create') }}" class="btn-add-main" style="text-decoration:none;">
            <i class="bi bi-plus-lg"></i> Tambah Unit Kompetensi
        </a>
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
                <th width="60%">JUDUL UNIT KOMPETENSI</th>
                <th width="15%" style="text-align: center;">AKSI</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($data_hirarki as $unit)
            @php $safe_unit_id = "unit_" . Str::slug($unit->kode_unit, '_'); @endphp
            
            <tr class="clickable-row" id="row-{{ $safe_unit_id }}" onclick="toggleRow('{{ $safe_unit_id }}', this)">
                <td style="text-align: center;"><i class="bi bi-chevron-right icon-{{ $safe_unit_id }}"></i></td>
                <td style="text-align: center; font-size: 13px; font-weight: 600; color: #2c3e50;">{{ $unit->kode_unit }}</td>
                <td style="font-size: 13px; font-weight: 500; color: #34495e;">
                    {{ $unit->judul_unit }}
                    <div style="font-size: 11px; color: #94a3b8; font-weight: 400; margin-top: 3px;"><i class="bi bi-folder2"></i> {{ $unit->jenis_kompetensi }}</div>
                </td>
                <td style="text-align: center;" class="no-propagate" onclick="event.stopPropagation();">
                    <button class="btn-action btn-edit" onclick="bukaModalEditUnit('{{ $unit->kode_unit }}', '{{ addslashes($unit->judul_unit) }}', '{{ $unit->jenis_kompetensi }}')"><i class="bi bi-pencil-square"></i></button>
                    <button class="btn-action btn-delete" onclick="hapusData('hapus_unit', '{{ $unit->kode_unit }}')"><i class="bi bi-trash"></i></button>
                </td>
            </tr>

            <tr class="sub-table-container" id="child-{{ $safe_unit_id }}">
                <td colspan="4" style="padding: 0;">
                    <div class="elemen-wrapper">
                        <div class="elemen-title-header">
                            <span><i class="bi bi-diagram-3"></i> Daftar Elemen & Aktivitas</span>
                            <button class="btn-add" onclick="bukaModalElemen('tambah', '{{ $unit->kode_unit }}', '', '', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Elemen</button>
                        </div>

                        @if($unit->elemen->count() > 0)
                            @foreach ($unit->elemen as $elemen)
                            @php $safe_elemen_id = "elemen_" . $elemen->elemen_id; @endphp
                                <div class="elemen-card">
                                    <div class="elemen-header" onclick="toggleElemen('{{ $safe_elemen_id }}', this)">
                                        <i class="bi bi-plus-circle-fill icon-{{ $safe_elemen_id }}" style="color: #3498db; font-size: 20px; margin-top: 2px;"></i>
                                        <div class="elemen-info">
                                            <div style="display: flex; justify-content: space-between;">
                                                <h4 style="font-size: 13px; color: #1e293b; margin: 0 0 8px 0; font-weight: 600;">{{ $elemen->kode_elemen_excel }} - {{ $elemen->elemen_kompetensi }}</h4>
                                                <div class="no-propagate" onclick="event.stopPropagation();">
                                                    <button class="btn-action btn-edit" onclick="bukaModalElemen('edit', '{{ $unit->kode_unit }}', '{{ $elemen->elemen_id }}', '{{ addslashes($elemen->kode_elemen_excel) }}', '{{ addslashes($elemen->elemen_kompetensi) }}', '{{ addslashes($elemen->input) }}', '{{ addslashes($elemen->output) }}', '{{ addslashes($elemen->outcome) }}')"><i class="bi bi-pencil-square"></i></button>
                                                    <button class="btn-action btn-delete" onclick="hapusData('hapus_elemen', '{{ $elemen->elemen_id }}')"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </div>
                                            <div style="background: #f8fafc; padding: 10px 12px; border-radius: 6px; margin-top: 8px; border: 1px solid #e2e8f0;">
                                                <p style="margin-bottom: 4px; font-size: 13px;"><b style="color:#1e293b;">Input:</b> {{ $elemen->input ?? '-' }}</p>
                                                <p style="margin-bottom: 4px; font-size: 13px;"><b style="color:#1e293b;">Output:</b> {{ $elemen->output ?? '-' }}</p>
                                                <p style="margin-bottom: 0; font-size: 13px;"><b style="color:#1e293b;">Outcome:</b> {{ $elemen->outcome ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="aktivitas-container" id="child-{{ $safe_elemen_id }}" style="display:none;">
                                        <div style="text-align: right; margin-bottom: 10px;">
                                            <button class="btn-add" onclick="bukaModalAktivitas('tambah', '{{ $elemen->elemen_id }}', '', '', '', '')"><i class="bi bi-plus"></i> Tambah Aktivitas</button>
                                        </div>
                                        
                                        @if($elemen->aktivitas->count() > 0)
                                            <table class="clean-table">
                                                <thead>
                                                    <tr>
                                                        <th width="15%">Aktivitas ID</th>
                                                        <th width="40%">Detail Aktivitas</th>
                                                        <th width="25%">Kriteria Kompetensi</th>
                                                        <th width="10%" style="text-align: center;">Bukti</th>
                                                        <th width="10%" style="text-align: center;">Aksi</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($elemen->aktivitas as $aktivitas)
                                                        <tr>
                                                            <td><b>{{ $aktivitas->aktivitas_id }}</b></td>
                                                            <td>{{ $aktivitas->detail_aktivitas }}</td>
                                                            <td><small>{{ $aktivitas->kriteria_kompetens }}</small></td>
                                                            <td style="text-align: center;"><span class="badge-evidence">{{ $aktivitas->jumlah_evidence_wa }} Dok</span></td>
                                                            <td style="text-align: center;">
                                                                <button class="btn-action btn-edit" onclick="bukaModalAktivitas('edit', '{{ $elemen->elemen_id }}', '{{ $aktivitas->aktivitas_id }}', '{{ addslashes($aktivitas->detail_aktivitas) }}', '{{ addslashes($aktivitas->kriteria_kompetens) }}', '{{ $aktivitas->jumlah_evidence_wa }}')"><i class="bi bi-pencil-square"></i></button>
                                                                <button class="btn-action btn-delete" onclick="hapusData('hapus_aktivitas', '{{ $aktivitas->aktivitas_id }}')"><i class="bi bi-trash"></i></button>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @else
                                            <div style="color: #e74c3c; font-size: 13px;"><i class="bi bi-info-circle"></i> Belum ada aktivitas di elemen ini.</div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div style="padding: 15px; color: #e74c3c; font-size: 13px; text-align:center; border: 1px dashed #f5b7b1;">Belum ada elemen kompetensi untuk unit ini.</div>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;">Belum ada data Unit Kompetensi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Unit -->
<div id="modalUnit" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalUnit')">&times;</span>
        <h3 id="unitModalTitle" style="margin-bottom: 20px;">Edit Unit Kompetensi</h3>
        <form method="POST" action="{{ route('admin.master_unit.action') }}">
            @csrf <input type="hidden" name="aksi" value="edit_unit">
            <input type="hidden" name="kode_unit_lama" id="unitKodeLama">
            <div class="form-group"><label>Kode Unit</label><input type="text" id="unitKode" disabled style="background:#f1f5f9;"></div>
            <div class="form-group"><label>Judul Unit</label><textarea name="judul_unit" id="unitJudul" required rows="3"></textarea></div>
            <div class="form-group"><label>Jenis Kompetensi</label>
                <select name="jenis_kompetensi" id="unitJenis" required>
                    <option value="Manajerial">Manajerial</option><option value="Teknis/Digital">Teknis/Digital</option><option value="Layanan">Layanan</option>
                </select>
            </div>
            <button type="submit" class="btn-submit">Update Unit</button>
        </form>
    </div>
</div>

<!-- Modal Elemen -->
<div id="modalElemen" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalElemen')">&times;</span>
        <h3 id="elemenModalTitle" style="margin-bottom: 20px;">Tambah Elemen</h3>
        <form method="POST" action="{{ route('admin.master_unit.action') }}">
            @csrf <input type="hidden" name="aksi" id="aksiElemen">
            <input type="hidden" name="kode_unit_parent" id="elemenParentUnit">
            <input type="hidden" name="elemen_id" id="elemenId">
            <div class="form-group"><label>Kode Elemen (Misal: 1A)</label><input type="text" name="kode_elemen_excel" id="elemenKode" required></div>
            <div class="form-group"><label>Judul Elemen</label><textarea name="elemen_kompetensi" id="elemenJudul" required rows="2"></textarea></div>
            <div class="form-group"><label>Input</label><textarea name="input" id="elemenInput" rows="2"></textarea></div>
            <div class="form-group"><label>Output</label><textarea name="output" id="elemenOutput" rows="2"></textarea></div>
            <div class="form-group"><label>Outcome</label><textarea name="outcome" id="elemenOutcome" rows="2"></textarea></div>
            <button type="submit" class="btn-submit">Simpan Elemen</button>
        </form>
    </div>
</div>

<!-- Modal Aktivitas -->
<div id="modalAktivitas" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="tutupModal('modalAktivitas')">&times;</span>
        <h3 id="aktModalTitle" style="margin-bottom: 20px;">Tambah Aktivitas</h3>
        <form method="POST" action="{{ route('admin.master_unit.action') }}">
            @csrf <input type="hidden" name="aksi" id="aksiAktivitas">
            <input type="hidden" name="elemen_id_parent" id="aktParentElemen">
            <input type="hidden" name="aktivitas_id_lama" id="aktIdLama">
            <div class="form-group"><label>ID Aktivitas (Unik)</label><input type="text" name="aktivitas_id" id="aktId" required></div>
            <div class="form-group"><label>Detail Aktivitas</label><textarea name="detail_aktivitas" id="aktDetail" required rows="3"></textarea></div>
            <div class="form-group"><label>Kriteria Kompetensi</label><textarea name="kriteria_kompetens" id="aktKriteria" required rows="2"></textarea></div>
            <div class="form-group"><label>Jumlah Dokumen Evidence</label><input type="number" name="jumlah_evidence" id="aktEvidence" required min="0" value="1"></div>
            <button type="submit" class="btn-submit">Simpan Aktivitas</button>
        </form>
    </div>
</div>

<!-- Form Rahasia untuk Hapus -->
<form id="formHapus" method="POST" action="{{ route('admin.master_unit.action') }}" style="display:none;">
    @csrf <input type="hidden" name="aksi" id="aksiHapus"> <input type="hidden" name="hapus_id" id="hapusId">
</form>

@endsection

@push('scripts')
<script>
// Javascript asli milikmu, tidak ada yang diubah fungsinya
function toggleRow(id, rowElement) {
    var childRow = document.getElementById('child-' + id);
    var icon = document.querySelector('.icon-' + id);
    if (!childRow) return;
    if (childRow.style.display === 'table-row') {
        childRow.style.display = 'none'; rowElement.classList.remove('row-expanded');
        icon.classList.replace('bi-chevron-down', 'bi-chevron-right');
    } else {
        childRow.style.display = 'table-row'; rowElement.classList.add('row-expanded');
        icon.classList.replace('bi-chevron-right', 'bi-chevron-down');
    }
}

function toggleElemen(id, headerElement) {
    var childContainer = document.getElementById('child-' + id);
    var icon = headerElement.querySelector('.icon-' + id);
    if (!childContainer) return;
    if (childContainer.style.display === 'block') {
        childContainer.style.display = 'none';
        icon.classList.replace('bi-dash-circle-fill', 'bi-plus-circle-fill');
        headerElement.style.borderBottom = 'none';
    } else {
        childContainer.style.display = 'block';
        icon.classList.replace('bi-plus-circle-fill', 'bi-dash-circle-fill');
        headerElement.style.borderBottom = '1px solid #e2e8f0';
    }
}

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
</script>
@endpush