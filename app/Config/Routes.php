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
    $routes->post('dashboard/getPemilih', 'Dashboard::getPemilih');

    // Pemilih Pindah TPS
    $routes->get('pemilih', 'Pemilih::index');
    $routes->post('pemilih/pindahTPS', 'Pemilih::pindahTPS');

    // Pemilih Baru
    $routes->get('pemilih/baru', 'Pemilih::baru');
    $routes->post('pemilih/getPemilihBaru', 'Pemilih::getPemilihBaru');

    // Pemilih Tersaring (TMS)
    $routes->get('pemilih/tersaring', 'Pemilih::tersaring');
    $routes->post('pemilih/getPemilihTersaring', 'Pemilih::getPemilihTersaring');

    // Pemilih Aktif / Memenuhi Syarat + Cetak
    $routes->get('pemilih/aktifPrint', 'Pemilih::aktifPrint');
    $routes->post('pemilih/aktif', 'Pemilih::aktif');
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
    $routes->post('residu/getGanda', 'Residu::getGanda');
});
