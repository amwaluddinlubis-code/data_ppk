<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase I (Pengembangan 2026) — lebarkan kolom konteks_jenis.
 * Jenis konteks baru Putusan MK 135 ('pemilu_nasional' 15 huruf,
 * 'pemilu_daerah' 13 huruf) tidak muat di VARCHAR(10) lama dan
 * terpotong diam-diam ('pemilu_nas'), merusak pemisahan potret
 * tahapan dan snapshot DPTb per peristiwa. Dilebarkan ke 30.
 */
class LebarkanKonteksJenis extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('tahap_penetapan', [
            'konteks_jenis' => ['type' => 'VARCHAR', 'constraint' => 30],
        ]);
        $this->forge->modifyColumn('pindah_memilih', [
            'konteks_jenis' => ['type' => 'VARCHAR', 'constraint' => 30],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('tahap_penetapan', [
            'konteks_jenis' => ['type' => 'VARCHAR', 'constraint' => 10],
        ]);
        $this->forge->modifyColumn('pindah_memilih', [
            'konteks_jenis' => ['type' => 'VARCHAR', 'constraint' => 10],
        ]);
    }
}
