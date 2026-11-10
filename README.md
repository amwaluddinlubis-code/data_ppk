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
  Komponen bersama menjaga konsistensi halaman operasional dan login dalam
  tema terang/gelap. Untuk membangun ulang CSS, jalankan dari `tools/tailwind`:
  `npx tailwindcss -c tailwind.config.js -i input.css -o ../../public/assets/css/app.css --minify`.
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

> **DIGANTIKAN oleh Fase 7 (10 November 2026).** Konsep basis data
> per kecamatan di bawah ini sudah tidak berlaku; riwayatnya disimpan
> di sini hanya sebagai catatan perubahan.

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

## Status Fase 7 (10 November 2026) — Konsep Kabupaten + Desain Ulang

Konsep wilayah diganti total atas keputusan pengguna:

- **Satu basis data = satu kabupaten.** Hierarki: Kabupaten →
  Kecamatan → Desa/Kelurahan → TPS → Pemilih. Semua kecamatan tinggal
  di basis data yang sama; kecamatan adalah **saringan wilayah**
  (kolom `KEC` pada data coklit), bukan basis data terpisah.
  Konfigurasi `app/Config/Kecamatan.php` dan grup koneksi dinamis
  Fase 6 dicabut.
- **Tingkat pengguna mengikuti jenjang penyelenggara:**
  - **Admin Kabupaten** (`admin`, tanpa cakupan): melihat seluruh
    kabupaten; dapat mengganti saringan kecamatan aktif per sesi di
    menu **Kecamatan**.
  - **PPK** (`operator`/`viewer` + `users.kecamatan`): terkunci ke
    kecamatannya.
  - **PPS** (`operator`/`viewer` + `users.kecamatan` + `users.desa`):
    terkunci ke desanya — baca, tulis, cetak, semua hanya desanya.
- Semua halaman & endpoint (dasbor, daftar pemilih, CRUD, riwayat,
  laporan, cetak MS/PDF) menerapkan saringan wilayah yang sama.
- **Impor per kecamatan:** impor data coklit selalu untuk satu
  kecamatan tujuan (PPK terkunci ke kecamatannya; Admin memilih /
  menulis nama kecamatan baru). Mode **GANTI hanya menimpa baris
  kecamatan tujuan** (cadangan CSV khusus kecamatan itu dibuat
  dahulu); baris berkas yang kolom KEC-nya berbeda dilewati.
- Migrasi `2026-11-10-100000_KonsepKabupaten`: tambah
  `users.kecamatan`, `riwayat_perubahan.kecamatan/desa`, dan kolom
  `KEC` pada view `tersaring`, `pemilih_active`, `pindahtps`,
  `analisis_nik_ganda`, `v_nkk`. Jalankan `php spark migrate` sesudah
  memperbarui kode; data lama (satu kecamatan) otomatis menjadi isi
  kecamatan pertamanya — tidak ada data yang dipindah.

Desain ulang tampilan (Tailwind + DaisyUI, CSS dikompilasi ulang):

- **Dasbor rupa kartu statistik** (total coklit, MS, baru, tersaring,
  pindah TPS, KK) + rincian L/P + tabel per kecamatan/desa.
- **Menu berkelompok ala Sidalih**: Data Pemilih, Pengolahan,
  Laporan, Administrasi, Akun.
- **Dua tema: `terang` dan `gelap`** — tombol bulan/matahari di
  navbar, pilihan tersimpan di peramban.
- **UI mobile**: navigasi bawah (Dasbor, Pemilih MS, Coklit,
  Wilayah, Menu) dan kartu statistik dua kolom di HP.

## Perbaikan Audit (10 November 2026) — T1 & T2

Hasil audit menyeluruh sesudah Fase 7 (±45 pemeriksaan; tanpa bug
kritis). Dua temuan kecil warisan aplikasi asal diperbaiki lewat
migration `2026-11-10-110000_PerbaikanViewBaruDanKodeTms`:

- **T1**: view `pemilihbaru2` menggandakan baris pemilih baru yang
  NIK-nya punya lebih dari satu baris tersaring. OLD_NAMA/OLD_DESA kini
  diambil lewat subkueri skalar (satu baris tersaring terawal per NIK) —
  halaman Pemilih Baru kini persis sama dengan hitungan kode B.
- **T2**: kode keterangan TMS dilengkapi sesuai standar coklit
  (4 = Pindah Domisili, 5 = WNA) pada view `tersaring`/`pemilih_active`,
  hitungan Dashboard & Laporan, label formulir Coklit, dan saringan
  halaman Tersaring. Kini MS + Tersaring = Total dan Pemilih Aktif = MS.

Sesudah `git pull`, jalankan `php spark migrate` seperti biasa.

## Analisis Kegandaan NIK (10 November 2026)

Halaman **NIK Ganda** ditulis ulang: analisis kini cukup memperhatikan
NIK pada seluruh data coklit (menggantikan view lama
`analisis_nik_ganda` yang hanya melihat duplikat di dalam daftar MS).

- **Antar Kecamatan**: satu NIK tercatat di lebih dari satu kecamatan.
- **Antar Desa**: satu NIK tercatat di lebih dari satu desa/kelurahan
  dalam kecamatan yang sama.

Kegandaan di dalam desa yang sama tidak ditampilkan (pola normal
coklit). Kelompok ganda dibatasi pada yang menyentuh wilayah pengguna
(PPK kecamatannya, PPS desanya), tetapi semua baris NIK kelompok itu
ditampilkan termasuk baris di wilayah seberang. Ada kartu ringkasan
per kategori dan saringan kategori. NIK/NKK Pemeriksa tetap tersamar.
Tidak perlu migration: tidak ada perubahan skema.

## Impor Cepat Satu Kabupaten (10 November 2026) — via Konsol

Untuk memasukkan data seluruh kabupaten sekaligus ke basis data lokal
tanpa mengunggah satu per satu lewat web:

```
php spark impor:massal D:\folder-data-coklit
php spark impor:massal berkas.csv --mode ganti
php spark impor:massal berkas-tanpa-kolom-kec.csv --kecamatan "RANTO BAEK"
```

- Path boleh satu berkas atau folder (semua CSV/XLSX di dalamnya
  diproses berurutan). Kecamatan setiap baris diambil dari kolom KEC.
- Aturan baris sama dengan impor web (judul = nama kolom mhc; NIK
  wajib 16 angka). Di akhir tampil rekonsiliasi per kecamatan.
- **Mode tambah** (bawaan) aman dijalankan ulang: baris yang persis
  sama dilewati. Kuncinya gabungan UID+KET+Desa+TPS — bukan UID saja,
  karena pada data coklit satu UID dipakai bersama oleh baris
  tersaring dan baris baru orang yang sama.
- **Mode ganti**: data lama kecamatan yang muncul di berkas
  dicadangkan dulu ke `writable/backup/` lalu diganti isi berkas.
- Pencatatan masuk `impor_log` dengan pengguna `konsol`.

---

## Versi Bangun Ulang (10 November 2026)

Kode aplikasi ditulis ulang di atas fondasi yang sama (CodeIgniter 4.7,
skema data kompatibel) untuk menghilangkan kelemahan warisan kode
manual:

- **Satu sumber definisi** — kode keterangan pemilih di
  `app/Libraries/Keterangan.php`; logika cakupan wilayah di
  `app/Libraries/Wilayah.php`; aturan impor (pemetaan kolom dua format,
  kunci baris) di `app/Libraries/ImporCoklit.php`. Tidak ada lagi
  daftar kode/logika yang disalin ke banyak berkas.
- **NIK adalah identitas unik pemilih** — kolom ter-generate
  `NIK_AKTIF` + indeks unik `uq_mhc_nik_aktif` menjamin satu NIK hanya
  mempunyai satu baris aktif (MS) di seluruh kabupaten; baris
  tersaring orang yang sama tetap boleh ada. Impor (web & konsol)
  menerima baris aktif pertama per NIK dan MENGALIHKAN baris aktif
  berikutnya sambil menghitung & melaporkannya. Bila data lama masih
  mengandung NIK aktif ganda, migrasi melewati pemasangan indeks unik
  dengan pesan penjelasan (penegakan tetap berjalan di aplikasi).
- **`mhc.ID` kini PRIMARY KEY AUTO_INCREMENT** — pengenal baris yang
  sesungguhnya; halaman Coklit mengalamatkan baris dengan ID ini.
- **Definisi view terpusat** — migration `ViewTerpusat` menetapkan
  bentuk akhir semua view aplikasi dalam satu tempat.
- **Impor Sidalih asli** — perintah `php spark impor:massal` mengenali
  ekspor Sidalih (KECAMATAN/KELURAHAN/DPID/KETERANGAN) dan membacanya
  secara mengalir (`app/Libraries/PembacaXlsx.php`), teruji pada
  338.955 baris data Kabupaten Mandailing Natal: 338.108 pemilih unik
  masuk, 847 baris teralih sebagai ganda NIK aktif, 4 baris ditolak
  (1 NIK tidak sah, 3 baris anomali sumber).

Pemasangan di komputer baru sama seperti sebelumnya: salin `.env`,
jalankan `php spark migrate`, buat admin awal lewat
`php spark db:seed PenggunaAwalSeeder`.

### Skema Konsolidasi 2026 (basis data baru)

Sejak 10 November 2026, `php spark migrate` pada basis data KOSONG
langsung membentuk struktur akhir — tanpa objek warisan:

- 7 tabel (`mhc`, `tms8`, `users`, `riwayat_perubahan`, `impor_log`,
  `akses_log`, `migrations`) + 7 view aplikasi.
- `mhc`: `ID` kunci pokok auto-increment; `NIK_AKTIF` ter-generate +
  indeks unik (satu NIK satu baris aktif); indeks wilayah
  (KEC, DESA, TPS, KET) & (KET, KEC); kolom inti NOT NULL; kolom
  `TGL_LAHIR` bertipe DATE yang diisi otomatis dari teks `TGLLHR`
  saat impor dan entri.
- Migration lama menjadi rintisan kosong (riwayat versi lama tetap
  terbaca); basis data lama disamakan bentuknya oleh migration
  penyesuai 2026-11-10-230000 dalam satu kali `spark migrate` —
  termasuk pengisian `TGL_LAHIR` dari data yang sudah ada dan
  pengarsipan objek warisan menjadi tabel `arsip_*`.

### Skema Final 2026 — Wilayah Ternormalisasi (10 November 2026)

Perintah AMWAL: **database lama dilupakan, MADINA.xlsx menjadi data
sumber, wilayah dinormalisasi.** Seluruh migration sebelumnya kini
rintisan kosong; basis data baru dibangun utuh oleh SATU migration
`2026-11-10-300000_SkemaFinalWilayah`:

- **Tabel master wilayah** — `kecamatan` (kode resmi Sidalih `KEC_ID`,
  4 digit, + nama) dan `kelurahan` (kode `KEL_ID`, 5 digit, + nama +
  FK ke kecamatan). Kode boleh kosong untuk wilayah yang lahir dari
  impor format coklit tanpa kode; kode resmi dilengkapi otomatis
  begitu impor berkode menyentuhnya.
- **`mhc` disederhanakan** — kolom `PROV`, `KAB`, `KEC`, `DESA`,
  `LAT`, `LONG` dibuang. Wilayah cukup disimpan sebagai
  `kelurahan_id` (FK ke master). Indeks: (kelurahan_id, TPS, KET)
  dan (KET, kelurahan_id). Aturan satu-NIK-satu-baris-aktif dan
  kolom `TGL_LAHIR` (DATE) tetap.
- **Ketujuh view menyajikan kembali kolom `KEC`/`DESA`** (nama hasil
  JOIN master), sehingga halaman daftar & cetak membaca nama wilayah
  seperti biasa.
- **`users` & `riwayat_perubahan`** — cakupan wilayah berbasis ID
  (`kecamatan_id` / `kelurahan_id`), bukan lagi teks nama.
- **Tabel `pengaturan`** (kunci-nilai) menyimpan identitas kabupaten
  (nama provinsi/kabupaten) — diisi oleh opsi `--prov`/`--kab` impor
  konsol, bukan lagi diulang pada setiap baris pemilih.
- **Proses impor diperbarui**: wilayah setiap baris diselesaikan ke
  master oleh `App\Libraries\WilayahMaster` (kode Sidalih didahulukan,
  nama sebagai cadangan) sebelum baris disimpan; cadangan pra-impor
  (mode ganti) mengekspor wilayah sebagai KEC_ID/KECAMATAN/KEL_ID/
  KELURAHAN sehingga dapat diimpor kembali tanpa kehilangan wilayah.

Langkah basis data baru: buat basis data kosong → arahkan `.env` →
`php spark migrate` → `php spark db:seed PenggunaAwalSeeder` →
`php spark impor:massal MADINA.xlsx --prov="SUMATERA UTARA" --kab="MANDAILING NATAL"`.

### Skrip impor Windows (`impor_madina.bat`)

Skrip ini mencatat keluaran proses ke `hasil_impor.txt` dan meneruskan kode keluar proses impor ke pemanggil. Jika migrasi, penyiapan akun admin, atau impor gagal, skrip berakhir dengan kode non-nol; jangan menganggap proses berhasil hanya karena jendela menampilkan log. Perintah `impor:massal` juga mengembalikan status gagal bila satu atau lebih berkas tidak dapat diproses, sehingga kegagalan tingkat berkas tidak tertutup oleh keberhasilan berkas lain. Periksa `hasil_impor.txt` untuk pesan kegagalan.
