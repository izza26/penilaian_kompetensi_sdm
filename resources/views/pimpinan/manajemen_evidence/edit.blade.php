@extends('layouts.app')
@section('title', 'Atur Evidence')
@section('page_title', 'Atur Rincian Evidence')
@section('page_subtitle', 'Jabarkan opsi dokumen yang diizinkan untuk setiap target evidence.')
@section('back_url', route('pimpinan.manajemen_evidence.index'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .detail-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px 30px; margin-bottom: 25px; display: flex; gap: 30px;}
        .detail-left { flex: 1; }
        .detail-right { flex: 1; background: #f8fafc; border-radius: 12px; padding: 20px; border: 1px solid #cbd5e1; }
        .unit-badge { font-size: 12px; font-weight: 700; color: #A08348; background: #fffbeb; padding: 4px 12px; border-radius: 6px; border: 1px solid #fde68a; display: inline-block; margin-bottom: 10px; }
        .detail-card h3 { margin: 0 0 5px 0; font-size: 13px; color: #0f172a; line-height: 1.4; }
        .kriteria-title { font-size: 12px; font-weight: 700; color: #182A3A; display: block; margin-bottom: 8px; text-transform: uppercase;}
        .kriteria-text { font-size: 14px; color: #334155; line-height: 1.5;}

        .form-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); }
        .form-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between;}
        
        .evidence-card { background: #ffffff; border: 1px solid #cbd5e1; border-left: 4px solid #A08348; border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
        .ev-card-header { background: #f8fafc; padding: 12px 20px; border-bottom: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center;}
        .ev-title { font-size: 14px; font-weight: 700; color: #0f172a; }
        .btn-hapus-group { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .ev-card-body { padding: 20px; }
        .input-row { display: flex; gap: 10px; margin-bottom: 12px; align-items: center; }
        .form-control { flex: 1; padding: 12px 15px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; box-sizing: border-box; }
        .btn-hapus-input { background: transparent; color: #94a3b8; border: none; width: 30px; height: 30px; cursor: pointer; font-size: 18px; }
        .btn-hapus-input:hover { color: #ef4444; }
        .ev-card-footer { padding: 15px 20px; background: #fff; border-top: 1px dashed #e2e8f0; }
        .btn-tambah-opsi { background: #fef9e8; color: #A08348; border: none; padding: 8px 15px; border-radius: 8px; font-weight: 600; font-size: 12px; cursor: pointer; }

        .btn-tambah-group { background: #fffdf5; color: #A08348; border: 1px dashed #A08348; width: 100%; padding: 15px; border-radius: 12px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; margin-bottom: 30px;}
        .btn-simpan { background: #bda572; color: white; padding: 14px 30px; border-radius: 10px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; display: flex; align-items: center; gap: 8px; width: 100%; justify-content: center;}
    </style>
@endpush

@section('content')
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    
    <div class="detail-card">
        <div class="detail-left">
            <span class="unit-badge">[{{ $aktivitas->kode_unit }}] {{ $aktivitas->judul_unit }}</span>
            <h4 style="color: #A08348; font-size: 12px; margin-bottom: 5px;">ID Aktivitas: {{ $aktivitas->aktivitas_id }}</h4>
            <h3>{{ $aktivitas->detail_aktivitas }}</h3>
        </div>
        <div class="detail-right">
            <span class="kriteria-title">Kriteria Kompetensi:</span>
            <div class="kriteria-text">{{ $aktivitas->kriteria_kompetens }}</div>
        </div>
    </div>

    <div class="form-card">
        <div class="form-title">
            <div>
                <i class="bi bi-folder-plus" style="color: #3b82f6; margin-right: 8px;"></i> Daftar Dokumen Evidence
                <p style="font-size: 12px; color: #64748b; font-weight: 500; margin: 4px 0 0 28px;">Jabarkan opsi nama dokumen secara spesifik untuk masing-masing Evidence.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('pimpinan.manajemen_evidence.update', $aktivitas->aktivitas_id) }}">
            @csrf
            <div id="wrapper_evidence">
                @if ($existing_evidences->count() > 0)
                    @foreach ($existing_evidences as $index => $ev)
                        @php $gIndex = $index + 1; $opsi_dokumen = explode("\n", $ev->nama_evidence); @endphp
                        <div class="evidence-card" data-group="{{ $gIndex }}">
                            <div class="ev-card-header">
                                <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">{{ $gIndex }}</span></span>
                                <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>
                            </div>
                            <div class="ev-card-body" id="group_body_{{ $gIndex }}">
                                @foreach ($opsi_dokumen as $opsi)
                                    <div class="input-row">
                                        <input type="text" name="nama_evidence[{{ $gIndex }}][]" class="form-control" value="{{ trim($opsi) }}" required>
                                        <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>
                                    </div>
                                @endforeach
                            </div>
                            <div class="ev-card-footer">
                                <button type="button" class="btn-tambah-opsi" onclick="tambahInput({{ $gIndex }})"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>
                            </div>
                        </div>
                    @endforeach
                @else
                    @php $generate_count = ($aktivitas->jumlah_evidence_wa > 0) ? $aktivitas->jumlah_evidence_wa : 1; @endphp
                    @for ($i = 1; $i <= $generate_count; $i++)
                        <div class="evidence-card" data-group="{{ $i }}">
                            <div class="ev-card-header">
                                <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">{{ $i }}</span></span>
                                <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>
                            </div>
                            <div class="ev-card-body" id="group_body_{{ $i }}">
                                <div class="input-row">
                                    <input type="text" name="nama_evidence[{{ $i }}][]" class="form-control" placeholder="Contoh: Dokumen Analisis Konsep..." required>
                                    <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>
                                </div>
                            </div>
                            <div class="ev-card-footer">
                                <button type="button" class="btn-tambah-opsi" onclick="tambahInput({{ $i }})"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>
                            </div>
                        </div>
                    @endfor
                @endif
            </div>

            <button type="button" class="btn-tambah-group" onclick="tambahGroup()"><i class="bi bi-folder-plus"></i> Tambah Target Evidence Baru</button>

            <div style="background: #fffbeb; padding: 15px; border-radius: 10px; border: 1px dashed #fde68a; margin-bottom: 20px;">
                <p style="margin: 0; font-size: 12px; color: #92400e;"><b>Penting:</b> Menyimpan form ini akan memperbarui target (<code>jumlah_evidence_wa</code>) menyesuaikan dengan jumlah Kotak Evidence di atas.</p>
            </div>

            <button type="submit" class="btn-simpan"><i class="bi bi-save2-fill"></i> Simpan Daftar Dokumen</button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
let groupCounter = {{ $existing_evidences->count() > 0 ? $existing_evidences->count() : (($aktivitas->jumlah_evidence_wa > 0) ? $aktivitas->jumlah_evidence_wa : 1) }};

function renumberGroups() {
    document.querySelectorAll('.ev-number').forEach((num, index) => { num.innerText = index + 1; });
}

function hapusGroup(btn) {
    if (document.getElementById('wrapper_evidence').children.length > 1) {
        btn.closest('.evidence-card').remove(); renumberGroups();
    } else {
        Swal.fire({icon: 'warning', text: 'Minimal harus ada 1 kotak target evidence!'});
    }
}

function tambahGroup() {
    groupCounter++;
    let wrapper = document.getElementById('wrapper_evidence');
    let div = document.createElement('div');
    div.className = 'evidence-card';
    div.setAttribute('data-group', groupCounter);
    div.innerHTML = `
        <div class="ev-card-header">
            <span class="ev-title">Evidence Wajib Ke-<span class="ev-number">0</span></span>
            <button type="button" class="btn-hapus-group" onclick="hapusGroup(this)"><i class="bi bi-trash"></i> Hapus Target Ini</button>
        </div>
        <div class="ev-card-body" id="group_body_${groupCounter}">
            <div class="input-row">
                <input type="text" name="nama_evidence[${groupCounter}][]" class="form-control" placeholder="Ketik nama dokumen..." required>
                <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>
            </div>
        </div>
        <div class="ev-card-footer">
            <button type="button" class="btn-tambah-opsi" onclick="tambahInput(${groupCounter})"><i class="bi bi-plus-lg"></i> Tambah Opsi Dokumen Lain</button>
        </div>
    `;
    wrapper.appendChild(div); renumberGroups();
}

function tambahInput(groupId) {
    let container = document.getElementById('group_body_' + groupId);
    let div = document.createElement('div'); div.className = 'input-row';
    div.innerHTML = `<input type="text" name="nama_evidence[${groupId}][]" class="form-control" placeholder="Opsi alternatif dokumen..." required> <button type="button" class="btn-hapus-input" onclick="hapusInput(this)"><i class="bi bi-x-circle-fill"></i></button>`;
    container.appendChild(div);
}

function hapusInput(btn) {
    let container = btn.closest('.ev-card-body');
    if (container.querySelectorAll('.input-row').length > 1) btn.closest('.input-row').remove();
    else Swal.fire({icon: 'warning', text: 'Tinggalkan minimal 1 opsi input.'});
}
</script>
@endpush