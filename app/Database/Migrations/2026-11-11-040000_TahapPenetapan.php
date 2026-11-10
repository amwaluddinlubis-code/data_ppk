<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase C: tahapan penetapan DPS -> DPSHP -> DPT untuk konteks
 * penyelenggaraan (pemilu/pilkada).
 *
 * - tahap_penetapan: satu baris per tahap (berjalan/ditutup) lengkap
 *   dengan metadata berita acara pleno kabupaten.
 * - arsip_pemilih: POTRET BARIS daftar pemilih MS saat tahap ditetapkan
 *   — cetakan Model A per tahap harus dapat direproduksi persis
 *   selamanya, jadi potretnya per baris (bukan hanya agregat).
 * - arsip_tahap: agregat per kelurahan/kecamatan/kabupaten (+ jumlah
 *   TPS) untuk rekap dan potret antar tahap.
 */
class TahapPenetapan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'konteks_jenis' => ['type' => 'VARCHAR', 'constraint' => 10],
            'konteks_nama'  => ['type' => 'VARCHAR', 'constraint' => 160],
            'tahap'         => ['type' => 'VARCHAR', 'constraint' => 6],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'berjalan'],
            'dibuat_oleh'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ditutup_oleh'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'ditutup_at'    => ['type' => 'DATETIME', 'null' => true],
            'ba_nomor'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'ba_tanggal'    => ['type' => 'DATE', 'null' => true],
            'ba_tempat'     => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'ba_ketua'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'ba_anggota'    => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['konteks_jenis', 'tahap']);
        $this->forge->createTable('tahap_penetapan', true);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tahap_id'     => ['type' => 'INT', 'unsigned' => true],
            'kelurahan_id' => ['type' => 'INT', 'unsigned' => true],
            'tps'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'nkk'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nik'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'tmptlhr'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'tgllhr'       => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'jenis_kelamin' => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'alamat'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'rt'           => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'rw'           => ['type' => 'VARCHAR', 'constraint' => 5, 'null' => true],
            'disabilitas'  => ['type' => 'INT', 'default' => 0],
            'ektp'         => ['type' => 'VARCHAR', 'constraint' => 2, 'null' => true],
            'ket'          => ['type' => 'VARCHAR', 'constraint' => 4, 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tahap_id', 'nik']);
        $this->forge->addKey(['tahap_id', 'kelurahan_id', 'tps']);
        $this->forge->createTable('arsip_pemilih', true);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tahap_id'     => ['type' => 'INT', 'unsigned' => true],
            'tingkat'      => ['type' => 'VARCHAR', 'constraint' => 12],
            'kecamatan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'kelurahan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'metrik'       => ['type' => 'TEXT'],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['tahap_id', 'tingkat']);
        $this->forge->createTable('arsip_tahap', true);
    }

    public function down()
    {
        $this->forge->dropTable('arsip_tahap', true);
        $this->forge->dropTable('arsip_pemilih', true);
        $this->forge->dropTable('tahap_penetapan', true);
    }
}
