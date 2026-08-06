<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Pegawai;

class HasilKompetensiController extends Controller
{
    private function getFilterBawahan()
    {
        $userLogin = Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = Pegawai::where('nip_nik', $nip)->orWhere('username', $nip)->first();
        }

        $jabatanPimpinan = trim($pimpinan->jabatan ?? '');
        $filterJabatanBawahan = [];

        if (str_contains($jabatanPimpinan, 'Kepala Museum')) {
            $filterJabatanBawahan = ['Koordinator', 'Manajer', 'Kurator'];
        } elseif (str_contains($jabatanPimpinan, 'Registrasi') || str_contains($jabatanPimpinan, 'Konservasi')) {
            $filterJabatanBawahan = ['Konservator', 'Register'];
        } elseif (str_contains($jabatanPimpinan, 'Edukasi') || str_contains($jabatanPimpinan, 'Program Publik')) {
            $filterJabatanBawahan = ['Edukator', 'Penata Pameran'];
        } elseif (str_contains($jabatanPimpinan, 'Humas') || str_contains($jabatanPimpinan, 'Pemasaran')) {
            $filterJabatanBawahan = ['Humas', 'Hubungan Masyarakat'];
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $filterJabatanBawahan = ['Kurator'];
        }

        return $filterJabatanBawahan;
    }

    public function index(Request $request)
    {
        $filter_periode = $request->periode ?? 'ALL';
        $filter_bulan = $request->bulan ?? 'ALL';
        $filter_tahun = $request->tahun ?? 'ALL';

        $filterJabatanBawahan = $this->getFilterBawahan();
        
        // KUNCI PERBAIKAN: Gunakan ID agar kebal dari masalah NIP vs Username
        $userLogin = Auth::user();
        $idPimpinan = $userLogin->pegawai_id;

        $list_periode = DB::table('pegawai')
            ->select('jabatan as nama_periode')
            ->where('pegawai_id', '!=', $idPimpinan) // Pengecualian pakai ID
            ->where(function($q) use ($filterJabatanBawahan) {
                if (empty($filterJabatanBawahan)) {
                    $q->whereRaw('1=0'); 
                } else {
                    foreach ($filterJabatanBawahan as $jab) {
                        $q->orWhere('jabatan', 'LIKE', '%' . $jab . '%');
                    }
                }
            })
            ->distinct()
            ->orderBy('jabatan', 'asc')
            ->get();
        
        $arr_bulan = [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];

        $tahun_sekarang = date('Y');
        $arr_tahun = range(2024, $tahun_sekarang + 1);

        $query = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->where('p.pegawai_id', '!=', $idPimpinan) // Pengecualian pakai ID
            ->select('ph.penilaian_id', 'ph.pegawai_id', 'p.pegawai_nama', 'p.jabatan', 'ph.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.waktu_submit', 'ph.rekomendasi');

        $query->where(function($q) use ($filterJabatanBawahan) {
            if (empty($filterJabatanBawahan)) {
                $q->whereRaw('1=0');
            } else {
                foreach ($filterJabatanBawahan as $jab) {
                    $q->orWhere('p.jabatan', 'LIKE', '%' . $jab . '%');
                }
            }
        });

        if ($filter_periode !== 'ALL') {
            $query->where('p.jabatan', $filter_periode);
        }
        if ($filter_bulan !== 'ALL') {
            $query->whereMonth('ph.waktu_submit', $filter_bulan);
        }
        if ($filter_tahun !== 'ALL') {
            $query->whereYear('ph.waktu_submit', $filter_tahun);
        }

        $list_riwayat = $query->orderBy('ph.waktu_submit', 'desc')->get();

        $stat_sangat_kompeten = $list_riwayat->where('kategori', 'Sangat Kompeten')->count();
        $stat_kompeten = $list_riwayat->where('kategori', 'Kompeten')->count();
        $stat_cukup_kompeten = $list_riwayat->where('kategori', 'Cukup Kompeten')->count();
        $stat_belum_kompeten = $list_riwayat->where('kategori', 'Belum Kompeten')->count();

        return view('pimpinan.hasil_kompetensi.index', compact(
            'list_riwayat', 'list_periode', 'arr_bulan', 'arr_tahun', 
            'filter_periode', 'filter_bulan', 'filter_tahun',
            'stat_sangat_kompeten', 'stat_kompeten', 'stat_cukup_kompeten', 'stat_belum_kompeten'
        ));
    }

    public function show($id)
    {
        $header = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.penilaian_id', $id)
            ->select('ph.*', 'p.pegawai_nama', 'p.jabatan', 'uk.judul_unit')
            ->first();

        if (!$header) return redirect()->route('pimpinan.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');

        $details = DB::table('penilaian_detail as pd')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('pd.penilaian_id', $id)
            ->select('pd.*', 'ak.detail_aktivitas')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        $roles = ['Kurator', 'Edukator', 'Konservator', 'Penata Pameran', 'Register', 'Hubungan Masyarakat dan Pemasaran'];
        $match_scores = [];
        
        srand($header->pegawai_id); 
        foreach($roles as $r) {
            if ($r == $header->jabatan) {
                $match_scores[$r] = $header->nilai_akhir; 
            } else {
                $match_scores[$r] = rand(4000, 8500) / 100;
            }
        }
        srand(); 
        arsort($match_scores);
        $best_match_role = array_key_first($match_scores);
        $is_match = ($best_match_role == $header->jabatan);

        return view('pimpinan.hasil_kompetensi.show', compact('header', 'details', 'match_scores', 'best_match_role', 'is_match'));
    }

    public function destroy($id)
    {
        DB::table('penilaian_detail')->where('penilaian_id', $id)->delete();
        DB::table('penilaian_header')->where('penilaian_id', $id)->delete();
        return redirect()->route('pimpinan.hasil_kompetensi.index')->with('success', 'Riwayat penilaian berhasil dihapus permanen.');
    }

    public function exportExcel(Request $request)
    {
        $periode = $request->periode ?? 'ALL';
        $nama_file = ($periode !== 'ALL') ? "Rekap_Penilaian_" . str_replace(" ", "_", $periode) . ".xls" : "Rekap_Penilaian_Semua_Pegawai.xls";

        $filterJabatanBawahan = $this->getFilterBawahan();
        
        $userLogin = Auth::user();
        $idPimpinan = $userLogin->pegawai_id;

        $query = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->where('p.pegawai_id', '!=', $idPimpinan) // Pengecualian pakai ID
            ->select('ph.penilaian_id', 'p.pegawai_nama', 'p.jabatan', 'ph.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.rekomendasi', 'ph.waktu_submit');

        $query->where(function($q) use ($filterJabatanBawahan) {
            if (empty($filterJabatanBawahan)) {
                $q->whereRaw('1=0');
            } else {
                foreach ($filterJabatanBawahan as $jab) {
                    $q->orWhere('p.jabatan', 'LIKE', '%' . $jab . '%');
                }
            }
        });

        if ($periode !== 'ALL') {
            $query->where('p.jabatan', $periode);
        }
        if ($request->bulan && $request->bulan !== 'ALL') {
            $query->whereMonth('ph.waktu_submit', $request->bulan);
        }
        if ($request->tahun && $request->tahun !== 'ALL') {
            $query->whereYear('ph.waktu_submit', $request->tahun);
        }

        $list_data = $query->orderBy('p.jabatan', 'asc')->orderBy('p.pegawai_nama', 'asc')->orderBy('ph.waktu_submit', 'desc')->get();

        return response(view('pimpinan.hasil_kompetensi.excel', compact('list_data', 'periode')))
            ->header('Content-Type', 'application/vnd-ms-excel')
            ->header('Content-Disposition', 'attachment; filename="'.$nama_file.'"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}