@echo off
chcp 65001 >nul
cd /d "%~dp0"
title Geotrax - Cek API (diagnostik)

set "PHP_EXE=D:\xampp\php\php.exe"

echo ============================================================
echo   GEOTRAX - CEK API TANPA BROWSER
echo ============================================================
echo.
echo   Skrip ini memanggil API dari dalam aplikasi, jadi tetap
echo   bekerja walaupun Apache XAMPP mati atau port 80 terpakai.
echo.

"%PHP_EXE%" cek_api.php
echo.
pause
