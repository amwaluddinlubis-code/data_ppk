# Aplikasi Pemilih PPK — `pemilurb`

Aplikasi analisis data pemilih tingkat PPK (hasil coklit Pantarlih): daftar hasil
coklit, pemilih pindah TPS, pemilih baru, pemilih tersaring (TMS), NIK ganda, dan
cetak daftar pemilih aktif/MS per desa–TPS.

## Status Fase 0 (9 November 2026)

Fondasi diperbarui dari versi awal (CodeIgniter 4.3.2, baca-saja):

- **Framework**: CodeIgniter **4.7.6** (dari 4.3.2) — kini jalan di **PHP 8.2–8.4**
  (versi lama fatal di PHP 8.4).
- **Rute eksplisit** di `app/Config/Routes.php`; auto-routing lama dimatikan.
- **Konfigurasi** (baseURL, database) sepenuhnya dari `.env` — salin `.env.example`.
- **Migration baseline** `2026-11-09-100000_BaselineSkemaPemilih`: skema kini hidup
  sebagai migration, bukan hanya dump SQL. Isinya **5 tabel asli** (`mhc`, `to_sql`,
  `config`, `tms8`, `nik_move_tps`) + **16 view** turunan di atas `mhc`
  (`ms`, `tersaring`, `pindahtps`, `pemilihbaru`, `desa`, `kec`, dst.).
  Seeder `DataAwalSeeder` mengisi tabel `config`.
- **Tampilan**: AdminLTE/Bootstrap diganti **Tailwind CSS 3 + DaisyUI 4**
  (CSS terkompilasi lokal di `public/assets/css/app.css`, tanpa CDN).
  jQuery + DataTables tetap dipakai untuk tabel server-side.
- **Perbaikan bug**:
  - Filter L/P di Pindah TPS kini bekerja (sebelumnya salah kolom & salah variabel,
    selalu error/terabaikan).
  - Kode PDF mati di `Pemilih::ms()` dihapus (cetak tetap HTML dari browser;
    PDF sungguhan masuk Fase 4).
  - Impor `PHPUnit\Framework\isEmpty` yang bocor di `PemilihModel` dihapus;
    kolom `UMUR` ganda di query dibersihkan.
  - File model salah ketik `DasahboardModel.php` → `DashboardModel.php`.
  - Properti dinamis `$kec` di `BaseController` kini dideklarasikan.
- Dompdf diperbarui ke **^3.0** (2.0 diblokir peringatan keamanan Composer).

## Status Fase 1 (9 November 2026) — Login & peran pengguna

Gerbang sebelum CRUD (Fase 2) — hanya orang berwenang yang bisa masuk:

- **Tabel `users`** (migration `2026-11-09-110000_TabelUsers`): nama,
  username unik, hash kata sandi (bcrypt), peran, desa (cakupan wilayah,
  kosong = seluruh kecamatan), aktif, `harus_ganti_sandi`.
- **Peran**: `admin` (Admin PPK, semua), `operator` (olah data wilayahnya),
  `viewer` (Pemeriksa — baca + cetak saja).
- **Filter `auth`** di semua rute kecuali login/logout: tanpa login semua
  halaman — termasuk endpoint data POST (DataTables) — dialihkan ke
  `/login`. Menu Kelola Pengguna hanya terlihat oleh admin; peran lain
  tetap tertolak walau memanggil URL-nya langsung.
- **Akun awal** (seeder `PenggunaAwalSeeder`): username `admin`, kata sandi
  awal `admin123` — **wajib diganti saat pertama masuk** (aplikasi
  mengarahkan paksa ke halaman ganti sandi sampai diganti).
- **Kelola pengguna** (admin, di `/pengguna`): tambah pengguna (sandi awal
  min. 8 karakter, wajib diganti saat pertama masuk), nonaktifkan/aktifkan
  akun (akun sendiri tidak bisa dinonaktifkan), reset sandi ke `ppk12345`
  (wajib diganti saat masuk berikutnya).
- **Ganti sandi** (`/ganti-sandi`): periksa sandi lama, sandi baru min.
  8 karakter + konfirmasi.
- **Nama pengguna asli** di sidebar menggantikan nama yang ditulis tetap
  di versi lama; tombol Keluar di navbar.
- **Uji lulus** (9 Nov 2026, DB bersih + data Ranto Baek 12.597 baris):
  tanpa login → 302 ke login; login salah → pesan galat di halaman login;
  admin pertama masuk → dipaksa ganti sandi; viewer tidak melihat menu
  Kelola Pengguna dan ditolak via URL langsung; akun dinonaktifkan →
  login ditolak dengan pesan; sesudah ganti sandi semua halaman baca
  tampil normal dengan angka data yang sama.

## Kebutuhan

- PHP **8.2+** (teruji 8.3 & 8.4) dengan ekstensi: mysqli, intl, mbstring, xml, curl, gd, zip
- MySQL/MariaDB
- Composer 2
- Node.js (hanya bila ingin membangun ulang CSS)

## Pemasangan

```bash
composer install
copy .env.example .env   # sesuaikan baseURL + database
```

Lalu pilih salah satu sumber basis data:

1. **Dari nol (skema kosong)**: buat database kosong, lalu
   `php spark migrate` dan `php spark db:seed DataAwalSeeder`
   dan `php spark db:seed PenggunaAwalSeeder` (akun awal: `admin` /
   `admin123`, wajib ganti sandi saat pertama masuk).
   Data coklit (`mhc`, `tms8`, dll.) diimpor terpisah.
2. **Dari dump yang ada**: impor `db_pemilih.sql` seperti biasa,
   lalu `php spark migrate` — migration baseline bersifat toleran
   (objek yang sudah ada dilewati), jadi aman di basis data hasil
   impor maupun basis data kosong. Sesudah itu
   `php spark db:seed PenggunaAwalSeeder` (bila akun awal belum ada).

Jalankan dengan web server menunjuk ke folder `public/`, atau
`php spark serve`. Sesuai konfigurasi contoh, aplikasi tampil di
`http://localhost:81/pemilurb/`.

## Membangun ulang CSS (Tailwind + DaisyUI)

```bash
cd tools/tailwind
npm install
npx tailwindcss -i input.css -o ../../public/assets/css/app.css --minify
```

## Peta singkat

| URL | Halaman |
|---|---|
| `/login` | Masuk (halaman publik) |
| `/ganti-sandi` | Ganti kata sandi |
| `/pengguna` | Kelola pengguna (hanya Admin PPK) |
| `/` | Daftar Hasil Coklit Pantarlih (tabel `mhc`) |
| `/pemilih` | Pemilih Pindah TPS (view `pindahtps`) |
| `/pemilih/baru` | Pemilih Baru (view `pemilihbaru2`) |
| `/pemilih/tersaring` | Pemilih Tersaring/TMS (view `tersaring`) |
| `/pemilih/aktifPrint` | Pemilih Aktif/MS (view `pemilih_active`) |
| `/pemilih/ms?desa=..&tps=..` | Cetak daftar MS (HTML siap cetak) |
| `/pemilih/ms-pdf?desa=..&tps=..` | Daftar MS sebagai PDF (Fase 4) |
| `/residu` | NIK Ganda (view `analisis_nik_ganda`) |
| `/coklit` | Kelola Data Coklit — CRUD (Fase 2, Admin & Operator) |
| `/riwayat` | Riwayat Perubahan + pemulihan baris (Fase 2) |
| `/impor` | Impor CSV/Excel coklit & keterangan tms8 (Fase 3) |
| `/laporan` | Laporan ringkasan per desa / TPS (Fase 4) |
| `/log-akses` | Log akses data sensitif (Fase 5, hanya Admin) |
| `/backup` | Cadangan basis data .sql (Fase 5, hanya Admin) |
| `/kecamatan` | Ganti kecamatan aktif (Fase 6, hanya Admin) |

Pengembangan lanjutan mengikuti `Kerangka_Pengembangan_App_Pemilih.md`
(Fase 1: login & peran; Fase 2: CRUD; dst.).

## Status Fase 2 (9 November 2026) — CRUD data coklit & jejak audit

- **Kelola Data Coklit** (`/coklit`, Admin & Operator): cari (NIK/NAMA/NKK),
  tambah, ubah, hapus baris `mhc` lewat formulir. Karena daftar aktif/MS
  adalah VIEW di atas `mhc`, koreksi langsung tercermin di semua halaman.
- **Validasi**: NIK & NKK tepat 16 angka; NIK tidak boleh ganda di daftar
  aktif (kelompok KET 0/U/B); desa/TPS/KET/jenis kelamin diperiksa.
- **Cakupan desa**: operator yang kolom `desa`-nya terisi (mis. PPS) hanya
  melihat & mengubah baris desanya sendiri.
- **Riwayat Perubahan** (`/riwayat`): setiap tambah/ubah/hapus tercatat
  beserta salinan baris sebelum & sesudah (JSON) dan pelakunya.
  Baris terhapus dapat **dipulihkan** oleh Admin dari halaman ini —
  penghapusan di `mhc` permanen, tetapi salinannya tidak hilang.

## Status Fase 3 (9 November 2026) — Impor CSV/Excel

- **Impor Data** (`/impor`, Admin & Operator kecamatan): berkas CSV/XLSX/XLS
  dengan baris judul = nama kolom `mhc` (wajib: NIK, NAMA, DESA, TPS, KET).
  - Mode **TAMBAH**: baris baru disisipkan; UID yang sudah ada dilewati.
  - Mode **GANTI SEMUA**: data lama dicadangkan otomatis ke
    `writable/backup/mhc-sebelum-impor-*.csv`, lalu `mhc` dikosongkan
    dan diisi dari berkas.
  - Sesudah impor tampil **rekonsiliasi** (total coklit, MS, baru,
    tersaring, pindah TPS, jumlah KK) — tidak ada lagi langkah analisis
    manual karena semua daftar adalah view atas `mhc`.
- **Impor keterangan `tms8`**: kolom NIK + STATUS; memperbarui yang ada,
  menambah yang belum ada. Semua impor tercatat di tabel `impor_log`
  dan di Riwayat Perubahan.

## Status Fase 4 (9 November 2026) — Cetak & laporan

- **Daftar MS sebagai PDF**: `/pemilih/ms-pdf?desa=..&tps=..` (dompdf,
  A4 landscape) dari tampilan cetak yang sama dengan versi HTML;
  tombol **PDF** tersedia di halaman Pemilih Aktif dan Laporan.
- **Laporan Ringkasan** (`/laporan`, semua peran): tabel per desa
  (TPS, total coklit, MS, KK, baru, tersaring, pindah TPS) + rincian
  per TPS untuk desa terpilih + tombol cetak halaman.

## Status Fase 5 (9 November 2026) — Keamanan data pribadi

- **Penyamaran NIK/NKK untuk Pemeriksa**: peran `viewer` menerima NIK &
  NKK tersamar (`1213********0008`) di semua tabel data. (Daftar cetak
  MS memang hanya menampilkan NKK ter-encode sejak awal.)
- **Log Akses** (`/log-akses`, Admin): masuk, masuk gagal, keluar,
  cetak daftar MS, unduh PDF, pembuatan cadangan, ganti kecamatan.
- **Cadangan Basis Data** (`/backup`, Admin): unduh dump `.sql` lengkap
  (struktur + data + view) dari basis data kecamatan aktif; berkas juga
  tersimpan di `writable/backup/`. Berisi data pribadi — simpan aman.
- **CSRF**: semua POST (formulir & DataTables AJAX) wajib token;
  token per sesi stabil (tidak di-regenerasi tiap permintaan).

## Status Fase 6 (9 November 2026) — Multi-kecamatan

- Satu pemasangan melayani banyak kecamatan: daftar kecamatan & nama
  basis datanya di `app/Config/Kecamatan.php` (grup koneksi dibuat
  otomatis meniru koneksi utama). Kecamatan contoh (Sinunukan, Batang
  Natal, PSM, MBG, Pansel, LSM, Batahan) tersedia nonaktif — sesuaikan
  nama basis data lalu set `aktif => true`.
- Admin mengganti kecamatan aktif di menu **Kecamatan** (`/kecamatan`);
  pilihan tersimpan per sesi dan semua halaman mengikuti basis data itu.
  Basis data kecamatan baru: buat DB kosong →
  `php spark migrate -g <grup>` → impor datanya lewat menu Impor.
- **Pengguna & log akses selalu dari basis data utama** (`users` dikunci
  ke grup `default`), jadi satu akun berlaku untuk semua kecamatan.
