<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminHasilKompetensiController extends Controller
{
    // --- 1. TAMPILAN INDEX HASIL KOMPETENSI ---
    public function index()
    {
        // Statistik Admin[cite: 45]
        $total_pegawai = DB::table('pegawai')->where('role', 'pegawai')->count();
        $jml_kompeten = DB::table('penilaian_header')->where('status', 'Selesai')->where('nilai_akhir', '>=', 70)->count();
        $jml_belum = DB::table('penilaian_header')->where('status', 'Selesai')->where('nilai_akhir', '<', 70)->count();
        $rata_db = DB::table('penilaian_header')->where('status', 'Selesai')->avg('nilai_akhir');
        $rata_rata = $rata_db ? round($rata_db, 1) : 0;

        // Tarik Data Tabel[cite: 45]
        $list_hasil = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('ph.penilaian_id', 'p.pegawai_id', 'p.pegawai_nama', 'p.jabatan', 'uk.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori')
            ->orderBy('ph.waktu_submit', 'desc')
            ->paginate(10);

        return view('admin.hasil_kompetensi.index', compact('total_pegawai', 'jml_kompeten', 'jml_belum', 'rata_rata', 'list_hasil'));
    }

    // --- 2. TAMPILAN DETAIL HASIL KOMPETENSI ---
    // --- 2. TAMPILAN DETAIL HASIL KOMPETENSI ---
    public function show($id)
    {
        // Perbaikan: Kita jadikan penilaian_header sebagai tabel utama
        // dan mengambil skor & status langsung dari penilaian_header
        $data = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.penilaian_id', $id)
            ->select(
                'p.pegawai_nama', 
                'p.jabatan', 
                'p.unit_kerja', 
                'uk.kode_unit', 
                'ph.nilai_akhir as skor_akhir_360', 
                'ph.kategori as status_kompeten', 
                'ph.waktu_submit', 
                'ph.catatan_umum'
            )
            ->first();

        if (!$data) {
            return redirect()->route('admin.hasil_kompetensi.index')->with('error', 'Data tidak ditemukan.');
        }

        $data->nama_elemen = DB::table('elemen_kompetensi')->where('kode_unit', $data->kode_unit)->value('elemen_kompetensi');

        // Ambil Rincian Skor per Aktivitas
        $list_nilai = DB::table('penilaian_detail as pd')
            ->join('aktivitas_kompeten as ak', 'pd.aktivitas_id', '=', 'ak.aktivitas_id')
            ->where('pd.penilaian_id', $id)
            ->select('ak.detail_aktivitas', 'pd.skor_final')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        return view('admin.hasil_kompetensi.show', compact('data', 'list_nilai'));
    }

    // --- 3. EXPORT EXCEL ---
    public function exportExcel()
    {
        $list_data = DB::table('penilaian_header as ph')
            ->join('pegawai as p', 'ph.pegawai_id', '=', 'p.pegawai_id')
            ->join('unit_kompetensi as uk', 'ph.kode_unit', '=', 'uk.kode_unit')
            ->where('ph.status', 'Selesai')
            ->select('p.pegawai_nama', 'p.jabatan', 'uk.kode_unit', 'uk.judul_unit', 'ph.nilai_akhir', 'ph.kategori', 'ph.waktu_submit', 'ph.rekomendasi')
            ->orderBy('p.jabatan', 'asc')
            ->orderBy('p.pegawai_nama', 'asc')
            ->get();

        return response(view('pimpinan.hasil_kompetensi.excel', ['list_data' => $list_data, 'periode' => 'Semua Pegawai']))
            ->header('Content-Type', 'application/vnd-ms-excel')
            ->header('Content-Disposition', 'attachment; filename="Rekap_Penilaian_Museum_Geologi.xls"')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}