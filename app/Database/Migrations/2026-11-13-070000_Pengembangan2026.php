<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Pengembangan 2026 (Fase F3/G/H) — tabel pendukung:
 * - coktas: hasil pencocokan dan penelitian terbatas per pemilih
 *   pada periode PDPB berjalan (Surat Dinas KPU 228/PP.05-SD/13/2026).
 * - permohonan_perbaikan: register permohonan perbaikan data dari
 *   warga (hak subjek data, UU 27/2022 — target tindak lanjut 3x24 jam).
 * - saran_perbaikan: register saran perbaikan tertulis Bawaslu
 *   (Perbawaslu 1/2025 Pasal 9) beserta status tindak lanjutnya.
 * - log_ekspor: catatan setiap ekspor/unduhan data keluar aplikasi
 *   (perekaman pemrosesan, UU 27/2022 Pasal 31).
 * - filter_tersimpan: filter halaman yang disimpan pengguna.
 */
class Pengembangan2026 extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'periode_id'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'mhc_id'       => ['type' => 'INT', 'unsigned' => true],
            'kelurahan_id' => ['type' => 'INT', 'unsigned' => true],
            'tps'          => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'hasil'        => ['type' => 'VARCHAR', 'constraint' => 20], // sesuai|tidak_sesuai|tidak_ditemukan
            'keterangan'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'oleh'         => ['type' => 'VARCHAR', 'constraint' => 60],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['periode_id', 'mhc_id']);
        $this->forge->addKey('kelurahan_id');
        $this->forge->createTable('coktas', true);

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'nik'          => ['type' => 'VARCHAR', 'constraint' => 20],
            'nama'         => ['type' => 'VARCHAR', 'constraint' => 120],
            'kelurahan_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'unsur'        => ['type' => 'VARCHAR', 'constraint' => 60],
            'uraian'       => ['type' => 'TEXT', 'null' => true],
            'pemohon'      => ['type' => 'VARCHAR', 'constraint' => 120],
            'kontak'       => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'baru'], // baru|diproses|selesai|ditolak
            'catatan'      => ['type' => 'TEXT', 'null' => true],
            'dibuat_oleh'  => ['type' => 'VARCHAR', 'constraint' => 60],
            'selesai_pada' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('permohonan_perbaikan', true);

        $this->forge->addField([
            'id'                => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'periode_id'        => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'nomor_surat'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'tanggal'           => ['type' => 'DATE', 'null' => true],
            'uraian'            => ['type' => 'TEXT'],
            'penanggung_jawab'  => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'status'            => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'baru'], // baru|ditindaklanjuti|selesai
            'tindak_lanjut'     => ['type' => 'TEXT', 'null' => true],
            'dibuat_oleh'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('saran_perbaikan', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 60],
            'jenis'      => ['type' => 'VARCHAR', 'constraint' => 80],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'jumlah'     => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('log_ekspor', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'unsigned' => true],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 80],
            'modul'      => ['type' => 'VARCHAR', 'constraint' => 40],
            'parameter'  => ['type' => 'TEXT'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'modul']);
        $this->forge->createTable('filter_tersimpan', true);
    }

    public function down()
    {
        $this->forge->dropTable('filter_tersimpan', true);
        $this->forge->dropTable('log_ekspor', true);
        $this->forge->dropTable('saran_perbaikan', true);
        $this->forge->dropTable('permohonan_perbaikan', true);
        $this->forge->dropTable('coktas', true);
    }
}
