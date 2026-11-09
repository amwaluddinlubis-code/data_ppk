<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Fase 6 — Daftar kecamatan & basis datanya (mode multi-kecamatan).
 *
 * Satu pemasangan aplikasi melayani banyak kecamatan: setiap kecamatan
 * punya basis data sendiri di server MySQL yang sama (kredensial sama
 * dengan basis data utama di .env; yang berbeda hanya nama basis data).
 *
 * Cara menambah kecamatan:
 * 1. Buat basis data kosong di MySQL, mis. `ppk_sinunukan`.
 * 2. Tambahkan entri di bawah (grup unik, nama tampilan, nama basis
 *    data) dan set 'aktif' => true.
 * 3. Bangun skemanya dari folder proyek:
 *      php spark migrate -g <grup>
 *      php spark db:seed DataAwalSeeder -g <grup>   (bila perlu)
 *    (tabel users TIDAK perlu diisi di sana — pengguna selalu dari
 *    basis data utama.)
 * 4. Admin PPK mengganti kecamatan aktif lewat menu Kecamatan.
 *
 * Entri contoh di bawah memakai pola nama basis data lama
 * (ppk_*) — sesuaikan dengan nama basis data yang sebenarnya,
 * lalu ubah 'aktif' menjadi true.
 */
class Kecamatan extends BaseConfig
{
    /**
     * @var list<array{grup: string, nama: string, database: string, aktif: bool}>
     */
    public array $daftar = [
        [
            'grup'     => 'default',
            'nama'     => 'Kecamatan Utama (dari .env)',
            'database' => '', // mengikuti database.default.database di .env
            'aktif'    => true,
        ],
        [
            'grup'     => 'sinunukan',
            'nama'     => 'Sinunukan',
            'database' => 'ppk_sinunukan',
            'aktif'    => false,
        ],
        [
            'grup'     => 'batangnatal',
            'nama'     => 'Batang Natal',
            'database' => 'ppk_batangnatal',
            'aktif'    => false,
        ],
        [
            'grup'     => 'psm',
            'nama'     => 'PSM',
            'database' => 'ppk_psm',
            'aktif'    => false,
        ],
        [
            'grup'     => 'mbg',
            'nama'     => 'MBG',
            'database' => 'ppk_mbg',
            'aktif'    => false,
        ],
        [
            'grup'     => 'pansel',
            'nama'     => 'Pansel',
            'database' => 'ppk_pansel',
            'aktif'    => false,
        ],
        [
            'grup'     => 'lsm',
            'nama'     => 'LSM',
            'database' => 'ppk_lsm',
            'aktif'    => false,
        ],
        [
            'grup'     => 'batahan',
            'nama'     => 'Batahan',
            'database' => 'ppk_batahan',
            'aktif'    => false,
        ],
    ];

    /** Entri satu grup, atau null bila tidak terdaftar. */
    public function cari(string $grup): ?array
    {
        foreach ($this->daftar as $entri) {
            if ($entri['grup'] === $grup) {
                return $entri;
            }
        }

        return null;
    }

    /** Daftar kecamatan yang aktif saja (termasuk utama). */
    public function yangAktif(): array
    {
        return array_values(array_filter($this->daftar, static fn ($e) => $e['aktif']));
    }
}
