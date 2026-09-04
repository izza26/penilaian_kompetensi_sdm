<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitKompetensi;
use App\Models\ElemenKompetensi;
use App\Models\AktivitasKompeten;
use Illuminate\Support\Facades\DB;

class AdminMasterUnitController extends Controller
{
    public function index()
    {
        $data_hirarki = UnitKompetensi::with(['elemen' => function($q) {
            $q->orderBy('elemen_id', 'asc');
        }, 'elemen.aktivitas' => function($q) {
            $q->orderBy('aktivitas_id', 'asc');
        }])->orderBy('kode_unit', 'asc')->get();

        return view('admin.master_unit.index', compact('data_hirarki'));
    }

    public function create()
    {
        $db_jenis = UnitKompetensi::whereNotNull('jenis_kompetensi')->where('jenis_kompetensi', '!=', '')->distinct()->pluck('jenis_kompetensi')->toArray();
        $default_jenis = ['Manajerial', 'Teknis/Digital', 'Layanan'];
        $all_jenis = array_unique(array_merge($default_jenis, $db_jenis));
        sort($all_jenis);

        return view('admin.master_unit.create', compact('all_jenis'));
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $cek = UnitKompetensi::where('kode_unit', $request->kode_unit)->first();
            if ($cek) throw new \Exception("Kode Unit '{$request->kode_unit}' sudah ada!");

            UnitKompetensi::create([
                'kode_unit' => $request->kode_unit, 'jabatan_unit_id' => 1, 'rekap_unit_id' => 0,
                'judul_unit' => $request->judul_unit, 'jenis_kompetensi' => $request->jenis_kompetensi,
                'sumber_skkni' => '-', 'aktif' => 'Y'
            ]);

            if ($request->has('elemen_kode')) {
                foreach ($request->elemen_kode as $i => $kode_el) {
                    if (!$kode_el) continue;
                    $new_el_id = ElemenKompetensi::max('elemen_id') + 1;
                    
                    ElemenKompetensi::create([
                        'elemen_id' => $new_el_id, 'kode_unit' => $request->kode_unit, 'rekap_elemen_id' => 1,
                        'kode_elemen_excel' => $kode_el, 'elemen_kompetensi' => $request->elemen_judul[$i],
                        'input' => $request->elemen_input[$i] ?? null, 'output' => $request->elemen_output[$i] ?? null,
                        'outcome' => $request->elemen_outcome[$i] ?? null, 'uni_kode_unit' => $request->kode_unit
                    ]);

                    if (isset($request->aktivitas_id[$i])) {
                        foreach ($request->aktivitas_id[$i] as $j => $akt_id) {
                            if (!$akt_id) continue;
                            AktivitasKompeten::create([
                                'aktivitas_id' => $akt_id, 'elemen_id' => $new_el_id, 'bukti_id' => 0, 'rekap_aktivitas_id' => 0,
                                'detail_aktivitas' => $request->aktivitas_detail[$i][$j], 'jumlah_evidence_wa' => $request->aktivitas_evidence[$i][$j],
                                'kriteria_kompetens' => $request->aktivitas_kriteria[$i][$j], 'bobot_aktivitas' => 0, 'ele_elemen_id' => $new_el_id, 'aktif' => 'Y'
                            ]);
                        }
                    }
                }
            }
            DB::commit();
            return redirect()->route('admin.master_unit.index')->with('success', 'Unit beserta Elemen dan Aktivitas berhasil disimpan!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function action(Request $request)
    {
        $aksi = $request->aksi;
        if ($aksi == 'edit_unit') {
            UnitKompetensi::where('kode_unit', $request->kode_unit_lama)->update(['judul_unit' => $request->judul_unit, 'jenis_kompetensi' => $request->jenis_kompetensi]);
            return back()->with('success', 'Unit berhasil diperbarui');
        } elseif ($aksi == 'hapus_unit') {
            $elemen_ids = ElemenKompetensi::where('kode_unit', $request->hapus_id)->pluck('elemen_id');
            AktivitasKompeten::whereIn('elemen_id', $elemen_ids)->delete(); ElemenKompetensi::where('kode_unit', $request->hapus_id)->delete(); UnitKompetensi::where('kode_unit', $request->hapus_id)->delete();
            return back()->with('success', 'Unit berhasil dihapus');
        } elseif ($aksi == 'tambah_elemen') {
            $new_el_id = ElemenKompetensi::max('elemen_id') + 1;
            ElemenKompetensi::create(['elemen_id' => $new_el_id, 'kode_unit' => $request->kode_unit_parent, 'rekap_elemen_id' => 1, 'kode_elemen_excel' => $request->kode_elemen_excel, 'elemen_kompetensi' => $request->elemen_kompetensi, 'input' => $request->input, 'output' => $request->output, 'outcome' => $request->outcome, 'uni_kode_unit' => $request->kode_unit_parent]);
            return back()->with('success', 'Elemen berhasil ditambahkan');
        } elseif ($aksi == 'edit_elemen') {
            ElemenKompetensi::where('elemen_id', $request->elemen_id)->update(['kode_elemen_excel' => $request->kode_elemen_excel, 'elemen_kompetensi' => $request->elemen_kompetensi, 'input' => $request->input, 'output' => $request->output, 'outcome' => $request->outcome]);
            return back()->with('success', 'Elemen berhasil diperbarui');
        } elseif ($aksi == 'hapus_elemen') {
            AktivitasKompeten::where('elemen_id', $request->hapus_id)->delete(); ElemenKompetensi::where('elemen_id', $request->hapus_id)->delete();
            return back()->with('success', 'Elemen berhasil dihapus');
        } elseif ($aksi == 'tambah_aktivitas') {
            AktivitasKompeten::create(['aktivitas_id' => $request->aktivitas_id, 'elemen_id' => $request->elemen_id_parent, 'bukti_id' => 0, 'rekap_aktivitas_id' => 0, 'bobot_aktivitas' => 0, 'ele_elemen_id' => $request->elemen_id_parent, 'detail_aktivitas' => $request->detail_aktivitas, 'kriteria_kompetens' => $request->kriteria_kompetens, 'jumlah_evidence_wa' => $request->jumlah_evidence, 'aktif' => 'Y']);
            return back()->with('success', 'Aktivitas berhasil ditambahkan');
        } elseif ($aksi == 'edit_aktivitas') {
            AktivitasKompeten::where('aktivitas_id', $request->aktivitas_id_lama)->update(['aktivitas_id' => $request->aktivitas_id, 'detail_aktivitas' => $request->detail_aktivitas, 'kriteria_kompetens' => $request->kriteria_kompetens, 'jumlah_evidence_wa' => $request->jumlah_evidence]);
            return back()->with('success', 'Aktivitas berhasil diperbarui');
        } elseif ($aksi == 'hapus_aktivitas') {
            AktivitasKompeten::where('aktivitas_id', $request->hapus_id)->delete(); return back()->with('success', 'Aktivitas berhasil dihapus');
        }
    }
}