<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminProfilController extends Controller
{
    public function index()
    {
        $admin = Auth::user();

        // Kalkulasi Statistik Admin
        $totalPegawai = DB::table('pegawai')->count();
        $totalUnit = DB::table('unit_kompetensi')->count();
        $totalKUK = DB::table('aktivitas_kompeten')->count();

        return view('admin.profil.index', compact('admin', 'totalPegawai', 'totalUnit', 'totalKUK'));
    }
}