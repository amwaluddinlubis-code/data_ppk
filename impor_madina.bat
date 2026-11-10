@echo off
chcp 65001 >nul
cd /d %~dp0
if "%~1"=="__log" goto utama

rem --- Jalankan ulang diri sendiri sambil mencatat seluruh keluaran
rem --- ke hasil_impor.txt, agar pesan kesalahan tidak hilang walau
rem --- jendela tertutup.
echo Impor berjalan... seluruh keluaran dicatat ke hasil_impor.txt
echo (Berkas itu bertambah isinya selama proses; boleh dibuka untuk memantau.)
"%~f0" __log > "%~dp0hasil_impor.txt" 2>&1
set "KODE_KELUAR=%ERRORLEVEL%"
echo.
echo ================= HASIL IMPOR =================
type "%~dp0hasil_impor.txt"
echo.
echo Hasil lengkap juga tersimpan di berkas hasil_impor.txt
echo di folder ini - kirimkan berkas itu bila impor gagal.
pause
exit /b %KODE_KELUAR%

:utama

rem ==================================================
rem   IMPOR DATA MADINA KE APLIKASI PEMILIH PPK
rem   (basis data baru + skema final wilayah)
rem ==================================================

if not exist "%~dp0MADINA.xlsx" (
  echo BERKAS MADINA.xlsx TIDAK DITEMUKAN di folder ini.
  echo Salin dulu MADINA.xlsx ke folder: %~dp0
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
  exit /b 1
)
echo.

echo [2/3] Menyiapkan akun admin awal (dilewati bila sudah ada)...
"%PHP%" spark db:seed PenggunaAwalSeeder
if errorlevel 1 (
  echo.
  echo PENYIAPAN AKUN ADMIN GAGAL. Impor dibatalkan.
  exit /b 1
)
echo.

echo [3/3] Mengimpor MADINA.xlsx ...
echo Proses ini memakan waktu sekitar 5-15 menit untuk 339 ribu baris.
echo JANGAN tutup jendela ini selama proses berjalan.
echo.
"%PHP%" spark impor:massal "%~dp0MADINA.xlsx" --prov="SUMATERA UTARA" --kab="MANDAILING NATAL"
if errorlevel 1 (
  echo.
  echo IMPOR GAGAL. Baca pesan kesalahan di atas.
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
exit /b 0
