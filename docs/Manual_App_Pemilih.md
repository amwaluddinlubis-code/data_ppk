# PANDUAN PENGGUNAAN APLIKASI DATA PEMILIH

## Alat Bantu Kerja PPK dan PPS untuk Mengolah, Membersihkan, dan Menganalisis Data Pemilih Satu Kabupaten

**Disusun oleh: Amwaluddin Lubis, S.T.**
Pengembang — PPK Ranto Baek, Kabupaten Mandailing Natal
Versi aplikasi: November 2026 · Bahasa: Indonesia sederhana

> **Penting sejak awal.** Aplikasi ini adalah **alat bantu kerja lokal** untuk Panitia Pemilihan Kecamatan (PPK) dan Panitia Pemungutan Suara (PPS). Aplikasi ini **bukan pengganti Sidalih**. Sidalih tetap sistem resmi KPU dan satu-satunya yang berwenang menetapkan daftar pemilih. Aplikasi ini membantu PPK/PPS **menyiapkan, memeriksa, dan membersihkan data** sebelum dan sesudah bekerja di Sidalih — supaya data yang diolah di Sidalih lebih cepat beres dan lebih sedikit salahnya.

---

# Daftar Isi

- Bab 1 — Mengenal Aplikasi Ini
- Bab 2 — Memulai: Masuk dan Mengenal Layar
- Bab 3 — Dasbor: Melihat Keadaan Data dalam Sekejap
- Bab 4 — Menu Data Pemilih
- Bab 5 — Mengolah Data Coklit
- Bab 6 — Membersihkan Data: Kegandaan dan Anomali
- Bab 7 — PDPB: Pemutakhiran Data Pemilih Berkelanjutan
- Bab 8 — Tahapan DPS, DPSHP, dan DPT
- Bab 9 — DPTb, DPK, dan TPS Lokasi Khusus
- Bab 10 — Banding Sidalih dan Analisis Beban TPS
- Bab 11 — Laporan dan Administrasi
- Bab 12 — Alur Kerja Lengkap dari Awal sampai Akhir
- Bab 13 — Kelebihan Aplikasi Ini Dibanding Sidalih Resmi
- Bab 14 — Keamanan dan Kerahasiaan Data
- Bab 15 — Tanya Jawab dan Pemecahan Masalah
- Lampiran A — Daftar Formulir Cetak
- Lampiran B — Arti Kode Keterangan Pemilih

---

# Bab 1 — Mengenal Aplikasi Ini

## 1.1 Apa aplikasi ini?

Aplikasi Data Pemilih adalah program yang dipasang di **satu komputer/laptop** (misalnya laptop PPK atau komputer sekretariat KPU Kabupaten/Kota) dan menampung data pemilih **satu kabupaten penuh** dalam satu basis data. Di dalamnya, data tersusun rapi menurut jenjang wilayah: **kabupaten → kecamatan → desa/kelurahan → TPS**.

Aplikasi ini dipakai untuk:

1. **Menyimpan dan mencari** data pemilih dengan cepat (berdasarkan nama, NIK, atau nomor KK).
2. **Memeriksa kebersihan data**: mencari NIK ganda, duplikat terselubung, KK yang tersebar, umur yang tidak wajar, dan ketidakcocokan jenis kelamin dengan kode NIK.
3. **Mengikuti tahapan resmi**: PDPB triwulanan, lalu DPS → DPSHP → DPT, lengkap dengan arsip penetapan dan berita acara.
4. **Melayani pindah memilih (DPTb)**, pemilih khusus (DPK), dan TPS lokasi khusus.
5. **Mencetak 17 formulir resmi** sesuai lampiran PKPU, plus 6 formulir PDPB.
6. **Membandingkan data** dengan berkas ekspor Sidalih (banding data) dan **menganalisis beban TPS**.

Seluruh angka pada contoh gambar di buku ini berasal dari data nyata satu kabupaten (338.108 pemilih, 23 kecamatan, 404 desa/kelurahan, 1.416 TPS) yang sudah pernah diolah dengan aplikasi ini. Nama, NIK, dan nomor KK pada gambar **sengaja disamarkan** demi kerahasiaan data pemilih.

## 1.2 Siapa penggunanya?

| Peran | Siapa | Wilayah yang bisa dilihat |
|---|---|---|
| Admin | Operator KPU Kabupaten/Kota | Seluruh kabupaten |
| PPK | Panitia Pemilihan Kecamatan | Satu kecamatan miliknya |
| PPS | Panitia Pemungutan Suara | Satu desa/kelurahan miliknya |
| Peninjau (viewer) | Pengawas/pembaca | Wilayahnya, hanya membaca, NIK tersamar |

Setiap pengguna **hanya bisa melihat dan mengolah wilayahnya sendiri**. PPK kecamatan lain tidak bisa mengintip data kecamatan tetangga. Ini penting untuk menjaga kerahasiaan data.

## 1.3 Apa yang dibutuhkan?

- Satu laptop/komputer Windows dengan **Laragon** (atau XAMPP) — sudah berisi PHP dan MySQL.
- Berkas program aplikasi (dari repositori kode).
- Berkas data pemilih hasil ekspor Sidalih (format Excel .xlsx) untuk diimpor pertama kali.
- Tidak perlu internet. Aplikasi berjalan **sepenuhnya di komputer lokal (offline)**.

## 1.4 Cara memasang (ringkas, untuk operator)

1. Salin folder aplikasi ke folder web Laragon.
2. Atur berkas `.env` (nama basis data, pengguna, kata sandi MySQL).
3. Jalankan perintah `php spark migrate` — basis data dan semua tabel dibuat otomatis.
4. Buat akun admin awal dengan `php spark db:seed PenggunaAwalSeeder`.
5. Masuk sebagai admin, lalu impor berkas Sidalih kabupaten dengan paket impor yang disediakan (satu perintah, seluruh kabupaten masuk; pada pengujian, 338.959 baris berkas Sidalih selesai diimpor menjadi 338.108 pemilih unik).
6. Buat akun untuk tiap PPK dan PPS sesuai wilayahnya.

---

# Bab 2 — Memulai: Masuk dan Mengenal Layar

## 2.1 Masuk (login)

Buka alamat aplikasi di peramban, isi **nama pengguna** dan **kata sandi**, lalu klik tombol masuk. Pada pemakaian pertama, aplikasi meminta pengguna mengganti kata sandi bawaan — lakukan segera demi keamanan.

![Halaman masuk aplikasi](manual-gambar/01-login.png)

## 2.2 Bagian-bagian layar

Setelah masuk, layar terbagi menjadi:

- **Menu samping (kiri)** — empat kelompok: *Data Pemilih*, *Pengolahan*, *Laporan*, dan *Administrasi*, ditambah *Akun*. Menu tersusun bertingkat seperti menu Sidalih sehingga mudah dikenali.
- **Cakupan wilayah** — tulisan di bawah nama pengguna menunjukkan wilayah yang sedang Anda lihat (seluruh kabupaten, satu kecamatan, atau satu desa).
- **Kotak pencarian cepat (atas)** — ketik nama, NIK, atau NKK dari halaman mana pun untuk melompat ke pemilih yang dicari.
- **Tombol tema** — ikon bulan/matahari untuk beralih tampilan terang dan gelap.
- **Kotak ketik pilihan** — semua kotak pilihan (desa, TPS, status, dan lain-lain) bisa **diketik untuk menyaring**, tidak perlu menggulir daftar panjang.

## 2.3 Mengatur konteks lebih dulu

Sebelum bekerja, admin mengatur **konteks** di menu Administrasi → Konteks: apakah pekerjaan saat ini adalah **Pemilu**, **Pilkada**, atau **PDPB**, beserta nama kegiatannya (misalnya "Pemilu 2029" atau "Pilkada 2029"). Konteks ini menentukan:

- arti kode keterangan 5 dan 8 (berbeda antara penyelenggaraan dan PDPB — dijelaskan di Lampiran B),
- batas jumlah pemilih per TPS (Pemilu 300, Pilkada 600),
- judul pada semua formulir cetak.

![Pengaturan konteks kegiatan](manual-gambar/13-konteks.png)

## 2.4 Tampilan di HP (mobile)

Aplikasi ini juga nyaman dibuka dari **HP**. Karena data tersimpan di komputer PPK/kabupaten, HP cukup terhubung ke jaringan lokal yang sama (Wi-Fi kantor) lalu membuka alamat aplikasi dari peramban HP. Tampilannya menyesuaikan layar kecil:

- **Menu berpindah ke bawah layar** — lima tombol besar: Dasbor, Pemilih MS, Coklit, Wilayah, dan Menu. Mudah dijangkau ibu jari.
- Kartu-kartu ringkasan tersusun dua kolom dan tetap terbaca jelas.
- Tabel daftar dapat digeser ke samping; pekerjaan periksa cepat (mencari nama, melihat temuan anomali, memantau angka) bisa dilakukan tanpa membuka laptop.

![Tampilan masuk di HP](manual-gambar/mobile-01-login.png)

![Dasbor di HP — kartu ringkasan dan Perlu Ditangani, dengan menu di bawah layar](manual-gambar/mobile-02-dasbor.png)

![Kelola Data Coklit di HP — kolom NIK, NKK, dan Nama sengaja disamarkan pada gambar buku ini](manual-gambar/mobile-03-coklit.png)

![Deteksi Anomali di HP — kartu temuan duplikat terselubung dan KK tersebar](manual-gambar/mobile-04-anomali.png)

![PDPB di HP](manual-gambar/mobile-05-pdpb.png)

![Analisis Beban TPS di HP](manual-gambar/mobile-06-beban.png)

---

# Bab 3 — Dasbor: Melihat Keadaan Data dalam Sekejap

Dasbor adalah halaman pertama setelah masuk. Dalam satu layar Anda bisa menjawab pertanyaan: *"Berapa pemilih kita? Apa yang bermasalah? Apa yang harus dikerjakan hari ini?"*

![Dasbor kabupaten: kartu ringkasan, kartu Perlu Ditangani, dan rincian per kecamatan](manual-gambar/02-dasbor.png)

Isi dasbor:

- **Kartu ringkasan** — total hasil coklit, pemilih MS (memenuhi syarat), pemilih baru, tersaring (TMS), pindah TPS, dan jumlah KK.
- **Kartu PERLU DITANGANI** — daftar temuan yang menunggu diperiksa: NIK ganda antar wilayah, peringatan jenis kelamin vs NIK, duplikat terselubung, KK tersebar, dan umur ekstrem. Klik kartunya untuk membuka daftar orangnya. Pada data nyata Mandailing Natal, kartu ini menunjukkan misalnya **5.244 peringatan** jenis kelamin vs NIK dan **1.650 kelompok** duplikat terselubung — temuan yang tidak terlihat dari daftar biasa.
- **Kartu PDPB periode berjalan** — jumlah pemilih baru, masuk TMS, dan ubah elemen data pada triwulan berjalan.
- **Rincian per kecamatan** dan pembagian laki-laki/perempuan.

---

# Bab 4 — Menu Data Pemilih

Kelompok menu ini berisi daftar-daftar pemilih yang sudah dipilah menurut keadaannya, sehingga petugas tidak perlu menyaring sendiri.

## 4.1 Pemilih Aktif (MS)

Daftar pemilih yang memenuhi syarat (kode keterangan 0, U, dan B). Dari halaman ini petugas dapat mencetak daftar pemilih per TPS untuk keperluan pencocokan di lapangan.

## 4.2 Pemilih Baru

Daftar pemilih berkode **B** (pemilih baru hasil coklit) dan pemilih yang belum tercoklit — bahan utama untuk mengetahui siapa yang baru memenuhi syarat.

## 4.3 Tersaring (TMS)

Daftar pemilih berkode 1 sampai 8 (meninggal, ganda, di bawah umur, dan sebagainya — arti lengkap di Lampiran B). Daftar ini adalah bahan penyusunan daftar pemilih yang tidak lagi memenuhi syarat.

## 4.4 Pindah TPS

Menampilkan pemilih yang **terdeteksi** berpindah desa/TPS dari data coklit (membandingkan alamat lama dan baru). Perhatikan: ini berbeda dari register DPTb di Bab 9. Halaman ini sifatnya *temuan dari data*; register DPTb adalah *pengajuan resmi* dari pemilih.

![Halaman Pindah TPS dengan saringan desa, TPS, status, dan jenis kelamin serta tombol ekspor](manual-gambar/16-pindah-tps.png)

## 4.5 Ekspor di semua daftar

Setiap daftar dapat diunduh sebagai **CSV** atau **Excel**. Pencarian dan penyaringan tidak memuat ulang seluruh halaman (teknologi HTMX), jadi terasa ringan walaupun datanya ratusan ribu baris. Kotak pencarian angka 6 digit atau lebih akan dicocokkan sebagai **awalan NIK/NKK**, memudahkan pencarian dari potongan nomor.

---

# Bab 5 — Mengolah Data Coklit

Menu Pengolahan → Kelola Data Coklit adalah tempat kerja harian PPS dan PPK.

![Kelola Data Coklit — kolom NIK, NKK, dan Nama sengaja disamarkan pada gambar buku ini](manual-gambar/04-coklit.png)

## 5.1 Mencari pemilih

Ketik NIK, nama, atau NKK pada kotak cari, pilih desa bila perlu, lalu tekan **Cari** atau Enter. Hasilnya langsung tampil per halaman (25/50/100 baris).

## 5.2 Menambah pemilih baru

1. Klik **Tambah Pemilih**.
2. Isi biodata lengkap: NIK, NKK, nama, tempat/tanggal lahir, status kawin, jenis kelamin, alamat, RT/RW, disabilitas, status KTP-el, desa, dan TPS.
3. Bila perubahan ini untuk keperluan PDPB, isi juga **jenis dokumen bukti** (KTP-el, KK, akta kematian, surat keterangan, dan lain-lain sesuai Pasal 17 ayat (4) PKPU 1/2025) dan keterangannya.
4. Simpan. Aplikasi **menolak NIK yang sama** bila sudah ada pemilih aktif dengan NIK itu di kabupaten ini — satu NIK hanya boleh aktif satu kali.

## 5.3 Mengubah data pemilih

Klik **Ubah** pada baris pemilih, perbaiki elemen yang salah (alamat, TPS, keterangan, dan lain-lain), lalu simpan. Setiap perubahan **tercatat otomatis di Riwayat Perubahan**: siapa yang mengubah, kapan, nilai lama dan nilai baru. Riwayat ini tidak bisa dihapus dari layar biasa, sehingga selalu ada jejak bila suatu hari data dipertanyakan.

## 5.4 Menandai TMS dan memulihkan

Untuk menandai pemilih tidak memenuhi syarat, ubah kodenya ke 1–8 sesuai sebabnya. Pemilih yang ternyata masih memenuhi syarat dapat **dipulihkan** kembali menjadi MS; perpindahan ini pun tercatat di riwayat dan dihitung pada rekap PDPB.

## 5.5 Menghapus

Tombol **Hapus** menghilangkan baris dari daftar aktif. Penghapusan tercatat di riwayat. Gunakan dengan hati-hati dan hanya atas dasar dokumen.

## 5.6 Mengimpor data

Menu Pengolahan → Impor Data menerima berkas Excel/CSV:

- **Format Sidalih** (berjudul KEC_ID, DPID, KETERANGAN, dan seterusnya) dikenali otomatis.
- **Format aplikasi** (cadangan/ekspor dari aplikasi ini sendiri) juga dikenali.
- Untuk satu kabupaten penuh, tersedia impor lewat perintah konsol yang jauh lebih cepat (belasan ribu baris per detik).

![Halaman impor data](manual-gambar/14-impor.png)

Hasil impor selalu dilaporkan: berapa baris masuk, berapa yang dialihkan karena NIK-nya sudah aktif, dan berapa yang ditolak beserta sebabnya. Tidak ada baris yang hilang tanpa laporan.

---

# Bab 6 — Membersihkan Data: Kegandaan dan Anomali

Inilah bagian yang paling membantu PPK/PPS. Sidalih menampilkan data apa adanya; aplikasi ini **mencari sendiri** pola-pola bermasalah dan menyerahkannya sebagai daftar kerja.

## 6.1 NIK Ganda Antar Wilayah

Menu Data Pemilih → NIK Ganda. Aplikasi memeriksa **seluruh kabupaten**: satu NIK yang tercatat aktif di lebih dari satu desa atau kecamatan dikelompokkan di sini, lengkap dengan perbandingan antar kecamatan, antar desa, dan dalam kecamatan yang sama. Kegandaan di dalam satu desa yang sama tidak ditampilkan karena itu pola normal hasil coklit (baris tersaring + baris baru orang yang sama).

Di bagian bawah halaman yang sama ada **Peringatan Jenis Kelamin vs Kode NIK**: pada NIK, tanggal lahir perempuan dikodekan dengan tanggal ditambah 40. Bila jenis kelamin yang tercatat tidak cocok dengan kode itu, pemilih ditandai. **Sifatnya hanya peringatan** — ketidakcocokan seperti ini sering berasal dari data Dukcapil dan belum tentu salah. Pada data nyata ditemukan **5.244 pemilih** dengan ketidakcocokan ini, terbanyak di Kecamatan Batahan (253 orang di satu desa) — pola seperti itu biasanya menunjukkan kesalahan entri massal yang layak diklarifikasi.

![Analisis kegandaan NIK antar wilayah dan peringatan jenis kelamin vs NIK — data pribadi disamarkan](manual-gambar/05-residu-ganda.png)

## 6.2 Deteksi Anomali

Menu Data Pemilih → Deteksi Anomali mencari empat hal:

1. **Duplikat terselubung** — nama dan tanggal lahir sama persis tetapi NIK berbeda. Ini calon pemilih ganda yang lolos dari pemeriksaan NIK. Pada data nyata: **1.650 kelompok (3.328 baris)**.
2. **KK tersebar** — satu nomor KK yang anggota aktifnya tercatat di lebih dari satu TPS, bahkan lebih dari satu desa. Pada data nyata: **3.202 KK**, 829 di antaranya menyeberang desa.
3. **Umur ekstrem** — pemilih berumur di atas 100 tahun, atau di bawah 17 tahun dan belum kawin, dihitung terhadap tanggal pemungutan yang diatur admin. Pada data nyata: 16 pemilih di atas 100 tahun dan 155 pemilih di bawah umur/belum kawin.
4. **Statistik kesesuaian NIK** — ringkasan peringatan jenis kelamin per kecamatan.

![Deteksi anomali: duplikat terselubung dan KK tersebar — data pribadi disamarkan](manual-gambar/06-anomali.png)

Setiap daftar anomali dapat diunduh (CSV/Excel) untuk dibagikan sebagai daftar kerja ke PPS desa yang bersangkutan — tentu hanya desa miliknya sendiri.

---

# Bab 7 — PDPB: Pemutakhiran Data Pemilih Berkelanjutan

Menu Laporan → PDPB Berkelanjutan menjalankan kewajiban PKPU 1/2025: pemutakhiran paling sedikit **setiap 3 bulan** di luar tahapan Pemilu/Pilkada.

![Halaman PDPB: periode berjalan dan rekap perubahan per desa](manual-gambar/08-pdpb.png)

## 7.1 Cara kerja periode

- Aplikasi membuka **periode triwulan berjalan** secara otomatis (misalnya "PDPB Triwulan IV 2026").
- Setiap penambahan, perubahan keterangan menjadi TMS, perbaikan elemen data, penghapusan, dan pemulihan yang dilakukan lewat Kelola Data Coklit **tercatat ke periode itu** beserta dokumen buktinya.
- Halaman PDPB menampilkan rekap per desa: pemilih awal, baru, TMS, ubah, hapus, pulih, dan pemilih akhir — dihitung langsung dari catatan, bukan diketik ulang.

## 7.2 Menutup periode dan pleno

1. Admin mengisi **Berita Acara** periode: nomor BA, tanggal, tempat, pukul, masukan masyarakat, serta nama ketua dan anggota yang menandatangani.
2. Klik **Tutup Periode & Arsipkan**. Rekap membeku menjadi arsip; periode berikutnya terbuka otomatis.
3. Arsip periode lama tetap dapat dibuka dan dicetak ulang persis seperti saat ditutup.

## 7.3 Formulir PDPB yang dapat dicetak

Dari halaman PDPB, enam dokumen lampiran PKPU 1/2025 siap cetak (rincian di Lampiran A): daftar pemilih PDPB, daftar perubahan, rekap perubahan kabupaten, rekap kabupaten PDPB, berita acara, dan formulir tanggapan masyarakat.

---

# Bab 8 — Tahapan DPS, DPSHP, dan DPT

Menu Laporan → Tahapan DPS–DPT dipakai saat konteks disetel ke Pemilu atau Pilkada.

![Halaman tahapan penetapan DPS, DPSHP, dan DPT](manual-gambar/09-tahapan.png)

## 8.1 Alur tahapan

1. Admin **membuka tahap** secara berurutan: DPS dulu, sesudah ditutup baru DPSHP, lalu DPT. Aplikasi menolak urutan yang dilompati.
2. Petugas bekerja memperbaiki data seperti biasa (tanggapan masyarakat, perbaikan, TMS).
3. Saat pleno, admin mengisi metadata berita acara lalu menekan **Tetapkan**.
4. Pada saat penetapan, aplikasi mengambil **potret (salinan beku) seluruh daftar pemilih pada detik itu** dan menyimpannya sebagai arsip tahap. Sesudah itu tahap terkunci: data boleh terus berubah untuk tahap berikutnya, tetapi arsip tahap yang sudah ditetapkan **tidak berubah selamanya**.

## 8.2 Mengapa potret itu penting?

Karena cetakan resmi harus bisa **dihasilkan ulang persis sama** kapan pun diminta — untuk pleno, untuk tanggapan, atau untuk pemeriksaan. Cetakan dari arsip tahap selalu sama dengan yang dicetak pada hari penetapan, walaupun data hidup sudah berubah.

## 8.3 Membandingkan antar tahap

Halaman tahapan menyediakan tabel **Potret Antar Tahap**: jumlah pemilih tiap kecamatan pada DPS, DPSHP, dan DPT berdampingan, sehingga naik-turunnya jumlah pemilih antar tahap terlihat jelas. Dari arsip ini pula dicetak daftar perubahan antar tahap (siapa yang baru, siapa yang berubah elemen datanya, siapa yang pergi/TMS) dan rekap perubahannya pada jenjang PPS, PPK, dan kabupaten.

---

# Bab 9 — DPTb, DPK, dan TPS Lokasi Khusus

Menu Laporan → DPTb & Lokasi Khusus adalah register **resmi** pindah memilih.

![Register DPTb dan TPS lokasi khusus](manual-gambar/10-dptb.png)

## 9.1 Mendaftarkan pemilih pindahan

1. Cari pemilih (harus berstatus MS) pada kotak pencarian.
2. Tentukan tujuan: pilih desa dan TPS tujuan bila masih di dalam kabupaten, atau tulis wilayah tujuannya bila keluar kabupaten.
3. Pilih **alasan pindah** dari sepuluh alasan resmi (bekerja di luar domisili, tugas belajar, rawat inap, pindah domisili, bencana, dan lain-lain).
4. Klik **Daftarkan**. Satu pemilih hanya boleh memiliki satu pengajuan aktif; pengajuan dapat dihapus oleh PPS/PPK di wilayah asal atau tujuan.

## 9.2 Cetakan DPTb

- **Model A-Surat Pindah Memilih** untuk setiap pemilih yang mengajukan.
- **Model A-Daftar Pemilih Pindahan** per TPS tujuan — daftar yang diterima PPS tujuan.
- **Model A-Rekap Pemilih Pindahan** jenjang PPS, PPK, dan kabupaten (masuk dan keluar dihitung terpisah dengan benar).
- **Tanda Bukti Terdaftar** dua rangkap untuk setiap pemilih.

## 9.3 DPK (pemilih khusus)

Sesuai ketentuan, DPK **dicatat oleh KPPS pada hari pemungutan** dari KTP-el pemilih, bukan dari data yang disiapkan sebelumnya. Karena itu aplikasi menyediakan **daftar hadir DPK berformat resmi dalam keadaan kosong** untuk dicetak per TPS dan diisi di tempat. Aplikasi menyatakan dengan jujur pada kakinya bahwa berkas itu adalah dokumen bantu, bukan formulir Model A.

## 9.4 TPS lokasi khusus

Daftarkan TPS yang berada di lokasi khusus — rumah tahanan/lapas, rumah sakit, panti sosial, panti rehabilitasi, pesantren, kampus, dan **perusahaan/perkebunan** (sangat relevan untuk daerah perkebunan) — beserta nama lokasinya. Daftar pemilih pada TPS itu dapat dicetak tersendiri untuk dibagikan kepada pengelola lokasi dan KPPS setempat.

---

# Bab 10 — Banding Sidalih dan Analisis Beban TPS

## 10.1 Mengapa perlu banding data?

PPK/PPS bekerja di dua tempat: di aplikasi ini untuk memeriksa dan membersihkan, dan di Sidalih untuk penetapan resmi. Supaya keduanya tidak diam-diam berbeda, aplikasi menyediakan alat banding.

![Ekspor dan banding Sidalih](manual-gambar/11-sidalih.png)

## 10.2 Ekspor format Sidalih

Klik **Unduh Ekspor Sidalih** untuk mendapatkan seluruh daftar pemilih wilayah Anda sebagai CSV dengan **judul kolom yang persis sama** dengan berkas Sidalih (DPID, KETERANGAN, TEMPAT LAHIR, dan seterusnya). Berkas ini mudah disandingkan di spreadsheet dengan berkas dari Sidalih.

## 10.3 Membandingkan (banding)

1. Unduh berkas data dari Sidalih.
2. Unggah berkas itu (Excel atau CSV) di halaman Banding Sidalih.
3. Aplikasi membandingkan **per NIK** dan melaporkan empat angka: berapa yang **sama**, berapa yang **beda elemen** (nama/keterangan/TPS/desa), berapa yang **hanya ada di Sidalih**, dan berapa yang **hanya ada di aplikasi**.
4. Unduh **CSV selisih** untuk melihat baris-barisnya satu per satu dan menindaklanjutinya.

Hasil banding tersimpan di basis data lokal dan dapat dikosongkan kapan pun. Ingat: berkas selisih memuat NIK — perlakukan seperti data pemilih pada umumnya.

## 10.4 Analisis Beban TPS

![Analisis beban TPS: 8 TPS melebihi batas dan 307 TPS mendekati batas pada data nyata](manual-gambar/12-beban-tps.png)

Halaman ini menghitung beban setiap TPS: **pemilih MS terdaftar + pemilih pindahan yang masuk**, dibandingkan dengan batas resmi (300 untuk Pemilu, 600 untuk Pilkada). Hasilnya:

- ringkasan per kecamatan (jumlah TPS, beban terbesar, berapa yang mendekati dan melebihi),
- daftar TPS yang **melebihi batas** (dengan angka kelebihannya) dan yang **mendekati** (90% batas).

Pada data nyata kabupaten ini ditemukan **8 TPS melebihi batas 300** (terbesar 341 pemilih) dan **307 TPS mendekati batas** — bahan yang sangat berguna untuk **usulan pemecahan TPS** kepada KPU Kabupaten/Kota pada tahap pemetaan TPS, jauh sebelum hari pemungutan.

---

# Bab 11 — Laporan dan Administrasi

## 11.1 Laporan ringkasan

Menu Laporan → Laporan Ringkasan menyajikan rekap per desa dan per TPS: total coklit, MS/aktif, jumlah KK, baru, tersaring, dan pindah TPS. Halaman ini dapat dicetak langsung untuk bahan rapat pleno terbuka dan koordinasi.

![Laporan ringkasan per desa](manual-gambar/07-laporan.png)

Selain itu tersedia ekspor sembilan kumpulan data (pemilih aktif, baru, tersaring, pindah, ganda, peringatan, duplikat terselubung, KK tersebar, umur ekstrem) dalam CSV dan Excel.

## 11.2 Pengguna

Admin mengelola akun dari menu Administrasi → Pengguna: membuat akun PPK/PPS, menentukan peran dan wilayahnya, menonaktifkan akun, dan mengatur ulang kata sandi (kata sandi baru dibuat acak oleh sistem dan hanya tampil satu kali — tidak ada kata sandi tetap yang diketahui semua orang).

![Pengelolaan pengguna](manual-gambar/15-pengguna.png)

## 11.3 Log akses dan cadangan

- **Log Akses** mencatat siapa masuk, siapa mengekspor, dan siapa mengunduh apa — jejak audit sederhana.
- **Cadangan (backup)** basis data dapat dibuat berkala dari menu Administrasi. Simpan berkas cadangan di tempat terpisah dari laptop utama.
- **Pencarian global** di bagian atas layar menemukan pemilih dari halaman mana pun.

---

# Bab 12 — Alur Kerja Lengkap dari Awal sampai Akhir

## Alur A — Menyiapkan data satu kabupaten (awal tahapan)

1. Admin memasang aplikasi dan membuat basis data (Bab 1).
2. Admin mengimpor berkas ekspor Sidalih satu kabupaten (Bab 5.6). Periksa laporan impor: jumlah masuk, tertahan karena NIK sudah aktif, dan tertolak.
3. Admin membuat akun PPK tiap kecamatan dan PPS tiap desa (Bab 11.2).
4. Setiap PPK/PPS membuka dasbor wilayahnya dan mencatat angka awal (Bab 3).

## Alur B — Membersihkan data (pekerjaan utama PPK/PPS)

1. Buka kartu **Perlu Ditangani** di dasbor.
2. Kerjakan satu per satu: NIK Ganda (Bab 6.1) → Duplikat Terselubung dan KK Tersebar (Bab 6.2) → Umur Ekstrem → Peringatan JK vs NIK.
3. Untuk setiap temuan: periksa ke lapangan/dokumen, lalu perbaiki lewat Kelola Data Coklit (Bab 5). Setiap perbaikan tercatat di riwayat.
4. Unduh daftar anomali per desa sebagai daftar kerja PPS bila pemeriksaan harus dilakukan berjenjang.
5. Setelah bersih di aplikasi, lakukan perbaikan yang sama di **Sidalih** — di sanalah penetapan resmi berada.

## Alur C — PDPB triwulanan (di luar tahapan)

1. Pastikan konteks = PDPB (Bab 2.3).
2. Sepanjang triwulan, catat setiap perubahan lewat Kelola Data Coklit beserta dokumen buktinya (Bab 5).
3. Di akhir triwulan, periksa rekap PDPB per desa (Bab 7.1).
4. Isi berita acara, tutup periode, dan cetak enam formulir PDPB untuk pleno (Bab 7.2–7.3).

## Alur D — Tahapan Pemilu/Pilkada

1. Admin mengubah konteks menjadi Pemilu/Pilkada dan membuka tahap **DPS** (Bab 8).
2. Masa tanggapan masyarakat: perbaikan dicatat, tanggapan dicetak dari formulir PDPB/tanggapan yang tersedia.
3. Tetapkan DPS saat pleno — potret membeku.
4. Ulangi untuk **DPSHP**, lalu **DPT**. Gunakan perbandingan antar tahap untuk menjelaskan perubahan angka pada pleno.
5. Cetak daftar pemilih per TPS, daftar perubahan, dan rekap-rekapnya dari arsip tahap (Lampiran A).

## Alur E — Menjelang pemungutan

1. Daftarkan seluruh pengajuan pindah memilih di register DPTb; cetak surat pindah dan daftar pindahan per TPS tujuan (Bab 9).
2. Daftarkan TPS lokasi khusus dan cetak daftar pemilihnya (Bab 9.4).
3. Cetak daftar hadir DPK kosong per TPS (Bab 9.3).
4. Periksa Beban TPS; sampaikan usulan pemecahan TPS yang melebihi batas (Bab 10.4).
5. Lakukan banding terakhir dengan berkas Sidalih untuk memastikan tidak ada selisih yang tertinggal (Bab 10.3).

---

# Bab 13 — Kelebihan Aplikasi Ini Dibanding Sidalih Resmi

Sidalih dan aplikasi ini **tidak berhadapan — mereka berpasangan**. Sidalih adalah sistem nasional milik KPU: terhubung ke data Dukcapil, dipakai berjenjang dari PPS sampai KPU RI, menjadi dasar penetapan resmi DPS/DPT, dan membuka layanan cek DPT bagi masyarakat. Semua itu **tidak dimiliki dan tidak coba digantikan** oleh aplikasi ini.

Yang dilakukan aplikasi ini adalah menutup **celah-celah kerja di tingkat bawah** yang selama ini dikerjakan manual dengan spreadsheet atau tidak dikerjakan sama sekali.

| Kebutuhan di lapangan | Sidalih resmi | Aplikasi ini |
|---|---|---|
| Penetapan resmi DPS/DPT | **Ya — satu-satunya yang sah** | Tidak (hanya menyiapkan datanya) |
| Terhubung Dukcapil (DP4) | Ya | Tidak |
| Cek DPT untuk masyarakat | Ya (daring) | Tidak |
| Bekerja tanpa internet | Tidak — harus daring | **Ya — 100% lokal/offline** |
| Kecepatan di wilayah internet lemah | Tergantung jaringan | **Cepat — data di komputer sendiri** |
| Mencari duplikat terselubung (nama + tanggal lahir sama, NIK beda) | Tidak ditampilkan sebagai daftar kerja | **Ya — daftar siap periksa** |
| Memeriksa KK tersebar antar TPS/desa | Tidak | **Ya** |
| Peringatan jenis kelamin vs kode NIK | Tidak | **Ya (5.244 temuan pada data nyata)** |
| Analisis beban TPS vs batas 300/600 + usulan pemecahan | Tidak | **Ya (8 TPS melebihi batas terdeteksi)** |
| Banding berkas Sidalih vs data kerja lokal per NIK | Tidak | **Ya, dengan unduhan selisih** |
| Arsip potret beku tiap penetapan tahap (cetak ulang persis sama) | Terbatas pada hasil penetapan | **Ya — DPS, DPSHP, DPT tersimpan per baris** |
| Cetak 17 formulir lampiran PKPU + 6 formulir PDPB dari data sendiri | Formulir dihasilkan di dalam Sidalih | **Ya — dari data lokal, bisa sebelum/di luar jadwal Sidalih** |
| PDPB triwulanan dengan rekap otomatis per desa dan berita acara | Ada di Sidalih untuk jajaran KPU | **Ya — dihitung dari catatan perubahan, bukan diketik ulang** |
| Jejak riwayat setiap perubahan (siapa, kapan, nilai lama–baru) | Tercatat di sistem pusat | **Ya — tersimpan lokal dan dapat diperiksa PPK/PPS** |
| Pembatasan wilayah per pengguna (PPK hanya kecamatannya, PPS hanya desanya) | Ya, berjenjang nasional | **Ya, berjenjang kabupaten** |
| Kemudahan belajar untuk petugas baru | Relatif kompleks (diakui dalam tulisan KPU sendiri: alur dan kodefikasi Sidalih banyak dan rumit dipahami) | **Sederhana — menu sedikit, bahasa sehari-hari, kotak pilihan bisa diketik** |
| Biaya lisensi | Sistem KPU | **Gratis — program lokal, tanpa biaya berlangganan** |

## Ringkasnya, lima kelebihan utama

1. **Tetap bekerja saat internet mati.** Seluruh data dan pengolahan ada di laptop PPK — tidak ada lagi pekerjaan tertunda karena jaringan atau server pusat sibuk.
2. **Membersihkan data secara aktif.** Aplikasi *mencari* kegandaan dan kejanggalan (duplikat terselubung, KK tersebar, umur ekstrem, JK vs NIK) dan memberikannya sebagai daftar kerja, bukan menunggu petugas menemukannya sendiri.
3. **Formulir resmi tercetak dari data sendiri, kapan pun.** 23 formulir lampiran PKPU (tahapan + PDPB) siap cetak dari data lokal, termasuk berita acara pleno tiga jenjang.
4. **Setiap penetapan membeku sebagai arsip.** Potret DPS/DPSHP/DPT tersimpan per baris pemilih, sehingga cetakan dan angka pleno dapat dibuktikan kembali persis sama di kemudian hari.
5. **Menjaga data tetap di rumah sendiri.** Data pemilih satu kabupaten tersimpan di komputer penyelenggara setempat dengan pembatasan wilayah per pengguna — tidak diunggah ke layanan pihak ketiga mana pun.

## Bentuk kerja sama yang ditawarkan

- **Uji coba terbatas** di satu-dua kecamatan untuk satu siklus PDPB triwulanan, dengan pendampingan pengembang.
- **Pelatihan singkat** bagi operator PPK/PPS (materi buku ini).
- **Penyesuaian formulir** bila KPU Kabupaten/Kota memiliki kebutuhan lokal tambahan.
- Pengembangan berkelanjutan berdasarkan masukan resmi KPU.

---

# Bab 14 — Keamanan dan Kerahasiaan Data

1. Data pemilih adalah data pribadi yang dilindungi undang-undang. Aplikasi ini menyimpan data **hanya di komputer penyelenggara**; tidak ada pengiriman data ke internet atau pihak ketiga.
2. Setiap pengguna masuk dengan akun sendiri; kata sandi tersimpan terenkripsi (hash). Bagikan akun = membagikan tanggung jawab.
3. Peran Peninjau (viewer) melihat NIK/NKK dalam keadaan **tersamar**.
4. Buat cadangan basis data secara berkala dan simpan di media terpisah.
5. Berkas hasil ekspor dan banding yang memuat NIK harus diperlakukan sebagai dokumen rahasia: jangan dibagikan lewat grup percakapan umum.
6. Gambar-gambar pada buku panduan ini telah disamarkan pada kolom NIK, NKK, dan nama.

---

# Bab 15 — Tanya Jawab dan Pemecahan Masalah

**Apakah aplikasi ini menggantikan Sidalih?**
Tidak. Sidalih tetap sistem resmi dan tempat penetapan. Aplikasi ini alat bantu persiapan dan pembersihan data di tingkat PPK/PPS.

**Apakah perlu internet?**
Tidak. Semua berjalan di komputer lokal. Internet hanya diperlukan sekali bila hendak mengambil berkas program atau berkas ekspor dari Sidalih.

**Data saya 300 ribu baris lebih. Apakah lambat?**
Tidak untuk pemakaian normal. Pada pengujian dengan 338.108 pemilih, halaman daftar dan analisis tampil dalam hitungan detik karena hasil analisis disimpan dalam cache yang diperbarui setiap ada perubahan data. Ekspor satu kabupaten penuh memang memerlukan waktu sekitar satu menit karena jumlah barisnya sangat besar.

**NIK yang sama ditolak saat saya menambah pemilih. Mengapa?**
Itu pengaman: satu NIK hanya boleh aktif satu kali dalam satu kabupaten. Periksa dulu apakah orang itu sudah terdaftar (mungkin di desa/kecamatan lain). Bila baris lamanya memang harus nonaktif, tandai TMS lebih dulu.

**Mengapa kode 5 dan 8 artinya berubah-ubah?**
Karena memang begitu aturannya: pada penyelenggaraan Pemilu/Pilkada, kode 5 = tidak dikenal dan 8 = salah penempatan TPS; pada PDPB, kode 5 = warga negara asing dan 8 = hak politik dicabut. Aplikasi menyesuaikan arti kode dengan konteks yang diatur admin.

**Periode PDPB salah nama, bagaimana?**
Admin dapat mengganti nama periode berjalan dari halaman PDPB sebelum periode ditutup.

**Saya salah menetapkan tahap. Bisakah dibatalkan?**
Tahap yang sudah ditetapkan terkunci demi keutuhan arsip. Hubungi admin kabupaten; pembatalan hanya dapat dilakukan langsung pada basis data oleh pengembang dengan berita acara perbaikan.

**Lupa kata sandi?**
Hubungi admin wilayah Anda (PPK untuk PPS, admin kabupaten untuk PPK) untuk pengaturan ulang. Kata sandi baru tampil satu kali — segera catat dan ganti setelah masuk.

**Angka di aplikasi berbeda dengan Sidalih. Mana yang benar?**
Sidalih yang resmi. Gunakan fitur Banding Sidalih (Bab 10) untuk menemukan baris-baris yang berbeda, periksa penyebabnya, lalu samakan data kerja Anda dengan Sidalih.

---

# Lampiran A — Daftar Formulir Cetak

## Formulir tahapan (Pemilu/Pilkada) — dari arsip potret tahap

| Kode | Nama formulir | Dicetak dari |
|---|---|---|
| P1 | Model A-Daftar Pemilih per TPS (DPS/DPSHP/DPT) | Arsip tahap |
| P2 | Daftar Pemilih Potensial per TPS | Data hidup |
| P3 | Laporan Hasil Coklit per TPS | Data hidup |
| P4 | Model A-Tanda Bukti Terdaftar (2 rangkap) | Per pemilih |
| P5 | Model A-Daftar Perubahan Pemilih antar tahap | Selisih dua potret |
| P6 | Model A-Rekap Perubahan Pemilih — PPS | Selisih dua potret |
| P10 | Model A-Rekap Perubahan Pemilih — PPK | Selisih dua potret |
| P11 | Model A-Rekap Perubahan Pemilih — Kabupaten/Kota | Selisih dua potret |
| P13 | Model A-Rekap Kabupaten/Kota per kecamatan | Arsip tahap |
| P15 | Model A-Surat Pindah Memilih | Per pengajuan DPTb |
| P16 | Model A-Daftar Pemilih Pindahan per TPS tujuan | Register DPTb |
| P17 | Model A-Rekap Pemilih Pindahan (PPS/PPK/Kabupaten) | Register DPTb |
| — | Berita Acara pleno PPS, PPK, dan Kabupaten/Kota | Metadata tahap |
| — | Daftar hadir DPK per TPS (dokumen bantu, register kosong) | Per TPS |
| — | Daftar pemilih TPS lokasi khusus | Register loksus |

## Formulir PDPB (PKPU 1/2025 jo. 1/2026)

| Kode | Nama formulir |
|---|---|
| D1 | Model A-Daftar Pemilih-PDPB per desa |
| D2 | Model A-Daftar Perubahan Pemilih-PDPB per desa per periode |
| D3 | Model A-Rekap Kabko-PDPB per kecamatan |
| D4 | Model A-Rekap Perubahan Kabko per desa |
| D5 | Berita Acara pleno PDPB (Lampiran VII) |
| D6 | Model A-Tanggapan Masyarakat-PDPB (register per desa) |

Semua formulir memakai kertas A4 (daftar dan rekap dalam posisi mendatar/landscape, berita acara tegak/portrait, tanda bukti kecil dua rangkap), dilengkapi kop nama model di kanan atas, identitas wilayah, dan blok pengesahan sesuai lampiran PKPU.

---

# Lampiran B — Arti Kode Keterangan Pemilih

## Kode memenuhi syarat (MS)

| Kode | Arti |
|---|---|
| 0 | Pemilih sesuai / memenuhi syarat |
| U | Ubah elemen data |
| B | Pemilih baru |

## Kode tidak memenuhi syarat (TMS) — penyelenggaraan Pemilu/Pilkada

| Kode | Arti |
|---|---|
| 1 | Meninggal dunia |
| 2 | Ganda |
| 3 | Di bawah umur |
| 4 | Pindah domisili |
| 5 | Tidak dikenal |
| 6 | TNI |
| 7 | Polri |
| 8 | Salah penempatan TPS |

## Kode tidak memenuhi syarat (TMS) — PDPB

| Kode | Arti |
|---|---|
| 1 | Meninggal dunia |
| 2 | Ganda |
| 3 | Di bawah umur |
| 4 | Pindah domisili |
| 5 | Warga negara asing |
| 6 | TNI |
| 7 | Polri |
| 8 | Hak politik dicabut |

Perhatikan perbedaan kode **5** dan **8** antara kedua rezim — aplikasi menampilkannya sesuai konteks yang sedang aktif.

---

*Dokumen ini dibuat sebagai panduan resmi penggunaan aplikasi dan bahan pengajuan kerja sama. Untuk pertanyaan, pelatihan, atau uji coba, hubungi pengembang: Amwaluddin Lubis, S.T. — amwaluddin.lubis@gmail.com.*
