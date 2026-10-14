<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StandarSoftskill;

class AdminSoftskillController extends Controller
{
    public function index()
    {
        $softskills = StandarSoftskill::orderBy('created_at', 'desc')->get();
        return view('admin.master_softskill.index', compact('softskills'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_softskill' => 'required',
            'target_skor' => 'required|numeric|min:1|max:5',
        ]);

        StandarSoftskill::create([
            'nama_softskill' => $request->nama_softskill,
            'target_skor' => $request->target_skor,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->back()->with('success', 'Master Softskill berhasil ditambahkan!');
    }

    public function update(Request $request)
    {
        $request->validate([
            'softskill_id' => 'required',
            'nama_softskill' => 'required',
            'target_skor' => 'required|numeric|min:1|max:5',
        ]);

        $softskill = StandarSoftskill::findOrFail($request->softskill_id);
        $softskill->update([
            'nama_softskill' => $request->nama_softskill,
            'target_skor' => $request->target_skor,
            'deskripsi' => $request->deskripsi,
        ]);

        return redirect()->back()->with('success', 'Master Softskill berhasil diperbarui!');
    }

    public function destroy($id)
    {
        StandarSoftskill::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Master Softskill berhasil dihapus!');
    }
}
