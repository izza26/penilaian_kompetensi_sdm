<?php

// Pimpinan
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PegawaiController;
use App\Http\Controllers\MasterUnitController;
use App\Http\Controllers\ManajemenPenilaianController;
use App\Http\Controllers\ManajemenEvidenceController;
use App\Http\Controllers\SkoringController;
use App\Http\Controllers\HasilKompetensiController;
use App\Http\Controllers\ProfilController;

// Pegawai
use App\Http\Controllers\PegawaiAktivitasController;
use App\Http\Controllers\PegawaiPenilaianController;
use App\Http\Controllers\PegawaiProfilController;

// Admin
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminPegawaiController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminMasterUnitController;
use App\Http\Controllers\AdminPenilaianController;
use App\Http\Controllers\AdminProfilController;
use App\Http\Controllers\AdminHasilKompetensiController;
use App\Http\Controllers\AdminStandarProfilController;


// ==========================================
// PINTU GERBANG UTAMA (ANTI-LOOPING)
// ==========================================
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Ambil role-nya, kecilkan hurufnya, dan bersihkan spasi nakal di awal/akhir
        $role = strtolower(trim($user->role ?? ''));

        // Cek berbagai kemungkinan nama role Admin
        if (in_array($role, ['superadmin', 'super admin', 'admin', 'administrator', 'super administrator', 'sa'])) {
            return redirect()->route('admin.dashboard');
        }
        // Cek berbagai kemungkinan nama role Pimpinan
        elseif (in_array($role, ['pimpinan', 'pimpinan unit', 'asesor', 'assessor'])) {
            return redirect()->route('pimpinan.dashboard');
        }
        // Cek role Pegawai biasa
        elseif (in_array($role, ['pegawai', 'user', 'staf', 'staff'])) {
            return redirect()->route('pegawai.dashboard');
        }
        // JIKA ROLE TETAP TIDAK DIKENALI, STOP LOOPING DAN TAMPILKAN ERROR!
        else {
            $roleAsli = $user->role ?? 'KOSONG / NULL';
            \Illuminate\Support\Facades\Auth::logout();
            return response("<div style='font-family:sans-serif; padding:50px; text-align:center;'>
                <h2 style='color:#e11d48;'>Pusaran Redirect Dihentikan! 🛑</h2>
                <p>Sistem bingung karena <b>Role</b> akun Anda di database tertulis sebagai: <b style='background:#fef08a; padding:4px 8px;'>'{$roleAsli}'</b></p>
                <p>Silakan perbaiki data role ini di tabel database menjadi <b>'superadmin'</b> agar sistem bisa membaca dengan benar.</p>
                <a href='/login' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3b82f6; color:white; text-decoration:none; border-radius:8px;'>Kembali ke Login</a>
            </div>", 403);
        }
    }

    // Jika BELUM login, baru lempar ke halaman login
    return redirect()->route('login');
});


// ==========================================
// ROUTE LOGIN & LOGOUT
// ==========================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// Route Register
Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.proses');


// ==========================================
// 1. GROUP ROUTE UNTUK ADMIN & SUPERADMIN
// ==========================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::resource('pegawai', AdminPegawaiController::class);
    // CRUD DATA USER
    Route::resource('user', AdminUserController::class);

    // MASTER UNIT SKKNI (Menggantikan Alur KUK)
    Route::get('/master-unit', [AdminMasterUnitController::class, 'index'])->name('master_unit.index');
    Route::get('/master-unit/create', [AdminMasterUnitController::class, 'create'])->name('master_unit.create');
    Route::post('/master-unit/store', [AdminMasterUnitController::class, 'store'])->name('master_unit.store');
    Route::post('/master-unit/action', [AdminMasterUnitController::class, 'action'])->name('master_unit.action');

    // STANDAR PROFIL JABATAN (Metode PM)
    Route::get('/standar-profil', [AdminStandarProfilController::class, 'index'])->name('standar_profil.index');
    Route::post('/standar-profil/store', [AdminStandarProfilController::class, 'store'])->name('standar_profil.store');
    Route::post('/standar-profil/gap', [AdminStandarProfilController::class, 'simpanBobotGap'])->name('standar_profil.gap.store');

    // AKTIVITAS PENILAIAN (Pemantauan murni oleh Admin)
    Route::get('/penilaian', [AdminPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/detail/{id}', [AdminPenilaianController::class, 'show'])->name('penilaian.show');

    // --- TAKEOVER SUPERADMIN (Numpang pakai controller Pimpinan) ---
    Route::get('/tim-saya', [AdminPenilaianController::class, 'takeoverIndex'])->name('tim_saya.index');
    Route::get('/tim-saya/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'beriNilai'])->name('tim_saya.beri_nilai');
    Route::post('/tim-saya/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'simpanNilai'])->name('tim_saya.simpan_nilai');

    // HASIL KOMPETENSI ADMIN
    Route::get('/hasil-kompetensi', [AdminHasilKompetensiController::class, 'index'])->name('hasil_kompetensi.index');
    Route::get('/hasil-kompetensi/export', [AdminHasilKompetensiController::class, 'exportExcel'])->name('hasil_kompetensi.export');
    Route::get('/hasil-kompetensi/detail/{id}', [AdminHasilKompetensiController::class, 'show'])->name('hasil_kompetensi.show');
    Route::delete('/hasil-kompetensi/{id}', [AdminHasilKompetensiController::class, 'destroy'])->name('hasil_kompetensi.destroy'); // Fungsi Hapus untuk SA

    // PROFIL ADMIN
    Route::get('/profil', [AdminProfilController::class, 'index'])->name('profil.index');

    // PERBAIKAN: MASTER KOLEKSI UNTUK ADMIN
    Route::get('/koleksi', [\App\Http\Controllers\Pimpinan\MasterKoleksiController::class, 'index'])->name('koleksi.index');
    Route::get('/koleksi/{id}/detail', [\App\Http\Controllers\Pimpinan\MasterKoleksiController::class, 'show'])->name('koleksi.show');
});


// ==========================================
// 2. GROUP ROUTE UNTUK PEGAWAI
// ==========================================
Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'pegawai'])->name('dashboard');

    Route::get('/aktivitas-saya', [PegawaiAktivitasController::class, 'index'])->name('aktivitas.index');
    Route::get('/aktivitas-saya/{id}', [PegawaiAktivitasController::class, 'show'])->name('aktivitas.show');
    Route::post('/aktivitas-saya/{id}/upload', [PegawaiAktivitasController::class, 'upload'])->name('aktivitas.upload');
    Route::delete('/aktivitas-saya/{id}/hapus-bukti/{bukti_id}', [PegawaiAktivitasController::class, 'destroyBukti'])->name('aktivitas.destroy_bukti');

    // PENILAIAN 360 PEGAWAI
    Route::get('/penilaian', [PegawaiPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/list', [PegawaiPenilaianController::class, 'list'])->name('penilaian.list');
    Route::get('/penilaian/detail/{aktivitas_id}', [PegawaiPenilaianController::class, 'show'])->name('penilaian.show');

    // HASIL KOMPETENSI (PENILAIAN SAYA)
    Route::get('/hasil-kompetensi', [PegawaiPenilaianController::class, 'hasil'])->name('hasil.index');

    // PROFIL PEGAWAI
    Route::get('/profil', [PegawaiProfilController::class, 'index'])->name('profil.index');

    // Master Koleksi (Read-Only)
    Route::get('/koleksi', [\App\Http\Controllers\Pegawai\MasterKoleksiController::class, 'index'])->name('koleksi.index');
    Route::get('/koleksi/{id}', [\App\Http\Controllers\Pegawai\MasterKoleksiController::class, 'show'])->name('koleksi.show');
});


// ==========================================
// 3. GROUP ROUTE UNTUK PIMPINAN
// ==========================================
Route::middleware(['auth', 'role:pimpinan'])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'pimpinan'])->name('dashboard');
    Route::resource('pegawai', PegawaiController::class);

    // MASTER UNIT SKKNI
    Route::get('/master-unit', [MasterUnitController::class, 'index'])->name('master_unit.index');
    Route::get('/master-unit/create', [MasterUnitController::class, 'create'])->name('master_unit.create');
    Route::post('/master-unit/store', [MasterUnitController::class, 'store'])->name('master_unit.store');
    Route::post('/master-unit/action', [MasterUnitController::class, 'action'])->name('master_unit.action');

    // MANAJEMEN PENILAIAN & PERIODE
    Route::get('/manajemen-penilaian', [ManajemenPenilaianController::class, 'periodeIndex'])->name('manajemen_penilaian.periode');
    Route::post('/manajemen-penilaian/action', [ManajemenPenilaianController::class, 'periodeAction'])->name('manajemen_penilaian.action');

    // KUSTOMISASI AKTIVITAS
    Route::get('/manajemen-penilaian/aktivitas', [ManajemenPenilaianController::class, 'aktivitasIndex'])->name('manajemen_penilaian.aktivitas');
    Route::post('/manajemen-penilaian/aktivitas', [ManajemenPenilaianController::class, 'aktivitasStore'])->name('manajemen_penilaian.aktivitas_store');

    // KELOLA TEMPLATE EVIDENCE
    Route::get('/manajemen-evidence', [ManajemenEvidenceController::class, 'index'])->name('manajemen_evidence.index');
    Route::get('/manajemen-evidence/{id}/edit', [ManajemenEvidenceController::class, 'edit'])->name('manajemen_evidence.edit');
    Route::post('/manajemen-evidence/{id}/update', [ManajemenEvidenceController::class, 'update'])->name('manajemen_evidence.update');

    // TIM SAYA & SKORING
    Route::get('/tim-saya', [SkoringController::class, 'timSaya'])->name('tim_saya.index');
    Route::get('/tim-saya/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'beriNilai'])->name('tim_saya.beri_nilai');
    Route::post('/tim-saya/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'simpanNilai'])->name('tim_saya.simpan_nilai');
    Route::post('/tim-saya/nilai/{pegawai_id}/{kode_unit}/simpan', [SkoringController::class, 'simpanNilai'])->name('tim_saya.simpan_nilai_post');

    // HASIL KOMPETENSI
    Route::get('/hasil-kompetensi', [HasilKompetensiController::class, 'index'])->name('hasil_kompetensi.index');
    Route::get('/hasil-kompetensi/export', [HasilKompetensiController::class, 'exportExcel'])->name('hasil_kompetensi.export');
    Route::get('/hasil-kompetensi/{id}', [HasilKompetensiController::class, 'show'])->name('hasil_kompetensi.show');
    Route::delete('/hasil-kompetensi/{id}', [HasilKompetensiController::class, 'destroy'])->name('hasil_kompetensi.destroy');

    // PROFIL PIMPINAN
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');

    // Master Koleksi (Pimpinan: Read, Update, Detail)
    Route::get('/koleksi', [\App\Http\Controllers\Pimpinan\MasterKoleksiController::class, 'index'])->name('koleksi.index');
    Route::get('/koleksi/{id}/detail', [\App\Http\Controllers\Pimpinan\MasterKoleksiController::class, 'show'])->name('koleksi.show');
    Route::post('/koleksi/update', [\App\Http\Controllers\Pimpinan\MasterKoleksiController::class, 'update'])->name('koleksi.update');
});


// ==========================================
// RUTE GLOBAL & PROXY
// ==========================================
Route::post('/ajax-panggil-ai', [SkoringController::class, 'ajaxPanggilAI'])->name('ajax.panggil.ai');

// RUTE PROXY UNTUK MENGAKALI BLOKIR CORS DARI CLOUD STORAGE SUPABASE
Route::get('/proxy-document', function (\Illuminate\Http\Request $request) {
    $url = $request->query('url');
    if (!$url) return response("URL Kosong", 400);

    $url = urldecode($url);

    try {
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url, ['verify' => false]);

        return response($response->getBody())
                ->header('Content-Type', $response->getHeaderLine('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*');
    } catch (\Exception $e) {
        try {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            if (\Illuminate\Support\Facades\Storage::disk('s3')->exists('uploads/evidence/' . $filename)) {
                $fileContent = \Illuminate\Support\Facades\Storage::disk('s3')->get('uploads/evidence/' . $filename);
                $mimeType = str_ends_with(strtolower($filename), 'docx') ? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' : 'application/msword';
                return response($fileContent)
                        ->header('Content-Type', $mimeType)
                        ->header('Access-Control-Allow-Origin', '*');
            }
        } catch (\Exception $ex) {
            // Abaikan
        }
        return response("Gagal mengambil dokumen dari Cloud.", 500);
    }
})->name('proxy.document');

Route::get('/template-dokumen', function () {
    return view('template_dokumen');
})->name('template.dokumen');
