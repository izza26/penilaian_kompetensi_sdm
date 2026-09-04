<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitKompetensi;
use App\Models\ElemenKompetensi;
use App\Models\AktivitasKompeten;
use App\Models\PeriodePenilaian;

// --- DUA BARIS PENYELAMAT INI HARUS ADA DI SINI! ---
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ManajemenPenilaianController extends Controller
{
    // --- 1. TAMPILAN HALAMAN MANAJEMEN PENILAIAN ---
    // --- 1. TAMPILAN HALAMAN MANAJEMEN PENILAIAN ---
    public function periodeIndex()
    {
        $userLogin = Auth::user();
        if (isset($userLogin->jabatan)) { $pimpinan = $userLogin; } 
        else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
        }

        // PERUBAHAN URUTAN: Sesuai hierarki SKKNI Museum (UK 001 s/d 034)
        $posisi_list = [
            'Kurator',
            'Register',
            'Konservator',
            'Edukator',
            'Penata Pameran',
            'Hubungan Masyarakat dan Pemasaran'
        ];

        // KUNCI PERBAIKAN: Memaksa hasil database agar diurutkan sesuai array $posisi_list di atas
        $periods = DB::table('geotrax_v3.periode_penilaian')
                     ->whereIn('nama_periode', $posisi_list)
                     ->get()
                     ->sortBy(function($item) use ($posisi_list) {
                         return array_search($item->nama_periode, $posisi_list);
                     });

        $all_uks = DB::table('geotrax_v3.unit_kompetensi')
                     ->orderBy('kode_unit', 'asc')
                     ->get();

        return view('pimpinan.manajemen_penilaian.periode', compact('periods', 'posisi_list', 'all_uks'));
    }

    // --- 3. HALAMAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasIndex(Request $request)
    {
        $jabatan = $request->jabatan;
        if (!$jabatan) return redirect()->route('pimpinan.manajemen_penilaian.periode');

        $display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;

        // PERUBAHAN: Hanya menarik Elemen yang berakhiran 'A' (Membuang elemen 1B, 2B, dst)
        $data = UnitKompetensi::with(['elemen' => function($q) {
                $q->where('kode_elemen_excel', 'ILIKE', '%A')->orderBy('kode_elemen_excel', 'asc');
            }, 'elemen.aktivitas' => function($q) {
                $q->orderBy('aktivitas_id', 'asc');
            }])
            ->where('posisi_target', 'ILIKE', "%{$jabatan}%")
            ->where('aktif', 'Y')
            ->orderBy('kode_unit', 'asc')
            ->get();

        return view('pimpinan.manajemen_penilaian.aktivitas', compact('data', 'jabatan', 'display_jabatan'));
    }

    // --- 2. AKSI MANAJEMEN PENILAIAN (TAMBAH, EDIT TANGGAL, HAPUS) ---
    public function periodeAction(\Illuminate\Http\Request $request)
    {
        $action = $request->action;

        $userLogin = \Illuminate\Support\Facades\Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->orWhere('username', $nip)->first();
        }
        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
        
        $allowed_roles = [];
        if (str_contains($jabatanPimpinan, 'Kepala Museum')) {
            $allowed_roles = ['Kurator', 'Konservator', 'Register', 'Edukator', 'Penata Pameran', 'Hubungan Masyarakat dan Pemasaran'];
        } elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) {
            $allowed_roles = ['Konservator', 'Register'];
        } elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) {
            $allowed_roles = ['Edukator', 'Penata Pameran'];
        } elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) {
            $allowed_roles = ['Hubungan Masyarakat dan Pemasaran'];
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $allowed_roles = ['Kurator'];
        }

        // AKSI 1: TAMBAH/MULAI PERIODE BARU
        if ($action == 'tambah_jabatan_baru') {
            $jabatan = $request->jabatan;
            
            if ($jabatan == 'Semua Jabatan') {
                $tgl_mulai = $request->tgl_mulai_serentak;
                $tgl_selesai = $request->tgl_selesai_serentak;
                
                foreach ($allowed_roles as $role) {
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $role)->update(['status_aktif' => 'N']);
                    
                    $new_id = (\Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') ?? 0) + 1;
                    
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 
                        'peserta_id' => 0, 
                        'nama_periode' => $role,
                        'tanggal_mulai' => $tgl_mulai ?? '', // FIX: Gunakan kutip kosong, bukan null
                        'tanggal_selesai' => $tgl_selesai ?? '', // FIX: Gunakan kutip kosong, bukan null
                        'status_aktif' => 'Y'
                    ]);
                }
                return redirect()->back()->with('success', 'Periode serentak dimulai! Data history sebelumnya aman diarsipkan.');
            } else {
                if(in_array($jabatan, $allowed_roles)) {
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->update(['status_aktif' => 'N']);
                    
                    $new_id = (\Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') ?? 0) + 1;
                    
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 
                        'peserta_id' => 0, 
                        'nama_periode' => $jabatan,
                        'tanggal_mulai' => '', // FIX: Gunakan kutip kosong, bukan null
                        'tanggal_selesai' => '', // FIX: Gunakan kutip kosong, bukan null
                        'status_aktif' => 'Y'
                    ]);
                    return redirect()->back()->with('success', 'Periode baru dimulai! Data history sebelumnya aman diarsipkan.');
                }
                return redirect()->back()->with('error', 'Akses ditolak! Anda tidak memiliki wewenang untuk jabatan ini.');
            }
        } 
        
        // AKSI 2: UPDATE TANGGAL
        elseif ($action == 'update_tanggal') {
            $jabatan = $request->jabatan;
            if(in_array($jabatan, $allowed_roles)) {
                $periodeLama = \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->where('status_aktif', 'Y')->first();
                
                if ($periodeLama && empty($periodeLama->tanggal_mulai)) {
                     \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('periode_id', $periodeLama->periode_id)
                        ->update(['tanggal_mulai' => $request->tanggal_mulai, 'tanggal_selesai' => $request->tanggal_selesai]);
                } else {
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->update(['status_aktif' => 'N']);
                    
                    $new_id = (\Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') ?? 0) + 1;
                    
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 
                        'peserta_id' => 0, 
                        'nama_periode' => $jabatan,
                        'tanggal_mulai' => $request->tanggal_mulai, 
                        'tanggal_selesai' => $request->tanggal_selesai, 
                        'status_aktif' => 'Y'
                    ]);
                }
                return redirect()->back()->with('success', 'Tanggal ditetapkan! History periode sebelumnya aman.');
            }
            return redirect()->back()->with('error', 'Akses ditolak! Anda tidak memiliki wewenang.');
        }
        
        // AKSI 3: HAPUS PERIODE
        elseif ($action == 'hapus_periode') {
            $jabatan = $request->jabatan;
            if(in_array($jabatan, $allowed_roles)) {
                \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->where('status_aktif', 'Y')->delete();
                return redirect()->back()->with('success', 'Periode aktif dibatalkan. Data history lama tidak terpengaruh.');
            }
            return redirect()->back()->with('error', 'Akses ditolak! Anda tidak memiliki wewenang.');
        }
        
        return redirect()->back();
    }


    // --- 4. PROSES SIMPAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasStore(Request $request)
    {
        $jabatan = $request->jabatan;
        $aktivitas_terpilih = $request->aktivitas_aktif ?? [];

        $unit_codes = UnitKompetensi::where('posisi_target', 'ILIKE', "%{$jabatan}%")->pluck('kode_unit');
        $elemen_ids = ElemenKompetensi::whereIn('kode_unit', $unit_codes)->pluck('elemen_id');
        AktivitasKompeten::whereIn('elemen_id', $elemen_ids)->update(['aktif' => 'N']);

        if (!empty($aktivitas_terpilih)) {
            AktivitasKompeten::whereIn('aktivitas_id', $aktivitas_terpilih)->update(['aktif' => 'Y']);
        }

        return back()->with('success', 'Kustomisasi aktivitas untuk jabatan ini telah diperbarui.');
    }
}