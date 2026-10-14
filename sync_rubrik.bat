@echo off
REM ============================================================
REM  sync_rubrik.bat
REM  Penjadwal sinkronisasi rubrik dari Google Spreadsheet.
REM  Daftarkan file ini di Windows Task Scheduler agar jalan
REM  otomatis (mis. tiap 15 menit).
REM ============================================================

cd /d "%~dp0"

REM Sesuaikan path php.exe kalau XAMPP ada di lokasi lain
set PHP_EXE=D:\xampp\php\php.exe

"%PHP_EXE%" artisan schedule:run >> "storage\logs\rubrik_scheduler.log" 2>&1
