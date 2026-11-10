<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --------------------------------------------------------------------
// Rute eksplisit Aplikasi Pemilih PPK (Fase 0 + Fase 1)
// Auto-routing lama DIMATIKAN: hanya URL di bawah ini yang bisa diakses.
// Semua rute kecuali login/logout berada di balik filter `auth` (Fase 1).
// --------------------------------------------------------------------

// Masuk / keluar (publik)
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::masuk');
$routes->get('logout', 'Auth::keluar');

$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Ganti sandi (semua peran yang sudah masuk)
    $routes->get('ganti-sandi', 'Auth::gantiSandi');
    $routes->post('ganti-sandi', 'Auth::simpanSandi');

    // Kelola pengguna (hanya Admin PPK — diperiksa lagi di controller)
    $routes->get('pengguna', 'Auth::pengguna');
    $routes->post('pengguna/tambah', 'Auth::tambahPengguna');
    $routes->post('pengguna/(:num)/aktif', 'Auth::alihAktif/$1');
    $routes->post('pengguna/(:num)/reset-sandi', 'Auth::resetSandi/$1');

    // Kelola Data Coklit — CRUD (Fase 2; Admin & Operator, diperiksa di controller)
    $routes->get('coklit', 'Coklit::index');
    $routes->get('coklit/tambah', 'Coklit::tambah');
    $routes->post('coklit/simpan', 'Coklit::simpan');
    $routes->get('coklit/edit/(:segment)', 'Coklit::edit/$1');
    $routes->post('coklit/update/(:segment)', 'Coklit::update/$1');
    $routes->post('coklit/hapus/(:segment)', 'Coklit::hapus/$1');

    // Riwayat Perubahan (Fase 2)
    $routes->get('riwayat', 'Riwayat::index');
    $routes->post('riwayat/pulih/(:num)', 'Riwayat::pulih/$1');

    // Impor data (Fase 3; Admin & Operator kecamatan, diperiksa di controller)
    $routes->get('impor', 'Impor::index');
    $routes->post('impor/mhc', 'Impor::prosesMhc');
    $routes->post('impor/tms8', 'Impor::prosesTms8');

    // Beranda: Daftar Hasil Coklit Pantarlih
    $routes->get('/', 'Dashboard::index');
    $routes->get('dashboard/tabelPemilih', 'Dashboard::tabelPemilih');
    $routes->get('filter/tps', 'Dashboard::daftarTps');
    $routes->get('filter/tps-opsi', 'Dashboard::opsiTps');

    // Pemilih Pindah TPS
    $routes->get('pemilih', 'Pemilih::index');
    $routes->get('pemilih/tabelPindah', 'Pemilih::tabelPindah');

    // Pemilih Baru
    $routes->get('pemilih/baru', 'Pemilih::baru');
    $routes->get('pemilih/tabelBaru', 'Pemilih::tabelBaru');

    // Pemilih Tersaring (TMS)
    $routes->get('pemilih/tersaring', 'Pemilih::tersaring');
    $routes->get('pemilih/tabelTersaring', 'Pemilih::tabelTersaring');

    // Pemilih Aktif / Memenuhi Syarat + Cetak
    $routes->get('pemilih/aktifPrint', 'Pemilih::aktifPrint');
    $routes->get('pemilih/tabelAktif', 'Pemilih::tabelAktif');
    $routes->get('pemilih/ms', 'Pemilih::ms');
    $routes->post('pemilih/ms', 'Pemilih::ms');
    $routes->get('pemilih/ms-pdf', 'Pemilih::msPdf');

    // Laporan ringkasan per desa / TPS (Fase 4)
    $routes->get('laporan', 'Laporan::index');

    // Keamanan data (Fase 5; Admin PPK, diperiksa lagi di controller)
    $routes->get('log-akses', 'LogAkses::index');
    $routes->get('backup', 'Backup::index');
    $routes->post('backup/buat', 'Backup::buat');

    // Multi-kecamatan (Fase 6; Admin PPK, diperiksa lagi di controller)
    // Fase 7: ganti kecamatan aktif = saringan wilayah di basis data
    // kabupaten yang sama (bukan ganti basis data seperti Fase 6).
    $routes->get('kecamatan', 'Kecamatan::index');
    $routes->post('kecamatan/aktifkan', 'Kecamatan::aktifkan');

    // NIK Ganda
    $routes->get('residu', 'Residu::index');
    $routes->get('residu/tabelGanda', 'Residu::tabelGanda');
    $routes->get('residu/tabelPeringatan', 'Residu::tabelPeringatan');
    $routes->get('anomali', 'Anomali::index');
    $routes->get('anomali/tabelTerselubung', 'Anomali::tabelTerselubung');
    $routes->get('anomali/tabelKk', 'Anomali::tabelKk');
    $routes->get('anomali/tabelUmur', 'Anomali::tabelUmur');

    // Ekspor CSV/XLSX (Paket Menengah) — mengikuti saringan halaman.
    $routes->get('ekspor/aktif', 'Pemilih::eksporAktif');
    $routes->get('ekspor/baru', 'Pemilih::eksporBaru');
    $routes->get('ekspor/tersaring', 'Pemilih::eksporTersaring');
    $routes->get('ekspor/pindah', 'Pemilih::eksporPindah');
    $routes->get('ekspor/ganda', 'Residu::eksporGanda');
    $routes->get('ekspor/peringatan', 'Residu::eksporPeringatan');
    $routes->get('ekspor/terselubung', 'Anomali::eksporTerselubung');
    $routes->get('ekspor/kk', 'Anomali::eksporKk');
    $routes->get('ekspor/umur', 'Anomali::eksporUmur');

    // Pencarian global (Paket Menengah)
    $routes->get('cari', 'Pencarian::index');
    $routes->post('anomali/acuan', 'Anomali::simpanAcuan');

    // Konteks penyelenggaraan (Fase A) — admin
    $routes->get('konteks', 'Konteks::index');
    $routes->post('konteks/simpan', 'Konteks::simpan');

    // PDPB berkelanjutan (Fase B)
    $routes->get('pdpb', 'Pdpb::index');
    $routes->get('pdpb/cetak', 'Pdpb::cetak');
    $routes->post('pdpb/tutup', 'Pdpb::tutup');
    $routes->post('pdpb/nama', 'Pdpb::gantiNama');

    // Cetak formulir PDPB Kelompok 1 (lampiran PKPU 1/2025)
    $routes->get('pdpb/cetak-perubahan', 'Pdpb::cetakPerubahan');
    $routes->get('pdpb/cetak-rekap-perubahan', 'Pdpb::cetakRekapPerubahan');
    $routes->get('pdpb/cetak-rekap', 'Pdpb::cetakRekap');
    $routes->get('pdpb/cetak-tanggapan', 'Pdpb::cetakTanggapan');
    $routes->get('pdpb/ba', 'Pdpb::ba');
    $routes->post('pdpb/ba/simpan', 'Pdpb::baSimpan');
    $routes->get('pdpb/ba/cetak', 'Pdpb::baCetak');

    // Fase C — tahapan penetapan DPS/DPSHP/DPT + cetak Kelompok 2
    $routes->get('tahapan', 'Tahapan::index');
    $routes->post('tahapan/buka', 'Tahapan::buka');
    $routes->post('tahapan/tetapkan', 'Tahapan::tetapkan');
    $routes->get('tahapan/cetak-daftar', 'Tahapan::cetakDaftar');
    $routes->get('tahapan/cetak-potensial', 'Tahapan::cetakPotensial');
    $routes->get('tahapan/cetak-laporan-coklit', 'Tahapan::cetakLaporanCoklit');
    $routes->get('tahapan/cetak-perubahan', 'Tahapan::cetakPerubahan');
    $routes->get('tahapan/cetak-rekap-perubahan', 'Tahapan::cetakRekapPerubahan');
    $routes->get('tahapan/cetak-rekap-kabko', 'Tahapan::cetakRekapKabko');
    $routes->get('tahapan/ba', 'Tahapan::baCetak');
});
