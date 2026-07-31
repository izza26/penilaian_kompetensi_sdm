<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash; // <-- Wajib ditambahkan untuk memanggil fungsi Hash
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

        // Cari pegawai berdasarkan nip_nik (username)
        $pegawai = Pegawai::where('nip_nik', $request->username)->first();

        if ($pegawai) {
            $isPasswordValid = false;

            // LOGIKA BARU: Cek apakah password di database sudah berupa Hash (dimulai dengan $2y$)
            if (str_starts_with($pegawai->password, '$2y$')) {
                // Gunakan Hash::check untuk membandingkan password yang dienkripsi
                $isPasswordValid = Hash::check($request->password, $pegawai->password);
            } else {
                // Jika belum di-hash, cek dengan plain text (untuk user dari sistem lama)
                if ($pegawai->password === $request->password) {
                    $isPasswordValid = true;
                    
                    // OTOMATIS MIGRASI: Langsung update password plain text ini menjadi Hash 
                    // agar ke depannya akun ini lebih aman dan menggunakan format baru
                    $pegawai->update(['password' => Hash::make($request->password)]);
                }
            }

            // Jika password terbukti valid (baik dari hash maupun plain text)
            if ($isPasswordValid) {
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
        }

        // Jika username tidak ditemukan atau password salah
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
            'pegawai_nama' => $request->pegawai_nama,
            'email'        => $request->email,
            'no_hp'        => $request->no_hp,
            'jabatan'      => $request->jabatan,
            'unit_kerja'   => $request->unit_kerja,
            'password'     => Hash::make($request->password), // <-- PERBAIKAN: Ubah menjadi Hash
            'role'         => trim(strtolower($request->role)),
            'status_aktif' => 'Aktif'
        ]);

        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}