<?php

use App\Http\Controllers\Api\RubrikSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Sinkronisasi Rubrik Skor
|--------------------------------------------------------------------------
| Semua route di sini otomatis ber-prefix /api dan tanpa CSRF token,
| sehingga bisa dipanggil dari Google Apps Script / cron / curl.
*/

Route::prefix('rubrik')->group(function () {
    // Tarik spreadsheet lalu update database
    Route::match(['get', 'post'], 'sync', [RubrikSyncController::class, 'sync']);

    // Unggah file .xlsx langsung
    Route::post('import', [RubrikSyncController::class, 'import']);

    // Info konfigurasi & isi tabel
    Route::get('status', [RubrikSyncController::class, 'status']);
});
