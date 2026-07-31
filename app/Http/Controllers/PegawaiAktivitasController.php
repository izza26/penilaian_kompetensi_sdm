<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;

class PegawaiAktivitasController extends Controller
{
    // --- 1. TAMPILAN DAFTAR AKTIVITAS SAYA (aktivitas_saya.php) ---
    public function index()
    {
        $pegawai = Auth::user();
        $jabatan = $pegawai->jabatan ?? 'Belum Ada Jabatan';

        // Cek Periode Penilaian
        $periodeAktif = DB::table('periode_penilaian')->where('status_aktif', 'Y')->first();
        $is_open = false;
        $pesan_periode = "Belum ada periode penilaian yang aktif saat ini.";
        $badge_class = "danger";

        if ($periodeAktif) {
            $tgl_mulai = strtotime($periodeAktif->tanggal_mulai);
            $tgl_selesai = strtotime($periodeAktif->tanggal_selesai . ' 23:59:59');
            $sekarang = time();

            if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) {
                $is_open = true;
                $pesan_periode = "Periode Pengisian: <b>" . date('d M Y', $tgl_mulai) . "</b> s/d <b>" . date('d M Y', $tgl_selesai) . "</b>.";
                $badge_class = "success";
            } elseif ($sekarang < $tgl_mulai) {
                $pesan_periode = "Periode Pengisian baru akan dibuka pada tanggal <b>" . date('d M Y', $tgl_mulai) . "</b>.";
                $badge_class = "warning";
            } else {
                $pesan_periode = "Periode Pengisian telah DITUTUP sejak <b>" . date('d M Y', $tgl_selesai) . "</b>.";
                $badge_class = "danger";
            }
        }

        // Query Data Aktivitas (menggunakan Query Builder)
        $sqlData = "
            SELECT 
                ak.aktivitas_id, ak.detail_aktivitas, ak.jumlah_evidence_wa, 
                ek.elemen_kompetensi, uk.kode_unit, uk.judul_unit,
                (SELECT COUNT(*) FROM bukti_pegawai bp WHERE bp.aktivitas_id = ak.aktivitas_id AND bp.pegawai_id = ?) as is_uploaded
            FROM aktivitas_kompeten ak
            LEFT JOIN elemen_kompetensi ek ON ak.elemen_id = ek.elemen_id
            LEFT JOIN unit_kompetensi uk ON ek.kode_unit = uk.kode_unit
            WHERE uk.posisi_target ILIKE ? AND ak.aktif = 'Y'
            ORDER BY uk.kode_unit ASC, ek.elemen_id ASC, ak.aktivitas_id ASC
        ";
        $aktivitas_raw = DB::select($sqlData, [$pegawai->pegawai_id, "%" . $jabatan . "%"]);

        // Grouping Data
        $dataGrouped = [];
        foreach ($aktivitas_raw as $row) {
            $unitKey = $row->kode_unit . "|||" . $row->judul_unit;
            $row->status_asli = ($row->is_uploaded > 0) ? "Diproses" : "Belum Dimulai";
            $row->classStatus = ($row->is_uploaded > 0) ? "warning" : "danger";
            $dataGrouped[$unitKey][] = $row;
        }

        return view('pegawai.aktivitas.index', compact('periodeAktif', 'is_open', 'pesan_periode', 'badge_class', 'dataGrouped', 'jabatan'));
    }

    // --- 2. HALAMAN DETAIL & UPLOAD (aktivitas_detail.php) ---
    public function show($id)
    {
        $pegawai = Auth::user();
        $jabatan = $pegawai->jabatan ?? 'Belum Ada Jabatan';

        // Tarik Detail Aktivitas
        $detail = DB::table('aktivitas_kompeten as ak')
            ->leftJoin('elemen_kompetensi as ek', 'ak.elemen_id', '=', 'ek.elemen_id')
            ->leftJoin('unit_kompetensi as uk', 'ek.kode_unit', '=', 'uk.kode_unit')
            ->where('ak.aktivitas_id', $id)
            ->where('uk.posisi_target', 'ILIKE', "%" . $jabatan . "%")
            ->select('ak.*', 'ek.elemen_kompetensi', 'ek.kode_elemen_excel', 'uk.judul_unit', 'uk.kode_unit')
            ->first();

        if (!$detail) return redirect()->route('pegawai.aktivitas.index')->with('error', 'Aktivitas ini tidak ditugaskan untuk posisi Anda.');

        $target_evidence = (int)$detail->jumlah_evidence_wa;

        // Cek Periode
        $periodeAktif = DB::table('periode_penilaian')->where('status_aktif', 'Y')->first();
        $is_open = false;
        if ($periodeAktif) {
            $tgl_mulai = strtotime($periodeAktif->tanggal_mulai);
            $tgl_selesai = strtotime($periodeAktif->tanggal_selesai . ' 23:59:59');
            $sekarang = time();
            if ($sekarang >= $tgl_mulai && $sekarang <= $tgl_selesai) { $is_open = true; }
        }

        // Logic Slot Upload (Sama persis dengan PHP Lamamu)
        $listEW = DB::table('evidence_wajib')->where('aktivitas_id', $id)->orderBy('evidence_wajib_id', 'asc')->get();
        $listBukti = DB::table('bukti_pegawai')->where('aktivitas_id', $id)->where('pegawai_id', $pegawai->pegawai_id)->orderBy('tanggal_upload', 'asc')->get();

        $buktiMapped = [];
        $buktiGeneric = [];
        foreach ($listBukti as $b) {
            if (!empty($b->evidence_wajib_id) && $b->evidence_wajib_id != 0) $buktiMapped[$b->evidence_wajib_id] = $b;
            else $buktiGeneric[] = $b;
        }

        $slots = [];
        if ($listEW->count() > 0) {
            foreach ($listEW as $ew) {
                $slots[] = ['id' => $ew->evidence_wajib_id, 'nama' => $ew->nama_evidence, 'format' => $ew->jenis_file_allowed, 'data' => $buktiMapped[$ew->evidence_wajib_id] ?? null];
            }
        } else {
            for ($i = 0; $i < $target_evidence; $i++) {
                $slots[] = ['id' => 0, 'nama' => 'Dokumen Evidence ' . ($i + 1), 'format' => 'PDF, DOC, JPG, PNG', 'data' => $buktiGeneric[$i] ?? null];
            }
        }

        // Slot Ekstra jika melebihi target
        $sisa_generic_terupload = count($buktiGeneric) - ($target_evidence > $listEW->count() ? $target_evidence : 0);
        if ($sisa_generic_terupload > 0) {
            $startIndex = ($target_evidence > $listEW->count()) ? $target_evidence : $listEW->count();
            for ($j = 0; $j < count($buktiGeneric); $j++) {
                if ($j >= $startIndex || $listEW->count() > 0) {
                    $slots[] = ['id' => 0, 'nama' => 'Dokumen Ekstra (Opsional)', 'format' => 'Telah diunggah', 'data' => $buktiGeneric[$j]];
                }
            }
        }

        return view('pegawai.aktivitas.show', compact('detail', 'is_open', 'slots', 'id'));
    }

    // --- 3. PROSES UPLOAD FILE ---
    public function upload(Request $request, $id)
    {
        // Pengecekan Periode di Controller (Keamanan Ekstra)
        $periodeAktif = DB::table('periode_penilaian')->where('status_aktif', 'Y')->first();
        if ($periodeAktif && (time() < strtotime($periodeAktif->tanggal_mulai) || time() > strtotime($periodeAktif->tanggal_selesai . ' 23:59:59'))) {
            return back()->with('error', 'Gagal! Periode ditutup.');
        }

        // Validasi File
        $request->validate([
            'file_evidence' => 'required|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120' // Max 5MB
        ], [
            'file_evidence.mimes' => 'Ekstensi file tidak diperbolehkan! Gunakan PDF, JPG, PNG, DOC.',
            'file_evidence.max' => 'Ukuran file terlalu besar! Maksimal 5 MB.'
        ]);

        $file = $request->file('file_evidence');
        $pegawai = Auth::user();
        
        $nama_file_baru = "EVI_" . $pegawai->pegawai_id . "_" . time() . "_" . rand(10,99) . "." . $file->getClientOriginalExtension();
        
        // Pindahkan ke folder public/uploads/evidence
        $file->move(public_path('uploads/evidence'), $nama_file_baru);

        $new_id = DB::table('bukti_pegawai')->max('bukti_id') + 1;
        DB::table('bukti_pegawai')->insert([
            'bukti_id' => $new_id,
            'file_path' => $nama_file_baru,
            'status_validasi' => 'Menunggu Review',
            'pegawai_id' => $pegawai->pegawai_id,
            'evidence_wajib_id' => $request->evidence_wajib_id,
            'tanggal_upload' => Carbon::now(),
            'aktivitas_id' => $id
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    // --- 4. PROSES HAPUS FILE ---
    public function destroyBukti($id, $bukti_id)
    {
        $pegawai = Auth::user();
        $file_hapus = DB::table('bukti_pegawai')
            ->where('bukti_id', $bukti_id)
            ->where('pegawai_id', $pegawai->pegawai_id)
            ->first();

        if ($file_hapus && in_array($file_hapus->status_validasi, ['Menunggu Review', 'Revisi'])) {
            $path = public_path('uploads/evidence/' . $file_hapus->file_path);
            if (File::exists($path)) {
                File::delete($path);
            }
            DB::table('bukti_pegawai')->where('bukti_id', $bukti_id)->delete();
            return back()->with('success', 'Dokumen berhasil dihapus.');
        }

        return back()->with('error', 'File tidak bisa dihapus karena sudah divalidasi Pimpinan.');
    }
}