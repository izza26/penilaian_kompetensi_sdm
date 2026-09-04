<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Ambil data admin dari tabel yang benar
        $userLogin = Auth::user();
        $admin = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        $nama_admin = $admin->pegawai_nama ?? 'Administrator HR';

        // 1. KPI Admin (Disesuaikan dengan tabel geotrax_v3 yang baru)
        // Mengecualikan akun admin dari hitungan total pegawai
        $tot_pegawai = DB::table('pegawai')->where('role', '!=', 'admin')->count(); 
        $tot_masuk   = DB::table('penilaian_header')->count();
        
        // Mengganti penugasan_penilai dengan menghitung jumlah bukti yang belum direview pimpinan
        $tot_tunggu  = DB::table('geotrax_v3.bukti_pegawai')->where('status_validasi', 'Menunggu Review')->count(); 
        
        $tot_selesai = DB::table('penilaian_header')->where('status', 'Selesai')->count();

        // 2. Statistik Kompetensi (Diambil dari tabel penilaian_header)
        $tot_elemen = DB::table('penilaian_header')->count();
        $kompeten   = DB::table('penilaian_header')->whereIn('kategori', ['Sangat Kompeten', 'Kompeten'])->count();
        $cukup      = DB::table('penilaian_header')->where('kategori', 'Cukup Kompeten')->count();
        $bina       = DB::table('penilaian_header')->where('kategori', 'Belum Kompeten')->count();

        $pct_kompeten = ($tot_elemen > 0) ? round(($kompeten / $tot_elemen) * 100) : 0;
        $pct_cukup    = ($tot_elemen > 0) ? round(($cukup / $tot_elemen) * 100) : 0;
        $pct_bina     = ($tot_elemen > 0) ? round(($bina / $tot_elemen) * 100) : 0;

        // 3. Query Aktivitas Terbaru (Pegawai Baru Terdaftar, kecuali admin)
        $aktivitas = DB::table('pegawai')->where('role', '!=', 'admin')->orderBy('pegawai_id', 'desc')->limit(4)->get();

        setlocale(LC_TIME, 'id_ID.utf8', 'Indonesian');
        $tanggalSekarang = \Carbon\Carbon::now()->translatedFormat('l, d F Y');

        return view('admin.dashboard', compact(
            'nama_admin', 'tanggalSekarang', 'tot_pegawai', 'tot_masuk', 
            'tot_tunggu', 'tot_selesai', 'tot_elemen', 'pct_kompeten', 'pct_cukup', 'pct_bina', 'aktivitas'
        ));
    }
}