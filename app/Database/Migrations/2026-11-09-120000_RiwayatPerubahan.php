<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 2 — Jejak audit perubahan data.
 *
 * Setiap tambah/ubah/hapus pada data coklit (mhc) dicatat di sini
 * beserta salinan baris sebelum & sesudah (JSON). Catatan aksi 'hapus'
 * dapat dipulihkan kembali menjadi baris mhc dari halaman Riwayat.
 */
class RiwayatPerubahan extends Migration
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
            'tabel' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'mhc',
            ],
            'kunci_uid' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
            ],
            'nik' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
            ],
            'aksi' => [
                'type'       => 'ENUM',
                'constraint' => ['tambah', 'ubah', 'hapus', 'pulih', 'impor'],
            ],
            'data_lama' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'data_baru' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'pengguna_id' => [
                'type'     => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null'     => true,
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
        $this->forge->addKey('kunci_uid');
        $this->forge->addKey('created_at');
        $this->forge->createTable('riwayat_perubahan');
    }

    public function down()
    {
        $this->forge->dropTable('riwayat_perubahan');
    }
}
