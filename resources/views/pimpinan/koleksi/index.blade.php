@extends('layouts.app')
@section('title', 'Master Koleksi')
@section('page_title', 'Master Koleksi Museum')
@section('page_subtitle', 'Kelola pembaruan informasi dan status terkini koleksi museum.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Desain Rekap & Toolbar Sama Persis dengan Pegawai */
        /* Desain Rekap Grid Otomatis (Tanpa Scrollbar Jelek!) */
        .stat-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-box { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); transition: 0.2s;}
        .stat-box:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.05); border-color: #cbd5e1;}
        .stat-icon { background: #f4f7fe; color: #3e54a0; width: 48px; height: 48px; border-radius: 14px; display: flex; justify-content: center; align-items: center; font-size: 22px; flex-shrink: 0;}
        .stat-info h4 { margin: 0 0 2px 0; font-size: 22px; color: #1e293b; font-weight: 800; line-height: 1;}
        .stat-info p { margin: 0; font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;}

        .toolbar-box { background: #ffffff; border-radius: 50px; padding: 12px 20px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #cbd5e1; box-shadow: 0 10px 30px rgba(0,0,0,0.02); gap: 15px; flex-wrap: wrap;}
        .toolbar-left { display: flex; align-items: center; gap: 15px; flex: 1; min-width: 250px;}
        .search-input { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 10px 16px; border-radius: 50px; flex: 1; border: 1px dashed #cbd5e1;}
        .search-input input { border: none; background: transparent; outline: none; width: 100%; font-size: 13px; color: #1e293b; font-family: inherit;}
        
        .toolbar-right { display: flex; align-items: center; gap: 10px;}
        .filter-select { padding: 10px 20px; font-size: 13px; font-weight: 600; color: #334155; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 50px; outline: none;}
        .btn-action { background: #3e54a0; color: white; border: none; padding: 10px 24px; border-radius: 50px; font-size: 12px; font-weight: 700; cursor: pointer; transition: 0.2s; white-space: nowrap;}
        .btn-action:hover { background: #2b3a70; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(62, 84, 160, 0.2);}
        .btn-reset { background: #fff1f2; color: #e11d48; border: none; width: 38px; height: 38px; border-radius: 50%; display: flex; justify-content: center; align-items: center; font-size: 16px; text-decoration: none;}
        .total-badge { background: #f4f7fe; color: #3e54a0; font-size: 12px; font-weight: 800; padding: 10px 20px; border-radius: 50px;}

        /* TABEL & AKSI PIMPINAN */
        .table-container { background: #fff; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); margin-bottom: 20px;}
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #64748b; padding: 16px 24px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.5px;}
        .styled-table td { padding: 18px 24px; border-bottom: 1px dashed #f1f5f9; vertical-align: top; }
        .koleksi-name { font-weight: 700; color: #1e293b; font-size: 14px; margin-bottom: 6px; display: block;}
        .koleksi-desc { font-size: 12px; color: #64748b; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; margin: 0;}
        .reg-no { font-family: monospace; font-size: 13px; color: #3e54a0; font-weight: 600; background: #f4f7fe; padding: 4px 10px; border-radius: 6px;}
        .badge-jenis { background: #eff6ff; color: #2563eb; font-size: 10px; font-weight: 800; padding: 6px 12px; border-radius: 50px; text-transform: uppercase;}
        .badge-status { background: #ecfdf5; color: #059669; font-size: 10px; font-weight: 800; padding: 6px 12px; border-radius: 50px; text-transform: uppercase;}

        .action-group { display: flex; gap: 8px; justify-content: center;}
        .btn-view { background: #f4f7fe; color: #3e54a0; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 13px; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s;}
        .btn-view:hover { background: #3e54a0; color: white;}
        .btn-edit { background: #fffbeb; color: #d97706; border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 13px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.2s;}
        .btn-edit:hover { background: #d97706; color: white;}

        /* MODAL EDIT KOLEKSI */
        .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.6); align-items: center; justify-content: center; backdrop-filter: blur(4px); }
        .modal-content { background-color: #fff; padding: 32px; border-radius: 24px; width: 100%; max-width: 500px; position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.1); animation: slideDown 0.3s ease-out;}
        @keyframes slideDown { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
        .close-modal { position: absolute; top: 24px; right: 24px; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; transition: 0.2s;}
        .close-modal:hover { color: #e11d48; }
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 11px; font-weight: 700; color: #64748b; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; color: #1e293b; background: #f8fafc; font-family: inherit; outline: none; transition: 0.2s;}
        .form-control:focus { border-color: #3e54a0; background: #fff; box-shadow: 0 0 0 3px rgba(62,84,160,0.1);}
        textarea.form-control { resize: vertical; min-height: 80px;}
        .btn-submit { background: #3e54a0; color: white; width: 100%; padding: 14px; border: none; border-radius: 50px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s; margin-top: 10px;}
        .btn-submit:hover { background: #2b3a70; }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>document.addEventListener("DOMContentLoaded", function() { Swal.fire({ icon: 'success', title: 'Berhasil!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false }); });</script>
    @endif

    <div class="stat-container">
        <div class="stat-box" style="background: #3e54a0; border: none;">
            <div class="stat-icon" style="background: rgba(255,255,255,0.2); color: white;"><i class="bi bi-box-seam-fill"></i></div>
            <div class="stat-info"><h4 style="color: white;">{{ number_format($total_semua) }}</h4><p style="color: #e0e7ff;">Total Registrasi</p></div>
        </div>
        @foreach($rekap as $rk)
        <div class="stat-box">
            <div class="stat-icon"><i class="bi bi-tag-fill"></i></div>
            <div class="stat-info"><h4>{{ number_format($rk->total) }}</h4><p>{{ $rk->kategori_koleksi }}</p></div>
        </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('pimpinan.koleksi.index') }}" class="toolbar-box">
        <div class="toolbar-left">
            <div class="search-input"><i class="bi bi-search"></i><input type="text" name="cari" value="{{ request('cari') }}" placeholder="Cari koleksi..."></div>
            <div class="total-badge"><i class="bi bi-card-list"></i> Menampilkan {{ $koleksi->total() }} Data</div>
        </div>
        <div class="toolbar-right">
            <select name="jenis" class="filter-select">
                <option value="">-- Semua Kategori --</option>
                @foreach($jenis_koleksi as $jk) <option value="{{ $jk->id_jenis }}" {{ request('jenis') == $jk->id_jenis ? 'selected' : '' }}>{{ $jk->kategori_koleksi }}</option> @endforeach
            </select>
            <button type="submit" class="btn-action">Filter Data</button>
            @if(request()->has('cari') || request()->has('jenis')) <a href="{{ route('pimpinan.koleksi.index') }}" class="btn-reset"><i class="bi bi-arrow-clockwise"></i></a> @endif
        </div>
    </form>

    <div class="table-container">
        <div style="overflow-x: auto;">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th width="15%">No. Registrasi</th>
                        <th width="35%">Identitas Koleksi</th>
                        <th width="15%" style="text-align: center;">Jenis/Kategori</th>
                        <th width="15%" style="text-align: center;">Status</th>
                        <th width="15%" style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($koleksi as $item)
                        <tr>
                            <td><span class="reg-no">{{ $item->nomor_registrasi }}</span></td>
                            <td><span class="koleksi-name">{{ $item->nama_koleksi }}</span><p class="koleksi-desc">{{ $item->deskripsi ?? 'Tidak ada deskripsi.' }}</p></td>
                            <td style="text-align: center;"><span class="badge-jenis">{{ $item->kategori_koleksi ?? 'Umum' }}</span></td>
                            <td style="text-align: center;"><span class="badge-status">{{ $item->status_koleksi }}</span></td>
                            <td>
                                <div class="action-group">
                                    <a href="{{ route('pimpinan.koleksi.show', $item->nomor_registrasi) }}" class="btn-view" title="Lihat Detail"><i class="bi bi-eye-fill"></i></a>
                                    <!-- Tombol Edit memanggil Modal -->
                                    <button type="button" class="btn-edit" title="Edit Koleksi" 
                                            onclick="bukaModalEdit('{{ $item->nomor_registrasi }}', '{{ addslashes($item->nama_koleksi) }}', '{{ $item->id_jenis }}', '{{ $item->status_koleksi }}', '{{ addslashes($item->deskripsi) }}')">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center; padding: 40px; color:#64748b;">Data Koleksi Kosong</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $koleksi->appends(request()->query())->links('pagination::bootstrap-4') }}

    <!-- MODAL EDIT KOLEKSI -->
    <div id="modalEdit" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="tutupModal('modalEdit')">&times;</span>
            <h3 style="margin: 0 0 20px 0; color: #1e293b; font-size: 18px;"><i class="bi bi-pencil-square" style="color: #3e54a0; margin-right: 6px;"></i> Update Data Koleksi</h3>
            
            <form method="POST" action="{{ route('pimpinan.koleksi.update') }}">
                @csrf
                <div class="form-group">
                    <label>Nomor Registrasi (Read-Only)</label>
                    <input type="text" id="edit_reg" class="form-control" disabled style="background:#e2e8f0; font-family: monospace; font-weight: bold;">
                    <input type="hidden" name="nomor_registrasi" id="hidden_reg">
                </div>
                
                <div class="form-group">
                    <label>Nama Koleksi</label>
                    <input type="text" name="nama_koleksi" id="edit_nama" class="form-control" required>
                </div>
                
                <div style="display: flex; gap: 15px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Kategori Koleksi</label>
                        <select name="id_jenis" id="edit_jenis" class="form-control" required>
                            @foreach($jenis_koleksi as $jk)
                                <option value="{{ $jk->id_jenis }}">{{ $jk->kategori_koleksi }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Status Koleksi</label>
                        <select name="status_koleksi" id="edit_status" class="form-control" required>
                            <option value="Aktif">Aktif</option>
                            <option value="Perawatan">Perawatan</option>
                            <option value="Dipinjamkan">Dipinjamkan</option>
                            <option value="Rusak/Dihapus">Rusak/Dihapus</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi Tambahan</label>
                    <textarea name="deskripsi" id="edit_desc" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn-submit">Simpan Perubahan</button>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
    function bukaModalEdit(reg, nama, jenis, status, desc) {
        document.getElementById('edit_reg').value = reg;
        document.getElementById('hidden_reg').value = reg;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_jenis').value = jenis;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_desc').value = desc;
        
        let modal = document.getElementById('modalEdit');
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function tutupModal(id) {
        document.getElementById(id).style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Tutup jika klik backdrop
    window.onclick = function(event) {
        let modal = document.getElementById('modalEdit');
        if (event.target == modal) { tutupModal('modalEdit'); }
    }
</script>
@endpush