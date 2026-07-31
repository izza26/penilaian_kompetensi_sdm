@extends('layouts.app')
@section('title', 'Detail Aktivitas')
@section('page_title', 'Detail Aktivitas')
@section('page_subtitle', 'Unggah dokumen evidence untuk aktivitas kompetensi Anda')
@section('back_url', route('pegawai.aktivitas.index'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .detail-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .card-box { background: #ffffff; padding: 25px; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06); }
        .card-title { font-size: 16px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;}
        .info-row { margin-bottom: 15px; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 6px; display: block; letter-spacing: 0.5px;}
        .info-value { font-size: 13px; color: #1e293b; font-weight: 500; line-height: 1.5; background: #f8fafc; padding: 12px 16px; border-radius: 10px; border: 1px solid #e2e8f0; }
        .info-value.highlight-blue { background: #eff6ff; border-color: #bfdbfe; }
        .info-value.highlight-orange { background: #fffbeb; color: #b45309; border-color: #fde68a; font-weight: 600;}

        .slot-box { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px; background: #fff; transition: 0.2s;}
        .slot-box:hover { border-color: #cbd5e1; box-shadow: 0 4px 15px rgba(0,0,0,0.04);}
        .slot-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 10px; margin-bottom: 15px; border-bottom: 1px dashed #e2e8f0;}
        .slot-header h5 { margin: 0; font-size: 14px; color: #0f172a; font-weight: 700;}
        .format-badge { font-size: 11px; background: #f1f5f9; color: #64748b; padding: 4px 8px; border-radius: 6px; font-weight: 600; border: 1px solid #e2e8f0;}
        
        .upload-form { display: flex; gap: 10px; align-items: center; }
        .file-input-wrapper { flex: 1; display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 8px 12px; border-radius: 8px; border: 1px dashed #cbd5e1; }
        .btn-pilih-file { background: #e2e8f0; color: #334155; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; border: none; cursor: pointer;}
        .file-name-text { font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; max-width: 150px; white-space: nowrap; }
        .btn-upload-small { background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer;}
        
        .uploaded-file { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between;}
        .file-info-group { display: flex; align-items: center; gap: 12px; }
        .file-info-group i { font-size: 24px; color: #ef4444; }
        .file-details a { font-size: 13px; font-weight: 600; color: #1e293b; text-decoration: none; display: block; margin-bottom: 2px;}
        .file-details small { font-size: 11px; color: #64748b; }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap;}
        .badge-warning { background: #fffbeb; color: #b45309; border: 1px solid #fde68a;}
        .badge-success { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0;}
        .badge-danger { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;}
        .btn-delete { color: #dc2626; background: #fef2f2; border: 1px solid #fecaca; padding: 6px 10px; border-radius: 6px; font-size: 13px; cursor: pointer; }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif
    @if(session('error'))
        <div style="background: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px; font-weight: 500;">
            <i class="bi bi-exclamation-circle-fill"></i> {{ session('error') }}
            @if ($errors->any())
                <ul style="margin: 5px 0 0 15px; padding: 0;">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            @endif
        </div>
    @endif

    <div class="detail-container">
        <!-- DETAIL AKTIVITAS (Kiri) -->
        <div class="card-box">
            <div class="card-title"><i class="bi bi-info-circle-fill"></i> Detail Aktivitas Kompetensi</div>
            <div class="info-row"><span class="info-label">Unit Kompetensi</span><div class="info-value"><b>[{{ $detail->kode_unit }}]</b><br>{{ $detail->judul_unit }}</div></div>
            <div class="info-row"><span class="info-label">Elemen Kompetensi</span><div class="info-value"><b>{{ $detail->kode_elemen_excel }}</b> - {{ $detail->elemen_kompetensi }}</div></div>
            <div class="info-row"><span class="info-label">Aktivitas yang Harus Dilakukan</span><div class="info-value highlight-blue"><b style="color: #1e3a8a;">[ {{ $detail->aktivitas_id }} ]</b><br>{{ $detail->detail_aktivitas }}</div></div>
            <div class="info-row"><span class="info-label">Kriteria Kompetensi (Hasil Diharapkan)</span><div class="info-value">{{ $detail->kriteria_kompetens }}</div></div>
            <div class="info-row"><span class="info-label">Target Evidence yang Dibutuhkan</span><div class="info-value highlight-orange"><i class="bi bi-file-earmark-check"></i> Minimal <b>{{ $detail->jumlah_evidence_wa }} Dokumen</b></div></div>
        </div>

        <!-- AREA UPLOAD (Kanan) -->
        <div class="card-box">
            <div class="card-title"><i class="bi bi-cloud-arrow-up-fill"></i> Daftar Upload Evidence</div>
            
            @if (!empty($detail->template_file))
                <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 16px; border-radius: 12px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                    <div><b style="color: #1e3a8a; font-size: 13px;"><i class="bi bi-info-circle-fill"></i> Template Tersedia</b><br><span style="color: #3b82f6; font-size: 12px;">Gunakan template ini untuk membuat dokumen evidence.</span></div>
                    <a href="{{ asset('uploads/templates/' . $detail->template_file) }}" target="_blank" style="background: #3b82f6; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 12px; font-weight: 600;"><i class="bi bi-download"></i> Unduh Format</a>
                </div>
            @endif
            
            @foreach($slots as $index => $slot)
                <div class="slot-box">
                    <div class="slot-header">
                        <h5><i class="bi bi-file-earmark-text" style="color:#3b82f6; margin-right:5px;"></i> {{ $slot['nama'] }}</h5>
                        <span class="format-badge">{{ $slot['format'] }}</span>
                    </div>
                    
                    @if ($slot['data'])
                        @php
                            $st = $slot['data']->status_validasi;
                            $b_class = ($st == 'Kompeten') ? 'success' : (($st == 'Revisi') ? 'danger' : 'warning');
                        @endphp
                        <div class="uploaded-file">
                            <div class="file-info-group">
                                <i class="bi bi-file-earmark-pdf-fill"></i>
                                <div class="file-details">
                                    <a href="{{ asset('uploads/evidence/' . $slot['data']->file_path) }}" target="_blank">{{ Str::limit($slot['data']->file_path, 25) }}</a>
                                    <small>{{ date('d M Y, H:i', strtotime($slot['data']->tanggal_upload)) }} WIB</small>
                                </div>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <span class="badge badge-{{ $b_class }}">{{ $st }}</span>
                                @if ($is_open && in_array($st, ['Menunggu Review', 'Revisi']))
                                    <form action="{{ route('pegawai.aktivitas.destroy_bukti', [$id, $slot['data']->bukti_id]) }}" method="POST" style="margin:0;">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-delete" title="Hapus File" onclick="return confirm('Yakin ingin menghapus file ini?')"><i class="bi bi-trash-fill"></i></button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @else
                        @if ($is_open)
                            <form method="POST" action="{{ route('pegawai.aktivitas.upload', $id) }}" enctype="multipart/form-data" class="upload-form">
                                @csrf <input type="hidden" name="evidence_wajib_id" value="{{ $slot['id'] }}">
                                <div class="file-input-wrapper">
                                    <input type="file" name="file_evidence" id="file_{{ $index }}" style="display:none;" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="showFileName(this, 'name_{{ $index }}')">
                                    <label for="file_{{ $index }}" class="btn-pilih-file"><i class="bi bi-folder2-open"></i> Pilih File</label>
                                    <span id="name_{{ $index }}" class="file-name-text">Belum ada file</span>
                                </div>
                                <button type="submit" class="btn-upload-small"><i class="bi bi-send-fill"></i> Unggah</button>
                            </form>
                        @else
                            <div style="background:#fef2f2; padding:10px; text-align:center; border-radius:8px; border: 1px solid #fecaca; color:#dc2626; font-size:12px; font-weight:600;"><i class="bi bi-lock-fill"></i> Terkunci (Periode Ditutup)</div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
function showFileName(input, textId) {
    var textElement = document.getElementById(textId);
    if (input.files && input.files.length > 0) textElement.innerHTML = '<b style="color:#059669;"><i class="bi bi-check-circle-fill"></i> ' + input.files[0].name + '</b>';
    else textElement.innerHTML = 'Belum ada file'; 
}
</script>
@endpush