<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Data awal tabel `config` (dari basis data contoh Ranto Baek).
 * Tabel referensi lain (desa, kec) adalah VIEW dari mhc sehingga
 * tidak memerlukan seeder.
 */
class DataAwalSeeder extends Seeder
{
    public function run()
    {
        $this->db->query('INSERT INTO `config` (`TB_PEMILIH_AKTIF`) VALUES
(\'tbpemilih\'),
(\'tbpemilih\'),
(\'tbpemilih\');');
    }
}
