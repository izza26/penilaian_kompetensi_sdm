<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Jadwal sinkronisasi rubrik
|--------------------------------------------------------------------------
| Aktif hanya kalau RUBRIK_AUTO_SYNC=true di .env.
| Supaya benar-benar jalan, daftarkan "php artisan schedule:run" tiap menit
| di Windows Task Scheduler (lihat docs/RUBRIK_SYNC.md).
*/
if (config('rubrik.auto_sync', false)) {
    Schedule::command('rubrik:sync')
        ->everyFifteenMinutes()
        ->withoutOverlapping()
        ->runInBackground();
}
