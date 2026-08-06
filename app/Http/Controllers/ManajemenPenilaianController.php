<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitKompetensi;
use App\Models\ElemenKompetensi;
use App\Models\AktivitasKompeten;
use App\Models\PeriodePenilaian;

class ManajemenPenilaianController extends Controller
{
    // --- 1. TAMPILAN HALAMAN MANAJEMEN PENILAIAN ---
    public function periodeIndex()
    {
        // Ambil data pimpinan yang login
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
        }

        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
        $allowed_roles = [];

        // Mapping Jabatan Bawahan
        if (str_contains($jabatanPimpinan, 'Kepala Museum')) {
            $allowed_roles = ['Kurator', 'Konservator', 'Register', 'Edukator', 'Penata Pameran', 'Hubungan Masyarakat dan Pemasaran'];
        } elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) {
            $allowed_roles = ['Konservator', 'Register'];
        } elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) {
            $allowed_roles = ['Edukator', 'Penata Pameran'];
        } elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) {
            $allowed_roles = ['Hubungan Masyarakat dan Pemasaran'];
            
        // --- TAMBAHKAN BARIS INI ---
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $allowed_roles = ['Kurator'];
        }

        $posisi_list = $allowed_roles;

        // Tarik data periode terbaru HANYA untuk jabatan bawahan yang diizinkan
        $periods = \Illuminate\Support\Facades\DB::table('periode_penilaian')
            ->whereIn('nama_periode', $allowed_roles)
            ->where('status_aktif', 'Y') // KUNCI PENTING: Hanya panggil yang statusnya Y
            ->get();

        $all_uks = \Illuminate\Support\Facades\DB::table('unit_kompetensi')
            ->where('aktif', 'Y')
            ->get();

        return view('pimpinan.manajemen_penilaian.periode', compact('periods', 'all_uks', 'posisi_list'));
    }

    // --- 2. AKSI MANAJEMEN PENILAIAN (TAMBAH, EDIT TANGGAL, HAPUS) ---
    public function periodeAction(\Illuminate\Http\Request $request)
    {
        $action = $request->action;

        $userLogin = \Illuminate\Support\Facades\Auth::user();
        $nip = $userLogin->username ?? $userLogin->nip_nik;
        $pimpinan = \App\Models\Pegawai::where('nip_nik', $nip)->first();
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
                    // ARSIPKAN LAMA: Set status_aktif = 'N'
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $role)->update(['status_aktif' => 'N']);
                    // BUAT BARU: Set status_aktif = 'Y'
                    $new_id = \Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') + 1;
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 'peserta_id' => 0, 'nama_periode' => $role,
                        'tanggal_mulai' => $tgl_mulai ?? '', 'tanggal_selesai' => $tgl_selesai ?? '', 'status_aktif' => 'Y'
                    ]);
                }
                return redirect()->back()->with('success', 'Periode serentak dimulai! Data history sebelumnya aman diarsipkan.');
            } else {
                if(in_array($jabatan, $allowed_roles)) {
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->update(['status_aktif' => 'N']);
                    $new_id = \Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') + 1;
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 'peserta_id' => 0, 'nama_periode' => $jabatan,
                        'tanggal_mulai' => '', 'tanggal_selesai' => '', 'status_aktif' => 'Y'
                    ]);
                    return redirect()->back()->with('success', 'Periode baru dimulai! Data history sebelumnya aman diarsipkan.');
                }
                return redirect()->back()->with('error', 'Akses ditolak!');
            }
        } 
        
        // AKSI 2: UPDATE TANGGAL (Juga membuat history)
        elseif ($action == 'update_tanggal') {
            $jabatan = $request->jabatan;
            if(in_array($jabatan, $allowed_roles)) {
                $periodeLama = \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->where('status_aktif', 'Y')->first();
                
                // Jika tanggalnya masih kosong (baru pencet tambah), cukup update baris itu
                if ($periodeLama && empty($periodeLama->tanggal_mulai)) {
                     \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('periode_id', $periodeLama->periode_id)
                        ->update(['tanggal_mulai' => $request->tanggal_mulai, 'tanggal_selesai' => $request->tanggal_selesai]);
                } else {
                    // Jika sudah pernah ada tanggal (ganti periode), arsipkan yang lama, buat baru!
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->update(['status_aktif' => 'N']);
                    $new_id = \Illuminate\Support\Facades\DB::table('periode_penilaian')->max('periode_id') + 1;
                    \Illuminate\Support\Facades\DB::table('periode_penilaian')->insert([
                        'periode_id' => $new_id, 'peserta_id' => 0, 'nama_periode' => $jabatan,
                        'tanggal_mulai' => $request->tanggal_mulai, 'tanggal_selesai' => $request->tanggal_selesai, 'status_aktif' => 'Y'
                    ]);
                }
                return redirect()->back()->with('success', 'Tanggal ditetapkan! History periode sebelumnya aman.');
            }
        }
        
        // AKSI 3: HAPUS PERIODE (Hanya membatalkan yang aktif)
        elseif ($action == 'hapus_periode') {
            $jabatan = $request->jabatan;
            if(in_array($jabatan, $allowed_roles)) {
                // KUNCI: Hanya hapus yang berstatus 'Y', membiarkan history 'N' tetap hidup!
                \Illuminate\Support\Facades\DB::table('periode_penilaian')->where('nama_periode', $jabatan)->where('status_aktif', 'Y')->delete();
                return redirect()->back()->with('success', 'Periode aktif dibatalkan. Data history lama tidak terpengaruh.');
            }
        }
        return redirect()->back();
    }

    // --- 3. HALAMAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasIndex(Request $request)
    {
        $jabatan = $request->jabatan;
        if (!$jabatan) return redirect()->route('pimpinan.manajemen_penilaian.periode');

        $display_jabatan = ($jabatan == 'Hubungan Masyarakat dan Pemasaran') ? 'Humas & Pemasaran' : $jabatan;

        // Ambil data hierarki otomatis
        $data = UnitKompetensi::with(['elemen' => function($q) {
                $q->orderBy('kode_elemen_excel', 'asc');
            }, 'elemen.aktivitas' => function($q) {
                $q->orderBy('aktivitas_id', 'asc');
            }])
            ->where('posisi_target', 'ILIKE', "%{$jabatan}%")
            ->where('aktif', 'Y')
            ->orderBy('kode_unit', 'asc')
            ->get();

        return view('pimpinan.manajemen_penilaian.aktivitas', compact('data', 'jabatan', 'display_jabatan'));
    }

    // --- 4. PROSES SIMPAN KUSTOMISASI AKTIVITAS ---
    public function aktivitasStore(Request $request)
    {
        $jabatan = $request->jabatan;
        $aktivitas_terpilih = $request->aktivitas_aktif ?? [];

        // Reset semua aktivitas menjadi 'N' untuk jabatan ini
        $unit_codes = UnitKompetensi::where('posisi_target', 'ILIKE', "%{$jabatan}%")->pluck('kode_unit');
        $elemen_ids = ElemenKompetensi::whereIn('kode_unit', $unit_codes)->pluck('elemen_id');
        AktivitasKompeten::whereIn('elemen_id', $elemen_ids)->update(['aktif' => 'N']);

        // Hidupkan (Set 'Y') hanya yang dicentang
        if (!empty($aktivitas_terpilih)) {
            AktivitasKompeten::whereIn('aktivitas_id', $aktivitas_terpilih)->update(['aktif' => 'Y']);
        }

        return back()->with('success', 'Kustomisasi aktivitas untuk jabatan ini telah diperbarui.');
    }
}