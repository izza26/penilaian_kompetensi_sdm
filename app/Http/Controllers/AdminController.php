<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        $nama_admin = Auth::user()->pegawai_nama ?? 'Administrator';

        // 1. KPI Admin
        $tot_pegawai = DB::table('pegawai')->count();
        $tot_masuk   = DB::table('penilaian_header')->count();
        $tot_tunggu  = DB::table('penugasan_penilai')->where('status_penugasan', 'Menunggu')->count();
        $tot_selesai = DB::table('penilaian_header')->where('status', 'Selesai')->count();

        // 2. Statistik Kompetensi (Persentase)[cite: 42]
        $tot_elemen = DB::table('rekap_elemen_360')->count();
        $kompeten   = DB::table('rekap_elemen_360')->where('status_kompeten', 'Kompeten')->count();
        $cukup      = DB::table('rekap_elemen_360')->where('status_kompeten', 'Cukup Kompeten')->count();
        $bina       = DB::table('rekap_elemen_360')->where('status_kompeten', 'Perlu Pembinaan')->count();

        $pct_kompeten = ($tot_elemen > 0) ? round(($kompeten / $tot_elemen) * 100) : 0;
        $pct_cukup    = ($tot_elemen > 0) ? round(($cukup / $tot_elemen) * 100) : 0;
        $pct_bina     = ($tot_elemen > 0) ? round(($bina / $tot_elemen) * 100) : 0;

        // 3. Query Aktivitas Terbaru (Pegawai Baru)[cite: 42]
        $aktivitas = DB::table('pegawai')->orderBy('pegawai_id', 'desc')->limit(4)->get();

        setlocale(LC_TIME, 'id_ID.utf8', 'Indonesian');
        $tanggalSekarang = \Carbon\Carbon::now()->translatedFormat('l, d F Y');

        return view('admin.dashboard', compact(
            'nama_admin', 'tanggalSekarang', 'tot_pegawai', 'tot_masuk', 
            'tot_tunggu', 'tot_selesai', 'tot_elemen', 'pct_kompeten', 'pct_cukup', 'pct_bina', 'aktivitas'
        ));
    }
}