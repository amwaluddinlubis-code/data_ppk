<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase D — DPTb, DPK, lokasi khusus.
 *
 * - pindah_memilih: register pengajuan pindah memilih (dasar Model
 *   A-Surat Pindah Memilih, daftar pemilih pindahan, dan rekapnya).
 *   Tujuan dapat di dalam kabupaten (kelurahan_id terisi) atau di luar
 *   kabupaten (tujuan_teks diisi bebas).
 * - tps_khusus: penanda TPS lokasi khusus (lapas, rumah sakit, panti,
 *   pesantren, kampus, perusahaan) beserta nama lokasinya.
 */
class PindahMemilih extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'mhc_id'              => ['type' => 'INT', 'unsigned' => true],
            'nik'                 => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'                => ['type' => 'VARCHAR', 'constraint' => 120],
            'asal_kelurahan_id'   => ['type' => 'INT', 'unsigned' => true],
            'asal_tps'            => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'tujuan_kelurahan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tujuan_tps'          => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'tujuan_teks'         => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'alasan'              => ['type' => 'VARCHAR', 'constraint' => 60],
            'konteks_jenis'       => ['type' => 'VARCHAR', 'constraint' => 10],
            'konteks_nama'        => ['type' => 'VARCHAR', 'constraint' => 160],
            'dibuat_oleh'         => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('nik');
        $this->forge->addKey('asal_kelurahan_id');
        $this->forge->addKey('tujuan_kelurahan_id');
        $this->forge->createTable('pindah_memilih', true);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'kelurahan_id' => ['type' => 'INT', 'unsigned' => true],
            'tps'          => ['type' => 'INT', 'unsigned' => true],
            'nama_lokasi'  => ['type' => 'VARCHAR', 'constraint' => 160],
            'jenis'        => ['type' => 'VARCHAR', 'constraint' => 40],
            'keterangan'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['kelurahan_id', 'tps']);
        $this->forge->createTable('tps_khusus', true);
    }

    public function down()
    {
        $this->forge->dropTable('tps_khusus', true);
        $this->forge->dropTable('pindah_memilih', true);
    }
}
