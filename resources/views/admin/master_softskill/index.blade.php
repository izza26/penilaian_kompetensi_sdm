@extends('layouts.app')
@section('title', 'Master Softskill')
@section('page_title', 'Master Softskill (Secondary Factor)')
@section('page_subtitle', 'Kelola kriteria penilaian sikap dan softskill pegawai')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card-form { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .card-table { background: white; padding: 24px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
        .form-label { font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; display: block; }
        .form-control { width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px; margin-bottom: 15px; box-sizing: border-box;}
        .form-control:focus { outline: none; border-color: #3e54a0; }
        .btn-submit { background: #3e54a0; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; width: 100%; cursor: pointer; transition: 0.2s;}
        .btn-submit:hover { background: #2b3a70; }
        .btn-cancel { background: #f1f5f9; color: #475569; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; width: 100%; cursor: pointer; margin-top: 10px; display: none;}

        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #64748b; padding: 12px 15px; font-size: 11px; font-weight: 700; text-transform: uppercase; text-align: left; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 15px; border-bottom: 1px dashed #cbd5e1; font-size: 13px; color: #334155; }
        .target-badge { background: #f4f7fe; color: #3e54a0; font-weight: 800; padding: 4px 12px; border-radius: 50px; }

        .action-btn { padding: 6px 10px; border-radius: 6px; border: none; cursor: pointer; margin-right: 5px; transition: 0.2s; }
        .btn-edit { background: #fffbeb; color: #d97706; }
        .btn-delete { background: #fef2f2; color: #ef4444; }
    </style>
@endpush

@section('content')
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">

    @if(session('success'))
        <script>document.addEventListener("DOMContentLoaded", function() { Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2500, showConfirmButton: false }); });</script>
    @endif

    <!-- KIRI: FORM TAMBAH / EDIT -->
    <div>
        <div class="card-form">
            <h3 id="formTitle" style="margin-top: 0; font-size: 16px; color: #1e293b; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Tambah Softskill Baru</h3>

            <form id="softskillForm" action="{{ route('admin.master_softskill.store') }}" method="POST">
                @csrf
                <input type="hidden" name="softskill_id" id="softskill_id">

                <label class="form-label">Nama Softskill / Sikap Kerja</label>
                <input type="text" name="nama_softskill" id="nama_softskill" class="form-control" placeholder="Contoh: Kerja Sama Tim" required>

                <label class="form-label">Target Skor Ideal (1-5)</label>
                <select name="target_skor" id="target_skor" class="form-control" required>
                    <option value="5">5 - Sangat Baik</option>
                    <option value="4" selected>4 - Baik</option>
                    <option value="3">3 - Cukup</option>
                    <option value="2">2 - Kurang</option>
                    <option value="1">1 - Sangat Kurang</option>
                </select>

                <label class="form-label">Deskripsi / Indikator (Opsional)</label>
                <textarea name="deskripsi" id="deskripsi" class="form-control" rows="4" placeholder="Jelaskan kriteria penilaian singkat..."></textarea>

                <button type="submit" id="btnSubmit" class="btn-submit">Simpan Data</button>
                <button type="button" id="btnCancel" class="btn-cancel" onclick="resetForm()">Batal Edit</button>
            </form>
        </div>
    </div>

    <!-- KANAN: TABEL DATA -->
    <div>
        <div class="card-table">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th width="35%">Kriteria Softskill</th>
                        <th width="15%" style="text-align: center;">Target Skor</th>
                        <th width="30%">Deskripsi</th>
                        <th width="15%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($softskills as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><b>{{ $item->nama_softskill }}</b></td>
                            <td style="text-align: center;"><span class="target-badge">{{ $item->target_skor }}</span></td>
                            <td style="font-size: 11px; color: #64748b;">{{ $item->deskripsi ?? '-' }}</td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="action-btn btn-edit" onclick="editData('{{ $item->softskill_id }}', '{{ $item->nama_softskill }}', '{{ $item->target_skor }}', '{{ $item->deskripsi }}')"><i class="bi bi-pencil-square"></i></button>

                                <form action="{{ route('admin.master_softskill.destroy', $item->softskill_id) }}" method="POST" style="display:inline;" onsubmit="return confirm('Yakin ingin menghapus kriteria softskill ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="action-btn btn-delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align: center; padding: 20px;">Belum ada data Master Softskill.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function editData(id, nama, target, deskripsi) {
        document.getElementById('formTitle').innerText = "Edit Softskill";
        document.getElementById('softskillForm').action = "{{ route('admin.master_softskill.update') }}";

        document.getElementById('softskill_id').value = id;
        document.getElementById('nama_softskill').value = nama;
        document.getElementById('target_skor').value = target;
        document.getElementById('deskripsi').value = deskripsi !== '-' ? deskripsi : '';

        document.getElementById('btnSubmit').innerText = "Update Data";
        document.getElementById('btnCancel').style.display = "block";
    }

    function resetForm() {
        document.getElementById('formTitle').innerText = "Tambah Softskill Baru";
        document.getElementById('softskillForm').action = "{{ route('admin.master_softskill.store') }}";
        document.getElementById('softskillForm').reset();
        document.getElementById('softskill_id').value = "";

        document.getElementById('btnSubmit').innerText = "Simpan Data";
        document.getElementById('btnCancel').style.display = "none";
    }
</script>
@endsection
