<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase E — tabel sementara hasil unggahan ekspor Sidalih untuk
 * dibanding dengan basis data aplikasi. Isinya data pemilih milik
 * pengguna sendiri (setara mhc), tidak pernah keluar dari basis data
 * lokal; dapat dikosongkan kapan pun dari halaman Banding Sidalih.
 */
class BandingTemp extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nik'       => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'ket'       => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
            'tps'       => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'kec_nama'  => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'desa_nama' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nik');
        $this->forge->createTable('banding_temp', true);
    }

    public function down()
    {
        $this->forge->dropTable('banding_temp', true);
    }
}
