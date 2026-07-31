<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManajemenEvidenceController extends Controller
{
    // --- 1. TAMPILAN HALAMAN MANAJEMEN EVIDENCE ---
    public function index()
    {
        // Tarik data evidence wajib dan kelompokkan berdasarkan aktivitas_id
        $ew_raw = DB::table('evidence_wajib')->orderBy('evidence_wajib_id', 'asc')->get();
        $ew_data = [];
        foreach ($ew_raw as $row) {
            $ew_data[$row->aktivitas_id][] = $row->nama_evidence;
        }

        // Tarik data aktivitas yang aktif
        $q_data = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('ak.aktif', 'Y')
            ->select('uk.kode_unit', 'uk.judul_unit', 'ak.aktivitas_id', 'ak.detail_aktivitas', 'ak.kriteria_kompetens', 'ak.jumlah_evidence_wa')
            ->orderBy('uk.kode_unit', 'asc')
            ->orderBy('ak.aktivitas_id', 'asc')
            ->get();

        $dataGrouped = [];
        foreach ($q_data as $row) {
            $unitKey = $row->kode_unit . "|||" . $row->judul_unit;
            $dataGrouped[$unitKey][] = $row;
        }

        return view('pimpinan.manajemen_evidence.index', compact('dataGrouped', 'ew_data'));
    }

    // --- 2. HALAMAN FORM ATUR EVIDENCE DINAMIS ---
    public function edit($id)
    {
        $aktivitas = DB::table('aktivitas_kompeten as ak')
            ->join('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->join('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('ak.aktivitas_id', $id)
            ->select('ak.*', 'ek.kode_unit', 'uk.judul_unit')
            ->first();

        if (!$aktivitas) {
            return redirect()->route('pimpinan.manajemen_evidence.index')->with('error', 'Aktivitas tidak ditemukan!');
        }

        $existing_evidences = DB::table('evidence_wajib')
            ->where('aktivitas_id', $id)
            ->orderBy('evidence_wajib_id', 'asc')
            ->get();

        return view('pimpinan.manajemen_evidence.edit', compact('aktivitas', 'existing_evidences'));
    }

    // --- 3. PROSES SIMPAN FORM DINAMIS BERSARANG ---
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Hapus evidence lama
            DB::table('evidence_wajib')->where('aktivitas_id', $id)->delete();
            
            $jumlah_berhasil = 0;
            
            // Loop tiap Grup Evidence dari form
            if ($request->has('nama_evidence') && is_array($request->nama_evidence)) {
                foreach ($request->nama_evidence as $group_inputs) {
                    if (is_array($group_inputs)) {
                        $valid_items = [];
                        foreach ($group_inputs as $item) {
                            $val = trim($item);
                            if ($val !== '') $valid_items[] = $val;
                        }
                        
                        // Gabungkan pakai Newline (\n) dan simpan
                        if (!empty($valid_items)) {
                            $nama_bersih = implode("\n", $valid_items);
                            $new_id = DB::table('evidence_wajib')->max('evidence_wajib_id') + 1;

                            DB::table('evidence_wajib')->insert([
                                'evidence_wajib_id' => $new_id,
                                'aktivitas_id' => $id,
                                'bukti_id' => 0,
                                'nama_evidence' => $nama_bersih,
                                'jenis_file_allowed' => 'PDF, DOC, JPG, PNG',
                                'mandatory' => 'Y',
                                'akt_aktivitas_id' => $id
                            ]);
                            $jumlah_berhasil++;
                        }
                    }
                }
            }
            
            // Update total target di aktivitas
            DB::table('aktivitas_kompeten')->where('aktivitas_id', $id)->update([
                'jumlah_evidence_wa' => $jumlah_berhasil
            ]);

            DB::commit();
            return redirect()->route('pimpinan.manajemen_evidence.index')->with('success', 'Daftar kebutuhan evidence telah diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat menyimpan data!');
        }
    }
}