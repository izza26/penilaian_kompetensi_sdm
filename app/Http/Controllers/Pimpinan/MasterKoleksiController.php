<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterKoleksiController extends Controller
{
    public function index(Request $request)
    {
        // 1. Rekap Atas
        $rekap = DB::table('geotrax_v3.koleksi as k')
            ->join('geotrax_v3.master_jenis_koleksi as jk', 'k.id_jenis', '=', 'jk.id_jenis')
            ->select('jk.kategori_koleksi', DB::raw('count(k.nomor_registrasi) as total'))
            ->groupBy('jk.kategori_koleksi')
            ->orderBy('jk.kategori_koleksi')
            ->get();

        $total_semua = DB::table('geotrax_v3.koleksi')->count();

        // 2. Data Master untuk Dropdown Filter & Modal Edit
        $jenis_koleksi = DB::table('geotrax_v3.master_jenis_koleksi')->orderBy('kategori_koleksi')->get();

        // 3. Query Utama
        $query = DB::table('geotrax_v3.koleksi as k')
                   ->leftJoin('geotrax_v3.master_jenis_koleksi as jk', 'k.id_jenis', '=', 'jk.id_jenis')
                   ->select('k.*', 'jk.kategori_koleksi');

        if ($request->has('cari') && $request->cari != '') {
            $cari = $request->cari;
            $query->where(function($q) use ($cari) {
                $q->where('k.nama_koleksi', 'ILIKE', "%{$cari}%")
                  ->orWhere('k.nomor_registrasi', 'ILIKE', "%{$cari}%");
            });
        }

        if ($request->has('jenis') && $request->jenis != '') {
            $query->where('k.id_jenis', $request->jenis);
        }

        $koleksi = $query->orderBy('k.tanggal_masuk', 'DESC')->paginate(15);

        return view('pimpinan.koleksi.index', compact('koleksi', 'rekap', 'jenis_koleksi', 'total_semua'));
    }

    public function show($id)
    {
        $koleksi = DB::table('geotrax_v3.koleksi as k')
                   ->leftJoin('geotrax_v3.master_jenis_koleksi as jk', 'k.id_jenis', '=', 'jk.id_jenis')
                   ->select('k.*', 'jk.kategori_koleksi')
                   ->where('k.nomor_registrasi', $id)
                   ->first();

        if (!$koleksi) {
            return redirect()->route('pimpinan.koleksi.index')->with('error', 'Koleksi tidak ditemukan.');
        }

        return view('pimpinan.koleksi.show', compact('koleksi'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'nomor_registrasi' => 'required',
            'nama_koleksi' => 'required',
            'id_jenis' => 'required',
            'status_koleksi' => 'required'
        ]);

        DB::table('geotrax_v3.koleksi')
            ->where('nomor_registrasi', $request->nomor_registrasi)
            ->update([
                'nama_koleksi' => $request->nama_koleksi,
                'id_jenis' => $request->id_jenis,
                'deskripsi' => $request->deskripsi,
                'status_koleksi' => $request->status_koleksi,
                'updated_at' => now()
            ]);

        return redirect()->back()->with('success', 'Data koleksi berhasil diperbarui!');
    }
}