@extends('layouts.app')
@section('title', 'Kustomisasi Aktivitas')
@section('page_title', 'Manajemen Penilaian')
@section('page_subtitle', 'Kustomisasi aktivitas kompetensi untuk jabatan ' . $display_jabatan)
@section('back_url', route('pimpinan.manajemen_penilaian.periode'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .header-jabatan { background: linear-gradient(135deg, #1B2D46 0%, #425B6F 100%); color: white; padding: 20px 25px; border-radius: 16px; margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 15px rgba(27, 45, 70, 0.15);}
        .header-jabatan h2 { margin: 0 0 5px 0; font-size: 22px; font-weight: 700; }
        .header-jabatan p { margin: 0; font-size: 13px; opacity: 0.9; }
        .uk-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; overflow: hidden; }
        .uk-header { background: #f8fafc; padding: 18px 25px; border-bottom: 1px solid #e2e8f0; }
        .uk-header h3 { margin: 0 0 5px 0; font-size: 15px; color: #0f172a; }
        .uk-header span { font-size: 14px; font-weight: 700; color: #A08348; padding: 4px 10px; border-radius: 6px; }
        .elemen-group { padding: 0 25px; margin-bottom: 10px; }
        .elemen-title { font-size: 13px; font-weight: 700; color: #475569; margin: 20px 0 10px 0; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;}
        .aktivitas-list { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
        .aktivitas-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 15px; border-radius: 8px; border: 1px solid #e2e8f0; transition: 0.2s; cursor: pointer; }
        .aktivitas-item:hover { background: #f8fafc; }
        .aktivitas-item input[type="checkbox"] { margin-top: 3px; transform: scale(1.2); }
        .aktivitas-item.checked { background: #fffcf7; border-color: #bda572; }
        .akt-id { font-size: 13px; color: #A08348; font-weight: 700; display: block; margin-bottom: 2px;}
        .akt-desc { font-size: 13px; color: #334155; }
        .floating-save-bar { position: sticky; bottom: 20px; background: rgba(255,255,255,0.9); backdrop-filter: blur(10px); padding: 15px 25px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; z-index: 100;}
        .btn-simpan { background: #A08348; color: white; padding: 12px 25px; border-radius: 8px; border: none; font-weight: 600; cursor: pointer;}
    </style>
@endpush

@section('content')
    <div class="header-jabatan">
        <div>
            <h2>Kustomisasi Aktivitas: {{ $display_jabatan }}</h2>
            <p>Pimpinan dapat menonaktifkan aktivitas yang dirasa tidak perlu dievaluasi pada periode ini.</p>
        </div>
        <i class="bi bi-ui-checks" style="font-size: 40px; opacity: 0.2;"></i>
    </div>

    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil Disimpan!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif

    @if($data->isEmpty())
        <div style="text-align: center; padding: 50px 20px; background: #fff; border-radius: 16px; border: 1px solid #e2e8f0;">
            <i class="bi bi-inbox" style="font-size: 40px; color: #94a3b8; display: block;"></i>
            <h3 style="color: #0f172a; margin-top: 15px;">Belum Ada Unit Kompetensi yang Aktif</h3>
        </div>
    @else
        <form method="POST" action="{{ route('pimpinan.manajemen_penilaian.aktivitas_store') }}">
            @csrf <input type="hidden" name="jabatan" value="{{ $jabatan }}">
            
            @foreach ($data as $uk)
            <div class="uk-card">
                <div class="uk-header">
                    <span><i class="bi bi-tag-fill"></i> {{ $uk->kode_unit }}</span>
                    <h3 style="margin-top: 10px;">{{ $uk->judul_unit }}</h3>
                </div>
                
                @foreach ($uk->elemen as $ek)
                <div class="elemen-group">
                    <div class="elemen-title">Elemen {{ $ek->kode_elemen_excel }}: {{ $ek->elemen_kompetensi }}</div>
                    <div class="aktivitas-list">
                        @foreach ($ek->aktivitas as $akt)
                            @php $isChecked = ($akt->aktif == 'Y'); @endphp
                            <label class="aktivitas-item {{ $isChecked ? 'checked' : '' }}">
                                <input type="checkbox" name="aktivitas_aktif[]" value="{{ $akt->aktivitas_id }}" {{ $isChecked ? 'checked' : '' }} onclick="toggleActive(this)">
                                <div><span class="akt-id">ID: {{ $akt->aktivitas_id }}</span><span class="akt-desc">{{ $akt->detail_aktivitas }}</span></div>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
            @endforeach

            <div class="floating-save-bar">
                <div style="font-size: 12px; color: #64748b;">Pastikan untuk menyimpan perubahan setelah mencentang aktivitas.</div>
                <button type="submit" name="simpan_aktivitas" class="btn-simpan"><i class="bi bi-save"></i> Simpan Kustomisasi Aktivitas</button>
            </div>
        </form>
    @endif
@endsection

@push('scripts')
<script>
function toggleActive(checkbox) {
    if (checkbox.checked) checkbox.closest('.aktivitas-item').classList.add('checked');
    else checkbox.closest('.aktivitas-item').classList.remove('checked');
}
</script>
@endpush