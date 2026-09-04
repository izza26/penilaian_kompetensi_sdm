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
            'nip_nik' => 'required',
            'pegawai_nama' => 'required',
            'unit_kerja' => 'required',
            'jabatan' => 'required',
            'password' => 'required|min:6'
        ]);

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            // 1. Cari ID Baru (Pakai DB::table agar 100% dipaksa ke geotrax_v3)
            $new_id = (\Illuminate\Support\Facades\DB::table('geotrax_v3.pegawai_skkni')->max('pegawai_id') ?? 0) + 1;
            $new_user_id = (\Illuminate\Support\Facades\DB::table('geotrax_v3.users')->max('id') ?? 0) + 1;
            
            // Buat username otomatis dari nama
            $nama_parts = explode(' ', strtolower(trim($request->pegawai_nama)));
            $username_awal = count($nama_parts) > 1 ? substr($nama_parts[0], 0, 1) . $nama_parts[1] : $nama_parts[0];
            $username = preg_replace('/[^a-z0-9]/', '', $username_awal);
            
            // 2. Enkripsi Password standar Laravel (Bcrypt)
            $hashed_password = bcrypt($request->password);

            // 3. DAFTARKAN KE TABEL UTAMA (USERS)
            \Illuminate\Support\Facades\DB::table('geotrax_v3.users')->insert([
                'id' => $new_user_id,
                'username' => $username,
                'nama_lengkap' => $request->pegawai_nama,
                'email' => $request->email ?? ($username . '@museum.go.id'),
                'role' => $request->role ?? 'pegawai',
                'password' => $hashed_password,
                'status' => 'Aktif'
            ]);

            // 4. DAFTARKAN KE TABEL KEPEGAWAIAN (GANTI ELOQUENT DENGAN DB::TABLE)
            \Illuminate\Support\Facades\DB::table('geotrax_v3.pegawai_skkni')->insert([
                'pegawai_id' => $new_id,
                'nip_nik' => $request->nip_nik,
                'pegawai_nama' => $request->pegawai_nama,
                'email' => $request->email,
                'no_hp' => $request->no_hp,
                'jabatan' => $request->jabatan,
                'unit_kerja' => $request->unit_kerja,
                'password' => $hashed_password, 
                'role' => $request->role ?? 'pegawai',
                'status_aktif' => $request->status ?? 'Aktif',
                'user_id' => $new_user_id,
                'username' => $username
            ]);

            // 5. DAFTARKAN KE PINTU RAHASIA VVIP (P_APP_USER) AGAR BISA LOGIN
            \Illuminate\Support\Facades\DB::table('geotrax_v3.p_app_user')->insert([
                'id_user' => (\Illuminate\Support\Facades\DB::table('geotrax_v3.p_app_user')->max('id_user') ?? 0) + 1,
                'username' => $username,
                'password_hash' => $hashed_password,
                'role' => 'Pelaku Kompetensi',
                'id_pegawai' => $new_id
            ]);

            // 6. DAFTARKAN KE PINTU RAHASIA PIMPINAN (P_PEGAWAI) AGAR MUNCUL DI "TIM SAYA"
            \Illuminate\Support\Facades\DB::table('geotrax_v3.p_pegawai')->insert([
                'id_pegawai' => $new_id,
                'nama_pegawai' => $request->pegawai_nama,
                'peran' => 'Pegawai',
                'spesialisasi' => $request->jabatan,
                'level_otoritas' => 3,
                'email' => $request->email,
                'telepon' => $request->no_hp,
                'status_aktif' => 1
            ]);

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('admin.pegawai.index')->with('success', 'Pegawai ' . $request->pegawai_nama . ' berhasil ditambahkan dan siap Login dengan username: ' . $username);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->with('error', 'Gagal menyimpan: ' . $e->getMessage())->withInput();
        }
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