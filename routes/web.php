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

// Jika buka web pertama kali, langsung arahkan ke login
Route::get('/', function () {
    return redirect()->route('login');
});

// Route Login & Logout
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// Route Register
Route::get('/register', [AuthController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register'])->name('register.proses');

// Group Route untuk ADMIN
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
    // PENILAIAN OLEH ADMIN
    Route::get('/penilaian', [AdminPenilaianController::class, 'index'])->name('penilaian.index');
    Route::get('/penilaian/form/{pegawai_id}/{kode_unit}', [AdminPenilaianController::class, 'form'])->name('penilaian.form');
    Route::post('/penilaian/form/{pegawai_id}/{kode_unit}', [AdminPenilaianController::class, 'store'])->name('penilaian.store');
    Route::get('/penilaian/detail/{id}', [AdminPenilaianController::class, 'show'])->name('penilaian.show');
    // HASIL KOMPETENSI ADMIN
    Route::get('/hasil-kompetensi', [AdminHasilKompetensiController::class, 'index'])->name('hasil_kompetensi.index');
    Route::get('/hasil-kompetensi/export', [AdminHasilKompetensiController::class, 'exportExcel'])->name('hasil_kompetensi.export');
    Route::get('/hasil-kompetensi/detail/{id}', [AdminHasilKompetensiController::class, 'show'])->name('hasil_kompetensi.show');
    // PROFIL ADMIN
    Route::get('/profil', [AdminProfilController::class, 'index'])->name('profil.index');
    Route::get('/standar-profil', [\App\Http\Controllers\AdminStandarProfilController::class, 'index'])->name('standar_profil.index');
    Route::post('/standar-profil/store', [\App\Http\Controllers\AdminStandarProfilController::class, 'store'])->name('standar_profil.store');
});

// Group Route untuk PEGAWAI
Route::middleware(['auth', 'role:pegawai'])->prefix('pegawai')->name('pegawai.')->group(function () {
    
    // Rute Dashboard Pegawai
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'pegawai'])->name('dashboard');
    
    // --- PASTIKAN 4 BARIS INI ADA DI SINI ---
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
    // HASIL KOMPETENSI PEGAWAI
    Route::get('/hasil-kompetensi', [PegawaiPenilaianController::class, 'hasil'])->name('hasil.index');
    // PROFIL PEGAWAI
    Route::get('/profil', [PegawaiProfilController::class, 'index'])->name('profil.index');
});

// Group Route untuk PIMPINAN
Route::middleware(['auth', 'role:pimpinan'])->prefix('pimpinan')->name('pimpinan.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'pimpinan'])->name('dashboard');
    
    // Tambahkan 1 baris sakti ini untuk memanggil seluruh fungsi CRUD!
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
    // SKORING (PENILAIAN EVIDENCE)
    Route::get('/skoring', [SkoringController::class, 'index'])->name('skoring.index');
    Route::get('/skoring/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'beriNilai'])->name('skoring.beri_nilai');
    Route::post('/skoring/nilai/{pegawai_id}/{kode_unit}', [SkoringController::class, 'simpanNilai'])->name('skoring.simpan_nilai');
    // HASIL KOMPETENSI
    Route::get('/hasil-kompetensi', [HasilKompetensiController::class, 'index'])->name('hasil_kompetensi.index');
    Route::get('/hasil-kompetensi/export', [HasilKompetensiController::class, 'exportExcel'])->name('hasil_kompetensi.export');
    Route::get('/hasil-kompetensi/{id}', [HasilKompetensiController::class, 'show'])->name('hasil_kompetensi.show');
    Route::delete('/hasil-kompetensi/{id}', [HasilKompetensiController::class, 'destroy'])->name('hasil_kompetensi.destroy');
    // PROFIL PIMPINAN
    Route::get('/profil', [ProfilController::class, 'index'])->name('profil.index');
});