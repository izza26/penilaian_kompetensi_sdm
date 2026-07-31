@extends('layouts.app')
@section('title', 'Edit Pegawai')
@section('page_title', 'Edit Pegawai')
@section('page_subtitle', 'Perbarui informasi data pegawai')
@section('back_url', route('admin.pegawai.index'))

@push('styles')
    <style>
        .page-card { background: #fff; border-radius: 16px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #e2e8f0; }
        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; padding-bottom: 20px; }
        .page-title h2 { margin: 0 0 5px 0; font-size: 22px; color: #0f172a; font-weight: 700; }
        .page-title p { margin: 0; font-size: 14px; color: #64748b; }
        
        .btn-kembali { background: #f1f5f9; color: #475569; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 13px; transition: 0.2s; border: 1px solid #e2e8f0; display: inline-flex; align-items: center; gap: 6px;}
        .btn-kembali:hover { background: #e2e8f0; color: #0f172a; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;}
        .form-group { display: flex; flex-direction: column; gap: 8px; }
        .form-group.full-width { grid-column: span 2; }
        .form-group label { font-size: 13px; font-weight: 600; color: #1e293b; text-transform: uppercase;}
        .form-control { padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; outline: none; transition: 0.2s; font-family: inherit;}
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-primary { background: #1B2D46; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px;}
        .btn-primary:hover { background: #29466b; }

        .password-wrapper { position: relative; display: flex; align-items: center; width: 100%; }
        .password-wrapper .form-control { width: 100%; padding-right: 45px; box-sizing: border-box; }
        .toggle-password { position: absolute; right: 12px; background: none; border: none; color: #64748b; cursor: pointer; padding: 5px; outline: none; font-size: 16px; transition: 0.2s; }
        .toggle-password:hover { color: #3b82f6; }
    </style>
@endpush

@section('content')
<div class="page-card">
    <div class="page-header">
        <div class="page-title">
            <h2>Edit Pegawai</h2>
            <p>Perbarui informasi pegawai yang terlibat dalam proses penilaian kompetensi.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.pegawai.update', $pegawai->pegawai_id) }}">
        @csrf @method('PUT')
        <div class="form-grid">
            <div class="form-group">
                <label>NIP</label>
                <input type="text" name="nip_nik" class="form-control" value="{{ $pegawai->nip_nik }}" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="pegawai_nama" class="form-control" value="{{ $pegawai->pegawai_nama }}" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="{{ $pegawai->email }}">
            </div>
            <div class="form-group">
                <label>No. WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" value="{{ $pegawai->no_hp }}">
            </div>
            
            <div class="form-group">
                <label>Jabatan Teknis</label>
                <input type="text" name="jabatan" class="form-control" value="{{ $pegawai->jabatan }}">
            </div>
            <div class="form-group">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" class="form-control" value="{{ $pegawai->unit_kerja }}" required>
            </div>
            
            <div class="form-group full-width">
                <label>Status Akun</label>
                <select name="status" class="form-control">
                    <option value="Aktif" {{ $pegawai->status_aktif == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Nonaktif" {{ $pegawai->status_aktif == 'Nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>

            <div class="form-group full-width" style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                <label style="color: #0f172a; text-transform: uppercase;">
                    <i class="bi bi-key-fill"></i> Reset Password (Opsional)
                </label>
                
                <div class="password-wrapper">
                    <input type="password" name="password" id="inputPassword" class="form-control" 
                           placeholder="Biarkan kosong jika tidak ingin mengubah password saat ini" autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" title="Tampilkan Password">
                        <i class="bi bi-eye-slash" id="iconToggle"></i>
                    </button>
                </div>

                <small style="color: #64748b; margin-top: 4px; display: block; font-weight: normal;">
                    Isi form ini hanya jika pegawai lupa password dan meminta reset ke Admin.
                </small>
            </div>
        </div>
        
        <div style="margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: right;">
            <button type="submit" name="update" class="btn-primary"><i class="bi bi-save-fill"></i> Simpan Perubahan</button>
        </div>
    </form>
</div>

<script>
    function togglePasswordVisibility() {
        const inputPass = document.getElementById('inputPassword');
        const iconToggle = document.getElementById('iconToggle');

        if (inputPass.type === 'password') {
            inputPass.type = 'text';
            iconToggle.classList.remove('bi-eye-slash');
            iconToggle.classList.add('bi-eye');
        } else {
            inputPass.type = 'password';
            iconToggle.classList.remove('bi-eye');
            iconToggle.classList.add('bi-eye-slash');
        }
    }
</script>
@endsection