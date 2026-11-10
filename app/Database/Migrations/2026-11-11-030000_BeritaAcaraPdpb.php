<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kelompok Cetak 1 PDPB: metadata Berita Acara pleno rekapitulasi
 * (Lampiran VII PKPU 1/2025) menempel pada periode — nomor, tanggal,
 * tempat, pukul, masukan yang diterima, dan nama ketua/anggota KPU
 * Kabupaten/Kota — agar cetakan ulang Berita Acara dan pengesahan
 * Model A-Rekap Kabko-PDPB selalu identik dengan aslinya.
 */
class BeritaAcaraPdpb extends Migration
{
    public function up()
    {
        $this->db->query('ALTER TABLE `periode`
            ADD COLUMN `ba_nomor` varchar(80) DEFAULT NULL AFTER `ditutup_oleh`,
            ADD COLUMN `ba_tanggal` date DEFAULT NULL AFTER `ba_nomor`,
            ADD COLUMN `ba_tempat` varchar(120) DEFAULT NULL AFTER `ba_tanggal`,
            ADD COLUMN `ba_pukul` varchar(5) DEFAULT NULL AFTER `ba_tempat`,
            ADD COLUMN `ba_masukan` text DEFAULT NULL AFTER `ba_pukul`,
            ADD COLUMN `ba_ketua` varchar(120) DEFAULT NULL AFTER `ba_masukan`,
            ADD COLUMN `ba_anggota` text DEFAULT NULL AFTER `ba_ketua`');
    }

    public function down()
    {
        $this->db->query('ALTER TABLE `periode`
            DROP COLUMN `ba_anggota`, DROP COLUMN `ba_ketua`,
            DROP COLUMN `ba_masukan`, DROP COLUMN `ba_pukul`,
            DROP COLUMN `ba_tempat`, DROP COLUMN `ba_tanggal`,
            DROP COLUMN `ba_nomor`');
    }
}
