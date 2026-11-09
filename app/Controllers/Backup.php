<?php

namespace App\Controllers;

/**
 * Fase 5 — Cadangan basis data (hanya Admin PPK).
 *
 * Membuat dump SQL lengkap (struktur + data semua tabel, lalu view)
 * dari basis data kecamatan yang SEDANG AKTIF, menyimpannya di
 * `writable/backup/` dan mengunduhkannya ke peramban. Berkas dump
 * berisi data pribadi pemilih — simpan di tempat aman.
 */
class Backup extends BaseController
{
    private function wajibAdmin()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('/')->with('galat', 'Cadangan basis data hanya untuk Admin PPK.');
        }

        return null;
    }

    public function index()
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $folder = WRITEPATH . 'backup';
        $daftar = [];
        if (is_dir($folder)) {
            foreach (glob($folder . '/*.sql') ?: [] as $f) {
                $daftar[] = [
                    'nama'  => basename($f),
                    'ukuran' => filesize($f),
                    'waktu' => date('Y-m-d H:i:s', filemtime($f)),
                ];
            }
            usort($daftar, static fn ($a, $b) => strcmp($b['nama'], $a['nama']));
        }

        return view('backup/index', [
            'title'       => 'Cadangan Basis Data',
            'title_page'  => 'Cadangan Basis Data',
            'kecamatan'   => $this->kec,
            'basisData'   => db_connect()->getDatabase(),
            'daftar'      => $daftar,
        ]);
    }

    public function buat()
    {
        if ($tolak = $this->wajibAdmin()) {
            return $tolak;
        }

        $db = db_connect();
        $namaBasis = $db->getDatabase();

        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $namaBerkas = 'backup-' . preg_replace('/[^A-Za-z0-9]+/', '-', $namaBasis) . '-' . date('Ymd-His') . '.sql';
        $path = $folder . '/' . $namaBerkas;
        $fh = fopen($path, 'w');
        if ($fh === false) {
            return redirect()->to('backup')->with('galat', 'Folder cadangan tidak bisa ditulis.');
        }

        try {
            fwrite($fh, "-- Cadangan basis data Aplikasi Pemilih PPK\n");
            fwrite($fh, '-- Basis data: ' . $namaBasis . "\n");
            fwrite($fh, '-- Dibuat: ' . date('Y-m-d H:i:s') . ' oleh ' . session()->get('pengguna_username') . "\n");
            fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n\n");

            $objek = $db->query(
                'SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY TABLE_TYPE, TABLE_NAME',
                [$namaBasis]
            )->getResultArray();

            $views = [];
            foreach ($objek as $o) {
                if ($o['TABLE_TYPE'] === 'VIEW') {
                    $views[] = $o['TABLE_NAME'];
                    continue;
                }
                $tabel = $o['TABLE_NAME'];
                $buat = $db->query('SHOW CREATE TABLE `' . $tabel . '`')->getRowArray();
                fwrite($fh, 'DROP TABLE IF EXISTS `' . $tabel . "`;\n");
                fwrite($fh, $buat['Create Table'] . ";\n\n");

                $query = $db->table($tabel)->get();
                while ($row = $query->getUnbufferedRow('array')) {
                    $nilai = [];
                    foreach ($row as $v) {
                        $nilai[] = $v === null ? 'NULL' : $db->escape($v);
                    }
                    fwrite($fh, 'INSERT INTO `' . $tabel . '` VALUES (' . implode(',', $nilai) . ");\n");
                }
                fwrite($fh, "\n");
            }

            foreach ($views as $view) {
                $buat = $db->query('SHOW CREATE VIEW `' . $view . '`')->getRowArray();
                fwrite($fh, 'DROP VIEW IF EXISTS `' . $view . "`;\n");
                fwrite($fh, $buat['Create View'] . ";\n\n");
            }

            fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
        } catch (\Throwable $e) {
            fclose($fh);
            @unlink($path);
            return redirect()->to('backup')->with('galat', 'Pembuatan cadangan gagal: ' . $e->getMessage());
        }
        fclose($fh);

        $this->catatAkses('backup_basis_data', $namaBerkas . ' (' . number_format(filesize($path)) . ' byte)');

        return $this->response->download($path, null)->setFileName($namaBerkas);
    }
}
