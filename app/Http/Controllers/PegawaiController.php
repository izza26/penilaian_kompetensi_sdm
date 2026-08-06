<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Pegawai;

class PegawaiController extends Controller
{
    // Fungsi bantuan untuk mendapatkan daftar filter jabatan bawahan
    private function getFilterBawahan()
    {
        $userLogin = Auth::user();
        if (isset($userLogin->jabatan)) {
            $pimpinan = $userLogin; 
        } else {
            $nip = $userLogin->username ?? $userLogin->nip_nik;
            $pimpinan = Pegawai::where('nip_nik', $nip)->first();
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
        
        // --- TAMBAHKAN BARIS INI ---
        } elseif (str_contains($jabatanPimpinan, 'Kurator')) {
            $filterJabatanBawahan = ['Kurator'];
        }
        

        return $filterJabatanBawahan;
    }

    // 1. TAMPILKAN SEMUA DATA & PENCARIAN (pegawai.php)
    public function index(Request $request)
    {
        $cari = $request->cari;
        $filterJabatanBawahan = $this->getFilterBawahan();

        // KUNCI PERBAIKAN: Tarik ID Pimpinan yang sedang login
        $userLogin = \Illuminate\Support\Facades\Auth::user();
        $idPimpinan = $userLogin->pegawai_id;

        $query = Pegawai::query();

        // 1. KUNCI UTAMA: Pengecualian menggunakan pegawai_id agar tahan banting
        $query->where('pegawai_id', '!=', $idPimpinan);

        // 2. KUNCI KEDUA: Hanya tampilkan pegawai yang jabatannya sesuai wewenang
        $query->where(function($q) use ($filterJabatanBawahan) {
            if (empty($filterJabatanBawahan)) {
                $q->whereRaw('1=0'); 
            } else {
                foreach ($filterJabatanBawahan as $jab) {
                    $q->orWhere('jabatan', 'LIKE', '%' . $jab . '%');
                }
            }
        });

        if (!empty($cari)) {
            $query->where(function($q) use ($cari) {
                $q->where('pegawai_nama', 'ILIKE', "%$cari%")
                  ->orWhere('nip_nik', 'ILIKE', "%$cari%")
                  ->orWhere('unit_kerja', 'ILIKE', "%$cari%");
            });
        }

        $pegawais = $query->orderBy('pegawai_nama', 'asc')->paginate(10); 

        return view('pimpinan.pegawai.index', compact('pegawais', 'cari'));
    }

    // 2. HALAMAN TAMBAH PEGAWAI (tambah_pegawai.php)
    public function create()
    {
        return view('pimpinan.pegawai.create');
    }

    // 3. PROSES SIMPAN DATA PEGAWAI BARU
    public function store(Request $request)
    {
        $request->validate([
            'nip_nik' => 'required|unique:pegawai,nip_nik',
            'pegawai_nama' => 'required',
            'jabatan' => 'required',
            'unit_kerja' => 'required'
        ]);

        $new_id = Pegawai::max('pegawai_id') + 1;

        Pegawai::create([
            'pegawai_id' => $new_id,
            'nip_nik' => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'password' => \Illuminate\Support\Facades\Hash::make('123456'), // Update: Langsung Hash!
            'role' => 'pegawai',
            'status_aktif' => 'Aktif'
        ]);

        return redirect()->route('pimpinan.pegawai.index')->with('success', 'Data Pegawai berhasil ditambahkan!');
    }

    // 4. HALAMAN DETAIL PEGAWAI (detail_pegawai.php)
    public function show($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('pimpinan.pegawai.show', compact('pegawai'));
    }

    // 5. HALAMAN EDIT PEGAWAI (edit_pegawai.php)
    public function edit($id)
    {
        $pegawai = Pegawai::findOrFail($id);
        return view('pimpinan.pegawai.edit', compact('pegawai'));
    }

    // 6. PROSES UPDATE DATA PEGAWAI
    public function update(Request $request, $id)
    {
        $pegawai = Pegawai::findOrFail($id);
        
        $pegawai->update([
            'nip_nik' => $request->nip_nik,
            'pegawai_nama' => $request->pegawai_nama,
            'email' => $request->email,
            'no_hp' => $request->no_hp,
            'jabatan' => $request->jabatan,
            'unit_kerja' => $request->unit_kerja,
            'status_aktif' => $request->status
        ]);

        return redirect()->route('pimpinan.pegawai.index')->with('success', 'Data Pegawai berhasil diperbarui!');
    }
}