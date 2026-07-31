<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pegawai;
use Illuminate\Support\Facades\Hash;

class AdminPegawaiController extends Controller
{
    public function index(Request $request)
    {
        $cari = $request->cari;

        // Query Builder Laravel untuk pencarian dan pagination
        $pegawais = Pegawai::when($cari, function ($query, $cari) {
            return $query->where('pegawai_nama', 'ILIKE', "%$cari%")
                         ->orWhere('nip_nik', 'ILIKE', "%$cari%")
                         ->orWhere('unit_kerja', 'ILIKE', "%$cari%");
        })->orderBy('pegawai_id', 'desc')->paginate(10); 

        return view('admin.pegawai.index', compact('pegawais', 'cari'));
    }

    public function create()
    {
        return view('admin.pegawai.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip_nik' => 'required|unique:pegawai,nip_nik',
            'pegawai_nama' => 'required',
            'unit_kerja' => 'required',
            'password' => 'required|min:6'
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
            'password' => bcrypt($request->password), 
            'role' => $request->role ?? 'pegawai',
            'status_aktif' => $request->status ?? 'Aktif',
            'user_id' => $new_id // <--- Tambahkan baris ini agar user_id ikut tersimpan
        ]);

        return redirect()->route('admin.pegawai.index')->with('success', 'Data Pegawai berhasil ditambahkan!');
    }

    public function show($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('admin.pegawai.show', compact('pegawai'));
    }

    public function edit($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('admin.pegawai.edit', compact('pegawai'));
    }

    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::findOrFail($id);
        
        // Data dasar yang pasti di-update
        $dataUpdate = [
            'nip_nik' => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'role' => $request->role ?? $pegawai->role,
            'status_aktif' => $request->status ?? $pegawai->status_aktif
        ];

        // LOGIKA BARU: Cek apakah Admin mengisi input password di form edit
        if ($request->filled('password')) {
            // Jika form password diisi, enkripsi password baru dan masukkan ke data update
            $dataUpdate['password'] = bcrypt($request->password);
        }

        $pegawai->update($dataUpdate);

        return redirect()->route('admin.pegawai.index')->with('success', 'Data Pegawai berhasil diperbarui!');
    }

    public function destroy($id)
    {
        Pegawai::findOrFail($id)->delete();
        return redirect()->route('admin.pegawai.index')->with('success', 'Data Pegawai berhasil dihapus!');
    }
}