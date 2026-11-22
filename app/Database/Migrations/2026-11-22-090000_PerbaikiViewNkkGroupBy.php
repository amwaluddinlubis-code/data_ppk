<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Perbaiki view v_nkk agar sah pada MySQL dengan sql_mode
 * ONLY_FULL_GROUP_BY (bawaan MySQL 8): definisi lama memilih kolom TPS
 * tanpa mengelompokkannya, sehingga setiap query ke v_nkk gagal di
 * MySQL ketat (di MariaDB lolos dengan TPS acak per NKK).
 *
 * Maksud view tidak berubah: satu baris per (KEC, DESA, NKK). TPS kini
 * diambil deterministik dengan MIN() — rumah tangga yang barisnya
 * tersebar di beberapa TPS dihitung pada TPS terkecilnya.
 */
class PerbaikiViewNkkGroupBy extends Migration
{
    public function up()
    {
        $this->db->query(
            'CREATE OR REPLACE VIEW `v_nkk` AS '
            . 'SELECT `ms`.`KEC` AS `KEC`, `ms`.`DESA` AS `DESA`, MIN(`ms`.`TPS`) AS `TPS`, `ms`.`NKK` AS `NKK` '
            . 'FROM `ms` GROUP BY `ms`.`KEC`, `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA`, `TPS`'
        );
    }

    public function down()
    {
        $this->db->query(
            'CREATE OR REPLACE VIEW `v_nkk` AS '
            . 'SELECT DISTINCT `ms`.`KEC` AS `KEC`, `ms`.`DESA` AS `DESA`, `ms`.`TPS` AS `TPS`, `ms`.`NKK` AS `NKK` '
            . 'FROM `ms` GROUP BY `ms`.`KEC`, `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA`, `ms`.`TPS`'
        );
    }
}
