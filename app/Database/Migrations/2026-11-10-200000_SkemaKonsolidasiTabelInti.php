<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SKEMA KONSOLIDASI 2026 — Tabel inti (perintah AMWAL 10 Nov 2026:
 * basis data baru harus langsung memakai struktur baru).
 *
 * Migration ini, bersama 210000/220000/230000, MENGGANTIKAN seluruh
 * rantai migration lama (yang kini menjadi rintisan kosong). Pada
 * basis data BARU, tabel dibuat langsung dalam bentuk akhirnya:
 *
 * - `mhc.ID` PRIMARY KEY AUTO_INCREMENT sejak lahir.
 * - Kolom ter-generate `NIK_AKTIF` + indeks unik: satu NIK hanya
 *   boleh mempunyai satu baris aktif (MS) se-kabupaten.
 * - Indeks wilayah (KEC, DESA, TPS, KET) dan (KET, KEC) bawaan.
 * - Kolom inti (NIK, NAMA, KET, KEC, DESA, TPS) NOT NULL.
 * - Kolom `TGL_LAHIR` bertipe DATE: padanan terstruktur dari teks
 *   `TGLLHR` (DD|MM|YYYY), diisi otomatis saat impor & entri.
 *
 * Pada basis data LAMA (sudah terbentuk), pembuatan dilewati dan
 * penyesuaian bentuk akhir ditangani migration 230000.
 */
class SkemaKonsolidasiTabelInti extends Migration
{
    public function up()
    {
        $db = $this->db;
        if (! in_array('mhc', $db->listTables(), true)) {
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
              `TPS` varchar(3) NOT NULL,
              `LAST_UPDATE` varchar(255) DEFAULT NULL,
              `LAT` varchar(255) DEFAULT NULL,
              `LONG` varchar(255) DEFAULT NULL,
              `PROV` varchar(255) DEFAULT NULL,
              `KAB` varchar(255) DEFAULT NULL,
              `KEC` varchar(255) NOT NULL,
              `DESA` varchar(255) NOT NULL,
              `TGL_COKLIT` date DEFAULT NULL,
              `NIK_AKTIF` varchar(16) GENERATED ALWAYS AS (CASE WHEN `KET` IN ('0','U','B') THEN `NIK` ELSE NULL END) STORED,
              PRIMARY KEY (`ID`),
              UNIQUE KEY `uq_mhc_nik_aktif` (`NIK_AKTIF`),
              KEY `IDX` (`NIK`, `KET`),
              KEY `idx_mhc_wilayah` (`KEC`, `DESA`, `TPS`, `KET`),
              KEY `idx_mhc_ket_kec` (`KET`, `KEC`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }
        if (! in_array('tms8', $db->listTables(), true)) {
            $db->query(<<<'SQL'
            CREATE TABLE `tms8` (
              `NIK` varchar(16) DEFAULT NULL,
              `STATUS` varchar(255) DEFAULT NULL,
              `NIKBARU` varchar(16) DEFAULT NULL,
              KEY `NIK` (`NIK`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            SQL);
        }
    }

    public function down()
    {
        // Hanya menurunkan objek yang dibuat pada basis data baru;
        // pada basis data lama up() tidak membuat apa pun.
        $this->db->query('DROP TABLE IF EXISTS `mhc`');
        $this->db->query('DROP TABLE IF EXISTS `tms8`');
    }
}
