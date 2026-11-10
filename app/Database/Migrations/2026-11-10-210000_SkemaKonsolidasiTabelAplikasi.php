<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SKEMA KONSOLIDASI 2026 — Tabel aplikasi: pengguna, riwayat
 * perubahan, catatan impor, dan log akses. Dibuat langsung dalam
 * bentuk akhirnya (termasuk kolom cakupan wilayah pada users dan
 * riwayat_perubahan). Pada basis data lama pembuatan dilewati;
 * kolom yang mungkin belum ada dilengkapi oleh migration 230000.
 */
class SkemaKonsolidasiTabelAplikasi extends Migration
{
    public function up()
    {
        $db    = $this->db;
        $tabel = $db->listTables();

        if (! in_array('users', $tabel, true)) {
            $db->query(<<<'SQL'
            CREATE TABLE `users` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `nama` varchar(100) NOT NULL,
              `username` varchar(50) NOT NULL,
              `password_hash` varchar(255) NOT NULL,
              `peran` enum('admin','operator','viewer') NOT NULL DEFAULT 'operator',
              `kecamatan` varchar(100) DEFAULT NULL,
              `desa` varchar(100) DEFAULT NULL,
              `aktif` tinyint(1) NOT NULL DEFAULT 1,
              `harus_ganti_sandi` tinyint(1) NOT NULL DEFAULT 1,
              `created_at` datetime DEFAULT NULL,
              `updated_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `username` (`username`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }

        if (! in_array('riwayat_perubahan', $tabel, true)) {
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
              `kecamatan` varchar(100) DEFAULT NULL,
              `desa` varchar(100) DEFAULT NULL,
              `created_at` datetime DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `kunci_uid` (`kunci_uid`),
              KEY `created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }

        if (! in_array('impor_log', $tabel, true)) {
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
        }

        if (! in_array('akses_log', $tabel, true)) {
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
        }
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `akses_log`');
        $this->db->query('DROP TABLE IF EXISTS `impor_log`');
        $this->db->query('DROP TABLE IF EXISTS `riwayat_perubahan`');
        $this->db->query('DROP TABLE IF EXISTS `users`');
    }
}
