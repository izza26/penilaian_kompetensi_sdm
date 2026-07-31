<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userRole = trim(strtolower(Auth::user()->role));

        if ($userRole !== $role) {
            // Jika role tidak sesuai, lempar ke dashboard masing-masing
            if ($userRole === 'admin') return redirect()->route('admin.dashboard');
            if ($userRole === 'pimpinan') return redirect()->route('pimpinan.dashboard');
            return redirect()->route('pegawai.dashboard');
        }

        return $next($request);
    }
}