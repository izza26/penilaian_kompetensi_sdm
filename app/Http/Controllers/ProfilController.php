<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfilController extends Controller
{
    public function index()
    {
        // 1. Ambil data pimpinan yang sedang login
        $pimpinan = Auth::user();

        // 2. Kalkulasi Statistik Kinerja Pimpinan[cite: 32]
        // Total Penilaian yang sudah diselesaikan
        $totalPenilaian = DB::table('penilaian_header')
            ->where('status', 'Selesai')
            ->count();

        // Total Pegawai Unik yang pernah dinilai
        $totalPegawaiDinilai = DB::table('penilaian_header')
            ->where('status', 'Selesai')
            ->distinct('pegawai_id')
            ->count('pegawai_id');

        // Rata-rata Skor Total yang diberikan
        $avg_skor = DB::table('penilaian_header')
            ->where('status', 'Selesai')
            ->avg('nilai_akhir');
            
        $rataRataSkor = round((float)$avg_skor, 1);

        return view('pimpinan.profil.index', compact('pimpinan', 'totalPenilaian', 'totalPegawaiDinilai', 'rataRataSkor'));
    }
}