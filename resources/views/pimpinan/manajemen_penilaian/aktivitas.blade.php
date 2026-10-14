@extends('layouts.app')
@section('title', 'Kustomisasi Aktivitas')
@section('page_title', 'Manajemen Penilaian')
@section('page_subtitle', 'Kustomisasi aktivitas kompetensi untuk jabatan ' . $display_jabatan)
@section('back_url', route('pimpinan.manajemen_penilaian.periode'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .header-jabatan { background: #3e54a0; color: white; padding: 32px; border-radius: 24px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 10px 30px rgba(62, 84, 160, 0.2); position: relative; overflow: hidden;}
        .header-jabatan::before { content: ''; position: absolute; right: -50px; top: -50px; width: 200px; height: 200px; background: rgba(255,255,255,0.05); border-radius: 50%; }
        .header-jabatan h2 { margin: 0 0 8px 0; font-size: 24px; font-weight: 700; position: relative; z-index: 2;}
        .header-jabatan p { margin: 0; font-size: 13px; opacity: 0.9; position: relative; z-index: 2;}
        
        .uk-card { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; margin-bottom: 24px; overflow: hidden; padding-bottom: 10px;}
        .uk-header { background: #f8fafc; padding: 24px 32px; border-bottom: 1px dashed #cbd5e1; display: flex; justify-content: space-between; align-items: center;}
        .uk-header h3 { margin: 8px 0 0 0; font-size: 16px; color: #1e293b; font-weight: 700; line-height: 1.4;}
        .uk-header span { font-size: 11px; font-weight: 800; color: #3e54a0; letter-spacing: 0.5px;}
        
        /* TOMBOL CEKLIS MASSAL DINAMIS */
        .btn-toggle-all { background: #ffffff; color: #3e54a0; border: 1px solid #bfdbfe; padding: 8px 18px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s; white-space: nowrap; height: fit-content; display: inline-flex; align-items: center; gap: 6px;}
        .btn-toggle-all:hover { background: #eff6ff; color: #1d4ed8;}
        .btn-toggle-all.btn-deselect { color: #e11d48; border-color: #fca5a5; background: #fff1f2; }
        .btn-toggle-all.btn-deselect:hover { background: #ffe4e6; color: #be123c;}
        
        .elemen-group { padding: 0 32px; margin-top: 24px; }
        .elemen-title { font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;}
        
        /* List Checkbox Super Clean */
        .aktivitas-list { display: flex; flex-direction: column; gap: 0; margin-bottom: 16px; border-left: 2px solid #f1f5f9; margin-left: 10px; padding-left: 20px;}
        .aktivitas-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 20px; transition: 0.2s; cursor: pointer; background: transparent; border-radius: 12px; margin-bottom: 8px;}
        .aktivitas-item:hover { background: #f8fafc; }
        .aktivitas-item input[type="checkbox"] { margin-top: 3px; transform: scale(1.3); accent-color: #3e54a0; cursor: pointer;}
        
        /* State Saat Dicentang */
        .aktivitas-item.checked { background: #f4f7fe; }
        .akt-id { font-size: 11px; color: #94a3b8; font-weight: 700; display: block; margin-bottom: 4px; letter-spacing: 0.5px;}
        .akt-desc { font-size: 13.5px; color: #1e293b; font-weight: 500; line-height: 1.6;}
        .aktivitas-item.checked .akt-desc { font-weight: 600; color: #3e54a0;}
        
        /* Floating Bar Bawah */
        .floating-save-bar { position: sticky; bottom: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 20px 32px; border-radius: 24px; border: 1px solid #cbd5e1; display: flex; justify-content: space-between; align-items: center; z-index: 100; box-shadow: 0 10px 30px rgba(0,0,0,0.05);}
        .btn-simpan { background: #3e54a0; color: white; padding: 0 24px; height: 48px; border-radius: 50px; border: none; font-weight: 600; cursor: pointer; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s;}
        .btn-simpan:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(62, 84, 160, 0.2);}
        
        @media(max-width: 768px) { .floating-save-bar { flex-direction: column; gap: 15px; text-align: center; } .btn-simpan { width: 100%; justify-content: center;} .uk-header { flex-direction: column; align-items: flex-start; gap: 15px;} }
    </style>
@endpush

@section('content')
    <div class="header-jabatan">
        <div>
            <h2>Kustomisasi Aktivitas: {{ $display_jabatan }}</h2>
            <p>Nonaktifkan unit atau aktivitas yang tidak relevan dengan menggunakan tombol "Pilih / Batal Semua".</p>
        </div>
        <i class="bi bi-ui-checks" style="font-size: 50px; opacity: 0.15; position: relative; z-index: 2;"></i>
    </div>

    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif

    @if($data->isEmpty())
        <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 24px; border: 1px solid #e2e8f0;">
            <i class="bi bi-folder-x" style="font-size: 48px; color: #cbd5e1; display: block;"></i>
            <h3 style="color: #1e293b; margin-top: 15px;">Belum Ada Unit Kompetensi yang Aktif</h3>
        </div>
    @else
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.aktivitas_store') }}">
            @csrf <input type="hidden" name="jabatan" value="{{ $jabatan }}">
            
            @foreach ($data as $index => $uk)
            <!-- Tambahan class uk-container dan data-index untuk mempermudah pencarian JS -->
            <div class="uk-card uk-container" id="uk_container_{{ $index }}" data-index="{{ $index }}">
                <div class="uk-header">
                    <div>
                        <span><i class="bi bi-tag-fill" style="margin-right: 4px;"></i> {{ $uk->kode_unit }}</span>
                        <h3>{{ $uk->judul_unit }}</h3>
                    </div>
                    <!-- TOMBOL DINAMIS -->
                    <button type="button" class="btn-toggle-all" id="btn_toggle_{{ $index }}" onclick="toggleAllUK({{ $index }})">
                        <i class="bi bi-check2-all"></i> Pilih Semua
                    </button>
                </div>
                
                @foreach ($uk->elemen as $ek)
                <div class="elemen-group">
                    <div class="elemen-title">Aktivitas {{ preg_replace('/[^0-9]/', '', $ek->kode_elemen_excel) }}: {{ $ek->elemen_kompetensi }}</div>
                    <div class="aktivitas-list">
                        @foreach ($ek->aktivitas as $akt)
                            @php $isChecked = ($akt->aktif == 'Y'); @endphp
                            <label class="aktivitas-item {{ $isChecked ? 'checked' : '' }}">
                                <!-- Paramater $index dilempar ke fungsi onclick agar tombol dinamis ikut terupdate -->
                                <input type="checkbox" name="aktivitas_aktif[]" class="chk-akt" value="{{ $akt->aktivitas_id }}" {{ $isChecked ? 'checked' : '' }} onclick="toggleActive(this, {{ $index }})">
                                <div><span class="akt-id">ID: {{ $akt->aktivitas_id }}</span><span class="akt-desc">{{ $akt->detail_aktivitas }}</span></div>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach

            <div class="floating-save-bar">
                <div style="font-size: 12px; color: #64748b; font-weight: 500;"><i class="bi bi-info-circle-fill" style="color:#3e54a0;"></i> Pastikan untuk menyimpan perubahan setelah mengedit aktivitas.</div>
                <button type="submit" name="simpan_aktivitas" class="btn-simpan"><i class="bi bi-save2-fill"></i> Simpan Kustomisasi</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
<script>
// Fungsi utama untuk mengupdate teks dan warna tombol (Pilih Semua / Batal Semua)
function updateToggleButton(index) {
    let container = document.getElementById('uk_container_' + index);
    let btn = document.getElementById('btn_toggle_' + index);
    if (!container || !btn) return;

    let checkboxes = container.querySelectorAll('.chk-akt');
    // Cek apakah masih ada kotak yang BELUM dicentang
    let hasUnchecked = Array.from(checkboxes).some(cb => !cb.checked);

    if (hasUnchecked) {
        // Jika ada yang bolong -> Tombol mode "Pilih Semua" (Biru)
        btn.innerHTML = '<i class="bi bi-check2-all"></i> Pilih Semua';
        btn.classList.remove('btn-deselect');
    } else {
        // Jika sudah tercentang penuh -> Tombol mode "Batal Semua" (Merah)
        btn.innerHTML = '<i class="bi bi-x-circle-fill"></i> Batal Semua';
        btn.classList.add('btn-deselect');
    }
}

// Fungsi saat 1 kotak dicentang manual
function toggleActive(checkbox, index) {
    if (checkbox.checked) {
        checkbox.closest('.aktivitas-item').classList.add('checked');
    } else {
        checkbox.closest('.aktivitas-item').classList.remove('checked');
    }
    // Panggil fungsi update tombol untuk mengecek apakah sudah penuh semua
    updateToggleButton(index);
}

// Fungsi saat tombol "Pilih / Batal Semua" ditekan
function toggleAllUK(index) {
    let container = document.getElementById('uk_container_' + index);
    let checkboxes = container.querySelectorAll('.chk-akt');
    
    let hasUnchecked = Array.from(checkboxes).some(cb => !cb.checked);
    
    // Jika ada yang belum dicentang -> Centang semuanya. Jika sudah dicentang semua -> Kosongkan semuanya.
    checkboxes.forEach(cb => {
        cb.checked = hasUnchecked;
        if (hasUnchecked) {
            cb.closest('.aktivitas-item').classList.add('checked');
        } else {
            cb.closest('.aktivitas-item').classList.remove('checked');
        }
    });
    
    // Refresh teks tombol
    updateToggleButton(index);
}

// Saat halaman pertama kali dimuat, periksa semua status tombol
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.uk-container').forEach(container => {
        let index = container.getAttribute('data-index');
        updateToggleButton(index);
    });
});
</script>
@endpush