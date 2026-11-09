<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 3 — Catatan setiap impor berkas (coklit `mhc` dan
 * keterangan `tms8`): berkas, mode, jumlah baris masuk/gagal,
 * dan lokasi cadangan otomatis sebelum mode timpa.
 */
class ImporLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'jenis' => [
                'type'       => 'ENUM',
                'constraint' => ['mhc', 'tms8'],
            ],
            'mode' => [
                'type'       => 'ENUM',
                'constraint' => ['ganti', 'tambah'],
            ],
            'nama_berkas' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'jumlah_baris' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'jumlah_gagal' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'berkas_cadangan' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'pengguna_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
            ],
            'pengguna_username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('impor_log');
    }

    public function down()
    {
        $this->forge->dropTable('impor_log');
    }
}
