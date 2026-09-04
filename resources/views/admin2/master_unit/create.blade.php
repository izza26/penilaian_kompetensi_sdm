@extends('layouts.app')
@section('title', 'Tambah Unit SKKNI')
@section('page_title', 'Tambah Unit Kompetensi')
@section('page_subtitle', 'Buat hierarki unit, elemen, dan aktivitas dalam satu kali proses.')
@section('back_url', route('admin.master_unit.index'))

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/css_pimpinan/master_unit.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* KARTU FORM UTAMA */
        .form-section { 
            background: #fff; border-radius: 24px; border: none; padding: 32px; 
            margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); 
        }
        
        .section-title { 
            font-size: 18px; color: #1e293b; border-bottom: 1px dashed #e2e8f0; 
            padding-bottom: 15px; margin-bottom: 25px; font-weight: 700; 
            display: flex; align-items: center; gap: 8px; 
        }
        .section-title i { color: #3e54a0 !important; font-size: 20px;} /* Icon ganti ke Royal Blue */

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        
        /* INPUT DIBUAT MELENGKUNG (PILL-SHAPE) */
        .form-control { 
            width: 100%; padding: 0 20px; height: 48px; border: 1px solid #cbd5e1; 
            border-radius: 50px; font-size: 13px; box-sizing: border-box; outline: none;
            background: #f9fafb; transition: 0.2s; color: #1e293b; font-family: inherit;
        }
        .form-control:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62, 84, 160, 0.15); }
        textarea.form-control { border-radius: 20px; padding: 16px 20px; height: auto; min-height: 80px;} /* Textarea harus sedikit kotak */

        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

        /* ELEMEN BOX DIBUAT LEBIH ELEGAN */
        .elemen-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 20px; padding: 25px; margin-bottom: 20px; position: relative;}
        
        .btn-hapus-elemen { 
            position: absolute; top: 20px; right: 20px; background: #fff1f2; color: #f43f5e; 
            border: none; padding: 8px 16px; border-radius: 50px; font-size: 12px; font-weight: 600; 
            cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 6px;
        }
        .btn-hapus-elemen:hover { background: #f43f5e; color: white; }

        /* AKTIVITAS BOX & LIST ROYAL BLUE */
        .aktivitas-box { 
            background: #ffffff; border: 1px solid #e2e8f0; border-left: 4px solid #3e54a0; 
            border-radius: 16px; padding: 24px; margin-top: 15px; position: relative;
        }
        
        .btn-hapus-aktivitas { 
            position: absolute; top: 15px; right: 15px; color: #f43f5e; background: #fff1f2; 
            border: none; cursor: pointer; font-size: 14px; width: 30px; height: 30px;
            border-radius: 50%; display: flex; justify-content: center; align-items: center; transition: 0.2s;
        }
        .btn-hapus-aktivitas:hover { background: #f43f5e; color: white; }

        /* TOMBOL TAMBAH (Royal Blue Outline) */
        .btn-tambah-elemen { 
            background: #f4f7fe; color: #3e54a0; border: 2px dashed #3e54a0; 
            width: 100%; padding: 15px; border-radius: 50px; font-weight: 600; font-size: 13px; 
            cursor: pointer; margin-bottom: 30px; transition: 0.2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-tambah-elemen:hover { background: #3e54a0; color: white; }

        .btn-tambah-aktivitas { 
            background: #ffffff; color: #3e54a0; border: 1px dashed #cbd5e1; 
            padding: 10px 20px; border-radius: 50px; font-weight: 600; font-size: 12px; 
            cursor: pointer; margin-top: 10px; transition: 0.2s;
            display: flex; align-items: center; gap: 6px;
        }
        .btn-tambah-aktivitas:hover { background: #f4f7fe; border-color: #3e54a0; }

        /* FLOATING SAVE BUTTON (Royal Blue Solid) */
        .floating-save { 
            position: fixed; bottom: 35px; right: 40px; 
            background: #3e54a0; color: white; border: none; padding: 16px 32px; 
            border-radius: 50px; font-size: 14px; font-weight: 700; 
            box-shadow: 0 10px 25px rgba(62, 84, 160, 0.35); 
            cursor: pointer; display: flex; align-items: center; gap: 10px; z-index: 1000;
            transition: 0.2s;
        }
        .floating-save:hover { background: #2b3a70; transform: translateY(-3px); box-shadow: 0 15px 30px rgba(62, 84, 160, 0.4); }
    </style>
@endpush

@section('content')
    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 15px 20px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 25px; font-weight: 500;">
            <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
        </div>
    @endif

    <form method="POST" action="{{ route('pimpinan.master_unit.store') }}" id="formMaster">
        @csrf
        <!-- TAHAP 1: INFO UNIT KOMPETENSI -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-1-circle-fill" style="color: #A08348;"></i> Identitas Unit Kompetensi</div>
            <div class="grid-2">
                <div class="form-group">
                    <label>Kode Unit (Wajib Unik)</label>
                    <input type="text" name="kode_unit" class="form-control" value="{{ old('kode_unit') }}" placeholder="Contoh: R.91MUS02.001.3" required>
                </div>
                <div class="form-group">
                    <label>Jenis Kompetensi</label>
                    <input list="jenis_list" name="jenis_kompetensi" class="form-control" value="{{ old('jenis_kompetensi') }}" placeholder="Pilih dari daftar / Ketik baru..." required autocomplete="off">
                    <datalist id="jenis_list">
                        @foreach($all_jenis as $j) <option value="{{ $j }}"> @endforeach
                    </datalist>
                </div>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label>Judul Unit Kompetensi</label>
                <textarea name="judul_unit" class="form-control" rows="2" required>{{ old('judul_unit') }}</textarea>
            </div>
        </div>

        <!-- TAHAP 2: DAFTAR ELEMEN & AKTIVITAS -->
        <div class="form-section">
            <div class="section-title"><i class="bi bi-2-circle-fill" style="color: #A08348;"></i> Daftar Elemen & Aktivitas</div>
            
            <div id="elemen_wrapper">
                <div class="elemen-box" data-index="0">
                    <button type="button" class="btn-hapus-elemen" onclick="hapusElemen(this)"><i class="bi bi-trash"></i> Hapus Elemen</button>
                    <h4 style="margin: 0 0 15px 0; color: #0f172a; font-size: 15px;">Elemen Kompetensi Baru</h4>
                    
                    <div class="form-group"><label>Kode/Nomor Elemen</label><input type="text" name="elemen_kode[0]" class="form-control" required></div>
                    <div class="form-group"><label>Judul Elemen</label><textarea name="elemen_judul[0]" class="form-control" rows="1" required></textarea></div>
                    <div class="grid-2">
                        <div class="form-group"><label>Input</label><textarea name="elemen_input[0]" class="form-control" rows="1"></textarea></div>
                        <div class="form-group"><label>Output</label><textarea name="elemen_output[0]" class="form-control" rows="1"></textarea></div>
                    </div>
                    <div class="form-group"><label>Outcome</label><textarea name="elemen_outcome[0]" class="form-control" rows="1"></textarea></div>

                    <div class="aktivitas_wrapper_0">
                        <h5 style="margin: 20px 0 10px 0; color: #475569; font-size: 13px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Rincian Aktivitas Kompetensi:</h5>
                        <div class="aktivitas-box">
                            <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)"><i class="bi bi-x-circle-fill"></i></button>
                            <div class="grid-2">
                                <div class="form-group"><label>ID Aktivitas</label><input type="text" name="aktivitas_id[0][]" class="form-control" required></div>
                                <div class="form-group"><label>Target Evidence (Angka)</label><input type="number" name="aktivitas_evidence[0][]" class="form-control" value="1" min="0" required></div>
                            </div>
                            <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[0][]" class="form-control" rows="2" required></textarea></div>
                            <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[0][]" class="form-control" rows="1" required></textarea></div>
                        </div>
                    </div>
                    <button type="button" class="btn-tambah-aktivitas" onclick="tambahAktivitas(0)"><i class="bi bi-plus"></i> Tambah Aktivitas ke Elemen Ini</button>
                </div>
            </div>

            <button type="button" class="btn-tambah-elemen" onclick="tambahElemen()"><i class="bi bi-folder-plus"></i> Tambah Elemen Kompetensi Baru</button>
        </div>

        <button type="submit" class="floating-save"><i class="bi bi-save2-fill"></i> Simpan Semua Data</button>
    </form>
@endsection

@push('scripts')
<script>
// Javascript asli dari file lamamu tidak diubah sama sekali
let elemenIndex = 1; 
function tambahElemen() {
    let wrapper = document.getElementById('elemen_wrapper');
    let html = `
        <div class="elemen-box" data-index="${elemenIndex}">
            <button type="button" class="btn-hapus-elemen" onclick="hapusElemen(this)"><i class="bi bi-trash"></i> Hapus Elemen</button>
            <h4 style="margin: 0 0 15px 0; color: #0f172a; font-size: 15px;">Elemen Kompetensi Baru</h4>
            <div class="form-group"><label>Kode/Nomor Elemen</label><input type="text" name="elemen_kode[${elemenIndex}]" class="form-control" required></div>
            <div class="form-group"><label>Judul Elemen</label><textarea name="elemen_judul[${elemenIndex}]" class="form-control" rows="1" required></textarea></div>
            <div class="grid-2">
                <div class="form-group"><label>Input</label><textarea name="elemen_input[${elemenIndex}]" class="form-control" rows="1"></textarea></div>
                <div class="form-group"><label>Output</label><textarea name="elemen_output[${elemenIndex}]" class="form-control" rows="1"></textarea></div>
            </div>
            <div class="form-group"><label>Outcome</label><textarea name="elemen_outcome[${elemenIndex}]" class="form-control" rows="1"></textarea></div>
            <div class="aktivitas_wrapper_${elemenIndex}">
                <h5 style="margin: 20px 0 10px 0; color: #475569; font-size: 13px; border-bottom: 1px solid #cbd5e1; padding-bottom: 5px;">Rincian Aktivitas Kompetensi:</h5>
                <div class="aktivitas-box">
                    <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)"><i class="bi bi-x-circle-fill"></i></button>
                    <div class="grid-2">
                        <div class="form-group"><label>ID Aktivitas</label><input type="text" name="aktivitas_id[${elemenIndex}][]" class="form-control" required></div>
                        <div class="form-group"><label>Target Evidence</label><input type="number" name="aktivitas_evidence[${elemenIndex}][]" class="form-control" value="1" min="0" required></div>
                    </div>
                    <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[${elemenIndex}][]" class="form-control" rows="2" required></textarea></div>
                    <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[${elemenIndex}][]" class="form-control" rows="1" required></textarea></div>
                </div>
            </div>
            <button type="button" class="btn-tambah-aktivitas" onclick="tambahAktivitas(${elemenIndex})"><i class="bi bi-plus"></i> Tambah Aktivitas</button>
        </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
    elemenIndex++;
}

function hapusElemen(btn) {
    if (document.getElementById('elemen_wrapper').children.length > 1) btn.closest('.elemen-box').remove();
    else Swal.fire({icon: 'warning', title: 'Perhatian', text: 'Minimal 1 Elemen Kompetensi.'});
}

function tambahAktivitas(elIndex) {
    let wrapper = document.querySelector('.aktivitas_wrapper_' + elIndex);
    let html = `
        <div class="aktivitas-box">
            <button type="button" class="btn-hapus-aktivitas" onclick="hapusAktivitas(this)"><i class="bi bi-x-circle-fill"></i></button>
            <div class="grid-2">
                <div class="form-group"><label>ID Aktivitas</label><input type="text" name="aktivitas_id[${elIndex}][]" class="form-control" required></div>
                <div class="form-group"><label>Target Evidence</label><input type="number" name="aktivitas_evidence[${elIndex}][]" class="form-control" value="1" min="0" required></div>
            </div>
            <div class="form-group"><label>Detail Aktivitas</label><textarea name="aktivitas_detail[${elIndex}][]" class="form-control" rows="2" required></textarea></div>
            <div class="form-group" style="margin-bottom:0;"><label>Kriteria Hasil</label><textarea name="aktivitas_kriteria[${elIndex}][]" class="form-control" rows="1" required></textarea></div>
        </div>
    `;
    wrapper.insertAdjacentHTML('beforeend', html);
}

function hapusAktivitas(btn) {
    let box = btn.closest('.aktivitas-box');
    if (box.parentElement.querySelectorAll('.aktivitas-box').length > 1) box.remove();
    else Swal.fire({icon: 'warning', title: 'Perhatian', text: 'Minimal 1 Aktivitas per Elemen.'});
}
</script>
@endpush