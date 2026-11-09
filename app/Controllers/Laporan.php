<?php

namespace App\Controllers;

use App\Models\PemilihModel;

/**
 * Fase 4 — Laporan ringkasan per desa & per TPS.
 *
 * Semua angka dihitung langsung dari data coklit dan view turunannya,
 * jadi selalu mutakhir. Tersedia untuk semua peran (termasuk Pemeriksa).
 * Tombol Cetak memakai cetak browser (window.print).
 */
class Laporan extends BaseController
{
    public function index()
    {
        $db = db_connect();
        $desaDipilih = trim((string) $this->request->getGet('desa'));

        // Agregat per desa dari mhc (satu kueri) + KK dari view v_nkk
        $perDesa = $db->table('mhc')
            ->select("DESA,
                COUNT(*) AS total,
                SUM(KET IN ('0','U','B')) AS ms,
                SUM(KET = 'B') AS baru,
                SUM(KET IN ('1','2','3','6','7','8')) AS tersaring,
                SUM(KET = '8') AS pindah,
                COUNT(DISTINCT TPS) AS jumlah_tps", false)
            ->groupBy('DESA')->orderBy('DESA')->get()->getResultArray();

        $kkPerDesa = [];
        foreach ($db->table('v_nkk')->select('DESA, COUNT(*) AS kk', false)->groupBy('DESA')->get()->getResultArray() as $r) {
            $kkPerDesa[$r['DESA']] = (int) $r['kk'];
        }
        foreach ($perDesa as &$pd) {
            $pd['kk'] = $kkPerDesa[$pd['DESA']] ?? 0;
        }
        unset($pd);

        // Rincian per TPS untuk desa terpilih
        $perTps = [];
        if ($desaDipilih !== '') {
            $perTps = $db->table('mhc')
                ->select("TPS,
                    COUNT(*) AS total,
                    SUM(KET IN ('0','U','B')) AS ms,
                    SUM(KET = 'B') AS baru,
                    SUM(KET IN ('1','2','3','6','7','8')) AS tersaring,
                    SUM(KET = '8') AS pindah", false)
                ->where('DESA', $desaDipilih)
                ->groupBy('TPS')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->get()->getResultArray();

            $kkPerTps = [];
            foreach ($db->table('v_nkk')->select('TPS, COUNT(*) AS kk', false)->where('DESA', $desaDipilih)->groupBy('TPS')->get()->getResultArray() as $r) {
                $kkPerTps[$r['TPS']] = (int) $r['kk'];
            }
            foreach ($perTps as &$pt) {
                $pt['kk'] = $kkPerTps[$pt['TPS']] ?? 0;
            }
            unset($pt);
        }

        $daftarDesa = $db->table('desa')->select('DESA')->orderBy('DESA')->get()->getResultArray();

        return view('laporan/index', [
            'title'       => 'Laporan Ringkasan',
            'title_page'  => 'Laporan Ringkasan Per Desa / TPS',
            'kecamatan'   => $this->kec,
            'perDesa'     => $perDesa,
            'perTps'      => $perTps,
            'desaDipilih' => $desaDipilih,
            'daftarDesa'  => array_map(static fn ($r) => $r['DESA'], $daftarDesa),
        ]);
    }
}
