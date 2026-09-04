<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MasterKoleksiController extends Controller
{
    public function index(Request $request)
    {
        $userLogin = Auth::user();
        $pegawai = DB::table('geotrax_v3.pegawai_skkni')->where('username', $userLogin->username)->first();
        
        // Ambil jabatan huruf kecil semua agar mudah dicek
        $jabatan = strtolower($pegawai->jabatan ?? '');
        $cari = $request->cari;
        
        $data = [];
        $jenis_koleksi = []; 
        $view_title = 'Master Data';
        $view_subtitle = 'Direktori log data sebagai referensi penyusunan evidence.';

        // 1. JIKA JABATAN KONSERVATOR
        if (str_contains($jabatan, 'konservator')) {
            $view_title = 'Master Konservasi Koleksi';
            $query = DB::table('geotrax_v3.pemantauan_kondisi_koleksi');
            if ($cari) {
                $query->where('nomor_registrasi', 'ILIKE', "%$cari%")
                      ->orWhere('gejala_kerusakan_spesifik', 'ILIKE', "%$cari%");
            }
            $data = $query->paginate(10);
        } 
        // 2. JIKA JABATAN PENATA PAMERAN
        elseif (str_contains($jabatan, 'penata pameran')) {
            $view_title = 'Master Proyek Pameran';
            $query = DB::table('geotrax_v3.proyek_pameran');
            if ($cari) {
                $query->where('nama_pameran', 'ILIKE', "%$cari%");
            }
            $data = $query->paginate(10);
        }
        // 3. JIKA JABATAN EDUKATOR
        elseif (str_contains($jabatan, 'edukator')) {
            $view_title = 'Master Program Edukasi Publik';
            $query = DB::table('geotrax_v3.program_publik');
            if ($cari) {
                $query->where('tujuan_program', 'ILIKE', "%$cari%");
            }
            $data = $query->paginate(10);
        }
        // 4. JIKA JABATAN HUMAS
        elseif (str_contains($jabatan, 'humas')) {
            $view_title = 'Master Kampanye & Publikasi';
            $query = DB::table('geotrax_v3.kampanye_publikasi_museum');
            if ($cari) {
                $query->where('judul_tema_publikasi', 'ILIKE', "%$cari%");
            }
            $data = $query->paginate(10);
        }
        // 5. DEFAULT (REGISTER / KURATOR)
        else {
            $view_title = 'Master Koleksi Museum';
            $query = DB::table('geotrax_v3.koleksi')
                ->leftJoin('geotrax_v3.master_jenis_koleksi', 'koleksi.id_jenis', '=', 'master_jenis_koleksi.id_jenis')
                ->select('koleksi.*', 'master_jenis_koleksi.kategori_koleksi');
            
            if ($cari) {
                $query->where('nama_koleksi', 'ILIKE', "%$cari%")
                      ->orWhere('nomor_registrasi', 'ILIKE', "%$cari%");
            }
            if ($request->jenis) {
                $query->where('koleksi.id_jenis', $request->jenis);
            }
            
            $data = $query->paginate(10);
            $jenis_koleksi = DB::table('geotrax_v3.master_jenis_koleksi')->get();
        }

        $total_semua = $data->total();

        return view('pegawai.koleksi.index', compact('data', 'cari', 'total_semua', 'jabatan', 'jenis_koleksi', 'view_title', 'view_subtitle'));
    }
}