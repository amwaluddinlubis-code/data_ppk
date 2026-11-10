<?php

namespace App\Controllers;

use \Hermawan\DataTables\DataTable;

/**
 * Analisis kegandaan berdasarkan NIK (diminta 10 Nov 2026).
 *
 * Menggantikan analisis lama yang hanya melihat view analisis_nik_ganda
 * (duplikat di dalam daftar MS saja — pada data contoh selalu kosong).
 * Analisis baru cukup memperhatikan NIK di SELURUH baris coklit:
 *
 * - Antar Kecamatan: satu NIK tercatat di lebih dari satu kecamatan.
 * - Antar Desa: satu NIK tercatat di lebih dari satu desa/kelurahan
 *   di dalam kecamatan yang sama.
 *
 * Kegandaan di dalam desa yang sama (pola normal coklit: baris TMS +
 * baris baru orang yang sama) tidak ikut dihitung. Kelompok ganda hanya
 * tampil bila menyentuh wilayah pengguna yang masuk (admin: semua;
 * PPK: kecamatannya; PPS: desanya), tetapi seluruh baris NIK kelompok
 * itu ditampilkan — termasuk baris di wilayah seberang, karena justru
 * itu yang perlu dikoordinasikan.
 */
class Residu extends BaseController
{
    public function index()
    {
        helper('url');
        $peta = $this->petaGanda();
        $data['title']        = 'ANALISIS NIK GANDA';
        $data['title_page']   = 'KEGANDAAN NIK ANTAR WILAYAH';
        $data['kecamatan']    = $this->kec;
        $data['kabupaten']    = $this->kab;
        $data['jmlGanda']     = count($peta);
        $data['jmlKecamatan'] = count(array_filter($peta, static fn ($k) => $k === 'kecamatan'));
        $data['jmlDesa']      = count(array_filter($peta, static fn ($k) => $k === 'desa'));
        $data['jmlPeringatan'] = $this->hitungPeringatanGender();
        $data['tabelGandaAwal']      = $this->dataGanda([]);
        $data['tabelPeringatanAwal'] = $this->dataPeringatan([]);
        return view('ganda', $data);
    }

    public function getGanda()
    {
        $db       = db_connect();
        $peta     = $this->petaGanda();
        $kategori = (string) $this->request->getPost('kategori');

        $niks = [];
        foreach ($peta as $nik => $kat) {
            if ($kategori === '' || $kat === $kategori) {
                $niks[] = $nik;
            }
        }

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->orderBy('mhc.NIK', 'ASC')
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.TPS', 'ASC');
        if ($niks === []) {
            // Tanpa NIK ganda, paksa hasil kosong dengan kondisi mentah:
            // where('1', '0') TIDAK bisa — kunci '1' diubah PHP menjadi
            // int sehingga BaseBuilder::getOperatorFromWhereKey() TypeError.
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('NIK', $niks);
        }

        $label = \App\Libraries\Keterangan::daftar();
        $json  = DataTable::of($builder)
            ->addNumbering()
            ->edit('KET', static fn ($row) => $label[(string) $row->KET] ?? (string) $row->KET)
            ->add('KATEGORI', static fn ($row) => ($peta[$row->NIK] ?? 'desa') === 'kecamatan' ? 'Antar Kecamatan' : 'Antar Desa')
            ->toJson();

        // Kolom: 0 No, 1 NIK, 2 NKK, 3 Nama, 4 L/P, 5 Kecamatan,
        // 6 Desa, 7 TPS, 8 Keterangan, 9 Sumber, 10 Kategori.
        return $this->samarkanUntukPemeriksa($json, [1, 2]);
    }

    /**
     * Kondisi SQL "jenis kelamin tercatat tidak cocok dengan kode NIK".
     * NIK mengodekan tanggal lahir pada digit 7-8: laki-laki 01-31,
     * perempuan 41-71 (tanggal + 40). Baris yang kode harinya di luar
     * kedua rentang itu tidak dinilai di sini (itu soal sah-tidaknya
     * NIK, bukan soal kecocokan jenis kelamin).
     */
    private function kondisiGender(string $alias = 'm'): string
    {
        $hari = "CAST(SUBSTRING({$alias}.NIK, 7, 2) AS UNSIGNED)";

        return "({$alias}.NIK REGEXP '^[0-9]{16}$' AND ("
            . "({$hari} BETWEEN 1 AND 31 AND {$alias}.JENIS_KELAMIN = 'P') OR "
            . "({$hari} BETWEEN 41 AND 71 AND {$alias}.JENIS_KELAMIN = 'L')))";
    }

    /** Jumlah baris peringatan jenis kelamin dalam cakupan pengguna. */
    private function hitungPeringatanGender(): int
    {
        $db       = db_connect();
        $bindings = [];
        $sentuh   = '1 = 1';
        $kecId    = \App\Libraries\Wilayah::kecEfektifId();
        $kelId    = \App\Libraries\Wilayah::kelurahanIdPengguna();
        if ($kelId !== null) {
            $sentuh   = 'm.kelurahan_id = ?';
            $bindings = [$kelId];
        } elseif ($kecId !== null) {
            $sentuh   = 'kel.kecamatan_id = ?';
            $bindings = [$kecId];
        }

        $sql = 'SELECT COUNT(*) AS jml FROM mhc m '
            . 'JOIN kelurahan kel ON kel.id = m.kelurahan_id '
            . 'WHERE ' . $this->kondisiGender('m') . " AND {$sentuh}";

        return (int) ($db->query($sql, $bindings)->getRow()->jml ?? 0);
    }

    /**
     * Tabel peringatan jenis kelamin vs kode NIK. Sifatnya HANYA
     * peringatan (diminta AMWAL 10 Nov 2026): ketidakcocokan seperti
     * ini sering berasal dari data Dukcapil, jadi tidak menghalangi
     * data apa pun — hanya bahan klarifikasi bagi petugas.
     */
    public function getPeringatan()
    {
        $db      = db_connect();
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select("mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, mhc.SUMBER AS NIK_JK, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET", false)
            ->where($this->kondisiGender('mhc'), null, false)
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.NAMA', 'ASC');
        \App\Libraries\Wilayah::saringId($builder);

        $label = \App\Libraries\Keterangan::daftar();
        $jk    = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        $json  = DataTable::of($builder)
            ->addNumbering()
            ->edit('JENIS_KELAMIN', static fn ($row) => $jk[(string) $row->JENIS_KELAMIN] ?? (string) $row->JENIS_KELAMIN)
            // Kolom NIK_JK meminjam tempat SUMBER pada select (parser
            // DataTables tidak menerima ekspresi CASE); nilainya dihitung
            // ulang di sini dari NIK baris itu sendiri.
            ->edit('NIK_JK', static fn ($row) => ((int) substr((string) $row->NIK, 6, 2)) > 40 ? 'Perempuan' : 'Laki-laki')
            ->edit('KET', static fn ($row) => $label[(string) $row->KET] ?? (string) $row->KET)
            ->add('TANDA', static fn () => 'Peringatan')
            ->toJson();

        // Kolom: 0 No, 1 NIK, 2 NKK, 3 Nama, 4 Tercatat, 5 Menurut NIK,
        // 6 Kecamatan, 7 Desa, 8 TPS, 9 Keterangan, 10 Tanda.
        return $this->samarkanUntukPemeriksa($json, [1, 2]);
    }

    // -- Tabel HTMX (konversi dari DataTables, 10 Nov 2026) -------------

    /** @param array<string, mixed> $param */
    private function dataGanda(array $param): array
    {
        $p = array_merge(['kategori' => '', 'cari' => '', 'hal' => 1, 'perhal' => 10], $param);
        $perHal = in_array((int) $p['perhal'], [10, 25, 50, 100], true) ? (int) $p['perhal'] : 10;
        $db   = db_connect();
        $peta = $this->petaGanda();

        $niks = [];
        foreach ($peta as $nik => $kat) {
            if ($p['kategori'] === '' || $kat === $p['kategori']) {
                $niks[] = $nik;
            }
        }

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->orderBy('mhc.NIK', 'ASC')
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.TPS', 'ASC');
        if ($niks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('NIK', $niks);
        }
        if ($p['cari'] !== '') {
            $builder->groupStart()->like('mhc.NAMA', $p['cari'])
                ->orLike('mhc.NIK', $p['cari'])->orLike('mhc.NKK', $p['cari'])->groupEnd();
        }

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], $perHal);
        $label = \App\Libraries\Keterangan::daftar();
        foreach ($rows as &$r) {
            $r['KET_LABEL'] = $label[(string) $r['KET']] ?? (string) $r['KET'];
            $r['KATEGORI']  = ($peta[$r['NIK']] ?? 'desa') === 'kecamatan' ? 'Antar Kecamatan' : 'Antar Desa';
        }
        unset($r);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal,
            'saring' => ['kategori' => $p['kategori'], 'cari' => $p['cari']],
            'urlTabel' => base_url('residu/tabelGanda'), 'target' => '#wadah-ganda',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataPeringatan(array $param): array
    {
        $p = array_merge(['cari' => '', 'hal' => 1, 'perhal' => 10], $param);
        $perHal = in_array((int) $p['perhal'], [10, 25, 50, 100], true) ? (int) $p['perhal'] : 10;
        $db      = db_connect();
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->where($this->kondisiGender('mhc'), null, false)
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.NAMA', 'ASC');
        \App\Libraries\Wilayah::saringId($builder);
        if ($p['cari'] !== '') {
            $builder->groupStart()->like('mhc.NAMA', $p['cari'])
                ->orLike('mhc.NIK', $p['cari'])->orLike('mhc.NKK', $p['cari'])->groupEnd();
        }

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], $perHal);
        $label = \App\Libraries\Keterangan::daftar();
        $jk    = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        foreach ($rows as &$r) {
            $r['TERCATAT']   = $jk[(string) $r['JENIS_KELAMIN']] ?? (string) $r['JENIS_KELAMIN'];
            $r['MENURUT']    = ((int) substr((string) $r['NIK'], 6, 2)) > 40 ? 'Perempuan' : 'Laki-laki';
            $r['KET_LABEL']  = $label[(string) $r['KET']] ?? (string) $r['KET'];
        }
        unset($r);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal,
            'saring' => ['cari' => $p['cari']],
            'urlTabel' => base_url('residu/tabelPeringatan'), 'target' => '#wadah-peringatan',
        ];
    }

    public function tabelGanda()
    {
        return view('residu/_tabel_ganda', $this->dataGanda([
            'kategori' => trim((string) $this->request->getGet('kategori')),
            'cari'     => trim((string) ($this->request->getGet('cari') ?? $this->request->getGet('cari_ganda'))),
            'hal'      => (int) $this->request->getGet('hal'),
            'perhal'   => (int) $this->request->getGet('perhal'),
        ]));
    }

    public function tabelPeringatan()
    {
        return view('residu/_tabel_peringatan', $this->dataPeringatan([
            'cari'   => trim((string) ($this->request->getGet('cari') ?? $this->request->getGet('cari_peringatan'))),
            'hal'    => (int) $this->request->getGet('hal'),
            'perhal' => (int) $this->request->getGet('perhal'),
        ]));
    }

    // -- Ekspor (Paket Menengah) ------------------------------------------

    public function eksporGanda()
    {
        $format = $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
        $semua  = $this->dataGandaSemua(trim((string) $this->request->getGet('kategori')));
        $baris = [];
        foreach ($semua as $i => $r) {
            $baris[] = [$i + 1, $r['NIK'], $r['NKK'], $r['NAMA'], $r['JENIS_KELAMIN'], $r['KEC'], $r['DESA'], $r['TPS'], $r['KET_LABEL'], $r['SUMBER'], $r['KATEGORI']];
        }
        $this->catatAkses('ekspor_nik_ganda', count($baris) . ' baris (' . $format . ')');

        return $this->kirimEkspor(['NO', 'NIK', 'NKK', 'NAMA', 'L/P', 'KECAMATAN', 'DESA', 'TPS', 'KETERANGAN', 'SUMBER', 'KATEGORI'], $baris, 'nik-ganda', $format);
    }

    /** Seluruh baris NIK ganda untuk ekspor (tanpa pagination). */
    private function dataGandaSemua(string $kategori): array
    {
        $db   = db_connect();
        $peta = $this->petaGanda();
        $niks = [];
        foreach ($peta as $nik => $kat) {
            if ($kategori === '' || $kat === $kategori) {
                $niks[] = $nik;
            }
        }
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->orderBy('mhc.NIK', 'ASC')->orderBy('KEC', 'ASC')->orderBy('DESA', 'ASC')->orderBy('mhc.TPS', 'ASC');
        if ($niks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('NIK', $niks);
        }
        $rows  = $builder->get()->getResultArray();
        $label = \App\Libraries\Keterangan::daftar();
        foreach ($rows as &$r) {
            $r['KET_LABEL'] = $label[(string) $r['KET']] ?? (string) $r['KET'];
            $r['KATEGORI']  = ($peta[$r['NIK']] ?? 'desa') === 'kecamatan' ? 'Antar Kecamatan' : 'Antar Desa';
        }
        unset($r);

        return $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']);
    }

    public function eksporPeringatan()
    {
        $format = $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
        $db     = db_connect();
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->where($this->kondisiGender('mhc'), null, false)
            ->orderBy('KEC', 'ASC')->orderBy('DESA', 'ASC')->orderBy('mhc.NAMA', 'ASC');
        \App\Libraries\Wilayah::saringId($builder);
        $rows  = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NIK', 'NKK']);
        $label = \App\Libraries\Keterangan::daftar();
        $jk    = ['L' => 'Laki-laki', 'P' => 'Perempuan'];
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['NIK'], $r['NKK'], $r['NAMA'], $jk[(string) $r['JENIS_KELAMIN']] ?? $r['JENIS_KELAMIN'], ((int) substr((string) $r['NIK'], 6, 2)) > 40 ? 'Perempuan' : 'Laki-laki', $r['KEC'], $r['DESA'], $r['TPS'], $label[(string) $r['KET']] ?? $r['KET'], 'Peringatan'];
        }
        $this->catatAkses('ekspor_peringatan_jk', count($baris) . ' baris (' . $format . ')');

        return $this->kirimEkspor(['NO', 'NIK', 'NKK', 'NAMA', 'TERCATAT', 'MENURUT NIK', 'KECAMATAN', 'DESA', 'TPS', 'KETERANGAN', 'TANDA'], $baris, 'peringatan-jk-nik', $format);
    }

    /**
     * Peta NIK ganda antar wilayah yang menyentuh cakupan pengguna:
     * NIK => 'kecamatan' | 'desa'. Klasifikasi dihitung dari seluruh
     * basis data (satu kabupaten), bukan dari baris yang tersaring.
     *
     * @return array<string, string>
     */
    private function petaGanda(): array
    {
        $db       = db_connect();
        $bindings = [];
        $sentuh   = '1 = 1';
        $kecId    = \App\Libraries\Wilayah::kecEfektifId();
        $kelId    = \App\Libraries\Wilayah::kelurahanIdPengguna();
        if ($kecId !== null && $kelId !== null) {
            $sentuh   = 'm.kelurahan_id = ?';
            $bindings = [$kelId];
        } elseif ($kecId !== null) {
            $sentuh   = 'kel.kecamatan_id = ?';
            $bindings = [$kecId];
        }

        $sql = "SELECT m.NIK AS nik,
                       CASE WHEN COUNT(DISTINCT kel.kecamatan_id) > 1 THEN 'kecamatan' ELSE 'desa' END AS kategori
                FROM mhc m
                JOIN kelurahan kel ON kel.id = m.kelurahan_id
                GROUP BY m.NIK
                HAVING (COUNT(DISTINCT kel.kecamatan_id) > 1
                        OR COUNT(DISTINCT m.kelurahan_id) > 1)
                   AND SUM(CASE WHEN {$sentuh} THEN 1 ELSE 0 END) > 0";

        $peta = [];
        foreach ($db->query($sql, $bindings)->getResult() as $baris) {
            $peta[(string) $baris->nik] = (string) $baris->kategori;
        }

        return $peta;
    }
}
