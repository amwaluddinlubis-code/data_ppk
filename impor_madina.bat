@echo off
chcp 65001 >nul
cd /d %~dp0
echo ==================================================
echo   IMPOR DATA MADINA KE APLIKASI PEMILIH PPK
echo   (basis data baru + skema final wilayah)
echo ==================================================
echo.

if not exist "%~dp0MADINA.xlsx" (
  echo BERKAS MADINA.xlsx TIDAK DITEMUKAN di folder ini.
  echo Salin dulu MADINA.xlsx ke folder: %~dp0
  echo.
  pause
  exit /b 1
)

rem --- Cari PHP: utamakan Laragon, cadangan PATH ---
set PHP=
for /d %%D in ("C:\laragon\bin\php\php-*") do if exist "%%D\php.exe" set PHP=%%D\php.exe
if "%PHP%"=="" (
  where php >nul 2>nul
  if not errorlevel 1 set PHP=php
)
if "%PHP%"=="" (
  echo PHP tidak ditemukan. Pastikan Laragon terpasang,
  echo atau jalankan berkas ini dari Terminal Laragon.
  echo.
  pause
  exit /b 1
)
echo PHP dipakai: %PHP%
echo.

echo [1/3] Menjalankan migrasi basis data...
"%PHP%" spark migrate
if errorlevel 1 (
  echo.
  echo MIGRASI GAGAL. Impor dibatalkan.
  echo Pastikan basis data pada .env sudah dibuat (kosong tidak apa-apa).
  pause
  exit /b 1
)
echo.

echo [2/3] Menyiapkan akun admin awal (dilewati bila sudah ada)...
"%PHP%" spark db:seed PenggunaAwalSeeder
echo.

echo [3/3] Mengimpor MADINA.xlsx ...
echo Proses ini memakan waktu sekitar 5-15 menit untuk 339 ribu baris.
echo JANGAN tutup jendela ini selama proses berjalan.
echo.
"%PHP%" spark impor:massal "%~dp0MADINA.xlsx" --prov="SUMATERA UTARA" --kab="MANDAILING NATAL"
if errorlevel 1 (
  echo.
  echo IMPOR GAGAL. Baca pesan kesalahan di atas.
  pause
  exit /b 1
)

echo.
echo ==================================================
echo   IMPOR SELESAI
echo   Periksa angka rekonsiliasi per kecamatan di atas:
echo   total masuk seharusnya 338.108 baris,
echo   847 baris teralih (NIK ganda aktif), 4 baris ditolak.
echo   Buka aplikasi, dasbor kabupaten akan menampilkan
echo   23 kecamatan. Masuk awal: admin / admin123
echo   (Bapak akan diminta mengganti sandi).
echo ==================================================
pause
