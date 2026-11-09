<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Fase 1 — Akun awal: satu Admin PPK.
 *
 * Username: admin — kata sandi awal: admin123
 * Akun ini WAJIB ganti sandi saat pertama masuk (flag
 * `harus_ganti_sandi` = 1; aplikasi mengarahkan paksa ke halaman
 * ganti sandi sampai sandi diganti). Hapus/ubah akun ini sesudah
 * akun admin sebenarnya dibuat di halaman Pengguna.
 */
class PenggunaAwalSeeder extends Seeder
{
    public function run()
    {
        $sudahAda = $this->db->table('users')->where('username', 'admin')->countAllResults();

        if ($sudahAda > 0) {
            return;
        }

        $this->db->table('users')->insert([
            'nama'              => 'Admin PPK',
            'username'          => 'admin',
            'password_hash'     => password_hash('admin123', PASSWORD_DEFAULT),
            'peran'             => 'admin',
            'desa'              => null,
            'aktif'             => 1,
            'harus_ganti_sandi' => 1,
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ]);
    }
}
