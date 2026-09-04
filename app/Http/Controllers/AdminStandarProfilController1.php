<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminStandarProfilController extends Controller
{
    // --- 1. TAMPILAN HALAMAN STANDAR PROFIL ---
    public function index(Request $request)
    {
        $jabatan_terpilih = $request->jabatan ?? '';
        
        // Daftar jabatan standar di Museum Geologi
        $list_jabatan = [
            'Kurator', 'Edukator', 'Konservator', 
            'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'
        ];

        $list_aktivitas = [];
        $bobot_ncf = 60; // Set default agar view tidak error
        $bobot_nsf = 40; // Set default agar view tidak error

        if ($jabatan_terpilih) {
            // Tarik aktivitas dari DB TANPA memanggil kolom target_skor & jenis_faktor
            $db_aktivitas = DB::table('unit_kompetensi as uk')
                ->join('elemen_kompetensi as ek', 'uk.kode_unit', '=', 'ek.kode_unit')
                ->join('aktivitas_kompeten as ak', 'ek.elemen_id', '=', 'ak.elemen_id')
                ->where('uk.posisi_target', 'ILIKE', "%{$jabatan_terpilih}%")
                ->where('uk.aktif', 'Y')
                ->select(
                    'uk.kode_unit', 'ak.aktivitas_id', 'ak.detail_aktivitas'
                )
                ->orderBy('uk.kode_unit', 'asc')
                ->orderBy('ak.aktivitas_id', 'asc')
                ->get();

            // Ambil data DUMMY yang disimpan di Laravel Session (jika Admin sudah pernah nge-save)
            $session_standar = Session::get("standar_profil_{$jabatan_terpilih}", []);

            foreach ($db_aktivitas as $ak) {
                $id = $ak->aktivitas_id;
                
                // Jika data ada di session, pakai itu. Jika belum ada, pakai nilai default (4 dan Core)
                $ak->target_skor = $session_standar[$id]['target'] ?? 4; 
                $ak->jenis_faktor = $session_standar[$id]['faktor'] ?? 'Core';
                
                $list_aktivitas[] = $ak;
            }
        }

        // --- TAMBAHAN UNTUK TAB 2 (MASTER BOBOT GAP) ---
        $list_bobot_gap = DB::table('p_master_bobot_gap')->orderBy('gap', 'desc')->get();

        return view('admin.standar_profil.index', compact(
            'list_jabatan', 'jabatan_terpilih', 'list_aktivitas', 'bobot_ncf', 'bobot_nsf', 'list_bobot_gap'
        ));
    }

    // --- 2. PROSES SIMPAN PENGATURAN STANDAR (KE DALAM SESSION) ---
    public function store(Request $request)
    {
        $jabatan = $request->jabatan;
        $targets = $request->target; // Array dari input target
        $faktors = $request->faktor; // Array dari input jenis faktor

        if ($targets && $faktors) {
            $standar_data = [];
            
            // Format ulang array untuk disimpan ke session
            foreach ($targets as $aktivitas_id => $nilai_target) {
                $standar_data[$aktivitas_id] = [
                    'target' => $nilai_target,
                    'faktor' => $faktors[$aktivitas_id] ?? 'Core'
                ];
            }
            
            // Simpan ke Session Laravel. Ini bertindak sebagai Dummy Database!
            Session::put("standar_profil_{$jabatan}", $standar_data);

            return back()->with('success', "Standar profil untuk jabatan $jabatan berhasil diperbarui (Disimpan di Memori Sementara)!");
        }

        return back()->with('error', "Data aktivitas tidak ditemukan.");
    }

    // --- 3. SIMPAN MASTER BOBOT GAP (TAB 2) ---
    public function simpanBobotGap(Request $request)
    {
        DB::beginTransaction();
        try {
            if ($request->has('bobot')) {
                foreach ($request->bobot as $gap => $nilai_bobot) {
                    DB::table('p_master_bobot_gap')
                        ->where('gap', $gap)
                        ->update([
                            'bobot' => (float) $nilai_bobot,
                            'keterangan' => $request->keterangan[$gap] ?? ''
                        ]);
                }
            }
            
            DB::commit();
            return redirect()->back()->with('success', 'Master Bobot Gap berhasil diperbarui!')->withFragment('tab-gap');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Gagal menyimpan bobot gap!')->withFragment('tab-gap');
        }
    }
}