<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SKEMA KONSOLIDASI 2026 — Penyesuai basis data LAMA.
 *
 * Pada basis data BARU migration ini tidak melakukan apa pun
 * (struktur sudah final dari 200000–220000). Pada basis data yang
 * terbentuk dari rantai migration lama, langkah ini menyamakan
 * bentuk akhirnya dalam satu jalan:
 *
 * 1. Kolom cakupan wilayah pada `users` & `riwayat_perubahan`
 *    (warisan KonsepKabupaten) ditambahkan bila belum ada.
 * 2. `mhc.ID` dijadikan PRIMARY KEY AUTO_INCREMENT (ID NULL/ganda
 *    diberi nomor baru lebih dulu).
 * 3. Kolom `NIK_AKTIF` + indeks uniknya (hanya bila data aktif
 *    bersih; bila masih ada NIK aktif ganda, indeks dilewati dengan
 *    pesan dan penegakan berjalan di tingkat aplikasi).
 * 4. Kolom `TGL_LAHIR` (DATE) ditambahkan dan diisi dari teks
 *    `TGLLHR` yang polanya sah.
 * 5. Indeks wilayah ditambahkan bila belum ada.
 * 6. Objek warisan dibersihkan: 9 view lama di-drop; 3 tabel lama
 *    diganti nama menjadi arsip_* (data utuh).
 *
 * Semua langkah memeriksa keadaan lebih dulu (idempoten).
 */
class SkemaKonsolidasiPenyesuai extends Migration
{
    public function up()
    {
        $db = $this->db;

        // -- 1. Kolom wilayah pada tabel aplikasi ----------------------
        $kolomUsers = $db->getFieldNames('users');
        if (! in_array('kecamatan', $kolomUsers, true)) {
            $db->query('ALTER TABLE `users` ADD COLUMN `kecamatan` varchar(100) DEFAULT NULL AFTER `peran`');
        }
        if (! in_array('desa', $kolomUsers, true)) {
            $db->query('ALTER TABLE `users` ADD COLUMN `desa` varchar(100) DEFAULT NULL AFTER `kecamatan`');
        }
        if (in_array('riwayat_perubahan', $db->listTables(), true)) {
            $kolomRiwayat = $db->getFieldNames('riwayat_perubahan');
            if (! in_array('kecamatan', $kolomRiwayat, true)) {
                $db->query('ALTER TABLE `riwayat_perubahan` ADD COLUMN `kecamatan` varchar(100) DEFAULT NULL');
            }
            if (! in_array('desa', $kolomRiwayat, true)) {
                $db->query('ALTER TABLE `riwayat_perubahan` ADD COLUMN `desa` varchar(100) DEFAULT NULL');
            }
        }

        if (! in_array('mhc', $db->listTables(), true)) {
            return; // Tidak ada tabel inti: tidak ada yang disesuaikan.
        }

        // -- 2. ID menjadi kunci pokok --------------------------------
        $kolomMhc = $db->getFieldNames('mhc');
        if (in_array('ID', $kolomMhc, true)) {
            $db->query('SET @nomor_baru := (SELECT COALESCE(MAX(ID), 0) FROM mhc)');
            $db->query('UPDATE mhc SET ID = (@nomor_baru := @nomor_baru + 1) WHERE ID IS NULL');
            $gandaId = $db->query('SELECT ID FROM mhc GROUP BY ID HAVING COUNT(*) > 1')->getResultArray();
            foreach ($gandaId as $r) {
                $db->query('SET @nomor_baru := (SELECT MAX(ID) FROM mhc)');
                $db->query(
                    'UPDATE mhc SET ID = (@nomor_baru := @nomor_baru + 1) WHERE ID = ? LIMIT 1000000',
                    [(int) $r['ID']]
                );
            }
            if ($db->query("SHOW INDEX FROM mhc WHERE Key_name = 'PRIMARY'")->getResultArray() === []) {
                $db->query('ALTER TABLE mhc MODIFY ID INT NOT NULL');
                $db->query('ALTER TABLE mhc ADD PRIMARY KEY (ID)');
                $db->query('ALTER TABLE mhc MODIFY ID INT NOT NULL AUTO_INCREMENT');
            }
        }

        // -- 3. NIK_AKTIF + indeks unik (bila data bersih) -------------
        if (! in_array('NIK_AKTIF', $kolomMhc, true)) {
            $db->query("ALTER TABLE mhc ADD COLUMN NIK_AKTIF VARCHAR(16)
                GENERATED ALWAYS AS (CASE WHEN KET IN ('0','U','B') THEN NIK ELSE NULL END) STORED");
        }
        if ($db->query("SHOW INDEX FROM mhc WHERE Key_name = 'uq_mhc_nik_aktif'")->getResultArray() === []) {
            $ganda = (int) $db->query(
                "SELECT COUNT(*) AS c FROM (SELECT NIK FROM mhc WHERE KET IN ('0','U','B')
                 GROUP BY NIK HAVING COUNT(*) > 1) t"
            )->getRowArray()['c'];
            if ($ganda === 0) {
                $db->query('ALTER TABLE mhc ADD UNIQUE KEY uq_mhc_nik_aktif (NIK_AKTIF)');
            } else {
                echo "\n[SkemaKonsolidasi] Indeks unik NIK_AKTIF DILEWATI: masih ada {$ganda} NIK aktif ganda pada data. Bersihkan lewat halaman Analisis NIK Ganda, lalu jalankan migrasi ulang.\n";
            }
        }

        // -- 4. Kolom TGL_LAHIR + isi dari TGLLHR -----------------------
        if (! in_array('TGL_LAHIR', $db->getFieldNames('mhc'), true)) {
            $db->query('ALTER TABLE mhc ADD COLUMN TGL_LAHIR DATE DEFAULT NULL AFTER TGLLHR');
        }
        $db->query("UPDATE mhc SET TGL_LAHIR = STR_TO_DATE(TGLLHR, '%d|%m|%Y')
            WHERE TGL_LAHIR IS NULL AND TGLLHR REGEXP '^[0-9]{2}\\\\|[0-9]{2}\\\\|[0-9]{4}$'");
        $db->query("UPDATE mhc SET TGL_LAHIR = STR_TO_DATE(TGLLHR, '%d-%m-%Y')
            WHERE TGL_LAHIR IS NULL AND TGLLHR REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}$'");

        // -- 5. Indeks wilayah ------------------------------------------
        $indeksAda = array_column($db->query('SHOW INDEX FROM mhc')->getResultArray(), 'Key_name');
        if (! in_array('idx_mhc_wilayah', $indeksAda, true)) {
            $db->query('ALTER TABLE mhc ADD INDEX idx_mhc_wilayah (KEC, DESA, TPS, KET)');
        }
        if (! in_array('idx_mhc_ket_kec', $indeksAda, true)) {
            $db->query('ALTER TABLE mhc ADD INDEX idx_mhc_ket_kec (KET, KEC)');
        }

        // -- 6. Bersihkan objek warisan ---------------------------------
        foreach (['desa', 'kec', 'group_nik', 'analisis_nik_ganda', 'vpt_tms', 'v_count_kk', 'lap10_1', 'lap10_2', 'lap10_3'] as $v) {
            $db->query('DROP VIEW IF EXISTS `' . $v . '`');
        }
        $tabelAda = $db->listTables();
        foreach (['to_sql' => 'arsip_to_sql_20261110', 'config' => 'arsip_config_20261110', 'nik_move_tps' => 'arsip_nik_move_tps_20261110'] as $asal => $arsip) {
            if (in_array($asal, $tabelAda, true) && ! in_array($arsip, $tabelAda, true)) {
                $db->query('RENAME TABLE `' . $asal . '` TO `' . $arsip . '`');
            }
        }
    }

    public function down()
    {
        // Penyesuaian bersifat menyamakan bentuk akhir; penurunan
        // versi tidak mengembalikan keadaan warisan.
    }
}
