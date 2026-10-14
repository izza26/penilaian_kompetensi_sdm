<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // 1. Pastikan sudah login, kalau belum lempar ke halaman login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // 2. Ambil role user, kecilkan huruf, dan bersihkan spasi
        $user = Auth::user();
        $userRole = strtolower(trim($user->role ?? ''));

        // 3. SUPERADMIN ADALAH DEWA 👑
        // Jika dia superadmin dan mencoba masuk ke wilayah 'admin', izinkan!
        if ($userRole === 'superadmin' && in_array('admin', $roles)) {
            return $next($request);
        }

        // 4. Cek role normal (apakah role user ada di daftar izin rute)
        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        // 5. JIKA DITOLAK (ANTI-LOOPING) 🛑
        // Jangan lempar sembarangan, kembalikan ke habitat (dashboard) masing-masing
        if ($userRole === 'superadmin' || $userRole === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($userRole === 'pimpinan' || $userRole === 'asesor') {
            return redirect()->route('pimpinan.dashboard');
        } else {
            return redirect()->route('pegawai.dashboard');
        }
    }
}
