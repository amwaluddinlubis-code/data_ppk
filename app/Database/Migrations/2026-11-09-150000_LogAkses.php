<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 5 — Log akses data sensitif.
 *
 * Mencatat masuk/keluar, cetak & unduh daftar pemilih, pembuatan
 * cadangan, dan pergantian kecamatan aktif (Fase 6). Tabel ini hanya
 * ada di basis data UTAMA (grup 'default') — penulisan selalu memakai
 * koneksi 'default' secara eksplisit (lihat BaseController::catatAkses).
 */
class LogAkses extends Migration
{
    protected $DBGroup = 'default';

    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
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
            'aksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'detail' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('created_at');
        $this->forge->createTable('akses_log');
    }

    public function down()
    {
        $this->forge->dropTable('akses_log');
    }
}
