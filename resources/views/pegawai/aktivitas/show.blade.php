@extends('layouts.app')
@section('title', 'Detail Aktivitas')
@section('page_title', 'Detail Aktivitas')
@section('page_subtitle', 'Unggah dokumen evidence untuk aktivitas kompetensi Anda')
@section('back_url', route('pegawai.aktivitas.index'))

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .detail-container { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card-box { background: #ffffff; padding: 32px; border-radius: 24px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .card-title { font-size: 18px; font-weight: 700; color: #1e293b; border-bottom: 1px dashed #e2e8f0; padding-bottom: 16px; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;}
        .card-title i { color: #3e54a0; }
        
        .info-row { margin-bottom: 20px; }
        .info-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: block; letter-spacing: 0.5px;}
        .info-value { font-size: 13px; color: #334155; font-weight: 500; line-height: 1.6; background: #f8fafc; padding: 16px 20px; border-radius: 16px; border: 1px dashed #cbd5e1; }
        .info-value.highlight-blue { background: #f4f7fe; border-color: #bfdbfe; color: #1e293b; }
        .info-value.highlight-orange { background: #fffbeb; color: #b45309; border-color: #fde68a; font-weight: 700;}

        .slot-box { border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px; background: #fff; transition: 0.2s;}
        .slot-box:hover { border-color: #cbd5e1; box-shadow: 0 10px 25px rgba(0,0,0,0.03);}
        .slot-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; margin-bottom: 16px; border-bottom: 1px dashed #e2e8f0;}
        .slot-header h5 { margin: 0; font-size: 14px; color: #1e293b; font-weight: 700;}
        .format-badge { font-size: 10px; background: #f1f5f9; color: #64748b; padding: 6px 12px; border-radius: 50px; font-weight: 700; border: none; letter-spacing: 0.5px;}
        
        /* Upload Form Baru */
        .upload-form { display: flex; flex-direction: column; gap: 12px; }
        .upload-controls { display: flex; gap: 12px; align-items: center; width: 100%; }
        .file-input-wrapper { flex: 1; display: flex; align-items: center; gap: 12px; background: #f8fafc; padding: 8px 16px; border-radius: 50px; border: 1px dashed #cbd5e1; transition: 0.2s;}
        .file-input-wrapper:hover { border-color: #3e54a0; background: #fff; }
        .btn-pilih-file { background: #e2e8f0; color: #334155; padding: 8px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; border: none; cursor: pointer; transition: 0.2s;}
        .btn-pilih-file:hover { background: #cbd5e1; }
        .file-name-text { font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; max-width: 150px; white-space: nowrap; font-weight: 500;}
        .btn-upload-small { background: #3e54a0; color: white; border: none; padding: 0 20px; height: 38px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s;}
        .btn-upload-small:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
        
        .uploaded-file { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px 20px; display: flex; flex-direction: column; gap: 15px;}
        .uploaded-header { display: flex; align-items: center; justify-content: space-between; width: 100%;}
        .file-info-group { display: flex; align-items: center; gap: 12px; }
        .file-info-group i { font-size: 28px; color: #ef4444; }
        .file-details a { font-size: 13px; font-weight: 700; color: #1e293b; text-decoration: none; display: block; margin-bottom: 2px;}
        .file-details a:hover { color: #3e54a0; text-decoration: underline;}
        .file-details small { font-size: 11px; color: #64748b; }
        
        .badge { padding: 6px 14px; border-radius: 50px; font-size: 10px; font-weight: 700; white-space: nowrap; border: none; text-transform: uppercase; letter-spacing: 0.5px;}
        .badge-warning { background: #fffbeb; color: #d97706; }
        .badge-success { background: #ecfdf5; color: #059669; }
        .badge-danger { background: #fff1f2; color: #e11d48; }
        
        .btn-delete { color: #e11d48; background: #fff1f2; border: none; width: 32px; height: 32px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 14px; cursor: pointer; transition: 0.2s;}
        .btn-delete:hover { background: #e11d48; color: white; transform: translateY(-2px);}
        .btn-toggle-preview { background: #f4f7fe; color: #3e54a0; border: none; padding: 6px 16px; border-radius: 50px; font-size: 11px; font-weight: 700; cursor: pointer; transition: 0.2s;}
        .btn-toggle-preview:hover { background: #e0e7ff; }

        /* Preview Container */
        .preview-box { width: 100%; margin-top: 10px; border-radius: 12px; overflow: hidden; background: #f8fafc; border: 1px dashed #cbd5e1; display: none; text-align: center; padding: 10px;}
        .preview-box img { max-width: 100%; max-height: 350px; border-radius: 8px; object-fit: contain; }
        .preview-box iframe { width: 100%; height: 350px; border: none; border-radius: 8px; }

        @media(max-width: 992px) { .detail-container { grid-template-columns: 1fr; } .upload-controls { flex-direction: column; } .btn-upload-small { width: 100%; } }
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
        <div style="background: #fff1f2; color: #e11d48; padding: 16px 20px; border-radius: 16px; margin-bottom: 24px; border: none; font-size: 13px; font-weight: 600; display: flex; gap: 10px; align-items: flex-start;">
            <i class="bi bi-exclamation-triangle-fill" style="font-size: 18px;"></i>
            <div>
                {{ session('error') }}
                @if ($errors->any())
                    <ul style="margin: 8px 0 0 15px; padding: 0; font-weight: 500;">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                @endif
            </div>
        </div>
    @endif

    <div class="detail-container">
        <!-- DETAIL AKTIVITAS (Kiri) -->
        <div class="card-box">
            <div class="card-title"><i class="bi bi-info-circle-fill"></i> Detail Aktivitas Kompetensi</div>
            <div class="info-row"><span class="info-label">Unit Kompetensi</span><div class="info-value"><b>[{{ $detail->kode_unit }}]</b><br>{{ $detail->judul_unit }}</div></div>
            <div class="info-row"><span class="info-label">Elemen Kompetensi</span><div class="info-value"><b>{{ $detail->kode_elemen_excel }}</b> - {{ $detail->elemen_kompetensi }}</div></div>
            <div class="info-row"><span class="info-label">Aktivitas yang Harus Dilakukan</span><div class="info-value highlight-blue"><b style="color: #3e54a0;">[ {{ $detail->aktivitas_id }} ]</b><br>{{ $detail->detail_aktivitas }}</div></div>
            <div class="info-row"><span class="info-label">Kriteria Kompetensi (Hasil Diharapkan)</span><div class="info-value">{{ $detail->kriteria_kompetens }}</div></div>
            <div class="info-row"><span class="info-label">Target Evidence yang Dibutuhkan</span><div class="info-value highlight-orange"><i class="bi bi-file-earmark-check-fill" style="margin-right: 4px;"></i> Minimal <b>{{ $detail->jumlah_evidence_wa }} Dokumen</b></div></div>
        </div>

        <!-- AREA UPLOAD (Kanan) -->
        <div class="card-box">
            <div class="card-title"><i class="bi bi-cloud-arrow-up-fill"></i> Daftar Upload Evidence</div>
            
            @if (!empty($detail->template_file))
                <div style="background: #f4f7fe; border: 1px dashed #bfdbfe; padding: 20px; border-radius: 16px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
                    <div><b style="color: #3e54a0; font-size: 13px; font-weight: 800;"><i class="bi bi-info-circle-fill"></i> Template Tersedia</b><br><span style="color: #64748b; font-size: 12px; font-weight: 500;">Gunakan format ini untuk dokumen evidence.</span></div>
                    <a href="{{ asset('uploads/templates/' . $detail->template_file) }}" target="_blank" style="background: #3e54a0; color: white; padding: 10px 20px; border-radius: 50px; text-decoration: none; font-size: 12px; font-weight: 700; transition: 0.2s; display: flex; gap: 6px; align-items: center;"><i class="bi bi-download"></i> Unduh Format</a>
                </div>
            @endif
            
            @foreach($slots as $index => $slot)
                <div class="slot-box">
                    <div class="slot-header">
                        <h5><i class="bi bi-file-earmark-text-fill" style="color:#3e54a0; margin-right:6px;"></i> {{ $slot['nama'] }}</h5>
                        <span class="format-badge">{{ $slot['format'] }}</span>
                    </div>
                    
                    @if ($slot['data'])
                        <!-- JIKA SUDAH UPLOAD -->
                        @php
                            $st = $slot['data']->status_validasi;
                            $b_class = ($st == 'Kompeten') ? 'success' : (($st == 'Revisi') ? 'danger' : 'warning');
                            $ext = strtolower(pathinfo($slot['data']->file_path, PATHINFO_EXTENSION));
                            $is_image = in_array($ext, ['jpg', 'jpeg', 'png']);
                            $is_pdf = ($ext === 'pdf');
                        @endphp
                        <div class="uploaded-file">
                            <div class="uploaded-header">
                                <div class="file-info-group">
                                    <i class="bi {{ $is_image ? 'bi-file-image-fill' : ($is_pdf ? 'bi-file-pdf-fill' : 'bi-file-earmark-fill') }}" style="color: {{ $is_image ? '#10b981' : ($is_pdf ? '#ef4444' : '#3b82f6') }};"></i>
                                    <div class="file-details">
                                        <a href="{{ asset('uploads/evidence/' . $slot['data']->file_path) }}" target="_blank">{{ Str::limit($slot['data']->file_path, 30) }}</a>
                                        <small>{{ date('d M Y, H:i', strtotime($slot['data']->tanggal_upload)) }} WIB</small>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <span class="badge badge-{{ $b_class }}">{{ $st }}</span>
                                    @if ($is_open && in_array($st, ['Menunggu Review', 'Revisi']))
                                        <form action="{{ route('pegawai.aktivitas.destroy_bukti', [$id, $slot['data']->bukti_id]) }}" method="POST" style="margin:0;">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn-delete" title="Hapus File" onclick="return confirm('Yakin ingin menghapus file ini?')"><i class="bi bi-trash3-fill"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Tombol & Area Preview Sesudah Upload -->
                            @if($is_image || $is_pdf)
                                <button type="button" class="btn-toggle-preview" onclick="togglePreview('preview_after_{{ $index }}')"><i class="bi bi-eye-fill"></i> Tampilkan Preview Dokumen</button>
                                <div id="preview_after_{{ $index }}" class="preview-box">
                                    @if($is_image)
                                        <img src="{{ asset('uploads/evidence/' . $slot['data']->file_path) }}">
                                    @elseif($is_pdf)
                                        <iframe src="{{ asset('uploads/evidence/' . $slot['data']->file_path) }}"></iframe>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @else
                        <!-- JIKA BELUM UPLOAD -->
                        @if ($is_open)
                            <form method="POST" action="{{ route('pegawai.aktivitas.upload', $id) }}" enctype="multipart/form-data" class="upload-form">
                                @csrf <input type="hidden" name="evidence_wajib_id" value="{{ $slot['id'] }}">
                                
                                <div class="upload-controls">
                                    <div class="file-input-wrapper">
                                        <input type="file" name="file_evidence" id="file_{{ $index }}" style="display:none;" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" onchange="previewBeforeUpload(this, 'name_{{ $index }}', 'preview_before_{{ $index }}')">
                                        <label for="file_{{ $index }}" class="btn-pilih-file"><i class="bi bi-folder2-open"></i> Cari File</label>
                                        <span id="name_{{ $index }}" class="file-name-text">Belum ada file dipilih</span>
                                    </div>
                                    <button type="submit" class="btn-upload-small"><i class="bi bi-cloud-arrow-up-fill" style="margin-right: 4px;"></i> Unggah</button>
                                </div>

                                <!-- Area Preview Sebelum Upload -->
                                <div id="preview_before_{{ $index }}" class="preview-box"></div>
                            </form>
                        @else
                            <div style="background:#fff1f2; padding:12px; text-align:center; border-radius:12px; color:#e11d48; font-size:12px; font-weight:700;"><i class="bi bi-lock-fill"></i> Terkunci (Periode Penilaian Telah Ditutup)</div>
                        @endif
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Fungsi untuk menampilkan file preview sebelum diupload
function previewBeforeUpload(input, textId, previewBoxId) {
    const textElement = document.getElementById(textId);
    const previewBox = document.getElementById(previewBoxId);
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileURL = URL.createObjectURL(file);
        const fileType = file.type;
        
        // Ubah teks nama file
        textElement.innerHTML = '<b style="color:#059669;"><i class="bi bi-check-circle-fill"></i> ' + file.name + '</b>';
        
        // Buat preview jika format didukung
        previewBox.style.display = 'block';
        if (fileType.startsWith('image/')) {
            previewBox.innerHTML = '<img src="' + fileURL + '">';
        } else if (fileType === 'application/pdf') {
            previewBox.innerHTML = '<iframe src="' + fileURL + '"></iframe>';
        } else {
            previewBox.innerHTML = '<div style="padding: 20px; color: #64748b; font-weight: 500; font-size: 12px;"><i class="bi bi-file-earmark-check" style="font-size: 32px; color: #3e54a0; display: block; margin-bottom: 8px;"></i> File siap diunggah. <br>(Preview visual tidak didukung untuk format ini)</div>';
        }
    } else {
        textElement.innerHTML = 'Belum ada file dipilih'; 
        previewBox.style.display = 'none';
        previewBox.innerHTML = '';
    }
}

// Fungsi untuk toggle (buka/tutup) preview setelah upload
function togglePreview(previewBoxId) {
    const previewBox = document.getElementById(previewBoxId);
    if (previewBox.style.display === "none" || previewBox.style.display === "") {
        previewBox.style.display = "block";
    } else {
        previewBox.style.display = "none";
    }
}
</script>
@endpush