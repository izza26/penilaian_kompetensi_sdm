<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    // --- 1. DAFTAR USER (user.php) ---
    public function index(Request $request)
    {
        $cari = $request->cari;

        $users = User::when($cari, function ($query, $cari) {
            return $query->where('username', 'ILIKE', "%$cari%")
                         ->orWhere('nama_lengkap', 'ILIKE', "%$cari%")
                         ->orWhere('role', 'ILIKE', "%$cari%");
        })->orderBy('id', 'desc')->paginate(10);

        return view('admin.user.index', compact('users', 'cari'));
    }

    // --- 2. FORM TAMBAH USER (tambah_user.php) ---
    public function create()
    {
        return view('admin.user.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'nama_lengkap' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'konfirmasi_password' => 'required|same:password',
        ], [
            'username.unique' => 'Gagal: Username sudah terdaftar, silakan gunakan username lain!',
            'konfirmasi_password.same' => 'Gagal: Password dan Konfirmasi Password tidak cocok!'
        ]);

        User::create([
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('admin.user.index')->with('success', 'User baru berhasil ditambahkan!');
    }

    // --- 3. DETAIL USER (detail_user.php) ---
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.show', compact('user'));
    }

    // --- 4. FORM EDIT USER (edit_user.php) ---
    public function edit($id)
    {
        $user = User::findOrFail($id);
        return view('admin.user.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'username' => 'required|unique:users,username,' . $id,
            'nama_lengkap' => 'required',
            'email' => 'required|email',
        ], [
            'username.unique' => 'Gagal: Username sudah dipakai oleh akun lain!'
        ]);

        $data_update = [
            'username' => $request->username,
            'nama_lengkap' => $request->nama_lengkap,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
        ];

        // Jika password diisi, berarti ingin diganti[cite: 35]
        if ($request->filled('password')) {
            $request->validate([
                'password' => 'min:6',
                'konfirmasi_password' => 'same:password'
            ], [
                'konfirmasi_password.same' => 'Gagal: Password baru dan Konfirmasi tidak cocok!'
            ]);
            $data_update['password'] = Hash::make($request->password);
        }

        $user->update($data_update);

        return redirect()->route('admin.user.index')->with('success', 'Data user berhasil diperbarui!');
    }

    // --- 5. HAPUS USER ---
    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return redirect()->route('admin.user.index')->with('success', 'User berhasil dihapus!');
    }
}