<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;

class PegawaiController extends Controller
{
    // 1. TAMPILKAN SEMUA DATA & PENCARIAN (pegawai.php)
    public function index(Request $request)
    {
        $cari = $request->cari;

        // Query Builder Laravel untuk pencarian
        $pegawais = Pegawai::when($cari, function ($query, $cari) {
            return $query->where('pegawai_nama', 'ILIKE', "%$cari%")
                         ->orWhere('nip_nik', 'ILIKE', "%$cari%")
                         ->orWhere('unit_kerja', 'ILIKE', "%$cari%");
        })->orderBy('pegawai_id', 'desc')->paginate(10); // Otomatis bikin Pagination!

        return view('pimpinan.pegawai.index', compact('pegawais', 'cari'));
    }

    // 2. HALAMAN TAMBAH PEGAWAI (tambah_pegawai.php)
    public function create()
    {
        return view('pimpinan.pegawai.create');
    }

    // 3. PROSES SIMPAN DATA PEGAWAI BARU
    public function store(Request $request)
    {
        $request->validate([
            'nip_nik' => 'required|unique:pegawai,nip_nik',
            'pegawai_nama' => 'required',
            'jabatan' => 'required',
            'unit_kerja' => 'required'
        ]);

        $new_id = Pegawai::max('pegawai_id') + 1;

        Pegawai::create([
            'pegawai_id' => $new_id,
            'nip_nik' => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'password' => '123456', // Sesuai sistem lama
            'role' => 'pegawai',
            'status_aktif' => 'Aktif'
        ]);

        return redirect()->route('pimpinan.pegawai.index')->with('success', 'Data Pegawai berhasil ditambahkan!');
    }

    // 4. HALAMAN DETAIL PEGAWAI (detail_pegawai.php)
    public function show($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('pimpinan.pegawai.show', compact('pegawai'));
    }

    // 5. HALAMAN EDIT PEGAWAI (edit_pegawai.php)
    public function edit($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('pimpinan.pegawai.edit', compact('pegawai'));
    }

    // 6. PROSES UPDATE DATA PEGAWAI
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::findOrFail($id);
        
        $pegawai->update([
            'nip_nik' => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'status_aktif' => $request->status
        ]);

        return redirect()->route('pimpinan.pegawai.index')->with('success', 'Data Pegawai berhasil diperbarui!');
    }
}