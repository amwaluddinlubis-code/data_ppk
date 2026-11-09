<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Baseline skema Aplikasi Pemilih (Fase 0).
 *
 * Dibangkitkan dari struktur db_pemilih.sql yang selama ini hanya hidup
 * sebagai dump: 5 tabel asli (mhc, to_sql, config, tms8, nik_move_tps)
 * dan 16 view turunan di atas mhc. Urutan pembuatan view disusun
 * mengikuti ketergantungannya (ms -> group_nik -> analisis_nik_ganda, dst).
 *
 * Data TIDAK termasuk di sini: isi tabel diimpor dari dump data,
 * sedangkan desa/kec adalah view (turunan dari mhc) sehingga tidak
 * memerlukan seeder.
 */
class BaselineSkemaPemilih extends Migration
{
    public function up()
    {
        $statements = [
            'CREATE TABLE `mhc` (
  `UID` varchar(16) DEFAULT NULL,
  `ID` int(8) DEFAULT NULL,
  `NKK` varchar(16) DEFAULT NULL,
  `NIK` varchar(16) DEFAULT NULL,
  `NAMA` varchar(255) DEFAULT NULL,
  `TMPTLHR` varchar(255) DEFAULT NULL,
  `TGLLHR` varchar(255) DEFAULT NULL,
  `STATUS` varchar(1) DEFAULT NULL,
  `JENIS_KELAMIN` varchar(1) DEFAULT NULL,
  `ALAMAT` varchar(255) DEFAULT NULL,
  `RT` int(1) DEFAULT NULL,
  `RW` int(1) DEFAULT NULL,
  `DISABILITAS` int(1) DEFAULT NULL,
  `EKTP` varchar(1) DEFAULT NULL,
  `KET` varchar(3) DEFAULT NULL,
  `SUMBER` varchar(255) DEFAULT NULL,
  `TPS` varchar(3) DEFAULT NULL,
  `LAST_UPDATE` varchar(255) DEFAULT NULL,
  `LAT` varchar(255) DEFAULT NULL,
  `LONG` varchar(255) DEFAULT NULL,
  `PROV` varchar(255) DEFAULT NULL,
  `KAB` varchar(255) DEFAULT NULL,
  `KEC` varchar(255) DEFAULT NULL,
  `DESA` varchar(255) DEFAULT NULL,
  `TGL_COKLIT` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;',
            'CREATE TABLE `to_sql` (
  `KEC_ID` varchar(255) DEFAULT NULL,
  `KECAMATAN` varchar(255) DEFAULT NULL,
  `KEL_ID` varchar(255) DEFAULT NULL,
  `KELURAHAN` varchar(255) DEFAULT NULL,
  `DPID` varchar(255) DEFAULT NULL,
  `NKK` varchar(255) DEFAULT NULL,
  `NIK` varchar(255) DEFAULT NULL,
  `NAMA` varchar(255) DEFAULT NULL,
  `TEMPAT LAHIR` varchar(255) DEFAULT NULL,
  `TANGGAL LAHIR` varchar(255) DEFAULT NULL,
  `STS` varchar(255) DEFAULT NULL,
  `KELAMIN` varchar(255) DEFAULT NULL,
  `ALAMAT` varchar(255) DEFAULT NULL,
  `RT` varchar(255) DEFAULT NULL,
  `RW` varchar(255) DEFAULT NULL,
  `DISABILITAS` varchar(255) DEFAULT NULL,
  `EKTP` varchar(255) DEFAULT NULL,
  `KETERANGAN` varchar(255) DEFAULT NULL,
  `SUMBER` varchar(255) DEFAULT NULL,
  `TPS` varchar(255) DEFAULT NULL,
  `TPS_ID` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;',
            'CREATE TABLE `config` (
  `TB_PEMILIH_AKTIF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;',
            'CREATE TABLE `tms8` (
  `NIK` varchar(255) DEFAULT NULL,
  `STATUS` varchar(255) DEFAULT NULL,
  `NIKBARU` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;',
            'CREATE TABLE `nik_move_tps` (
  `NIK` varchar(255) DEFAULT NULL,
  `KET` varchar(255) DEFAULT NULL,
  `TPS2` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;',
            'ALTER TABLE `mhc`
  ADD KEY `IDX` (`NIK`,`KET`);',
            'ALTER TABLE `tms8`
  ADD KEY `NIK` (`NIK`);',
            'CREATE OR REPLACE VIEW `ms` AS SELECT `mhc`.`UID` AS `UID`, `mhc`.`ID` AS `ID`, `mhc`.`NKK` AS `NKK`, `mhc`.`NIK` AS `NIK`, `mhc`.`NAMA` AS `NAMA`, `mhc`.`TMPTLHR` AS `TMPTLHR`, `mhc`.`TGLLHR` AS `TGLLHR`, `mhc`.`STATUS` AS `STATUS`, `mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `mhc`.`ALAMAT` AS `ALAMAT`, `mhc`.`RT` AS `RT`, `mhc`.`RW` AS `RW`, `mhc`.`DISABILITAS` AS `DISABILITAS`, `mhc`.`EKTP` AS `EKTP`, `mhc`.`KET` AS `KET`, `mhc`.`SUMBER` AS `SUMBER`, `mhc`.`TPS` AS `TPS`, `mhc`.`LAST_UPDATE` AS `LAST_UPDATE`, `mhc`.`LAT` AS `LAT`, `mhc`.`LONG` AS `LONG`, `mhc`.`PROV` AS `PROV`, `mhc`.`KAB` AS `KAB`, `mhc`.`KEC` AS `KEC`, `mhc`.`DESA` AS `DESA` FROM `mhc` WHERE `mhc`.`KET` in (\'0\',\'U\',\'B\');',
            'CREATE OR REPLACE VIEW `desa` AS SELECT DISTINCT `mhc`.`DESA` AS `DESA` FROM `mhc` GROUP BY `mhc`.`DESA` ORDER BY `mhc`.`DESA` ASC;',
            'CREATE OR REPLACE VIEW `kec` AS SELECT `mhc`.`KEC` AS `KEC` FROM `mhc` LIMIT 0, 1;',
            'CREATE OR REPLACE VIEW `lap10_1` AS SELECT `mhc`.`UID` AS `UID`, `mhc`.`ID` AS `ID`, `mhc`.`NKK` AS `NKK`, `mhc`.`NIK` AS `NIK`, `mhc`.`NAMA` AS `NAMA`, `mhc`.`TMPTLHR` AS `TMPTLHR`, `mhc`.`TGLLHR` AS `TGLLHR`, `mhc`.`STATUS` AS `STATUS`, `mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `mhc`.`ALAMAT` AS `ALAMAT`, `mhc`.`RT` AS `RT`, `mhc`.`RW` AS `RW`, `mhc`.`DISABILITAS` AS `DISABILITAS`, `mhc`.`EKTP` AS `EKTP`, `mhc`.`KET` AS `KET`, `mhc`.`SUMBER` AS `SUMBER`, `mhc`.`TPS` AS `TPS`, `mhc`.`LAST_UPDATE` AS `LAST_UPDATE`, `mhc`.`LAT` AS `LAT`, `mhc`.`LONG` AS `LONG`, `mhc`.`PROV` AS `PROV`, `mhc`.`KAB` AS `KAB`, `mhc`.`KEC` AS `KEC`, `mhc`.`DESA` AS `DESA`, `mhc`.`TGL_COKLIT` AS `TGL_COKLIT` FROM `mhc` WHERE `mhc`.`TGL_COKLIT` between cast(\'2023-02-12\' as date) and cast(\'2023-02-21\' as date);',
            'CREATE OR REPLACE VIEW `lap10_2` AS SELECT `mhc`.`UID` AS `UID`, `mhc`.`ID` AS `ID`, `mhc`.`NKK` AS `NKK`, `mhc`.`NIK` AS `NIK`, `mhc`.`NAMA` AS `NAMA`, `mhc`.`TMPTLHR` AS `TMPTLHR`, `mhc`.`TGLLHR` AS `TGLLHR`, `mhc`.`STATUS` AS `STATUS`, `mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `mhc`.`ALAMAT` AS `ALAMAT`, `mhc`.`RT` AS `RT`, `mhc`.`RW` AS `RW`, `mhc`.`DISABILITAS` AS `DISABILITAS`, `mhc`.`EKTP` AS `EKTP`, `mhc`.`KET` AS `KET`, `mhc`.`SUMBER` AS `SUMBER`, `mhc`.`TPS` AS `TPS`, `mhc`.`LAST_UPDATE` AS `LAST_UPDATE`, `mhc`.`LAT` AS `LAT`, `mhc`.`LONG` AS `LONG`, `mhc`.`PROV` AS `PROV`, `mhc`.`KAB` AS `KAB`, `mhc`.`KEC` AS `KEC`, `mhc`.`DESA` AS `DESA`, `mhc`.`TGL_COKLIT` AS `TGL_COKLIT` FROM `mhc` WHERE `mhc`.`TGL_COKLIT` between cast(\'2023-02-22\' as date) and cast(\'2023-03-03\' as date);',
            'CREATE OR REPLACE VIEW `lap10_3` AS SELECT `mhc`.`UID` AS `UID`, `mhc`.`ID` AS `ID`, `mhc`.`NKK` AS `NKK`, `mhc`.`NIK` AS `NIK`, `mhc`.`NAMA` AS `NAMA`, `mhc`.`TMPTLHR` AS `TMPTLHR`, `mhc`.`TGLLHR` AS `TGLLHR`, `mhc`.`STATUS` AS `STATUS`, `mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `mhc`.`ALAMAT` AS `ALAMAT`, `mhc`.`RT` AS `RT`, `mhc`.`RW` AS `RW`, `mhc`.`DISABILITAS` AS `DISABILITAS`, `mhc`.`EKTP` AS `EKTP`, `mhc`.`KET` AS `KET`, `mhc`.`SUMBER` AS `SUMBER`, `mhc`.`TPS` AS `TPS`, `mhc`.`LAST_UPDATE` AS `LAST_UPDATE`, `mhc`.`LAT` AS `LAT`, `mhc`.`LONG` AS `LONG`, `mhc`.`PROV` AS `PROV`, `mhc`.`KAB` AS `KAB`, `mhc`.`KEC` AS `KEC`, `mhc`.`DESA` AS `DESA`, `mhc`.`TGL_COKLIT` AS `TGL_COKLIT` FROM `mhc` WHERE `mhc`.`TGL_COKLIT` between cast(\'2023-03-04\' as date) and cast(\'2023-03-14\' as date);',
            'CREATE OR REPLACE VIEW `pemilihbaru` AS (select `mhc`.`UID` AS `UID`,`mhc`.`ID` AS `ID`,`mhc`.`NKK` AS `NKK`,`mhc`.`NIK` AS `NIK`,`mhc`.`NAMA` AS `NAMA`,`mhc`.`TMPTLHR` AS `TMPTLHR`,`mhc`.`TGLLHR` AS `TGLLHR`,`mhc`.`STATUS` AS `STATUS`,`mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`,`mhc`.`ALAMAT` AS `ALAMAT`,`mhc`.`RT` AS `RT`,`mhc`.`RW` AS `RW`,`mhc`.`DISABILITAS` AS `DISABILITAS`,`mhc`.`EKTP` AS `EKTP`,`mhc`.`KET` AS `KET`,`mhc`.`SUMBER` AS `SUMBER`,`mhc`.`TPS` AS `TPS`,`mhc`.`LAST_UPDATE` AS `LAST_UPDATE`,`mhc`.`LAT` AS `LAT`,`mhc`.`LONG` AS `LONG`,`mhc`.`PROV` AS `PROV`,`mhc`.`KAB` AS `KAB`,`mhc`.`KEC` AS `KEC`,`mhc`.`DESA` AS `DESA` from `mhc` where `mhc`.`KET` = \'B\');',
            'CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR` FROM (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` in (1,2,3,6,7,8);',
            'CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, concat(left(`a`.`NKK`,4),\'******\',right(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, concat(right(`a`.`TGLLHR`,4),\'-\',substr(`a`.`TGLLHR`,4,2),\'-\',left(`a`.`TGLLHR`,2)) AS `BDATE`, timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),\'-\',substr(`a`.`TGLLHR`,4,2),\'-\',left(`a`.`TGLLHR`,2)),\'2024-02-14\') AS `UMUR` FROM `mhc` AS `a` WHERE `a`.`KET` not in (1,2,3,6,7,8);',
            'CREATE OR REPLACE VIEW `group_nik` AS SELECT count(`ms`.`NIK`) AS `JLH_TERCOKLIT`, `ms`.`NIK` AS `NIK` FROM `ms` GROUP BY `ms`.`NIK`;',
            'CREATE OR REPLACE VIEW `v_nkk` AS SELECT DISTINCT `ms`.`DESA` AS `DESA`, `ms`.`TPS` AS `TPS`, `ms`.`NKK` AS `NKK` FROM `ms` GROUP BY `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA` ASC, `ms`.`TPS` ASC;',
            'CREATE OR REPLACE VIEW `pemilihbaru2` AS SELECT `a`.`UID` AS `UID`, `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`TMPTLHR` AS `TMPTLHR`, `a`.`TGLLHR` AS `TGLLHR`, `a`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`, `a`.`ALAMAT` AS `ALAMAT`, `a`.`RT` AS `RT`, `a`.`RW` AS `RW`, `a`.`DISABILITAS` AS `DISABILITAS`, `a`.`EKTP` AS `EKTP`, `a`.`KET` AS `KET`, `a`.`SUMBER` AS `SUMBER`, `a`.`TPS` AS `TPS`, `a`.`LAST_UPDATE` AS `LAST_UPDATE`, `a`.`LAT` AS `LAT`, `a`.`LONG` AS `LONG`, `a`.`PROV` AS `PROV`, `a`.`KAB` AS `KAB`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `b`.`NAMA` AS `OLD_NAMA`, `b`.`DESA` AS `OLD_DESA` FROM (`pemilihbaru` `a` left join `tersaring` `b` on(`a`.`NIK` = `b`.`NIK`));',
            'CREATE OR REPLACE VIEW `pindahtps` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`DESA` AS `MSDESA`, `b`.`TPS` AS `MSTPS`, `b`.`NAMA` AS `MSNAMA`, `b`.`JENIS_KELAMIN` AS `MSLP`, `a`.`STATUS` AS `STATUS` FROM (`tersaring` `a` left join `pemilihbaru` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` = 8;',
            'CREATE OR REPLACE VIEW `vpt_tms` AS SELECT replace(concat(`a`.`DESA`,`a`.`TPS`,`a`.`KET`),\' \',\'\') AS `ID_TMS`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, count(`a`.`KET`) AS `JLH_TERSARING` FROM `tersaring` AS `a` GROUP BY `a`.`DESA`, `a`.`TPS`, `a`.`KET`;',
            'CREATE OR REPLACE VIEW `analisis_nik_ganda` AS SELECT `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`NIK` AS `NIK`, `a`.`NKK` AS `NKK`, `a`.`NAMA` AS `NAMA` FROM `ms` AS `a` WHERE `a`.`NIK` in (select `b`.`NIK` from `group_nik` `b` where `b`.`JLH_TERCOKLIT` > 1) ORDER BY `a`.`NIK` ASC;',
            'CREATE OR REPLACE VIEW `v_count_kk` AS SELECT `v_nkk`.`DESA` AS `DESA`, concat(\'TPS 00\',`v_nkk`.`TPS`) AS `TPS`, count(`v_nkk`.`NKK`) AS `JLH_KK` FROM `v_nkk` GROUP BY `v_nkk`.`DESA`, `v_nkk`.`TPS`;',
        ];

        foreach ($statements as $sql) {
            $this->db->query($sql);
        }
    }

    public function down()
    {
        $statements = [
            'DROP VIEW IF EXISTS `v_count_kk`;',
            'DROP VIEW IF EXISTS `analisis_nik_ganda`;',
            'DROP VIEW IF EXISTS `vpt_tms`;',
            'DROP VIEW IF EXISTS `pindahtps`;',
            'DROP VIEW IF EXISTS `pemilihbaru2`;',
            'DROP VIEW IF EXISTS `v_nkk`;',
            'DROP VIEW IF EXISTS `group_nik`;',
            'DROP VIEW IF EXISTS `pemilih_active`;',
            'DROP VIEW IF EXISTS `tersaring`;',
            'DROP VIEW IF EXISTS `pemilihbaru`;',
            'DROP VIEW IF EXISTS `lap10_3`;',
            'DROP VIEW IF EXISTS `lap10_2`;',
            'DROP VIEW IF EXISTS `lap10_1`;',
            'DROP VIEW IF EXISTS `kec`;',
            'DROP VIEW IF EXISTS `desa`;',
            'DROP VIEW IF EXISTS `ms`;',
            'DROP TABLE IF EXISTS `mhc`;',
            'DROP TABLE IF EXISTS `to_sql`;',
            'DROP TABLE IF EXISTS `config`;',
            'DROP TABLE IF EXISTS `tms8`;',
            'DROP TABLE IF EXISTS `nik_move_tps`;',
        ];

        foreach ($statements as $sql) {
            $this->db->query($sql);
        }
    }
}
