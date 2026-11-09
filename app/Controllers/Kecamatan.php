<?php

namespace App\Controllers;

/**
 * Fase 6 — Ganti kecamatan aktif (hanya Admin PPK).
 *
 * Kecamatan aktif disimpan di sesi (`grup_db`); BaseController
 * menerapkannya ke grup basis data bawaan di awal setiap permintaan.
 * Pengguna, log akses, dan halaman ini sendiri selalu memakai
 * basis data utama.
 */
class Kecamatan extends BaseController
{
    public function index()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Pengaturan kecamatan hanya untuk Admin PPK.');
        }

        $konfig = config('Kecamatan');
        $grupAktif = (string) (session()->get('grup_db') ?? 'default');

        $daftar = [];
        foreach ($konfig->yangAktif() as $entri) {
            $namaBasis = $entri['grup'] === 'default'
                ? \Config\Database::connect('default')->getDatabase()
                : $entri['database'];
            $jumlah = null;
            try {
                $jumlah = \Config\Database::connect($entri['grup'])->table('mhc')->countAllResults();
            } catch (\Throwable) {
                $jumlah = null; // basis data belum dibangun / tidak terjangkau
            }
            $daftar[] = [
                'grup'     => $entri['grup'],
                'nama'     => $entri['nama'],
                'database' => $namaBasis,
                'jumlah'   => $jumlah,
                'aktif'    => $grupAktif === $entri['grup'],
            ];
        }

        return view('kecamatan/index', [
            'title'      => 'Kecamatan',
            'title_page' => 'Kecamatan & Basis Data Aktif',
            'kecamatan'  => $this->kec,
            'daftar'     => $daftar,
        ]);
    }

    public function aktifkan($grup = null)
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Pengaturan kecamatan hanya untuk Admin PPK.');
        }

        $grup = (string) $grup;
        $konfig = config('Kecamatan');
        $entri = $konfig->cari($grup);
        if ($entri === null || ! $entri['aktif']) {
            return redirect()->to('kecamatan')->with('galat', 'Kecamatan itu tidak terdaftar / belum diaktifkan di konfigurasi.');
        }

        if ($grup === 'default') {
            session()->remove('grup_db');
            $nama = 'kecamatan utama';
        } else {
            // Pastikan basis datanya benar-benar bisa dihubungi
            try {
                \Config\Database::connect($grup)->table('mhc')->countAllResults();
            } catch (\Throwable) {
                return redirect()->to('kecamatan')->with('galat', 'Basis data kecamatan "' . $entri['nama'] . '" belum siap (buat & migrasikan dulu).');
            }
            session()->set('grup_db', $grup);
            $nama = $entri['nama'];
        }

        $this->catatAkses('ganti_kecamatan', 'Kecamatan aktif diganti ke ' . $nama);

        return redirect()->to('/')->with('sukses', 'Kecamatan aktif sekarang: ' . $nama . '. Semua halaman mengikuti basis data kecamatan itu.');
    }
}
