<?php

namespace App\Controllers;

use App\Libraries\Keterangan;

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

        // Kartu statistik + rincian per wilayah + kartu Perlu Ditangani
        // dihitung dari cache analisis (optimasi 11 Nov 2026): angka
        // hanya berubah bila data berubah. Tabel Hasil Coklit di bawah
        // tetap langsung dari basis data.
        $bundle = $this->ambilCacheAnalisis('dasbor_statistik', fn () => $this->hitungStatistikDasbor());
        $data['stats']        = $bundle['stats'];
        $data['perWilayah']   = $bundle['perWilayah'];
        $data['perKecamatan'] = $bundle['perKecamatan'];
        $data['perlu']        = $this->ambilCacheAnalisis(
            'dasbor_perlu',
            fn () => $this->hitungPerluDitangani(),
            true
        );

        $data['title']      = 'Dasbor — Data Pemilih';
        $data['title_page'] = 'Dasbor Data Pemilih';
        $data['kecamatan']  = $this->kec;
        $data['kabupaten']  = $this->kab;
        $data['arrDesa']      = $this->daftarDesa();
        // Pilot HTMX: tabel Hasil Coklit dirender server-side sejak
        // muat pertama; saringan & pagination berikutnya menukar
        // potongan tabel lewat endpoint tabelPemilih().
        $data['tpsAwal']      = $this->ambilDaftarTps('', 'semua');
        $data['tabelAwal']    = $this->dataTabelPemilih([]);
        $data['pdpb']         = $this->ringkasanPdpb();

        return view('dashboard', $data);
    }

    /**
     * Kartu statistik + tabel rincian per wilayah (bagian dasbor yang
     * mahal: beberapa pemindaian agregat atas mhc dan view v_nkk).
     *
     * @return array{stats: array<string, int>, perWilayah: array, perKecamatan: bool}
     */
    /**
     * Ringkasan PDPB periode berjalan untuk kartu dasbor (Fase B).
     * Dihitung langsung dari riwayat_perubahan terikat periode —
     * tabelnya kecil sehingga tidak perlu cache analisis.
     *
     * @return array{nama: string, baru: int, tms: int, ubah: int}
     */
    private function ringkasanPdpb(): array
    {
        $kosong = ['nama' => '', 'baru' => 0, 'tms' => 0, 'ubah' => 0];
        try {
            $periode = \App\Libraries\PeriodePdpb::pastikanAktif();
            if ($periode === []) {
                return $kosong;
            }
            $db = db_connect();
            $q = $db->table('riwayat_perubahan')
                ->select('aksi, data_lama, data_baru')
                ->where('periode_id', (int) $periode['id'])->where('tabel', 'mhc');
            $kelId = \App\Libraries\Wilayah::kelurahanIdPengguna();
            $kecId = \App\Libraries\Wilayah::kecEfektifId();
            if ($kelId !== null) {
                $q->where('kelurahan_id', $kelId);
            } elseif ($kecId !== null) {
                $q->where('kecamatan_id', $kecId);
            }
            $hasil = ['nama' => (string) $periode['nama'], 'baru' => 0, 'tms' => 0, 'ubah' => 0];
            foreach ($q->get()->getResultArray() as $r) {
                if ($r['aksi'] === 'tambah') {
                    $hasil['baru']++;
                } elseif ($r['aksi'] === 'ubah') {
                    $lama = json_decode((string) $r['data_lama'], true) ?? [];
                    $baru = json_decode((string) $r['data_baru'], true) ?? [];
                    $msLama = \App\Libraries\Keterangan::isMs((string) ($lama['KET'] ?? ''));
                    $msBaru = \App\Libraries\Keterangan::isMs((string) ($baru['KET'] ?? ''));
                    if ($msLama && ! $msBaru) {
                        $hasil['tms']++;
                    } else {
                        $hasil['ubah']++;
                    }
                }
            }

            return $hasil;
        } catch (\Throwable) {
            return $kosong;
        }
    }

    private function hitungStatistikDasbor(): array
    {
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

        return [
            'stats' => [
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
            ],
            'perWilayah'   => $perWilayah,
            'perKecamatan' => $perKecamatan,
        ];
    }

    /** Potongan tabel Hasil Coklit untuk HTMX (GET, tanpa muat ulang halaman). */
    /**
     * Angka "Perlu Ditangani" untuk kartu tugas di dasbor (Paket
     * Menengah): jumlah kelompok NIK ganda, baris peringatan JK, dan
     * kelompok anomali dalam cakupan pengguna. Kuerinya meniru modul
     * Residu dan Anomali (pola "menyentuh wilayah"), tetapi hanya
     * menghitung — rincian tetap dikerjakan di modul masing-masing.
     *
     * @return array{ganda: int, peringatan: int, terselubung: int, kk: int, umur: int}
     */
    private function hitungPerluDitangani(): array
    {
        $db    = db_connect();
        $kelId = \App\Libraries\Wilayah::kelurahanIdPengguna();
        $kecId = \App\Libraries\Wilayah::kecEfektifId();
        $sentuh = '1 = 1';
        $ikat   = [];
        if ($kelId !== null) {
            $sentuh = 'm.kelurahan_id = ?';
            $ikat   = [$kelId];
        } elseif ($kecId !== null) {
            $sentuh = 'kel.kecamatan_id = ?';
            $ikat   = [$kecId];
        }
        $gabung = 'FROM mhc m JOIN kelurahan kel ON kel.id = m.kelurahan_id ';
        $sentuhSum = "SUM(CASE WHEN {$sentuh} THEN 1 ELSE 0 END) > 0";

        $ganda = (int) $db->query(
            "SELECT COUNT(*) AS j FROM (SELECT m.NIK {$gabung} "
            . "GROUP BY m.NIK HAVING (COUNT(DISTINCT kel.kecamatan_id) > 1 "
            . "OR COUNT(DISTINCT m.kelurahan_id) > 1) AND {$sentuhSum}) t",
            $ikat
        )->getRow()->j;

        $hari = "CAST(SUBSTRING(m.NIK, 7, 2) AS UNSIGNED)";
        $kondisiGender = "(m.NIK REGEXP '^[0-9]{16}$' AND ("
            . "({$hari} BETWEEN 1 AND 31 AND m.JENIS_KELAMIN = 'P') OR "
            . "({$hari} BETWEEN 41 AND 71 AND m.JENIS_KELAMIN = 'L')))";
        $peringatan = (int) $db->query(
            "SELECT COUNT(*) AS j {$gabung} WHERE {$kondisiGender} AND {$sentuh}",
            $ikat
        )->getRow()->j;

        $ms = Keterangan::sqlMs();
        $terselubung = (int) $db->query(
            "SELECT COUNT(*) AS j FROM (SELECT m.NAMA {$gabung} "
            . "WHERE m.KET IN ({$ms}) AND m.TGL_LAHIR IS NOT NULL "
            . "GROUP BY m.NAMA, m.TGL_LAHIR HAVING COUNT(DISTINCT m.NIK) > 1 AND {$sentuhSum}) t",
            $ikat
        )->getRow()->j;

        $kk = (int) $db->query(
            "SELECT COUNT(*) AS j FROM (SELECT m.NKK {$gabung} "
            . "WHERE m.KET IN ({$ms}) AND m.NKK IS NOT NULL AND m.NKK <> '' AND m.NKK <> '0' "
            . "GROUP BY m.NKK HAVING COUNT(DISTINCT CONCAT(m.kelurahan_id, '-', m.TPS)) > 1 AND {$sentuhSum}) t",
            $ikat
        )->getRow()->j;

        $barisAcuan = $db->table('pengaturan')->getWhere(['kunci' => 'tanggal_pemungutan'])->getRow();
        $tgl = $barisAcuan ? (string) $barisAcuan->nilai : '';
        $cek = \DateTimeImmutable::createFromFormat('Y-m-d', $tgl);
        $acuan = ($cek && $cek->format('Y-m-d') === $tgl) ? $tgl : '2024-02-14';
        $dt = new \DateTimeImmutable($acuan);
        $batas100 = $dt->modify('-100 years')->format('Y-m-d');
        $batas17  = $dt->modify('-17 years')->format('Y-m-d');
        $atas = (int) $db->query(
            "SELECT COUNT(*) AS j {$gabung} WHERE m.KET IN ({$ms}) AND {$sentuh} AND m.TGL_LAHIR < ?",
            array_merge($ikat, [$batas100])
        )->getRow()->j;
        $bawah = (int) $db->query(
            "SELECT COUNT(*) AS j {$gabung} WHERE m.KET IN ({$ms}) AND {$sentuh} AND m.TGL_LAHIR > ? AND m.STATUS = 'B'",
            array_merge($ikat, [$batas17])
        )->getRow()->j;

        return [
            'ganda'      => $ganda,
            'peringatan' => $peringatan,
            'terselubung' => $terselubung,
            'kk'         => $kk,
            'umur'       => $atas + $bawah,
        ];
    }

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
     * endpoint HTMX). NIK/NKK Pemeriksa disamarkan.
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
        return $this->daftarTpsDari($desa, $sumber);
    }

}
