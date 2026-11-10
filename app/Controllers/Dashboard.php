<?php

namespace App\Controllers;

use App\Libraries\Keterangan;
use \Hermawan\DataTables\DataTable;

/**
 * Dasbor (rupa kartu statistik; menu ala Sidalih).
 *
 * Semua angka mengikuti wilayah efektif sesi: kabupaten penuh untuk
 * Admin, kecamatan cakupan untuk PPK, desa cakupan untuk PPS. Bagian
 * bawah halaman adalah tabel Hasil Coklit Pantarlih yang bisa disaring
 * per desa/TPS/keterangan/jenis kelamin.
 *
 * Bangun ulang 2026: daftar kode MS/TMS tidak lagi ditulis mentah di
 * SQL, melainkan diambil dari App\Libraries\Keterangan.
 */
class Dashboard extends BaseController
{
    public function index()
    {
        helper('url');
        $db = db_connect();

        // -- Kartu statistik (mengikuti wilayah efektif) ---------------
        $b = $db->table('mhc');
        $this->saringWilayahId($b);
        $ringkas = $b->select(
            'COUNT(*) AS total,
             SUM(KET IN (' . Keterangan::sqlMs() . ')) AS ms,
             SUM(KET = \'B\') AS baru,
             SUM(KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
             SUM(KET = \'8\') AS pindah,
             COUNT(DISTINCT kelurahan_id) AS jumlah_desa,
             COUNT(DISTINCT kelurahan_id, TPS) AS jumlah_tps',
            false
        )->get()->getRowArray();

        $bKk = $db->table('v_nkk');
        $this->saringWilayah($bKk);
        $jumlahKk = $bKk->countAllResults();

        $bLp = $db->table('ms');
        $this->saringWilayah($bLp);
        $lp = $bLp->select(
            "SUM(JENIS_KELAMIN = 'L') AS laki,
             SUM(JENIS_KELAMIN = 'P') AS perempuan",
            false
        )->get()->getRowArray();

        // -- Tabel per wilayah -----------------------------------------
        // Kecamatan aktif → rincian per desa. Seluruh kabupaten →
        // rincian per kecamatan.
        $perKecamatan = $this->kecEfektif() === null;
        $bW = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
        $this->saringWilayahId($bW);
        if ($perKecamatan) {
            $perWilayah = $bW->select(
                'kecamatan.nama AS wilayah,
                 COUNT(*) AS total,
                 SUM(mhc.KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                 SUM(mhc.KET = \'B\') AS baru,
                 SUM(mhc.KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
                 SUM(mhc.KET = \'8\') AS pindah,
                 COUNT(DISTINCT mhc.kelurahan_id) AS jumlah_desa,
                 COUNT(DISTINCT mhc.kelurahan_id, mhc.TPS) AS jumlah_tps',
                false
            )->groupBy('kecamatan.nama')->orderBy('wilayah')->get()->getResultArray();
        } else {
            $perWilayah = $bW->select(
                'kelurahan.nama AS wilayah,
                 COUNT(*) AS total,
                 SUM(mhc.KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                 SUM(mhc.KET = \'B\') AS baru,
                 SUM(mhc.KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
                 SUM(mhc.KET = \'8\') AS pindah,
                 COUNT(DISTINCT mhc.TPS) AS jumlah_tps',
                false
            )->groupBy('kelurahan.nama')->orderBy('wilayah')->get()->getResultArray();
        }

        $data['title']      = 'Dasbor — Data Pemilih';
        $data['title_page'] = 'Dasbor Data Pemilih';
        $data['kecamatan']  = $this->kec;
        $data['kabupaten']  = $this->kab;
        $data['stats']      = [
            'total'     => (int) ($ringkas['total'] ?? 0),
            'ms'        => (int) ($ringkas['ms'] ?? 0),
            'baru'      => (int) ($ringkas['baru'] ?? 0),
            'tersaring' => (int) ($ringkas['tersaring'] ?? 0),
            'pindah'    => (int) ($ringkas['pindah'] ?? 0),
            'kk'        => $jumlahKk,
            'desa'      => (int) ($ringkas['jumlah_desa'] ?? 0),
            'tps'       => (int) ($ringkas['jumlah_tps'] ?? 0),
            'laki'      => (int) ($lp['laki'] ?? 0),
            'perempuan' => (int) ($lp['perempuan'] ?? 0),
        ];
        $data['perWilayah']   = $perWilayah;
        $data['perKecamatan'] = $perKecamatan;
        $data['arrDesa']      = $this->daftarDesa();
        // Pilot HTMX: tabel Hasil Coklit dirender server-side sejak
        // muat pertama; saringan & pagination berikutnya menukar
        // potongan tabel lewat endpoint tabelPemilih().
        $data['tpsAwal']      = $this->ambilDaftarTps('', 'semua');
        $data['tabelAwal']    = $this->dataTabelPemilih([]);

        return view('dashboard', $data);
    }

    /** Potongan tabel Hasil Coklit untuk HTMX (GET, tanpa muat ulang halaman). */
    public function tabelPemilih()
    {
        $param = [
            'desa' => trim((string) $this->request->getGet('desa')),
            'tps'  => trim((string) $this->request->getGet('tps')),
            'tms'  => trim((string) $this->request->getGet('tms')),
            'lp'   => trim((string) $this->request->getGet('lp')),
            'hal'  => (int) $this->request->getGet('hal'),
            'perhal' => (int) $this->request->getGet('perhal'),
        ];

        return view('dashboard/_tabel_pemilih', $this->dataTabelPemilih($param));
    }

    /**
     * Data satu halaman tabel Hasil Coklit (dipakai muat pertama dan
     * endpoint HTMX). Saringan sama seperti getPemilih() versi
     * DataTables; NIK/NKK Pemeriksa disamarkan.
     *
     * @param array{desa?: string, tps?: string, tms?: string, lp?: string, hal?: int, perhal?: int} $param
     * @return array<string, mixed>
     */
    private function dataTabelPemilih(array $param): array
    {
        $db   = db_connect();
        $desa = (string) ($param['desa'] ?? '');
        $tps  = (string) ($param['tps'] ?? '');
        $ket  = (string) ($param['tms'] ?? '');
        $lp   = (string) ($param['lp'] ?? '');
        $perHal = (int) ($param['perhal'] ?? 10);
        if (! in_array($perHal, [10, 25, 50], true)) {
            $perHal = 10;
        }
        $halaman = max(1, (int) ($param['hal'] ?? 1));

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->select('kelurahan.nama AS DESA, mhc.TPS, mhc.NKK, mhc.NIK, mhc.NAMA, mhc.TGLLHR, LEFT(mhc.LAST_UPDATE,10) AS LU, mhc.KET, mhc.JENIS_KELAMIN', false);
        $this->saringWilayahId($builder);
        if ($desa !== '') {
            $kel = $db->table('kelurahan')->select('kelurahan.id AS id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->where('kelurahan.nama', $desa);
            $kecId = \App\Libraries\Wilayah::kecEfektifId();
            if ($kecId !== null) {
                $kel->where('kelurahan.kecamatan_id', $kecId);
            }
            $kelRow = $kel->get()->getRowArray();
            $builder->where('mhc.kelurahan_id', $kelRow !== null ? (int) $kelRow['id'] : -1);
        }
        if ($tps !== '') {
            $builder->where('mhc.TPS', $tps);
        }
        if ($lp !== '') {
            $builder->where('mhc.JENIS_KELAMIN', $lp);
        }
        if ($ket !== '') {
            if (Keterangan::isMs($ket)) {
                $builder->where('mhc.KET', $ket);
            } elseif ($ket === 'MS') {
                $builder->whereIn('mhc.KET', Keterangan::MS);
            } elseif ($ket === 'TMS') {
                $builder->whereIn('mhc.KET', Keterangan::TMS);
            }
        }

        $total    = $builder->countAllResults(false);
        $totalHal = max(1, (int) ceil($total / $perHal));
        $halaman  = min($halaman, $totalHal);
        $rows = $builder->orderBy('kelurahan.nama', 'ASC')
            ->orderBy('CAST(mhc.TPS AS UNSIGNED)', 'ASC', false)
            ->orderBy('mhc.NAMA', 'ASC')
            ->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        if (session()->get('pengguna_peran') === 'viewer') {
            foreach ($rows as &$r) {
                $r['NKK'] = $this->samarkanNomor($r['NKK']);
                $r['NIK'] = $this->samarkanNomor($r['NIK']);
            }
            unset($r);
        }

        return [
            'rows'    => $rows,
            'total'   => $total,
            'halaman' => $halaman,
            'totalHal' => $totalHal,
            'perHal'  => $perHal,
            'saring'  => ['desa' => $desa, 'tps' => $tps, 'tms' => $ket, 'lp' => $lp],
        ];
    }

    /**
     * Daftar nomor TPS yang benar-benar ada pada data, untuk filter
     * dinamis di semua halaman daftar. Pilihan TPS tidak lagi dibatasi
     * 1–5: isinya mengikuti desa dan cakupan pengguna yang masuk.
     */
    public function daftarTps()
    {
        $desa   = trim((string) $this->request->getGet('desa'));
        $sumber = (string) $this->request->getGet('sumber');

        return $this->response->setJSON(['tps' => $this->ambilDaftarTps($desa, $sumber)]);
    }

    /** Opsi TPS sebagai potongan HTML untuk ditukar HTMX ke <select>. */
    public function opsiTps()
    {
        $desa   = trim((string) $this->request->getGet('desa'));
        $sumber = (string) $this->request->getGet('sumber');

        $html = '<option value="">PILIH TPS</option>';
        foreach ($this->ambilDaftarTps($desa, $sumber) as $nomor) {
            $html .= '<option value="' . esc($nomor, 'attr') . '">TPS ' . esc($nomor) . '</option>';
        }

        return $this->response->setContentType('text/html')->setBody($html);
    }

    /** @return list<string> Nomor TPS yang ada pada data sesuai desa/sumber. */
    private function ambilDaftarTps(string $desa, string $sumber): array
    {
        $db = db_connect();

        // Halaman daftar memakai view yang berbeda-beda; TPS harus
        // diambil dari view yang sama agar nomor yang ditawarkan
        // benar-benar menghasilkan baris pada halaman itu.
        $view = [
            'aktif'    => 'pemilih_active',
            'baru'     => 'pemilihbaru2',
            'tersaring' => 'tersaring',
            'pindah'   => 'pindahtps',
        ][$sumber] ?? null;

        if ($view !== null) {
            $builder = $db->table($view)->distinct()->select('TPS');
            $this->saringWilayah($builder);
            if ($desa !== '') {
                $builder->where('DESA', $desa);
            }
            $rows = $builder->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)
                ->get()->getResultArray();
        } else {
            $builder = $db->table('mhc')->distinct()->select('mhc.TPS')
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
            $this->saringWilayahId($builder);
            if ($desa !== '') {
                $kel = $db->table('kelurahan')->select('kelurahan.id AS id')
                    ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                    ->where('kelurahan.nama', $desa);
                $kecId = \App\Libraries\Wilayah::kecEfektifId();
                if ($kecId !== null) {
                    $kel->where('kelurahan.kecamatan_id', $kecId);
                }
                $ids = array_map(
                    static fn ($r) => (int) $r['id'],
                    $kel->get()->getResultArray()
                );
                $builder->whereIn('mhc.kelurahan_id', $ids === [] ? [-1] : $ids);
            }
            $rows = $builder->orderBy('CAST(mhc.TPS AS UNSIGNED)', 'ASC', false)
                ->get()->getResultArray();
        }

        $daftar = [];
        foreach ($rows as $r) {
            $tps = trim((string) ($r['TPS'] ?? ''));
            if ($tps !== '') {
                $daftar[] = $tps;
            }
        }

        return array_values(array_unique($daftar));
    }

    public function getPemilih()
    {
        $db   = db_connect();
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $ket  = (string) $this->request->getPost('tms');
        $lp   = $this->request->getPost('lp');

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->select('kelurahan.nama AS DESA, mhc.TPS, mhc.NKK, mhc.NIK, mhc.NAMA, mhc.TGLLHR, LEFT(mhc.LAST_UPDATE,10) AS LU, mhc.KET, mhc.JENIS_KELAMIN', false);
        $this->saringWilayahId($builder);
        if ($desa != '') {
            // Pilihan desa datang sebagai nama; selesaikan ke master.
            $kel = $db->table('kelurahan')->select('kelurahan.id AS id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->where('kelurahan.nama', $desa);
            $kecId = \App\Libraries\Wilayah::kecEfektifId();
            if ($kecId !== null) {
                $kel->where('kelurahan.kecamatan_id', $kecId);
            }
            $kelRow = $kel->get()->getRowArray();
            $builder->where('mhc.kelurahan_id', $kelRow !== null ? (int) $kelRow['id'] : -1);
        }
        if ($tps != '') {
            $builder->where('mhc.TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('mhc.JENIS_KELAMIN', $lp);
        }
        if ($ket !== '') {
            if (Keterangan::isMs($ket)) {
                $builder->where('mhc.KET', $ket);
            } elseif ($ket === 'MS') {
                // Agregat seluruh pemilih memenuhi syarat (0/U/B).
                $builder->whereIn('mhc.KET', Keterangan::MS);
            } elseif ($ket === 'TMS') {
                // Agregat seluruh pemilih tersaring (kode 1–8).
                $builder->whereIn('mhc.KET', Keterangan::TMS);
            }
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3, 4]
        );
    }
}
