@extends('layouts.app')
@section('title', 'Atur Evidence')
@section('page_title', 'Atur Rincian Evidence')
@section('page_subtitle', 'Jabarkan opsi dokumen yang diizinkan untuk setiap target evidence.')
@section('back_url', route('pimpinan.manajemen_evidence.index'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Kartu Detail Atas */
        .detail-card { background: #fff; border-radius: 24px; border: none; padding: 32px; margin-bottom: 24px; display: flex; gap: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.02);}
        .detail-left { flex: 1; }
        .detail-right { flex: 1; background: #f8fafc; border-radius: 16px; padding: 24px; border: 1px dashed #cbd5e1; }
        
        .unit-badge { font-size: 11px; font-weight: 700; color: #3e54a0; background: #f4f7fe; padding: 6px 14px; border-radius: 50px; border: none; display: inline-block; margin-bottom: 12px; }
        .detail-card h3 { margin: 0 0 5px 0; font-size: 16px; color: #1e293b; line-height: 1.5; font-weight: 700;}
        
        .kriteria-title { font-size: 11px; font-weight: 700; color: #94a3b8; display: block; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .kriteria-text { font-size: 14px; color: #334155; line-height: 1.6; font-weight: 500;}

        /* Kartu Form Bawah */
        .form-card { background: #fff; border-radius: 24px; border: none; padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .form-title { font-size: 18px; font-weight: 700; color: #1e293b; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px dashed #e2e8f0; display: flex; align-items: center; justify-content: space-between;}
        
        /* Box Input Evidence per Target */
        .evidence-card { background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; border-radius: 16px; margin-bottom: 20px; overflow: hidden; transition: 0.2s;}
        .evidence-card:hover { box-shadow: 0 4px 15px rgba(0,0,0,0.03); border-color: #cbd5e1;}
        .ev-card-header { background: #f8fafc; padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;}
        .ev-title { font-size: 13px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: 0.5px;}
        
        /* Tombol Hapus Grup Merah Kapsul */
        .btn-hapus-group { background: #fff1f2; color: #e11d48; border: none; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-hapus-group:hover { background: #e11d48; color: white;}
        
        .ev-card-body { padding: 20px 20px 8px 20px; } /* Padding bottom dikurangi agar form merapat */
        .input-row { display: flex; gap: 12px; margin-bottom: 12px; align-items: center; }
        
        /* Input Form Pill-Shape */
        .form-control { 
            flex: 1; padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; border-radius: 50px; 
            font-size: 13px; box-sizing: border-box; background: #f9fafb; outline: none; transition: 0.2s; font-family: inherit; color: #1e293b;
        }
        .form-control:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15);}
        
        .btn-hapus-input { background: transparent; color: #cbd5e1; border: none; width: 36px; height: 36px; cursor: pointer; font-size: 20px; transition: 0.2s; display: flex; align-items: center; justify-content: center;}
        .btn-hapus-input:hover { color: #e11d48; }
        
        /* Footer Tambah Opsi di Dalam Group */
        .ev-card-footer { padding: 12px 20px 20px 20px; background: #fff; border-top: none; } /* Border top dihapus biar menyatu */
        .btn-tambah-opsi { background: #f4f7fe; color: #3e54a0; border: none; padding: 8px 16px; border-radius: 50px; font-weight: 600; font-size: 12px; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px;}
        .btn-tambah-opsi:hover { background: #e0e7ff; }

        /* Tombol Tambah Target Besar */
        .btn-tambah-group { 
            background: #ffffff; color: #3e54a0; border: 2px dashed #3e54a0; width: 100%; 
            height: 54px; border-radius: 50px; font-weight: 700; font-size: 14px; cursor: pointer; 
            margin-top: 10px; margin-bottom: 24px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 10px;
        }
        .btn-tambah-group:hover { background: #f4f7fe; }
        
        /* Alert Kuning */
        .alert-penting { background: #fffbeb; padding: 16px 20px; border-radius: 16px; border: 1px dashed #fde68a; margin-bottom: 24px; color: #92400e; font-size: 12px; line-height: 1.5;}

        /* Tombol Simpan Final */
        .btn-simpan { 
            background: #3e54a0; color: white; height: 54px; border-radius: 50px; border: none; 
            font-weight: 700; font-size: 14px; cursor: pointer; display: flex; align-items: center; 
            gap: 10px; width: 100%; justify-content: center; transition: 0.2s;
        }
        .btn-simpan:hover { background: #2b3a70; box-shadow: 0 8px 20px rgba(62, 84, 160, 0.2); transform: translateY(-2px);}
    </style>
@endpush

@section('content')
<div class="page-card" style="background:transparent; border:none; box-shadow:none; padding:0;">
    
    <!-- Bagian Detail Atas -->
    <div class="detail-card">
        <div class="detail-left">
            <span class="unit-badge">[{{ $aktivitas->kode_unit }}] {{ $aktivitas->judul_unit }}</span>
            <h4 style="color: #3e54a0; font-size: 12px; margin-bottom: 5px; font-weight: 700;">ID Aktivitas: {{ $aktivitas->aktivitas_id }}</h4>
            <h3>{{ $aktivitas->detail_aktivitas }}</h3>
        </div>
        <div class="detail-right">
            <span class="kriteria-title">Kriteria Kompetensi:</span>
            <div class="kriteria-text">{{ $aktivitas->kriteria_kompetens }}</div>
        </div>
    </div>

    <!-- Bagian Form Bawah (Dibuat menyatu) -->
    <form method="POST" action="{{ route('pimpinan.manajemen_evidence.update', $aktivitas->aktivitas_id) }}" class="form-card">
        @csrf
        
        <div class="form-title">
            <div>
                <i class="bi bi-folder-plus" style="color: #3e54a0; margin-right: 8px;"></i> Daftar Dokumen Evidence
                <p style="font-size: 12px; color: #64748b; font-weight: 500; margin: 4px 0 0 28px;">Jabarkan opsi nama dokumen secara spesifik untuk masing-masing Evidence.</p>
            </div>
        </div>

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

        <div class="alert-penting">
            <p style="margin: 0;"><b>Penting:</b> Menyimpan form ini akan memperbarui target (<code>jumlah_evidence_wa</code>) menyesuaikan dengan jumlah Kotak Evidence di atas.</p>
        </div>

        <button type="submit" class="btn-simpan"><i class="bi bi-save2-fill"></i> Simpan Daftar Dokumen</button>
    </form>
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