<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase A + B (analisis PKPU 11 Nov 2026):
 *
 * - Fase A: kunci konteks penyelenggaraan pada `pengaturan`
 *   (konteks_jenis: pemilu|pilkada|pdpb; konteks_nama) — label kode
 *   keterangan 5/8 dan batas TPS mengikuti rezim aktif.
 * - Fase B: tabel `periode` (periode PDPB berjalan/ditutup),
 *   `arsip_rekap` (potret agregat pleno per periode), dan kolom
 *   penanda periode + dokumen bukti pada `riwayat_perubahan`
 *   (PKPU 1/2025 Pasal 17 ayat (4): perubahan dibuktikan dokumen).
 */
class KonteksDanPdpb extends Migration
{
    public function up()
    {
        $db = $this->db;

        $db->query(
            "INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES (?, ?), (?, ?) "
            . 'ON DUPLICATE KEY UPDATE `kunci` = `kunci`',
            [
                'konteks_jenis', 'pdpb',
                'konteks_nama', 'Pemutakhiran Data Pemilih Berkelanjutan (PDPB)',
            ]
        );

        $db->query(<<<'SQL'
        CREATE TABLE `periode` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `jenis` varchar(20) NOT NULL DEFAULT 'pdpb',
          `nama` varchar(120) NOT NULL,
          `mulai` date NOT NULL,
          `selesai` date DEFAULT NULL,
          `status` enum('berjalan','ditutup') NOT NULL DEFAULT 'berjalan',
          `ditutup_oleh` varchar(50) DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_periode_status` (`jenis`, `status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `arsip_rekap` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `periode_id` int(11) unsigned NOT NULL,
          `tingkat` enum('kabupaten','kecamatan','kelurahan') NOT NULL,
          `kecamatan_id` int(11) DEFAULT NULL,
          `kelurahan_id` int(11) DEFAULT NULL,
          `metrik` longtext NOT NULL,
          `created_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_arsip_periode` (`periode_id`, `tingkat`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query('ALTER TABLE `riwayat_perubahan`
            ADD COLUMN `periode_id` int(11) unsigned DEFAULT NULL AFTER `kelurahan_id`,
            ADD COLUMN `dokumen_jenis` varchar(60) DEFAULT NULL AFTER `periode_id`,
            ADD COLUMN `dokumen_keterangan` varchar(255) DEFAULT NULL AFTER `dokumen_jenis`,
            ADD KEY `idx_riwayat_periode` (`periode_id`)');
    }

    public function down()
    {
        $db = $this->db;
        $db->query('ALTER TABLE `riwayat_perubahan`
            DROP KEY `idx_riwayat_periode`,
            DROP COLUMN `dokumen_keterangan`,
            DROP COLUMN `dokumen_jenis`,
            DROP COLUMN `periode_id`');
        $db->query('DROP TABLE IF EXISTS `arsip_rekap`');
        $db->query('DROP TABLE IF EXISTS `periode`');
        $db->query("DELETE FROM `pengaturan` WHERE `kunci` IN ('konteks_jenis', 'konteks_nama')");
    }
}
