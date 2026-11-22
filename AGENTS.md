# AGENTS.md — Aturan Pengembangan Aplikasi Pemilih PPK (`pemilurb`)

Dokumen ini wajib dibaca dan diikuti sebelum mengubah repo ini. Isinya
merangkum konvensi yang sudah berjalan di proyek; bila ragu, ikuti pola
yang sudah ada di kode dan README, bukan pola dari proyek lain.

## Tentang proyek

Aplikasi analisis data pemilih tingkat PPK dari hasil coklit Pantarlih
dan PDPB: daftar MS/TMS, pindah TPS, pemilih baru, kegandaan NIK,
tahapan DPS–DPSHP–DPT, DPTb/DPK, cetak formulir lampiran PKPU 1/2025,
ekspor-banding Sidalih, dukungan Bawaslu, dan pelindungan data pribadi.
Riwayat pengembangan per fase didokumentasikan di `README.md`; manual
pengguna ada di `docs/Manual_App_Pemilih.md`.

## Teknologi

- CodeIgniter **4.7.6**, PHP **^8.2** (teruji 8.3 & 8.4; CI menguji 8.3 dan 8.4).
- MySQL/MariaDB lewat driver MySQLi.
- Tampilan: **Tailwind CSS 3 + DaisyUI 4** terkompilasi lokal di
  `public/assets/css/app.css` — **tanpa CDN**. jQuery + DataTables
  (server-side) untuk tabel data. PDF memakai Dompdf ^3.0.

## Menjalankan lokal

```bash
composer install
cp .env.example .env   # isi baseURL + kredensial database
php spark migrate
php spark db:seed DataAwalSeeder
php spark db:seed PenggunaAwalSeeder
php spark serve
```

Akun awal dari seeder: `admin` / `admin123` — aplikasi memaksa ganti
sandi saat pertama masuk. Konfigurasi (baseURL, database) **hanya**
dari `.env`; file `.env` tidak pernah masuk repo.

## Aturan wajib

1. **Route eksplisit.** Semua route didaftarkan di
   `app/Config/Routes.php`; auto-routing mati dan tidak boleh
   dinyalakan. Endpoint privat selalu di balik filter `auth`;
   pembatasan peran memakai argumen filter (mis. `auth:admin`) atau
   pemeriksaan peran di server — tidak cukup hanya menyembunyikan menu.
2. **Skema hanya lewat migrasi baru.** Jangan mengedit migrasi yang
   sudah pernah dijalankan. Migrasi baseline bersifat toleran (objek
   yang sudah ada dilewati) — pertahankan sifat itu agar basis data
   hasil impor dump tetap bisa di-`migrate`.
3. **Hormati cakupan wilayah.** Saringan kecamatan aktif dan cakupan
   pengguna dikelola satu tempat di `App\Libraries\Wilayah` (keadaan
   sesi di `BaseController::$kec`/`$kab`). Jangan menulis query data
   pemilih tanpa saringan wilayah yang berlaku. Definisi kode
   keterangan pemilih hidup di `App\Libraries\Keterangan`.
4. **Peran pengguna.** `admin` (semua), `operator` (olah data
   wilayahnya), `viewer`/Pemeriksa (baca + cetak, data sensitif
   disamarkan). Penyamaran data untuk Pemeriksa dan gerbang peran
   tidak boleh dilemahkan.
5. **Data pribadi itu sensitif.** NIK dan data pemilih asli tidak
   boleh masuk repo, fixture tes, tangkapan layar, atau log. Akses ke
   data sensitif tercatat lewat mekanisme log akses yang ada; tampilkan
   data seperlunya saja.
6. **CRUD coklit meninggalkan jejak.** Perubahan dan penghapusan data
   coklit tercatat di Riwayat dan baris terhapus dapat dipulihkan —
   jangan menghapus permanen (hard delete) baris data pemilih.
7. **Keamanan form standar.** Setiap form POST memakai `csrf_field()`;
   keluaran ke tampilan di-`esc()`; query memakai parameter terikat.
   Sandi pengguna selalu hash bcrypt; sandi awal/reset wajib diganti
   saat masuk berikutnya (`harus_ganti_sandi`).
8. **CSS dibangun, bukan diedit.** Ubah sumber di `tools/tailwind/`,
   lalu bangun ulang dari folder itu:
   `npx tailwindcss -c tailwind.config.js -i input.css -o ../../public/assets/css/app.css --minify`.
   Jangan mengedit langsung hasil kompilasi `public/assets/css/app.css`.
9. **Nama warisan dipertahankan.** Tabel asli (`mhc`, `tms8`, `to_sql`,
   `config`) dan view turunannya adalah kontrak dengan data lapangan
   dan impor Sidalih — jangan mengganti nama atau bentuknya tanpa
   migrasi dan penyesuaian impor/ekspor terkait.
10. **Cetak mengikuti formulir resmi.** Keluaran cetak adalah replika
    formulir lampiran PKPU 1/2025 (Kelompok 1–3 dan BA pleno);
    perubahan tata letak harus tetap setia pada formulir sumbernya.
11. **Tes sebelum commit.** `composer test` (PHPUnit) harus hijau.
    Logika baru yang bisa diuji sebagai unit — terutama di
    `app/Libraries/` — disertai tes unit di `tests/` (pola Fase F).
    CI juga menjalankan `composer validate --strict` dan build
    Tailwind; jangan sampai ketiganya merah.
12. **Dokumentasi mengikuti fitur.** Fitur atau perubahan perilaku
    dicatat di `README.md` (bagian status fase) dan, bila menyentuh
    cara pakai, di `docs/Manual_App_Pemilih.md`.
13. **Bahasa & commit.** Antarmuka, pesan, dokumentasi, dan pesan
    commit dalam Bahasa Indonesia. Pesan commit deskriptif per
    fase/fitur (mis. `Fase D + E PKPU: ...`, `CI: ...`,
    `Dokumentasi: ...`). Perubahan sekecil mungkin yang menyelesaikan
    kebutuhan; jangan mencampur perbaikan tak terkait dalam satu commit.
14. **Git.** Jangan commit, push, atau mengubah branch tanpa diminta
    pemilik repo. `.env`, `vendor/`, isi `writable/` (cache, log,
    sesi, unggahan, backup), dan `tools/tailwind/node_modules/` tidak
    masuk repo (lihat `.gitignore`).

## Peta singkat

- `app/Controllers/` — satu controller per modul (Pemilih, Coklit,
  Tahapan, Pdpb, Dptb, Sidalih, Bawaslu, Laporan, dst.).
- `app/Libraries/` — logika bersama: `Wilayah` (cakupan), `Keterangan`
  (kode keterangan), dan pustaka fitur 2026.
- `app/Database/Migrations/` — sumber kebenaran skema (baseline +
  migrasi per fase). `app/Database/Seeds/` — `DataAwalSeeder`
  (konfigurasi awal) dan `PenggunaAwalSeeder` (akun awal).
- `tests/` — suite PHPUnit (`composer test`).
- `tools/` — sumber Tailwind dan peralatan impor; impor satu kabupaten
  lewat konsol (`app/Commands/ImporMassal.php`, dibantu
  `impor_madina.bat` di Windows).
