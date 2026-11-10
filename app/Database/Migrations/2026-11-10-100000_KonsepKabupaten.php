<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Fase 7 — Konsep kabupaten: SATU basis data = SATU kabupaten.
 *
 * Menggantikan konsep Fase 6 (satu basis data per kecamatan):
 * - Semua kecamatan tinggal di basis data yang sama; kecamatan menjadi
 *   saringan wilayah di dalam aplikasi (kolom `KEC` pada `mhc`).
 * - Hierarki: Kabupaten → Kecamatan → Desa/Kelurahan → TPS → Pemilih.
 * - users.kecamatan: cakupan kecamatan pengguna (PPK/PPS). Kosong pada
 *   admin = seluruh kabupaten.
 * - riwayat_perubahan.kecamatan/desa: denormalisasi wilayah baris agar
 *   halaman Riwayat bisa disaring per wilayah tanpa membaca JSON.
 * - View tersaring, pemilih_active, pindahtps, analisis_nik_ganda, dan
 *   v_nkk ditambah kolom KEC (dari mhc/ms) supaya bisa disaring per
 *   kecamatan; definisi lain tidak berubah dari baseline.
 */
class KonsepKabupaten extends Migration
{
    public function up()
    {
        // -- users.kecamatan -------------------------------------------
        if (! $this->db->getFieldNames('users') || ! in_array('kecamatan', $this->db->getFieldNames('users'), true)) {
            $this->forge->addColumn('users', [
                'kecamatan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'desa',
                ],
            ]);
        }

        // -- riwayat_perubahan.kecamatan / desa -------------------------
        $kolomRiwayat = $this->db->getFieldNames('riwayat_perubahan');
        $tambah = [];
        if (! in_array('kecamatan', $kolomRiwayat, true)) {
            $tambah['kecamatan'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
        }
        if (! in_array('desa', $kolomRiwayat, true)) {
            $tambah['desa'] = ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null];
        }
        if ($tambah !== []) {
            $this->forge->addColumn('riwayat_perubahan', $tambah);
        }

        // Isi wilayah riwayat lama dari JSON data (terbaik-sebisanya).
        try {
            $this->db->query(
                "UPDATE riwayat_perubahan SET
                    kecamatan = COALESCE(kecamatan, JSON_UNQUOTE(JSON_EXTRACT(COALESCE(data_baru, data_lama), '$.KEC'))),
                    desa = COALESCE(desa, JSON_UNQUOTE(JSON_EXTRACT(COALESCE(data_baru, data_lama), '$.DESA')))
                 WHERE data_baru IS NOT NULL OR data_lama IS NOT NULL"
            );
        } catch (\Throwable) {
            // Abaikan: kolom tetap kosong untuk catatan lama.
        }

        // -- View + kolom KEC -------------------------------------------
        $views = [
            'tersaring' => "CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR` FROM (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` in (1,2,3,6,7,8)",
            'pemilih_active' => "CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, concat(left(`a`.`NKK`,4),'******',right(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)) AS `BDATE`, timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)),'2024-02-14') AS `UMUR` FROM `mhc` AS `a` WHERE `a`.`KET` not in (1,2,3,6,7,8)",
            'pindahtps' => "CREATE OR REPLACE VIEW `pindahtps` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`DESA` AS `MSDESA`, `b`.`TPS` AS `MSTPS`, `b`.`NAMA` AS `MSNAMA`, `b`.`JENIS_KELAMIN` AS `MSLP`, `a`.`STATUS` AS `STATUS` FROM (`tersaring` `a` left join `pemilihbaru` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` = 8",
            'analisis_nik_ganda' => "CREATE OR REPLACE VIEW `analisis_nik_ganda` AS SELECT `a`.`KEC` AS `KEC`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`NIK` AS `NIK`, `a`.`NKK` AS `NKK`, `a`.`NAMA` AS `NAMA` FROM `ms` AS `a` WHERE `a`.`NIK` in (select `b`.`NIK` from `group_nik` `b` where `b`.`JLH_TERCOKLIT` > 1) ORDER BY `a`.`NIK` ASC",
            'v_nkk' => "CREATE OR REPLACE VIEW `v_nkk` AS SELECT DISTINCT `ms`.`KEC` AS `KEC`, `ms`.`DESA` AS `DESA`, `ms`.`TPS` AS `TPS`, `ms`.`NKK` AS `NKK` FROM `ms` GROUP BY `ms`.`KEC`, `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA` ASC, `ms`.`TPS` ASC",
        ];
        foreach ($views as $sql) {
            $this->db->query($sql);
        }
    }

    public function down()
    {
        // Kembalikan view ke definisi baseline (tanpa KEC).
        $views = [
            "CREATE OR REPLACE VIEW `tersaring` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`STATUS` AS `STATUS`, `a`.`JENIS_KELAMIN` AS `LP`, `a`.`TGLLHR` AS `TGLLHR` FROM (`mhc` `a` left join `tms8` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` in (1,2,3,6,7,8)",
            "CREATE OR REPLACE VIEW `pemilih_active` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, concat(left(`a`.`NKK`,4),'******',right(`a`.`NKK`,6)) AS `NKKENCODE`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`JENIS_KELAMIN` AS `JK`, `a`.`STATUS` AS `STATUS`, `a`.`TGLLHR` AS `TGLLHR`, concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)) AS `BDATE`, timestampdiff(YEAR,concat(right(`a`.`TGLLHR`,4),'-',substr(`a`.`TGLLHR`,4,2),'-',left(`a`.`TGLLHR`,2)),'2024-02-14') AS `UMUR` FROM `mhc` AS `a` WHERE `a`.`KET` not in (1,2,3,6,7,8)",
            "CREATE OR REPLACE VIEW `pindahtps` AS SELECT `a`.`ID` AS `ID`, `a`.`NKK` AS `NKK`, `a`.`NIK` AS `NIK`, `a`.`NAMA` AS `NAMA`, `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `b`.`DESA` AS `MSDESA`, `b`.`TPS` AS `MSTPS`, `b`.`NAMA` AS `MSNAMA`, `b`.`JENIS_KELAMIN` AS `MSLP`, `a`.`STATUS` AS `STATUS` FROM (`tersaring` `a` left join `pemilihbaru` `b` on(`a`.`NIK` = `b`.`NIK`)) WHERE `a`.`KET` = 8",
            "CREATE OR REPLACE VIEW `analisis_nik_ganda` AS SELECT `a`.`DESA` AS `DESA`, `a`.`TPS` AS `TPS`, `a`.`KET` AS `KET`, `a`.`NIK` AS `NIK`, `a`.`NKK` AS `NKK`, `a`.`NAMA` AS `NAMA` FROM `ms` AS `a` WHERE `a`.`NIK` in (select `b`.`NIK` from `group_nik` `b` where `b`.`JLH_TERCOKLIT` > 1) ORDER BY `a`.`NIK` ASC",
            "CREATE OR REPLACE VIEW `v_nkk` AS SELECT DISTINCT `ms`.`DESA` AS `DESA`, `ms`.`TPS` AS `TPS`, `ms`.`NKK` AS `NKK` FROM `ms` GROUP BY `ms`.`DESA`, `ms`.`NKK` ORDER BY `ms`.`DESA` ASC, `ms`.`TPS` ASC",
        ];
        foreach ($views as $sql) {
            $this->db->query($sql);
        }

        $this->forge->dropColumn('riwayat_perubahan', ['kecamatan', 'desa']);
        $this->forge->dropColumn('users', 'kecamatan');
    }
}
