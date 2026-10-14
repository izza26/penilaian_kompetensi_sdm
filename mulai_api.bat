@echo off
chcp 65001 >nul
cd /d "%~dp0"

set "PHP_EXE=D:\xampp\php\php.exe"
set "PORT=8000"
set "URL_STATUS=http://127.0.0.1:%PORT%/api/rubrik/status"
set "URL_APP=http://127.0.0.1:%PORT%/"
set "CURL_EXE=%SystemRoot%\System32\curl.exe"
set "TIMEOUT_EXE=%SystemRoot%\System32\timeout.exe"

title Geotrax - Menyalakan API

echo ============================================================
echo   GEOTRAX - LANGKAH 1: MENYALAKAN API
echo ============================================================
echo.
echo   Aplikasi : %URL_APP%
echo   API      : %URL_STATUS%
echo.

if not exist "%PHP_EXE%" (
    echo [X] PHP tidak ditemukan di: %PHP_EXE%
    echo     Periksa instalasi XAMPP Anda.
    echo.
    pause
    exit /b 1
)

echo   Menyalakan server di jendela terpisah...
start "Geotrax API (server) - JANGAN DITUTUP" cmd /k ""%PHP_EXE%" artisan serve --host=127.0.0.1 --port=%PORT%"

echo   Menunggu server siap...
set /a n=0

:wait
"%TIMEOUT_EXE%" /t 1 /nobreak >nul 2>&1
set /a n+=1
if exist "%CURL_EXE%" (
    "%CURL_EXE%" -s -o nul -m 3 "%URL_STATUS%" 2>nul && goto siap
) else (
    if %n% geq 8 goto siap
)
if %n% lss 20 goto wait
goto gagal

:siap
echo.
echo   [OK] Server siap setelah %n% detik.
echo.
echo   Membuka browser...
start "" "%URL_STATUS%"
"%TIMEOUT_EXE%" /t 2 /nobreak >nul 2>&1
start "" "%URL_APP%"
echo.
echo ------------------------------------------------------------
echo   API SUDAH JALAN.
echo.
echo   Jendela "Geotrax API (server) - JANGAN DITUTUP" yang
echo   baru terbuka itu adalah mesin API-nya.
echo   JANGAN DITUTUP selama demo.
echo.
echo   Setelah demo selesai, tutup jendela server tersebut
echo   untuk mematikan API.
echo ------------------------------------------------------------
echo.
pause
exit /b 0

:gagal
echo.
echo   [!] Server belum merespons setelah %n% detik.
echo.
echo   Kemungkinan penyebab:
echo     1. Port %PORT% sudah dipakai program lain
echo     2. Ada pesan error di jendela "Geotrax API (server)"
echo.
echo   Alternatif yang tidak butuh port sama sekali:
echo     jalankan  cek_api.bat
echo.
pause
exit /b 1
