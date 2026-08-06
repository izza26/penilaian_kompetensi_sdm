@extends('layouts.app') <!-- Sesuaikan dengan layout admin kamu -->
@section('title', 'Standar Profil Jabatan')
@section('page_title', 'Standar Profil Kompetensi')
@section('page_subtitle', 'Tentukan target nilai dan jenis faktor per jabatan untuk perhitungan Profile Matching.')

@push('styles')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .filter-card { background: #ffffff; padding: 20px 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; display: flex; gap: 15px; align-items: flex-end; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .filter-group { flex: 1; }
        .filter-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
        .filter-group select { width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid #cbd5e1; outline: none; background: #f8fafc; font-size: 13px; cursor: pointer; }
        .filter-group select:focus { border-color: #bda572; background: #fff; }
        
        .btn-tampilkan { background: #ebdbb6; color: black; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; height: 40px; display: inline-flex; align-items: center; gap: 8px; transition: 0.2s; }
        .btn-tampilkan:hover { background: #bda572; }

        .table-card { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.02); overflow: hidden; padding: 20px; }
        .styled-table { width: 100%; border-collapse: collapse; }
        .styled-table th { background: #f8fafc; color: #64748b; padding: 14px 15px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
        .styled-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; color: #334155; }
        
        .input-target { width: 70px; padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; text-align: center; font-weight: 700; color: #0f172a; outline: none; }
        .input-target:focus { border-color: #3b82f6; }
        .select-faktor { padding: 8px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; outline: none; font-weight: 500; cursor: pointer;}
        
        .btn-simpan { background: #bda572; color: white; padding: 12px 24px; border-radius: 8px; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; font-size: 14px; transition: 0.2s; }
        .btn-simpan:hover { background: #826835; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state i { font-size: 40px; color: #cbd5e1; margin-bottom: 10px; display: block; }
    </style>
@endpush

@section('content')
    @if(session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({ icon: 'success', title: 'Tersimpan!', text: "{{ session('success') }}", timer: 2000, showConfirmButton: false });
            });
        </script>
    @endif

    <!-- Filter Jabatan -->
    <form action="{{ route('admin.standar_profil.index') }}" method="GET" class="filter-card">
        <div class="filter-group">
            <label><i class="bi bi-briefcase-fill" style="color: #bda572;"></i> Pilih Jabatan untuk Diatur Targetnya:</label>
            <select name="jabatan" onchange="this.form.submit()">
                <option value="">-- Silakan Pilih Jabatan --</option>
                @foreach($list_jabatan as $jab)
                    <option value="{{ $jab }}" {{ $jabatan_terpilih == $jab ? 'selected' : '' }}>{{ $jab }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-tampilkan"><i class="bi bi-search"></i> Tampilkan</button>
    </form>

    <!-- Tabel Form Pengaturan Standar -->
    @if($jabatan_terpilih)
        <div class="table-card">
            @if(count($list_aktivitas) > 0)
                <form action="{{ route('admin.standar_profil.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="jabatan" value="{{ $jabatan_terpilih }}">
                    
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th width="5%" style="text-align: center;">No</th>
                                <th width="15%">Kode Unit Kompetensi</th>
                                <th width="45%">Aktivitas</th>
                                <th width="15%" style="text-align: center;">Nilai Target</th>
                                <th width="20%" style="text-align: center;">Jenis Faktor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($list_aktivitas as $index => $ak)
                            <tr>
                                <td style="text-align: center; font-weight: 600; color: #64748b;">{{ $index + 1 }}</td>
                                <td><span style="font-size: 11px; font-weight: 800; color: #183851; background: #fffbeb; padding: 4px 8px; border-radius: 6px;">{{ $ak->kode_unit }}</span></td>
                                <td style="line-height: 1.5; font-weight: 500;">{{ $ak->detail_aktivitas }}</td>
                                <td style="text-align: center;">
                                    <input type="number" name="target[{{ $ak->aktivitas_id }}]" class="input-target" min="1" max="5" value="{{ $ak->target_skor ?? 4 }}" required>
                                </td>
                                <td style="text-align: center;">
                                    <select name="faktor[{{ $ak->aktivitas_id }}]" class="select-faktor">
                                        <option value="Core" {{ ($ak->jenis_faktor ?? 'Core') == 'Core' ? 'selected' : '' }}>Core Factor (60%)</option>
                                        <option value="Secondary" {{ ($ak->jenis_faktor ?? '') == 'Secondary' ? 'selected' : '' }}>Secondary Factor (40%)</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <div style="margin-top: 25px; display: flex; justify-content: flex-end; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" class="btn-simpan">
                            <i class="bi bi-save-fill"></i> Simpan Standar Jabatan
                        </button>
                    </div>
                </form>
            @else
                <div class="empty-state">
                    <i class="bi bi-folder-x"></i>
                    <h3 style="margin: 0; color: #0f172a;">Aktivitas Belum Diatur</h3>
                    <p style="margin: 5px 0 0 0;">Tidak ada aktivitas kompetensi yang ditemukan untuk jabatan <b>{{ $jabatan_terpilih }}</b>.</p>
                </div>
            @endif
        </div>
    @else
        <div class="table-card empty-state">
            <i class="bi bi-hand-index-thumb"></i>
            <h3 style="margin: 0; color: #0f172a;">Pilih Jabatan Terlebih Dahulu</h3>
            <p style="margin: 5px 0 0 0;">Silakan pilih jabatan pada filter di atas untuk mengatur standar profilnya.</p>
        </div>
    @endif
@endsection