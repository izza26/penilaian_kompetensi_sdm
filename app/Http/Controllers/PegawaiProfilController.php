<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PegawaiProfilController extends Controller
{
    public function index()
    {
        $pegawai = Auth::user();

        // Mengganti Dummy Data menjadi Real Data berdasarkan aktivitas[cite: 41]
        $totalEvidence = DB::table('bukti_pegawai')->where('pegawai_id', $pegawai->pegawai_id)->count();
        $totalKompetensi = DB::table('penilaian_header')->where('pegawai_id', $pegawai->pegawai_id)->count();
        $totalKompeten = DB::table('penilaian_header')
            ->where('pegawai_id', $pegawai->pegawai_id)
            ->whereIn('kategori', ['Kompeten', 'Sangat Kompeten'])
            ->count();

        return view('pegawai.profil.index', compact('pegawai', 'totalEvidence', 'totalKompetensi', 'totalKompeten'));
    }
}