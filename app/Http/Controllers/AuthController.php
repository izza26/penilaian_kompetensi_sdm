<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pegawai;

class AuthController extends Controller
{
    public function showLogin()
    {
        // Akan memanggil file login.blade.php nanti
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        // Cari pegawai berdasarkan nip_nik (username)
        $pegawai = Pegawai::where('nip_nik', $request->username)->first();

        // Cek manual karena password di DB adalah plain text (tidak di-hash)
        if ($pegawai && $pegawai->password === $request->password) {
            
            // Daftarkan session login ke sistem Laravel
            Auth::login($pegawai);

            $role = trim(strtolower($pegawai->role));

            // Redirect sesuai role
            if ($role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($role === 'pegawai') {
                return redirect()->route('pegawai.dashboard');
            } elseif ($role === 'pimpinan') {
                return redirect()->route('pimpinan.dashboard');
            }
        }

        // Jika username/password salah
        return back()->with('error', 'Username atau Password salah!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
    
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Validasi input dari form
        $request->validate([
            'nip_nik'      => 'required|unique:pegawai,nip_nik', // Otomatis ngecek duplikat di DB!
            'pegawai_nama' => 'required',
            'email'        => 'required|email',
            'no_hp'        => 'required',
            'role'         => 'required',
            'jabatan'      => 'required',
            'unit_kerja'   => 'required',
            'password'     => 'required|min:6',
        ], [
            // Pesan error custom jika NIK sudah ada
            'nip_nik.unique' => 'Pendaftaran Gagal! NIP / NIK tersebut sudah terdaftar.'
        ]);

        // Generate ID Baru (Cari ID tertinggi + 1)
        $new_id = Pegawai::max('pegawai_id') + 1;

        // Insert data ke database menggunakan Eloquent
        Pegawai::create([
            'pegawai_id'   => $new_id,
            'nip_nik'      => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email'        => $request->email,
            'no_hp'        => $request->no_hp,
            'jabatan'      => $request->jabatan,
            'unit_kerja'   => $request->unit_kerja,
            'password'     => $request->password, // Tetap plain text menyesuaikan sistem lama
            'role'         => trim(strtolower($request->role)),
            'status_aktif' => 'Aktif'
        ]);

        // Lempar kembali ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}