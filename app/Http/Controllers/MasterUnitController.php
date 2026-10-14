<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UnitKompetensi;

class MasterUnitController extends Controller
{
    public function index()
    {
        // Hanya tarik Elemen yang berakhiran 'A' (Abaikan yang 'B')
        $data_hirarki = UnitKompetensi::with(['elemen' => function($q) {
            $q->where('kode_elemen_excel', 'ILIKE', '%A')
              ->orderBy('elemen_id', 'asc');
        }, 'elemen.aktivitas' => function($q) {
            $q->orderBy('aktivitas_id', 'asc');
        }])->orderBy('kode_unit', 'asc')->get();

        return view('pimpinan.master_unit.index', compact('data_hirarki'));
    }
}