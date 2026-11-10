<?php

namespace App\Controllers;

use App\Models\PemilihModel;
use \Hermawan\DataTables\DataTable;

/**
 * Halaman-halaman daftar pemilih hasil coklit: Pindah TPS, Pemilih
 * Baru, Tersaring, daftar Aktif (siap cetak), dan cetak daftar MS
 * (HTML & PDF).
 *
 * Semua daftar memakai DataTables server-side di atas view basis data,
 * disaring wilayah efektif + saringan halaman (desa/TPS/jenis kelamin/
 * keterangan). NIK/NKK Pemeriksa disamarkan oleh BaseController.
 *
 * Bangun ulang 2026: perakitan data halaman & persiapan cetak yang
 * dulu disalin di banyak metode kini terpusat di metode pembantu.
 */
class Pemilih extends BaseController
{
    // -- Halaman ------------------------------------------------------

    public function index()
    {
        $data = $this->dataHalaman('PINDAH TPS', 'DAFTAR PEMILIH PINDAH TPS');
        $data['tpsAwal']   = $this->daftarTpsDari('', 'pindah');
        $data['tabelAwal'] = $this->dataPindah([]);

        return view('pemilih', $data);
    }

    public function baru()
    {
        $data = $this->dataHalaman('PEMILIH BARU', 'DAFTAR PEMILIH BARU');
        $data['tpsAwal']   = $this->daftarTpsDari('', 'baru');
        $data['tabelAwal'] = $this->dataBaru([]);

        return view('pemilihbaru', $data);
    }

    public function tersaring()
    {
        $data = $this->dataHalaman('PEMILIH TERSARING', 'DAFTAR PEMILIH TERSARING');
        $data['tpsAwal']   = $this->daftarTpsDari('', 'tersaring');
        $data['tabelAwal'] = $this->dataTersaring([]);

        return view('tersaring', $data);
    }

    public function aktifPrint()
    {
        $data = $this->dataHalaman('CETAK DAFTAR PEMILIH MEMENUHI SYARAT', 'CETAK DAFTAR PEMILIH MEMENUHI SYARAT');
        $data['tpsAwal']   = $this->daftarTpsDari('', 'aktif');
        $data['tabelAwal'] = $this->dataAktif([]);

        return view('pemilihAktif', $data);
    }

    /** Data bersama semua halaman daftar: judul, wilayah, daftar desa. */
    private function dataHalaman(string $title, string $titlePage): array
    {
        helper('url');

        return [
            'title'     => $title,
            'title_page' => $titlePage,
            'kecamatan' => $this->kec,
            'arrDesa'   => $this->daftarDesa(),
            'kabupaten' => $this->kab,
        ];
    }

    // -- Umpan DataTables ----------------------------------------------

    public function pindahTPS()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $stat = $this->request->getPost('stat');

        $builder = db_connect()->table('pindahtps')->select('');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('MSLP', $lp);
        }
        if ($stat != '') {
            if ($stat == 1) {
                $builder->where('MSTPS >', '1');
            } else {
                $builder->where('MSTPS IS NULL', null);
            }
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [2, 3]
        );
    }

    public function getPemilihBaru()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $dp4  = $this->request->getPost('dp4');

        $builder = db_connect()->table('pemilihbaru2')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
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
        if ($dp4 != '') {
            $builder->where($dp4 > 0 ? 'OLD_NAMA IS NOT NULL' : 'OLD_NAMA IS NULL', null);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3]
        );
    }

    public function getPemilihTersaring()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');
        $ket  = $this->request->getPost('ket');

        $builder = db_connect()->table('tersaring')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('LP', $lp);
        }
        if ($ket != '') {
            $builder->where('KET', $ket);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3]
        );
    }

    public function aktif()
    {
        $desa = $this->request->getPost('desa');
        $tps  = $this->request->getPost('tps');
        $lp   = $this->request->getPost('lp');

        $builder = db_connect()->table('pemilih_active')
            ->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        $this->saringWilayah($builder);
        if ($desa != '') {
            $builder->where('DESA', $desa);
        }
        if ($tps != '') {
            $builder->where('TPS', $tps);
        }
        if ($lp != '') {
            $builder->where('JK', $lp);
        }

        return $this->samarkanUntukPemeriksa(
            DataTable::of($builder)->addNumbering()->toJson(),
            [3, 4]
        );
    }

    // -- Tabel HTMX (konversi dari DataTables, 10 Nov 2026) -------------
    //
    // Setiap halaman daftar kini dirender server-side sebagai partial
    // dan ditukar lewat HTMX. Umpan DataTables POST di atas
    // dipertahankan untuk kompatibilitas, tetapi tidak lagi dirujuk
    // tampilan mana pun.

    /** Parameter bersama tabel HTMX dari query string. */
    private function paramTabel(array $ekstra = []): array
    {
        $req    = $this->request;
        $perHal = (int) $req->getGet('perhal');
        $param  = [
            'desa'  => trim((string) $req->getGet('desa')),
            'tps'   => trim((string) $req->getGet('tps')),
            'lp'    => trim((string) $req->getGet('lp')),
            'cari'  => trim((string) $req->getGet('cari')),
            'urut'  => trim((string) $req->getGet('urut')),
            'arah'  => strtolower(trim((string) $req->getGet('arah'))) === 'desc' ? 'desc' : 'asc',
            'hal'   => (int) $req->getGet('hal'),
            'perhal' => in_array($perHal, [10, 25, 50, 100], true) ? $perHal : 10,
        ];
        foreach ($ekstra as $kunci) {
            $param[$kunci] = trim((string) $req->getGet($kunci));
        }

        return $param;
    }

    /** Pencarian bebas gaya DataTables: cocokkan beberapa kolom (LIKE). */
    private function terapkanCari($builder, string $cari, array $kolom): void
    {
        if ($cari === '') {
            return;
        }
        $builder->groupStart();
        $pertama = true;
        foreach ($kolom as $k) {
            if ($pertama) {
                $builder->like($k, $cari);
                $pertama = false;
            } else {
                $builder->orLike($k, $cari);
            }
        }
        $builder->groupEnd();
    }

    /**
     * Urutan tabel: kunci 'urut' dari pengguna dipetakan ke kolom yang
     * diizinkan; tanpa kunci sah dipakai urutan bawaan halaman.
     *
     * @param array<string, string> $peta    kunci => ekspresi kolom
     * @param list<string>          $bawaan  ekspresi kolom urutan bawaan
     */
    private function terapkanUrut($builder, string $urut, string $arah, array $peta, array $bawaan): void
    {
        if (isset($peta[$urut])) {
            $builder->orderBy($peta[$urut], $arah, false);
            return;
        }
        foreach ($bawaan as $kolom) {
            $builder->orderBy($kolom, 'ASC', false);
        }
    }

    /** @param array<string, mixed> $param */
    private function dataAktif(array $param): array
    {
        $p = array_merge(['desa' => '', 'tps' => '', 'lp' => '', 'cari' => '', 'urut' => '', 'arah' => 'asc', 'hal' => 1, 'perhal' => 10], $param);
        $builder = db_connect()->table('pemilih_active')
            ->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('JK', $p['lp']);
        }
        $this->terapkanCari($builder, $p['cari'], ['NAMA', 'NIK', 'NKK']);
        $this->terapkanUrut($builder, $p['urut'], $p['arah'], [
            'desa' => 'DESA', 'tps' => 'CAST(TPS AS UNSIGNED)', 'nama' => 'NAMA',
            'tgllhr' => 'TGLLHR', 'umur' => 'UMUR', 'ket' => 'KET',
        ], ['DESA', 'CAST(TPS AS UNSIGNED)', 'NAMA']);

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], (int) $p['perhal']);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NKK', 'NIK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => (int) $p['perhal'],
            'saring' => ['desa' => $p['desa'], 'tps' => $p['tps'], 'lp' => $p['lp'], 'cari' => $p['cari'], 'urut' => $p['urut'], 'arah' => $p['arah']],
            'urut' => $p['urut'], 'arah' => $p['arah'],
            'urlTabel' => base_url('pemilih/tabelAktif'), 'target' => '#wadah-tabel-aktif',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataBaru(array $param): array
    {
        $p = array_merge(['desa' => '', 'tps' => '', 'lp' => '', 'dp4' => '', 'cari' => '', 'urut' => '', 'arah' => 'asc', 'hal' => 1, 'perhal' => 10], $param);
        $builder = db_connect()->table('pemilihbaru2')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('JENIS_KELAMIN', $p['lp']);
        }
        if ($p['dp4'] !== '') {
            $builder->where($p['dp4'] > 0 ? 'OLD_NAMA IS NOT NULL' : 'OLD_NAMA IS NULL', null);
        }
        $this->terapkanCari($builder, $p['cari'], ['NAMA', 'NIK']);
        $this->terapkanUrut($builder, $p['urut'], $p['arah'], [
            'desa' => 'DESA', 'tps' => 'CAST(TPS AS UNSIGNED)', 'nama' => 'NAMA', 'tgllhr' => 'TGLLHR',
        ], ['DESA', 'CAST(TPS AS UNSIGNED)', 'NAMA']);

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], (int) $p['perhal']);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => (int) $p['perhal'],
            'saring' => ['desa' => $p['desa'], 'tps' => $p['tps'], 'lp' => $p['lp'], 'dp4' => $p['dp4'], 'cari' => $p['cari'], 'urut' => $p['urut'], 'arah' => $p['arah']],
            'urut' => $p['urut'], 'arah' => $p['arah'],
            'urlTabel' => base_url('pemilih/tabelBaru'), 'target' => '#wadah-tabel-baru',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataTersaring(array $param): array
    {
        $p = array_merge(['desa' => '', 'tps' => '', 'lp' => '', 'ket' => '', 'cari' => '', 'urut' => '', 'arah' => 'asc', 'hal' => 1, 'perhal' => 10], $param);
        $builder = db_connect()->table('tersaring')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('LP', $p['lp']);
        }
        if ($p['ket'] !== '') {
            $builder->where('KET', $p['ket']);
        }
        $this->terapkanCari($builder, $p['cari'], ['NAMA', 'NIK']);
        $this->terapkanUrut($builder, $p['urut'], $p['arah'], [
            'desa' => 'DESA', 'tps' => 'CAST(TPS AS UNSIGNED)', 'nama' => 'NAMA', 'tgllhr' => 'TGLLHR', 'ket' => 'KET',
        ], ['DESA', 'CAST(TPS AS UNSIGNED)', 'NAMA']);

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], (int) $p['perhal']);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => (int) $p['perhal'],
            'saring' => ['desa' => $p['desa'], 'tps' => $p['tps'], 'lp' => $p['lp'], 'ket' => $p['ket'], 'cari' => $p['cari'], 'urut' => $p['urut'], 'arah' => $p['arah']],
            'urut' => $p['urut'], 'arah' => $p['arah'],
            'urlTabel' => base_url('pemilih/tabelTersaring'), 'target' => '#wadah-tabel-tersaring',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataPindah(array $param): array
    {
        $p = array_merge(['desa' => '', 'tps' => '', 'lp' => '', 'stat' => '', 'cari' => '', 'urut' => '', 'arah' => 'asc', 'hal' => 1, 'perhal' => 10], $param);
        $builder = db_connect()->table('pindahtps')
            ->select('ID, NKK, NIK, NAMA, DESA, TPS, KET, MSDESA, MSTPS, MSNAMA, MSLP, STATUS');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('MSLP', $p['lp']);
        }
        if ($p['stat'] !== '') {
            if ($p['stat'] == 1) {
                $builder->where('MSTPS >', '1');
            } else {
                $builder->where('MSTPS IS NULL', null);
            }
        }
        $this->terapkanCari($builder, $p['cari'], ['NAMA', 'NIK', 'NKK']);
        $this->terapkanUrut($builder, $p['urut'], $p['arah'], [
            'desa' => 'DESA', 'tps' => 'CAST(TPS AS UNSIGNED)', 'nama' => 'NAMA', 'ket' => 'KET',
        ], ['DESA', 'CAST(TPS AS UNSIGNED)', 'NAMA']);

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], (int) $p['perhal']);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NKK', 'NIK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => (int) $p['perhal'],
            'saring' => ['desa' => $p['desa'], 'tps' => $p['tps'], 'lp' => $p['lp'], 'stat' => $p['stat'], 'cari' => $p['cari'], 'urut' => $p['urut'], 'arah' => $p['arah']],
            'urut' => $p['urut'], 'arah' => $p['arah'],
            'urlTabel' => base_url('pemilih/tabelPindah'), 'target' => '#wadah-tabel-pindah',
        ];
    }

    public function tabelAktif()
    {
        return view('pemilih/_tabel_aktif', $this->dataAktif($this->paramTabel()));
    }

    public function tabelBaru()
    {
        return view('pemilih/_tabel_baru', $this->dataBaru($this->paramTabel(['dp4'])));
    }

    public function tabelTersaring()
    {
        return view('pemilih/_tabel_tersaring', $this->dataTersaring($this->paramTabel(['ket'])));
    }

    public function tabelPindah()
    {
        return view('pemilih/_tabel_pindah', $this->dataPindah($this->paramTabel(['stat'])));
    }

    // -- Ekspor (Paket Menengah) -----------------------------------------
    //
    // Unduhan CSV/XLSX mengikuti saringan halaman yang sedang aktif dan
    // cakupan wilayah pengguna; NIK/NKK Pemeriksa tersamar; setiap
    // ekspor tercatat di log akses.

    public function eksporAktif()
    {
        $p = $this->paramTabel();
        $builder = db_connect()->table('pemilih_active')
            ->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, JK, UMUR, KET');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('JK', $p['lp']);
        }
        $builder->orderBy('DESA', 'ASC')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->orderBy('NAMA', 'ASC');
        $rows = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NKK', 'NIK']);
        $label = \App\Libraries\Keterangan::daftar();
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['DESA'], $r['TPS'], $r['NKK'], $r['NIK'], $r['NAMA'], $r['TGLLHR'], $r['JK'], $r['UMUR'], $label[(string) $r['KET']] ?? $r['KET']];
        }
        $this->catatAkses('ekspor_pemilih_aktif', count($baris) . ' baris (' . $this->formatEkspor() . ')');

        return $this->kirimEkspor(['NO', 'DESA', 'TPS', 'NKK', 'NIK', 'NAMA', 'TGL LAHIR', 'L/P', 'UMUR', 'KETERANGAN'], $baris, 'pemilih-aktif', $this->formatEkspor());
    }

    public function eksporBaru()
    {
        $p = $this->paramTabel(['dp4']);
        $builder = db_connect()->table('pemilihbaru2')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, JENIS_KELAMIN, OLD_NAMA');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('JENIS_KELAMIN', $p['lp']);
        }
        if ($p['dp4'] !== '') {
            $builder->where($p['dp4'] > 0 ? 'OLD_NAMA IS NOT NULL' : 'OLD_NAMA IS NULL', null);
        }
        $builder->orderBy('DESA', 'ASC')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->orderBy('NAMA', 'ASC');
        $rows = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NIK']);
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['DESA'], $r['TPS'], $r['NIK'], $r['NAMA'], $r['TGLLHR'], $r['JENIS_KELAMIN'], $r['OLD_NAMA'] ?? ''];
        }
        $this->catatAkses('ekspor_pemilih_baru', count($baris) . ' baris (' . $this->formatEkspor() . ')');

        return $this->kirimEkspor(['NO', 'DESA', 'TPS', 'NIK', 'NAMA', 'TGL LAHIR', 'L/P', 'NAMA LAMA'], $baris, 'pemilih-baru', $this->formatEkspor());
    }

    public function eksporTersaring()
    {
        $p = $this->paramTabel(['ket']);
        $builder = db_connect()->table('tersaring')
            ->select('DESA, TPS, NIK, NAMA, TGLLHR, LP, KET');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('LP', $p['lp']);
        }
        if ($p['ket'] !== '') {
            $builder->where('KET', $p['ket']);
        }
        $builder->orderBy('DESA', 'ASC')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->orderBy('NAMA', 'ASC');
        $rows = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NIK']);
        $label = \App\Libraries\Keterangan::daftar();
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['DESA'], $r['TPS'], $r['NIK'], $r['NAMA'], $r['TGLLHR'], $r['LP'], $label[(string) $r['KET']] ?? $r['KET']];
        }
        $this->catatAkses('ekspor_pemilih_tersaring', count($baris) . ' baris (' . $this->formatEkspor() . ')');

        return $this->kirimEkspor(['NO', 'DESA', 'TPS', 'NIK', 'NAMA', 'TGL LAHIR', 'L/P', 'KETERANGAN'], $baris, 'pemilih-tersaring', $this->formatEkspor());
    }

    public function eksporPindah()
    {
        $p = $this->paramTabel(['stat']);
        $builder = db_connect()->table('pindahtps')
            ->select('NKK, NIK, NAMA, DESA, TPS, MSDESA, MSTPS, MSNAMA, MSLP, STATUS');
        $this->saringWilayah($builder);
        if ($p['desa'] !== '') {
            $builder->where('DESA', $p['desa']);
        }
        if ($p['tps'] !== '') {
            $builder->where('TPS', $p['tps']);
        }
        if ($p['lp'] !== '') {
            $builder->where('MSLP', $p['lp']);
        }
        if ($p['stat'] !== '') {
            if ($p['stat'] == 1) {
                $builder->where('MSTPS >', '1');
            } else {
                $builder->where('MSTPS IS NULL', null);
            }
        }
        $builder->orderBy('DESA', 'ASC')->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)->orderBy('NAMA', 'ASC');
        $rows = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NKK', 'NIK']);
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['NIK'], $r['NAMA'], $r['DESA'], $r['TPS'], $r['MSDESA'] ?? '', $r['MSTPS'] ?? '', $r['MSNAMA'] ?? '', $r['MSLP'] ?? '', \App\Libraries\KodeBiodata::STATUS[(string) $r['STATUS']] ?? (string) ($r['STATUS'] ?? '')];
        }
        $this->catatAkses('ekspor_pindah_tps', count($baris) . ' baris (' . $this->formatEkspor() . ')');

        return $this->kirimEkspor(['NO', 'NIK', 'NAMA', 'DESA', 'TPS', 'DESA BARU', 'TPS BARU', 'NAMA BARU', 'L/P', 'STATUS'], $baris, 'pindah-tps', $this->formatEkspor());
    }

    private function formatEkspor(): string
    {
        return $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
    }

    // -- Cetak daftar MS -------------------------------------------------

    /** Cetak daftar pemilih MS per desa/TPS sebagai halaman HTML siap cetak. */
    public function ms()
    {
        $data = $this->siapkanCetakMs(false);
        $this->catatAkses('cetak_daftar_ms', 'Desa ' . $data['desa'] . ' TPS ' . $data['tps'] . ' (' . count($data['dtTable']) . ' pemilih)');

        return view('print/print_ms', $data);
    }

    /** Daftar MS yang sama sebagai berkas PDF (dompdf). */
    public function msPdf()
    {
        $data = $this->siapkanCetakMs(true);
        $html = view('print/print_ms', $data);

        $pdf        = new \App\Libraries\pdfGenerator();
        $namaBerkas = 'Daftar-MS-' . preg_replace('/[^A-Za-z0-9]+/', '-', $data['desa']) . '-TPS' . $data['tps'];
        $isi        = $pdf->generate($html, $namaBerkas, 'A4', 'landscape', false);

        $this->catatAkses('unduh_pdf_ms', 'Desa ' . $data['desa'] . ' TPS ' . $data['tps'] . ' (' . count($data['dtTable']) . ' pemilih)');

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $namaBerkas . '.pdf"')
            ->setBody($isi);
    }

    /**
     * Siapkan data cetak MS untuk desa/TPS terpilih. PPS terkunci ke
     * desanya sendiri; pengguna lain mengambil desa dari permintaan.
     *
     * @return array{dtTable: array, kecamatan: ?string, desa: string, tps: string, title: string}
     */
    private function siapkanCetakMs(bool $dariGet): array
    {
        $desa = $this->desaPengguna()
            ?? (string) ($dariGet ? $this->request->getGet('desa') : $this->request->getPostGet('desa'));
        $tps  = (string) ($dariGet ? $this->request->getGet('tps') : $this->request->getPostGet('tps'));

        $klausa = ['DESA' => $desa, 'TPS' => $tps];
        if ($this->kecEfektif() !== null) {
            $klausa['KEC'] = $this->kecEfektif();
        }

        $dt = new PemilihModel();

        return [
            'dtTable'   => $this->samarkanBarisCetak($dt->pemilihMS($klausa)),
            'kecamatan' => $this->kecEfektif(),
            'desa'      => strtoupper($desa),
            'tps'       => strtoupper($tps),
            'title'     => 'CETAK PEMILIH AKTIF - HASIL PANTARLIH | ' . strtoupper($desa) . '00' . $tps,
        ];
    }
}
