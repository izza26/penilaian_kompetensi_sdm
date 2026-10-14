<?php

/**
 * config/rubrik.php
 *
 * Konfigurasi sinkronisasi rubrik skor dari Google Spreadsheet ke database.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | ID Google Spreadsheet sumber
    |--------------------------------------------------------------------------
    | Ambil dari URL spreadsheet:
    | https://docs.google.com/spreadsheets/d/<ID_INI>/edit
    */
    'sheet_id' => env('RUBRIK_SHEET_ID', '1aNW4MM2pKhlB-m6Pfqpyu4-81pDIYeHK'),

    /*
    |--------------------------------------------------------------------------
    | URL ekspor (xlsx)
    |--------------------------------------------------------------------------
    | Spreadsheet harus bisa diakses lewat link (share = "Anyone with the link").
    */
    'export_url' => 'https://docs.google.com/spreadsheets/d/%s/export?format=xlsx',

    /*
    |--------------------------------------------------------------------------
    | Token keamanan endpoint
    |--------------------------------------------------------------------------
    | Endpoint sync hanya jalan kalau token cocok. Kosongkan untuk menonaktifkan
    | pemeriksaan (tidak disarankan di server publik).
    */
    'token' => env('RUBRIK_SYNC_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Schema database tujuan
    |--------------------------------------------------------------------------
    | Default mengikuti DB_SCHEMA aplikasi. Schema aktif: geotrax_v4
    | (hasil pembaruan breakdown Excel Pak Unggul + sinkronisasi spreadsheet).
    | geotrax_v3 hanya cadangan/rollback.
    */
    'schema' => env('RUBRIK_SCHEMA', env('DB_SCHEMA', 'geotrax_v4')),

    /*
    |--------------------------------------------------------------------------
    | Nama tabel
    |--------------------------------------------------------------------------
    */
    'table' => 'rubrik_skor',

    /*
    |--------------------------------------------------------------------------
    | Simpan salinan xlsx terakhir
    |--------------------------------------------------------------------------
    */
    'cache_path' => storage_path('app/rubrik'),

    /*
    |--------------------------------------------------------------------------
    | Timeout unduh (detik)
    |--------------------------------------------------------------------------
    */
    'download_timeout' => (int) env('RUBRIK_DOWNLOAD_TIMEOUT', 300),

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi otomatis (penjadwal Laravel)
    |--------------------------------------------------------------------------
    | Set RUBRIK_AUTO_SYNC=true untuk mengaktifkan jadwal tiap 15 menit.
    */
    'auto_sync' => filter_var(env('RUBRIK_AUTO_SYNC', false), FILTER_VALIDATE_BOOL),

    /*
    |--------------------------------------------------------------------------
    | Pangkas baris lama
    |--------------------------------------------------------------------------
    | Kalau true, baris rubrik_skor yang sudah tidak ada lagi di spreadsheet
    | akan dihapus, supaya rubrik yang dipindah/dihapus di spreadsheet tidak
    | tertinggal sebagai data basi. Aman karena kolom deskripsi_skor,
    | aturan_rubrik, dan verifikator selalu kosong dan tidak ada tabel lain
    | yang mereferensikan rubrik_skor.
    |
    | Pengaman: kalau spreadsheet menghasilkan < 50% dari jumlah aktivitas yang
    | sekarang ada di tabel, pemangkasan otomatis dibatalkan (menghindari tabel
    | terkuras saat sheet sedang rusak/kosong).
    */
    'prune' => filter_var(env('RUBRIK_PRUNE', true), FILTER_VALIDATE_BOOL),

];
