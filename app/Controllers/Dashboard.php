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
        $this->saringWilayah($b);
        $ringkas = $b->select(
            'COUNT(*) AS total,
             SUM(KET IN (' . Keterangan::sqlMs() . ')) AS ms,
             SUM(KET = \'B\') AS baru,
             SUM(KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
             SUM(KET = \'8\') AS pindah,
             COUNT(DISTINCT DESA) AS jumlah_desa,
             COUNT(DISTINCT DESA, TPS) AS jumlah_tps',
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
        $bW = $db->table('mhc');
        $this->saringWilayah($bW);
        if ($perKecamatan) {
            $perWilayah = $bW->select(
                'KEC AS wilayah,
                 COUNT(*) AS total,
                 SUM(KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                 SUM(KET = \'B\') AS baru,
                 SUM(KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
                 SUM(KET = \'8\') AS pindah,
                 COUNT(DISTINCT DESA) AS jumlah_desa,
                 COUNT(DISTINCT DESA, TPS) AS jumlah_tps',
                false
            )->groupBy('KEC')->orderBy('KEC')->get()->getResultArray();
        } else {
            $perWilayah = $bW->select(
                'DESA AS wilayah,
                 COUNT(*) AS total,
                 SUM(KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                 SUM(KET = \'B\') AS baru,
                 SUM(KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
                 SUM(KET = \'8\') AS pindah,
                 COUNT(DISTINCT TPS) AS jumlah_tps',
                false
            )->groupBy('DESA')->orderBy('DESA')->get()->getResultArray();
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

        return view('dashboard', $data);
    }

    public function getPemilih()
    {
        $db   = db_connect();
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $ket  = (string) $this->request->getPost('tms');
        $lp   = $this->request->getPost('lp');

        $builder = $db->table('mhc')->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, LEFT(LAST_UPDATE,10) AS LU, KET, JENIS_KELAMIN');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('JENIS_KELAMIN', $lp);
        }
        if ($ket !== '') {
            if (Keterangan::isMs($ket)) {
                $builder->where('KET', $ket);
            } elseif ($ket === 'TMS') {
                // Nilai 'TMS' pada pilihan berarti "Pemilih Memenuhi Syarat"
                // (warisan penamaan aplikasi asal; dipertahankan).
                $builder->whereIn('KET', Keterangan::MS);
            } else {
                $builder->whereIn('KET', Keterangan::TMS);
            }
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3, 4]
        );
    }
}
