<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 1 — Tabel pengguna + peran (gerbang sebelum CRUD).
 *
 * Peran: admin (Admin PPK, semua), operator (olah data wilayahnya),
 * viewer (Pemeriksa — baca + cetak saja).
 *
 * Kolom `desa` (boleh kosong): cakupan wilayah pengguna. Kosong berarti
 * seluruh kecamatan; berisi nama desa berarti pengguna PPS desa itu.
 * Dipakai Fase 1 hanya sebagai data — pembatasan per-desa ditegakkan
 * penuh saat CRUD (Fase 2). Satu pemasangan tetap satu kecamatan
 * (keputusan multi-kecamatan ditunda sampai Fase 6, sesuai kerangka).
 */
class TabelUsers extends Migration
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
            'nama' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'username' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'peran' => [
                'type'       => 'ENUM',
                'constraint' => ['admin', 'operator', 'viewer'],
                'default'    => 'operator',
            ],
            'desa' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
            ],
            'aktif' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'harus_ganti_sandi' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('username');
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
