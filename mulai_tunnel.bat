@echo off
chcp 65001 >nul
cd /d "%~dp0"
title Geotrax Tunnel - JANGAN DITUTUP

set "CF_EXE=C:\Program Files (x86)\cloudflared\cloudflared.exe"

echo ============================================================
echo   GEOTRAX - LANGKAH 2: MEMBUKA TUNNEL PUBLIK
echo ============================================================
echo.
echo   Jalankan LANGKAH 1 dulu (mulai_api.bat) sebelum ini.
echo.

if not exist "%CF_EXE%" (
    echo [X] Tidak menemukan cloudflared di:
    echo     %CF_EXE%
    echo.
    pause
    exit /b 1
)

echo   Membuat alamat publik... tunggu sampai muncul kotak berisi
echo   alamat berakhiran .trycloudflare.com
echo.
echo   ------------------------------------------------------------------
echo   PENTING:
echo     - Salin alamat DI DALAM KOTAK itu (bukan IP angka).
echo     - Tambahkan /api/rubrik/sync di belakangnya, lalu tempel
echo       sebagai nilai API_URL di Apps Script.
echo     - Alamat ini BERUBAH setiap kali tunnel dijalankan ulang.
echo     - Jendela ini JANGAN DITUTUP selama demo.
echo   ------------------------------------------------------------------
echo.

"%CF_EXE%" tunnel --url http://127.0.0.1:8000 --no-autoupdate

echo.
echo   Tunnel berhenti. Alamat publik sudah tidak berlaku.
pause
