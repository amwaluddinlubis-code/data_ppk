<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Definisi view basis data TERPUSAT (bangun ulang 2026).
 *
 * Sebelumnya view dibuat & diubah oleh banyak migration bertahap
 * (baseline, konsep kabupaten, perbaikan T1/T2) sehingga bentuk
 * akhirnya hanya jelas dari hasil akumulasinya. Migration ini
 * menetapkan bentuk akhir ketujuh view yang dipakai aplikasi
 * dalam satu tempat — termasuk perbaikan T1 (pemilihbaru2 anti
 * berlipat) dan T2 (kode TMS 4/5 pada tersaring & pemilih_active).
 * Idempoten: aman dijalankan berulang (CREATE OR REPLACE).
 */
class ViewTerpusat extends Migration
{
    public function up()
    {
        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `ms` AS select `mhc`.`UID` AS `UID`,`mhc`.`ID` AS `ID`,`mhc`.`NKK` AS `NKK`,`mhc`.`NIK` AS `NIK`,`mhc`.`NAMA` AS `NAMA`,`mhc`.`TMPTLHR` AS `TMPTLHR`,`mhc`.`TGLLHR` AS `TGLLHR`,`mhc`.`STATUS` AS `STATUS`,`mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`,`mhc`.`ALAMAT` AS `ALAMAT`,`mhc`.`RT` AS `RT`,`mhc`.`RW` AS `RW`,`mhc`.`DISABILITAS` AS `DISABILITAS`,`mhc`.`EKTP` AS `EKTP`,`mhc`.`KET` AS `KET`,`mhc`.`SUMBER` AS `SUMBER`,`mhc`.`TPS` AS `TPS`,`mhc`.`LAST_UPDATE` AS `LAST_UPDATE`,`mhc`.`LAT` AS `LAT`,`mhc`.`LONG` AS `LONG`,`mhc`.`PROV` AS `PROV`,`mhc`.`KAB` AS `KAB`,`mhc`.`KEC` AS `KEC`,`mhc`.`DESA` AS `DESA` from `mhc` where `mhc`.`KET` in ('0','U','B')
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pemilihbaru` AS (select `mhc`.`UID` AS `UID`,`mhc`.`ID` AS `ID`,`mhc`.`NKK` AS `NKK`,`mhc`.`NIK` AS `NIK`,`mhc`.`NAMA` AS `NAMA`,`mhc`.`TMPTLHR` AS `TMPTLHR`,`mhc`.`TGLLHR` AS `TGLLHR`,`mhc`.`STATUS` AS `STATUS`,`mhc`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`,`mhc`.`ALAMAT` AS `ALAMAT`,`mhc`.`RT` AS `RT`,`mhc`.`RW` AS `RW`,`mhc`.`DISABILITAS` AS `DISABILITAS`,`mhc`.`EKTP` AS `EKTP`,`mhc`.`KET` AS `KET`,`mhc`.`SUMBER` AS `SUMBER`,`mhc`.`TPS` AS `TPS`,`mhc`.`LAST_UPDATE` AS `LAST_UPDATE`,`mhc`.`LAT` AS `LAT`,`mhc`.`LONG` AS `LONG`,`mhc`.`PROV` AS `PROV`,`mhc`.`KAB` AS `KAB`,`mhc`.`KEC` AS `KEC`,`mhc`.`DESA` AS `DESA` from `mhc` where `mhc`.`KET` = 'B')
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `tersaring` AS select `a`.`ID` AS `ID`,`a`.`NKK` AS `NKK`,`a`.`NIK` AS `NIK`,`a`.`NAMA` AS `NAMA`,`a`.`KEC` AS `KEC`,`a`.`DESA` AS `DESA`,`a`.`TPS` AS `TPS`,`a`.`KET` AS `KET`,`b`.`STATUS` AS `STATUS`,`a`.`JENIS_KELAMIN` AS `LP`,`a`.`TGLLHR` AS `TGLLHR` from (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) where `a`.`KET` in (1,2,3,4,5,6,7,8)
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pemilihbaru2` AS select `a`.`UID` AS `UID`,`a`.`ID` AS `ID`,`a`.`NKK` AS `NKK`,`a`.`NIK` AS `NIK`,`a`.`NAMA` AS `NAMA`,`a`.`TMPTLHR` AS `TMPTLHR`,`a`.`TGLLHR` AS `TGLLHR`,`a`.`STATUS` AS `STATUS`,`a`.`JENIS_KELAMIN` AS `JENIS_KELAMIN`,`a`.`ALAMAT` AS `ALAMAT`,`a`.`RT` AS `RT`,`a`.`RW` AS `RW`,`a`.`DISABILITAS` AS `DISABILITAS`,`a`.`EKTP` AS `EKTP`,`a`.`KET` AS `KET`,`a`.`SUMBER` AS `SUMBER`,`a`.`TPS` AS `TPS`,`a`.`LAST_UPDATE` AS `LAST_UPDATE`,`a`.`LAT` AS `LAT`,`a`.`LONG` AS `LONG`,`a`.`PROV` AS `PROV`,`a`.`KAB` AS `KAB`,`a`.`KEC` AS `KEC`,`a`.`DESA` AS `DESA`,(select `b`.`NAMA` from `tersaring` `b` where `b`.`NIK` = `a`.`NIK` order by `b`.`ID` limit 1) AS `OLD_NAMA`,(select `b`.`DESA` from `tersaring` `b` where `b`.`NIK` = `a`.`NIK` order by `b`.`ID` limit 1) AS `OLD_DESA` from `pemilihbaru` `a`
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pindahtps` AS select `a`.`ID` AS `ID`,`a`.`NKK` AS `NKK`,`a`.`NIK` AS `NIK`,`a`.`NAMA` AS `NAMA`,`a`.`KEC` AS `KEC`,`a`.`DESA` AS `DESA`,`a`.`TPS` AS `TPS`,`a`.`KET` AS `KET`,`b`.`DESA` AS `MSDESA`,`b`.`TPS` AS `MSTPS`,`b`.`NAMA` AS `MSNAMA`,`b`.`JENIS_KELAMIN` AS `MSLP`,`a`.`STATUS` AS `STATUS` from (`tersaring` `a` left join `pemilihbaru` `b` on(`a`.`NIK` = `b`.`NIK`)) where `a`.`KET` = 8
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `pemilih_active` AS select `a`.`ID` AS `ID`,`a`.`NKK` AS `NKK`,concat(left(`a`.`NKK`,4),'******',right(`a`.`NKK`,6)) AS `NKKENCODE`,`a`.`NIK` AS `NIK`,`a`.`NAMA` AS `NAMA`,`a`.`KEC` AS `KEC`,`a`.`DESA` AS `DESA`,`a`.`TPS` AS `TPS`,`a`.`KET` AS `KET`,`a`.`JENIS_KELAMIN` AS `JK`,`a`.`STATUS` AS `STATUS`,`a`.`TGLLHR` AS `TGLLHR`,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)) AS `BDATE`,timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)),'2024-02-14') AS `UMUR` from `mhc` `a` where `a`.`KET` not in (1,2,3,4,5,6,7,8)
        SQL);

        $this->db->query(<<<'SQL'
        CREATE OR REPLACE VIEW `v_nkk` AS select distinct `ms`.`KEC` AS `KEC`,`ms`.`DESA` AS `DESA`,`ms`.`TPS` AS `TPS`,`ms`.`NKK` AS `NKK` from `ms` group by `ms`.`KEC`,`ms`.`DESA`,`ms`.`NKK` order by `ms`.`DESA`,`ms`.`TPS`
        SQL);

    }

    public function down()
    {
        // Tidak ada penurunan versi: bentuk view sebelumnya berasal
        // dari akumulasi migration lama dan tidak direkonstruksi.
    }
}
