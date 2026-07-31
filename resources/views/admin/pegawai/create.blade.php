@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page_title', 'Tambah Pegawai')
@section('page_subtitle', 'Tambahkan data pegawai baru ke sistem')
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
        .form-control:focus { border-color: #A08348; box-shadow: 0 0 0 3px rgba(160,131,72,0.1); }
        .btn-primary { background: #1B2D46; color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; font-size: 14px;}
        .btn-primary:hover { background: #29466b; }

        /* Styling untuk fitur Show/Hide Password */
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
            <h2>Tambah Pegawai</h2>
            <p>Lengkapi informasi pegawai yang akan mengikuti proses penilaian kompetensi.</p>
        </div>
    </div>

    @if ($errors->any())
        <div style="background: #fef2f2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #fecaca; font-size: 13px;">
            <ul style="margin: 0; padding-left: 15px;">
                @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.pegawai.store') }}" method="POST">
        @csrf
        <div class="form-grid">
            <!-- BARIS 1: NIP dan NAMA -->
            <div class="form-group">
                <label>NIP</label>
                <input type="text" name="nip_nik" class="form-control" placeholder="Nomor Induk Pegawai" value="{{ old('nip_nik') }}" required>
            </div>
            <div class="form-group">
                <label>Nama Lengkap</label>
                <input type="text" name="pegawai_nama" class="form-control" placeholder="Nama Lengkap" value="{{ old('pegawai_nama') }}" required>
            </div>

            <!-- BARIS 2: EMAIL dan NO HP -->
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" placeholder="pegawai@example.com" value="{{ old('email') }}">
            </div>
            <div class="form-group">
                <label>No. WhatsApp</label>
                <input type="text" name="no_hp" class="form-control" placeholder="Contoh: 0812..." value="{{ old('no_hp') }}">
            </div>

            <!-- BARIS 3: JABATAN dan UNIT KERJA (Role sudah otomatis 'pegawai' di sistem) -->
            <div class="form-group">
                <label>Jabatan Teknis</label>
                <select name="jabatan" class="form-control" required>
                    <option value="">-- Pilih Jabatan --</option>
                    <option value="Kepala Museum">Kepala Museum</option>
                    <option value="Register">Register</option>
                    <option value="Kurator">Kurator</option>
                    <option value="Konservator">Konservator</option>
                    <option value="Penata Pameran">Penata Pameran</option>
                    <option value="Edukator">Edukator</option>
                    <option value="Hubungan Masyarakat dan Pemasaran">Hubungan Masyarakat & Pemasaran</option>
                </select>
            </div>
            <div class="form-group">
                <label>Unit Kerja</label>
                <input type="text" name="unit_kerja" class="form-control" value="Museum Geologi" required>
            </div>

            <!-- BARIS 4: PASSWORD -->
            <div class="form-group full-width">
                <label>Password</label>
                <div class="password-wrapper">
                    <input type="password" name="password" id="inputPassword" class="form-control" placeholder="Masukkan password untuk pegawai login" required autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility()" title="Tampilkan Password">
                        <i class="bi bi-eye-slash" id="iconToggle"></i>
                    </button>
                </div>
            </div>
        </div>

        <div style="margin-top: 10px; border-top: 1px solid #f1f5f9; padding-top: 20px; text-align: right;">
            <button type="submit" class="btn-primary"><i class="bi bi-save-fill"></i> Simpan Pegawai</button>
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