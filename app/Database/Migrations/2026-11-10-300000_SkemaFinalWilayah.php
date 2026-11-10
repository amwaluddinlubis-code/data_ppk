<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SKEMA FINAL — WILAYAH TERNORMALISASI (perintah AMWAL 10 Nov 2026).
 *
 * "Lupakan database lama; jadikan MADINA.xlsx sebagai data sumber.
 *  Buatkan tabel KELURAHAN dan KECAMATAN, lalu sederhanakan tabel
 *  pemilih utama."
 *
 * Rancangan (dari struktur berkas Sidalih: KEC_ID 4 digit unik,
 * KEL_ID 5 digit unik, satu kelurahan hanya di satu kecamatan,
 * TPS_ID = KEL_ID + nomor TPS 3 digit):
 *
 * - `kecamatan`  : master wilayah — kode resmi Sidalih (KEC_ID) +
 *                  nama. Kode boleh kosong untuk kecamatan yang
 *                  lahir dari impor format coklit (tanpa kode).
 * - `kelurahan`  : master desa/kelurahan — kode resmi (KEL_ID),
 *                  nama, dan kecamatan induknya (FK).
 * - `mhc`        : tabel pemilih utama yang disederhanakan. Kolom
 *                  PROV, KAB, KEC, DESA, LAT, LONG dibuang; wilayah
 *                  cukup disimpan sebagai `kelurahan_id` (FK ke
 *                  master). Nama kecamatan/desa diperoleh lewat
 *                  JOIN — dan ketujuh view menyajikannya kembali
 *                  sebagai kolom KEC/DESA agar halaman lama tetap
 *                  membaca nama seperti biasa.
 * - `users`      : cakupan wilayah akun kini berbasis ID
 *                  (kecamatan_id / kelurahan_id), bukan teks.
 * - `riwayat_perubahan`: kolom wilayah snapshot juga berbasis ID.
 *
 * Seluruh migration sebelumnya kini rintisan kosong; basis data
 * baru dibangun utuh oleh migration ini. down() membongkar semua
 * objek yang dibuat up() sehingga rollback bersih.
 */
class SkemaFinalWilayah extends Migration
{
    public function up()
    {
        $db = $this->db;

        // ---------- Master wilayah ----------
        $db->query(<<<'SQL'
        CREATE TABLE `kecamatan` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `kode` varchar(10) DEFAULT NULL,
          `nama` varchar(255) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_kecamatan_kode` (`kode`),
          UNIQUE KEY `uq_kecamatan_nama` (`nama`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `kelurahan` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `kecamatan_id` int(11) NOT NULL,
          `kode` varchar(10) DEFAULT NULL,
          `nama` varchar(255) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_kelurahan_kode` (`kode`),
          UNIQUE KEY `uq_kelurahan_kec_nama` (`kecamatan_id`, `nama`),
          KEY `idx_kelurahan_kecamatan` (`kecamatan_id`),
          CONSTRAINT `fk_kelurahan_kecamatan` FOREIGN KEY (`kecamatan_id`) REFERENCES `kecamatan` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        // ---------- Tabel pemilih utama (disederhanakan) ----------
        $db->query(<<<'SQL'
        CREATE TABLE `mhc` (
          `ID` int(11) NOT NULL AUTO_INCREMENT,
          `UID` varchar(16) DEFAULT NULL,
          `NKK` varchar(16) DEFAULT NULL,
          `NIK` varchar(16) NOT NULL,
          `NAMA` varchar(255) NOT NULL,
          `TMPTLHR` varchar(255) DEFAULT NULL,
          `TGLLHR` varchar(255) DEFAULT NULL,
          `TGL_LAHIR` date DEFAULT NULL,
          `STATUS` varchar(1) DEFAULT NULL,
          `JENIS_KELAMIN` varchar(1) DEFAULT NULL,
          `ALAMAT` varchar(255) DEFAULT NULL,
          `RT` int(11) DEFAULT NULL,
          `RW` int(11) DEFAULT NULL,
          `DISABILITAS` int(11) DEFAULT NULL,
          `EKTP` varchar(1) DEFAULT NULL,
          `KET` varchar(3) NOT NULL,
          `SUMBER` varchar(255) DEFAULT NULL,
          `kelurahan_id` int(11) NOT NULL,
          `TPS` varchar(3) NOT NULL,
          `LAST_UPDATE` varchar(255) DEFAULT NULL,
          `TGL_COKLIT` date DEFAULT NULL,
          `NIK_AKTIF` varchar(16) GENERATED ALWAYS AS (CASE WHEN `KET` IN ('0','U','B') THEN `NIK` ELSE NULL END) STORED,
          PRIMARY KEY (`ID`),
          UNIQUE KEY `uq_mhc_nik_aktif` (`NIK_AKTIF`),
          KEY `IDX` (`NIK`, `KET`),
          KEY `idx_mhc_wilayah` (`kelurahan_id`, `TPS`, `KET`),
          KEY `idx_mhc_ket_kelurahan` (`KET`, `kelurahan_id`),
          CONSTRAINT `fk_mhc_kelurahan` FOREIGN KEY (`kelurahan_id`) REFERENCES `kelurahan` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `tms8` (
          `NIK` varchar(16) DEFAULT NULL,
          `STATUS` varchar(255) DEFAULT NULL,
          `NIKBARU` varchar(16) DEFAULT NULL,
          KEY `NIK` (`NIK`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        // ---------- Tabel aplikasi ----------
        $db->query(<<<'SQL'
        CREATE TABLE `users` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `nama` varchar(100) NOT NULL,
          `username` varchar(50) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `peran` enum('admin','operator','viewer') NOT NULL DEFAULT 'operator',
          `kecamatan_id` int(11) DEFAULT NULL,
          `kelurahan_id` int(11) DEFAULT NULL,
          `aktif` tinyint(1) NOT NULL DEFAULT 1,
          `harus_ganti_sandi` tinyint(1) NOT NULL DEFAULT 1,
          `created_at` datetime DEFAULT NULL,
          `updated_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `riwayat_perubahan` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `tabel` varchar(50) NOT NULL,
          `kunci_uid` varchar(32) DEFAULT NULL,
          `nik` varchar(20) DEFAULT NULL,
          `aksi` enum('tambah','ubah','hapus','pulih','impor') NOT NULL,
          `data_lama` longtext DEFAULT NULL,
          `data_baru` longtext DEFAULT NULL,
          `pengguna_id` int(11) unsigned DEFAULT NULL,
          `pengguna_username` varchar(50) DEFAULT NULL,
          `kecamatan_id` int(11) DEFAULT NULL,
          `kelurahan_id` int(11) DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `kunci_uid` (`kunci_uid`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `impor_log` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `jenis` enum('mhc','tms8') NOT NULL,
          `mode` enum('ganti','tambah') NOT NULL,
          `nama_berkas` varchar(255) NOT NULL,
          `jumlah_baris` int(11) NOT NULL DEFAULT 0,
          `jumlah_gagal` int(11) NOT NULL DEFAULT 0,
          `berkas_cadangan` varchar(255) DEFAULT NULL,
          `pengguna_id` int(11) unsigned DEFAULT NULL,
          `pengguna_username` varchar(50) DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        $db->query(<<<'SQL'
        CREATE TABLE `akses_log` (
          `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
          `pengguna_id` int(11) unsigned DEFAULT NULL,
          `pengguna_username` varchar(50) DEFAULT NULL,
          `aksi` varchar(50) NOT NULL,
          `detail` varchar(255) DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `created_at` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        // Pengaturan kecil kunci-nilai: identitas kabupaten (nama
        // provinsi/kabupaten) kini tinggal di sini — bukan lagi kolom
        // yang diulang pada setiap baris pemilih.
        $db->query(<<<'SQL'
        CREATE TABLE `pengaturan` (
          `kunci` varchar(50) NOT NULL,
          `nilai` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`kunci`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        SQL);

        // ---------- View (nama wilayah lewat JOIN master) ----------
        $gabung = 'FROM `mhc` '
            . 'JOIN `kelurahan` ON `kelurahan`.`id` = `mhc`.`kelurahan_id` '
            . 'JOIN `kecamatan` ON `kecamatan`.`id` = `kelurahan`.`kecamatan_id`';

        $db->query("CREATE OR REPLACE VIEW `ms` AS SELECT `mhc`.*, `kelurahan`.`nama` AS `DESA`, `kecamatan`.`nama` AS `KEC` {$gabung} WHERE `mhc`.`KET` IN ('0','U','B')");
        $db->query("CREATE OR REPLACE VIEW `pemilihbaru` AS SELECT `mhc`.*, `kelurahan`.`nama` AS `DESA`, `kecamatan`.`nama` AS `KEC` {$gabung} WHERE `mhc`.`KET` = 'B'");

        $db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `kecamatan`.`nama` AS `KEC`, `kelurahan`.`nama` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR`, `a`.`kelurahan_id` AS `kelurahan_id` FROM (`mhc` `a` JOIN `kelurahan` ON `kelurahan`.`id` = `a`.`kelurahan_id` JOIN `kecamatan` ON `kecamatan`.`id` = `kelurahan`.`kecamatan_id` LEFT JOIN `tms8` `b` ON (`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` IN ('1','2','3','4','5','6','7','8')
        SQL);

        $db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pemilihbaru2` AS SELECT `a`.*, (SELECT `b`.`NAMA` FROM `tersaring` `b` WHERE `b`.`NIK` = `a`.`NIK` ORDER BY `b`.`ID` LIMIT 1) AS `OLD_NAMA`, (SELECT `b`.`DESA` FROM `tersaring` `b` WHERE `b`.`NIK` = `a`.`NIK` ORDER BY `b`.`ID` LIMIT 1) AS `OLD_DESA` FROM `pemilihbaru` `a`
        SQL);

        $db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pindahtps` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`DESA` AS `MSDESA`, `b`.`TPS` AS `MSTPS`, `b`.`NAMA` AS `MSNAMA`, `b`.`JENIS_KELAMIN` AS `MSLP`, `a`.`STATUS` AS `STATUS` FROM (`tersaring` `a` LEFT JOIN `pemilihbaru` `b` ON (`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` = '8'
        SQL);

        $db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, CONCAT(LEFT(`a`.`NKK`,4),'******',RIGHT(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `kecamatan`.`nama` AS `KEC`, `kelurahan`.`nama` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, CONCAT(RIGHT(`a`.`TGLLHR`,4),'-',SUBSTR(`a`.`TGLLHR`,4,2),'-',LEFT(`a`.`TGLLHR`,2)) AS `BDATE`, TIMESTAMPDIFF(YEAR, CONCAT(RIGHT(`a`.`TGLLHR`,4),'-',SUBSTR(`a`.`TGLLHR`,4,2),'-',LEFT(`a`.`TGLLHR`,2)), '2024-02-14') AS `UMUR` FROM (`mhc` `a` JOIN `kelurahan` ON `kelurahan`.`id` = `a`.`kelurahan_id` JOIN `kecamatan` ON `kecamatan`.`id` = `kelurahan`.`kecamatan_id`) WHERE `a`.`KET` NOT IN ('1','2','3','4','5','6','7','8')
        SQL);

        $db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `v_nkk` AS SELECT DISTINCT `ms`.`KEC` AS `KEC`, `ms`.`DESA` AS `DESA`, `ms`.`TPS` AS `TPS`, `ms`.`NKK` AS `NKK` FROM `ms` GROUP BY `ms`.`KEC`, `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA`, `ms`.`TPS`
        SQL);
    }

    public function down()
    {
        foreach (['v_nkk', 'pemilih_active', 'pindahtps', 'pemilihbaru2', 'tersaring', 'pemilihbaru', 'ms'] as $v) {
            $this->db->query('DROP VIEW IF EXISTS `' . $v . '`');
        }
        $this->db->query('DROP TABLE IF EXISTS `mhc`');
        $this->db->query('DROP TABLE IF EXISTS `tms8`');
        $this->db->query('DROP TABLE IF EXISTS `pengaturan`');
        $this->db->query('DROP TABLE IF EXISTS `akses_log`');
        $this->db->query('DROP TABLE IF EXISTS `impor_log`');
        $this->db->query('DROP TABLE IF EXISTS `riwayat_perubahan`');
        $this->db->query('DROP TABLE IF EXISTS `users`');
        $this->db->query('DROP TABLE IF EXISTS `kelurahan`');
        $this->db->query('DROP TABLE IF EXISTS `kecamatan`');
    }
}
