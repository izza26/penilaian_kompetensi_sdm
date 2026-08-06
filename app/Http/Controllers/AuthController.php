<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; 
use App\Models\Pegawai;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login'); 
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        $input = $request->username;

        // PERBAIKAN: Cari pegawai berdasarkan nip_nik ATAU username
        $pegawai = Pegawai::where('nip_nik', $input)
                          ->orWhere('username', $input)
                          ->first();

        if ($pegawai) {
            $isPasswordValid = false;

            if (str_starts_with($pegawai->password, '$2y$') || str_starts_with($pegawai->password, '$2a$')) {
                $isPasswordValid = Hash::check($request->password, $pegawai->password);
            } else {
                if ($pegawai->password === $request->password) {
                    $isPasswordValid = true;
                    $pegawai->update(['password' => Hash::make($request->password)]);
                }
            }

            if ($isPasswordValid) {
                Auth::login($pegawai);

                $role = trim(strtolower($pegawai->role));

                if ($role === 'admin') {
                    return redirect()->route('admin.dashboard');
                } elseif ($role === 'pegawai') {
                    return redirect()->route('pegawai.dashboard');
                } elseif ($role === 'pimpinan') {
                    return redirect()->route('pimpinan.dashboard');
                }
            }
        }

        return back()->with('error', 'Username/NIP atau Password salah!');
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
        $request->validate([
            'nip_nik'      => 'required|unique:pegawai,nip_nik',
            'pegawai_nama' => 'required',
            'email'        => 'required|email',
            'no_hp'        => 'required',
            'role'         => 'required',
            'jabatan'      => 'required',
            'unit_kerja'   => 'required',
            'password'     => 'required|min:6',
        ], [
            'nip_nik.unique' => 'Pendaftaran Gagal! NIP / NIK tersebut sudah terdaftar.'
        ]);

        $new_id = Pegawai::max('pegawai_id') + 1;

        Pegawai::create([
            'pegawai_id'   => $new_id,
            'nip_nik'      => $request->nip_nik,
            'username'     => $request->nip_nik, // Gunakan NIP sebagai username default
            'pegawai_nama' => $request->pegawai_nama,
            'email'        => $request->email,
            'no_hp'        => $request->no_hp,
            'jabatan'      => $request->jabatan,
            'unit_kerja'   => $request->unit_kerja,
            'password'     => Hash::make($request->password), 
            'role'         => trim(strtolower($request->role)),
            'status_aktif' => 'Aktif'
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}