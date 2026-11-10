<?php

namespace App\Controllers;


/**
 * Deteksi Anomali — tahap pertama (diminta AMWAL 10 Nov 2026):
 *
 * 1. Duplikat Terselubung: nama + tanggal lahir sama persis tetapi NIK
 *    berbeda, di antara baris aktif (MS). Modul NIK Ganda tidak
 *    menangkapnya karena NIK-nya memang berlainan — pola tersering
 *    adalah salah ketik NIK saat coklit.
 * 2. KK Tersebar: satu NKK yang anggota aktifnya tercatat di lebih
 *    dari satu TPS — dibedakan "Antar Desa" (lebih dari satu
 *    desa/kelurahan) dan "Beda TPS" (satu desa, TPS berbeda).
 *
 * Kedua analisis hanya menilai baris aktif (MS). Kelompok hanya tampil
 * bila menyentuh wilayah pengguna yang masuk, tetapi seluruh baris
 * kelompok itu ditampilkan — termasuk baris di wilayah seberang,
 * karena justru itu yang perlu dikoordinasikan (pola yang sama dengan
 * Analisis NIK Ganda).
 */
class Anomali extends BaseController
{
    public function index()
    {
        helper('url');
        $petaKk = $this->petaKkTersebar();
        [$jmlKelompok, $jmlBaris] = $this->ringkasanTerselubung();

        $data['title']        = 'DETEKSI ANOMALI';
        $data['title_page']   = 'DUPLIKAT TERSELUBUNG & KK TERSEBAR';
        $data['kecamatan']    = $this->kec;
        $data['kabupaten']    = $this->kab;
        $data['jmlTerselubung'] = $jmlKelompok;
        $data['jmlBaris']       = $jmlBaris;
        $data['jmlKk']          = count($petaKk);
        $data['jmlKkDesa']      = count(array_filter($petaKk, static fn ($k) => $k === 'desa'));
        $data['jmlKkTps']       = count(array_filter($petaKk, static fn ($k) => $k === 'tps'));

        $acuan = $this->tanggalAcuan();
        [$jmlAtas, $jmlBawah] = $this->hitungUmur($acuan);
        $data['acuan']        = $acuan;
        $data['acuanTampil']  = date('d-m-Y', strtotime($acuan));
        $data['jmlAtas100']   = $jmlAtas;
        $data['jmlBawah17']   = $jmlBawah;
        $data['statNik']      = $this->statistikNik();
        $data['statKecamatan'] = $this->statistikPerKecamatan();
        $data['adalahAdmin']  = session()->get('pengguna_peran') === 'admin';
        $data['tabelTerselubungAwal'] = $this->dataTerselubung([]);
        $data['tabelKkAwal']          = $this->dataKk([]);
        $data['tabelUmurAwal']        = $this->dataUmur([]);
        return view('anomali', $data);
    }

    /** Kondisi "menyentuh wilayah pengguna" untuk kueri mentah. */
    private function sentuh(string $aliasMhc = 'm', string $aliasKel = 'kel'): array
    {
        $kelId = \App\Libraries\Wilayah::kelurahanIdPengguna();
        if ($kelId !== null) {
            return ["{$aliasMhc}.kelurahan_id = ?", [$kelId]];
        }
        $kecId = \App\Libraries\Wilayah::kecEfektifId();
        if ($kecId !== null) {
            return ["{$aliasKel}.kecamatan_id = ?", [$kecId]];
        }

        return ['1 = 1', []];
    }

    private function daftarMs(): string
    {
        return "'" . implode("','", \App\Libraries\Keterangan::MS) . "'";
    }

    /**
     * Pasangan (nama, tanggal lahir) duplikat terselubung yang menyentuh
     * cakupan pengguna.
     *
     * @return list<array{nama: string, tgl: string}>
     */
    private function pasanganTerselubung(): array
    {
        // Cache analisis (optimasi 11 Nov 2026): hasil GROUP BY ini
        // dipakai berulang (ringkasan, tabel, ekspor) dan hanya berubah
        // bila data berubah.
        return $this->ambilCacheAnalisis('anomali_pasangan', function () {
        $db = db_connect();
        [$sentuh, $bindings] = $this->sentuh();
        $ms = $this->daftarMs();

        $sql = "SELECT m.NAMA AS nama, m.TGL_LAHIR AS tgl
                FROM mhc m
                JOIN kelurahan kel ON kel.id = m.kelurahan_id
                WHERE m.KET IN ({$ms}) AND m.TGL_LAHIR IS NOT NULL
                GROUP BY m.NAMA, m.TGL_LAHIR
                HAVING COUNT(DISTINCT m.NIK) > 1
                   AND SUM(CASE WHEN {$sentuh} THEN 1 ELSE 0 END) > 0";

        $hasil = [];
        foreach ($db->query($sql, $bindings)->getResult() as $baris) {
            $hasil[] = ['nama' => (string) $baris->nama, 'tgl' => (string) $baris->tgl];
        }

        return $hasil;
        });
    }

    /** NIK baris aktif yang termasuk pasangan terselubung tertentu. */
    private function nikTerselubung(array $pasangan): array
    {
        return $this->ambilCacheAnalisis('anomali_niks_terselubung', function () use ($pasangan) {
        if ($pasangan === []) {
            return [];
        }
        $db = db_connect();
        $ms = $this->daftarMs();

        $tempat  = implode(', ', array_fill(0, count($pasangan), '(?, ?)'));
        $ikat    = [];
        foreach ($pasangan as $p) {
            $ikat[] = $p['nama'];
            $ikat[] = $p['tgl'];
        }

        $sql = "SELECT DISTINCT NIK FROM mhc
                WHERE KET IN ({$ms})
                  AND (NAMA, TGL_LAHIR) IN ({$tempat})";

        $niks = [];
        foreach ($db->query($sql, $ikat)->getResult() as $baris) {
            $niks[] = (string) $baris->NIK;
        }

        return $niks;
        });
    }

    /** @return array{0: int, 1: int} [jumlah kelompok, jumlah baris] */
    private function ringkasanTerselubung(): array
    {
        $pasangan = $this->pasanganTerselubung();

        return [count($pasangan), count($this->nikTerselubung($pasangan))];
    }

    /**
     * Peta KK tersebar dalam cakupan pengguna: NKK => 'desa' | 'tps'.
     *
     * @return array<string, string>
     */
    private function petaKkTersebar(): array
    {
        return $this->ambilCacheAnalisis('anomali_peta_kk', function () {
        $db = db_connect();
        [$sentuh, $bindings] = $this->sentuh();
        $ms = $this->daftarMs();

        $sql = "SELECT m.NKK AS nkk,
                       CASE WHEN COUNT(DISTINCT m.kelurahan_id) > 1 THEN 'desa' ELSE 'tps' END AS kategori
                FROM mhc m
                JOIN kelurahan kel ON kel.id = m.kelurahan_id
                WHERE m.KET IN ({$ms})
                  AND m.NKK IS NOT NULL AND m.NKK <> '' AND m.NKK <> '0'
                GROUP BY m.NKK
                HAVING COUNT(DISTINCT CONCAT(m.kelurahan_id, '-', m.TPS)) > 1
                   AND SUM(CASE WHEN {$sentuh} THEN 1 ELSE 0 END) > 0";

        $peta = [];
        foreach ($db->query($sql, $bindings)->getResult() as $baris) {
            $peta[(string) $baris->nkk] = (string) $baris->kategori;
        }

        return $peta;
        });
    }

    // ------------------------------------------------------------------
    // Tahap 2 (10 Nov 2026): Umur Ekstrem + Statistik Kesesuaian NIK
    // ------------------------------------------------------------------

    /**
     * Tanggal hari pemungutan suara sebagai acuan umur, disimpan di
     * tabel pengaturan (kunci 'tanggal_pemungutan'). Bawaan 2024-02-14
     * (Pemilu 2024, sesuai data yang diimpor); admin dapat mengubahnya
     * untuk tahapan berikutnya.
     */
    private function tanggalAcuan(): string
    {
        $baris = db_connect()->table('pengaturan')
            ->getWhere(['kunci' => 'tanggal_pemungutan'])->getRow();
        $tgl = $baris ? (string) $baris->nilai : '';
        $cek = \DateTimeImmutable::createFromFormat('Y-m-d', $tgl);

        return ($cek && $cek->format('Y-m-d') === $tgl) ? $tgl : '2024-02-14';
    }

    public function simpanAcuan()
    {
        if (session()->get('pengguna_peran') !== 'admin') {
            return redirect()->to('anomali')
                ->with('galat', 'Hanya admin kabupaten yang dapat mengubah tanggal acuan.');
        }
        $tgl = (string) $this->request->getPost('tanggal');
        $cek = \DateTimeImmutable::createFromFormat('Y-m-d', $tgl);
        if (! $cek || $cek->format('Y-m-d') !== $tgl) {
            return redirect()->to('anomali')->with('galat', 'Tanggal acuan tidak sah.');
        }
        db_connect()->table('pengaturan')
            ->replace(['kunci' => 'tanggal_pemungutan', 'nilai' => $tgl]);

        return redirect()->to('anomali')
            ->with('sukses', 'Tanggal acuan pemungutan diubah ke ' . $cek->format('d-m-Y') . '.');
    }

    /** @return array{0: string, 1: string} [batas lahir atas-100, batas lahir bawah-17] */
    private function batasUmur(string $acuan): array
    {
        $dt = new \DateTimeImmutable($acuan);

        return [
            $dt->modify('-100 years')->format('Y-m-d'),
            $dt->modify('-17 years')->format('Y-m-d'),
        ];
    }

    /** @return array{0: int, 1: int} [jumlah atas-100, jumlah bawah-17] dalam cakupan baris pengguna. */
    private function hitungUmur(string $acuan): array
    {
        return $this->ambilCacheAnalisis('anomali_umur', function () use ($acuan) {
        $db = db_connect();
        [$sentuh, $b] = $this->sentuh();
        $ms = $this->daftarMs();
        [$batas100, $batas17] = $this->batasUmur($acuan);

        $dasar = 'FROM mhc m JOIN kelurahan kel ON kel.id = m.kelurahan_id '
            . "WHERE m.KET IN ({$ms}) AND {$sentuh}";
        $atas = (int) $db->query(
            "SELECT COUNT(*) AS j {$dasar} AND m.TGL_LAHIR < ?",
            array_merge($b, [$batas100])
        )->getRow()->j;
        $bawah = (int) $db->query(
            "SELECT COUNT(*) AS j {$dasar} AND m.TGL_LAHIR > ? AND m.STATUS = 'B'",
            array_merge($b, [$batas17])
        )->getRow()->j;

        return [$atas, $bawah];
        }, true);
    }

    // -- Tabel HTMX (konversi dari DataTables, 10 Nov 2026) -------------

    /** @param array<string, mixed> $param */
    private function dataTerselubung(array $param): array
    {
        $p = array_merge(['cari' => '', 'hal' => 1, 'perhal' => 10], $param);
        $perHal = in_array((int) $p['perhal'], [10, 25, 50, 100], true) ? (int) $p['perhal'] : 10;
        $db   = db_connect();
        $niks = $this->nikTerselubung($this->pasanganTerselubung());

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.TGL_LAHIR, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->orderBy('mhc.NAMA', 'ASC')
            ->orderBy('mhc.TGL_LAHIR', 'ASC')
            ->orderBy('mhc.NIK', 'ASC');
        if ($niks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('mhc.NIK', $niks);
        }
        if ($p['cari'] !== '') {
            $builder->groupStart()->like('mhc.NAMA', $p['cari'])
                ->orLike('mhc.NIK', $p['cari'])->orLike('mhc.NKK', $p['cari'])->groupEnd();
        }

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], $perHal);
        $label = \App\Libraries\Keterangan::daftar();
        foreach ($rows as &$r) {
            $r['TGL_TAMPIL'] = $r['TGL_LAHIR'] ? date('d-m-Y', strtotime((string) $r['TGL_LAHIR'])) : '';
            $r['KET_LABEL']  = $label[(string) $r['KET']] ?? (string) $r['KET'];
        }
        unset($r);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal,
            'saring' => ['cari' => $p['cari']],
            'urlTabel' => base_url('anomali/tabelTerselubung'), 'target' => '#wadah-terselubung',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataKk(array $param): array
    {
        $p = array_merge(['kategori' => '', 'cari' => '', 'hal' => 1, 'perhal' => 10], $param);
        $perHal = in_array((int) $p['perhal'], [10, 25, 50, 100], true) ? (int) $p['perhal'] : 10;
        $db   = db_connect();
        $peta = $this->petaKkTersebar();

        $nkks = [];
        foreach ($peta as $nkk => $kat) {
            if ($p['kategori'] === '' || $kat === $p['kategori']) {
                $nkks[] = $nkk;
            }
        }

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NKK, mhc.NIK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->orderBy('mhc.NKK', 'ASC')
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.TPS', 'ASC')
            ->orderBy('mhc.NAMA', 'ASC');
        if ($nkks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('mhc.NKK', $nkks);
        }
        if ($p['cari'] !== '') {
            $builder->groupStart()->like('mhc.NAMA', $p['cari'])
                ->orLike('mhc.NIK', $p['cari'])->orLike('mhc.NKK', $p['cari'])->groupEnd();
        }

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], $perHal);
        $label = \App\Libraries\Keterangan::daftar();
        foreach ($rows as &$r) {
            $r['KET_LABEL'] = $label[(string) $r['KET']] ?? (string) $r['KET'];
            $r['KATEGORI']  = ($peta[$r['NKK']] ?? 'tps') === 'desa' ? 'Antar Desa' : 'Beda TPS';
        }
        unset($r);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NKK', 'NIK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal,
            'saring' => ['kategori' => $p['kategori'], 'cari' => $p['cari']],
            'urlTabel' => base_url('anomali/tabelKk'), 'target' => '#wadah-kk',
        ];
    }

    /** @param array<string, mixed> $param */
    private function dataUmur(array $param): array
    {
        $p = array_merge(['kategori' => '', 'cari' => '', 'hal' => 1, 'perhal' => 10], $param);
        $perHal = in_array((int) $p['perhal'], [10, 25, 50, 100], true) ? (int) $p['perhal'] : 10;
        $db       = db_connect();
        $acuan    = $this->tanggalAcuan();
        [$batas100, $batas17] = $this->batasUmur($acuan);

        $kondisi = match ($p['kategori']) {
            'atas'  => "mhc.TGL_LAHIR < '{$batas100}'",
            'bawah' => "(mhc.TGL_LAHIR > '{$batas17}' AND mhc.STATUS = 'B')",
            default => "(mhc.TGL_LAHIR < '{$batas100}' OR (mhc.TGL_LAHIR > '{$batas17}' AND mhc.STATUS = 'B'))",
        };

        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.TGL_LAHIR, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->where($kondisi, null, false)
            ->orderBy('mhc.TGL_LAHIR', 'ASC')
            ->orderBy('mhc.NAMA', 'ASC');
        \App\Libraries\Wilayah::saringId($builder);
        if ($p['cari'] !== '') {
            $builder->groupStart()->like('mhc.NAMA', $p['cari'])
                ->orLike('mhc.NIK', $p['cari'])->orLike('mhc.NKK', $p['cari'])->groupEnd();
        }

        [$rows, $total, $halaman, $totalHal] = $this->halamanTabel($builder, (int) $p['hal'], $perHal);
        $label = \App\Libraries\Keterangan::daftar();
        foreach ($rows as &$r) {
            $r['TGL_TAMPIL'] = $r['TGL_LAHIR'] ? date('d-m-Y', strtotime((string) $r['TGL_LAHIR'])) : '';
            $r['KET_LABEL']  = $label[(string) $r['KET']] ?? (string) $r['KET'];
            $r['UMUR']       = (new \DateTimeImmutable((string) $r['TGL_LAHIR']))
                ->diff(new \DateTimeImmutable($acuan))->y . ' tahun';
            $r['KATEGORI']   = (string) $r['TGL_LAHIR'] < $batas100 ? 'Di Atas 100 Tahun' : 'Di Bawah 17 Tahun';
        }
        unset($r);

        return [
            'rows' => $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']),
            'total' => $total, 'halaman' => $halaman, 'totalHal' => $totalHal, 'perHal' => $perHal,
            'saring' => ['kategori' => $p['kategori'], 'cari' => $p['cari']],
            'urlTabel' => base_url('anomali/tabelUmur'), 'target' => '#wadah-umur',
        ];
    }

    public function tabelTerselubung()
    {
        return view('anomali/_tabel_terselubung', $this->dataTerselubung([
            'cari'   => trim((string) ($this->request->getGet('cari') ?? $this->request->getGet('cari_terselubung'))),
            'hal'    => (int) $this->request->getGet('hal'),
            'perhal' => (int) $this->request->getGet('perhal'),
        ]));
    }

    public function tabelKk()
    {
        return view('anomali/_tabel_kk', $this->dataKk([
            'kategori' => trim((string) $this->request->getGet('kategori')),
            'cari'     => trim((string) ($this->request->getGet('cari') ?? $this->request->getGet('cari_kk'))),
            'hal'      => (int) $this->request->getGet('hal'),
            'perhal'   => (int) $this->request->getGet('perhal'),
        ]));
    }

    public function tabelUmur()
    {
        return view('anomali/_tabel_umur', $this->dataUmur([
            'kategori' => trim((string) $this->request->getGet('kategori')),
            'cari'     => trim((string) ($this->request->getGet('cari') ?? $this->request->getGet('cari_umur'))),
            'hal'      => (int) $this->request->getGet('hal'),
            'perhal'   => (int) $this->request->getGet('perhal'),
        ]));
    }

    // -- Ekspor (Paket Menengah) ------------------------------------------

    public function eksporTerselubung()
    {
        $format = $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
        $db     = db_connect();
        $niks   = $this->nikTerselubung($this->pasanganTerselubung());
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.TGL_LAHIR, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->orderBy('mhc.NAMA', 'ASC')->orderBy('mhc.TGL_LAHIR', 'ASC')->orderBy('mhc.NIK', 'ASC');
        if ($niks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('mhc.NIK', $niks);
        }
        $rows  = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NIK', 'NKK']);
        $label = \App\Libraries\Keterangan::daftar();
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['NIK'], $r['NKK'], $r['NAMA'], $r['TGL_LAHIR'] ? date('d-m-Y', strtotime((string) $r['TGL_LAHIR'])) : '', $r['JENIS_KELAMIN'], $r['KEC'], $r['DESA'], $r['TPS'], $label[(string) $r['KET']] ?? $r['KET'], $r['SUMBER']];
        }
        $this->catatAkses('ekspor_anomali_terselubung', count($baris) . ' baris (' . $format . ')');

        return $this->kirimEkspor(['NO', 'NIK', 'NKK', 'NAMA', 'TGL LAHIR', 'L/P', 'KECAMATAN', 'DESA', 'TPS', 'KETERANGAN', 'SUMBER'], $baris, 'duplikat-terselubung', $format);
    }

    public function eksporKk()
    {
        $format   = $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
        $kategori = trim((string) $this->request->getGet('kategori'));
        $db       = db_connect();
        $peta     = $this->petaKkTersebar();
        $nkks     = [];
        foreach ($peta as $nkk => $kat) {
            if ($kategori === '' || $kat === $kategori) {
                $nkks[] = $nkk;
            }
        }
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NKK, mhc.NIK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->orderBy('mhc.NKK', 'ASC')->orderBy('KEC', 'ASC')->orderBy('DESA', 'ASC')->orderBy('mhc.TPS', 'ASC')->orderBy('mhc.NAMA', 'ASC');
        if ($nkks === []) {
            $builder->where('1 = 0', null, false);
        } else {
            $builder->whereIn('mhc.NKK', $nkks);
        }
        $rows  = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NKK', 'NIK']);
        $label = \App\Libraries\Keterangan::daftar();
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['NKK'], $r['NIK'], $r['NAMA'], $r['JENIS_KELAMIN'], $r['KEC'], $r['DESA'], $r['TPS'], $label[(string) $r['KET']] ?? $r['KET'], ($peta[$r['NKK']] ?? 'tps') === 'desa' ? 'Antar Desa' : 'Beda TPS'];
        }
        $this->catatAkses('ekspor_anomali_kk', count($baris) . ' baris (' . $format . ')');

        return $this->kirimEkspor(['NO', 'NKK', 'NIK', 'NAMA', 'L/P', 'KECAMATAN', 'DESA', 'TPS', 'KETERANGAN', 'KATEGORI'], $baris, 'kk-tersebar', $format);
    }

    public function eksporUmur()
    {
        $format   = $this->request->getGet('format') === 'xlsx' ? 'xlsx' : 'csv';
        $kategori = trim((string) $this->request->getGet('kategori'));
        $db       = db_connect();
        $acuan    = $this->tanggalAcuan();
        [$batas100, $batas17] = $this->batasUmur($acuan);
        $kondisi = match ($kategori) {
            'atas'  => "mhc.TGL_LAHIR < '{$batas100}'",
            'bawah' => "(mhc.TGL_LAHIR > '{$batas17}' AND mhc.STATUS = 'B')",
            default => "(mhc.TGL_LAHIR < '{$batas100}' OR (mhc.TGL_LAHIR > '{$batas17}' AND mhc.STATUS = 'B'))",
        };
        $builder = $db->table('mhc')
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.TGL_LAHIR, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false)
            ->whereIn('mhc.KET', \App\Libraries\Keterangan::MS)
            ->where($kondisi, null, false)
            ->orderBy('mhc.TGL_LAHIR', 'ASC')->orderBy('mhc.NAMA', 'ASC');
        \App\Libraries\Wilayah::saringId($builder);
        $rows  = $this->samarkanBarisPemeriksa($builder->get()->getResultArray(), ['NIK', 'NKK']);
        $label = \App\Libraries\Keterangan::daftar();
        $baris = [];
        foreach ($rows as $i => $r) {
            $baris[] = [$i + 1, $r['NIK'], $r['NKK'], $r['NAMA'], $r['TGL_LAHIR'] ? date('d-m-Y', strtotime((string) $r['TGL_LAHIR'])) : '', $r['JENIS_KELAMIN'], $r['KEC'], $r['DESA'], $r['TPS'], $label[(string) $r['KET']] ?? $r['KET'], (new \DateTimeImmutable((string) $r['TGL_LAHIR']))->diff(new \DateTimeImmutable($acuan))->y . ' tahun', (string) $r['TGL_LAHIR'] < $batas100 ? 'Di Atas 100 Tahun' : 'Di Bawah 17 Tahun'];
        }
        $this->catatAkses('ekspor_anomali_umur', count($baris) . ' baris (' . $format . ')');

        return $this->kirimEkspor(['NO', 'NIK', 'NKK', 'NAMA', 'TGL LAHIR', 'L/P', 'KECAMATAN', 'DESA', 'TPS', 'KETERANGAN', 'UMUR', 'KATEGORI'], $baris, 'umur-ekstrem', $format);
    }

    /** Ekspresi SQL tanggal lahir (hari) yang terkode pada NIK, apa adanya. */
    private function ekspresiHariNik(string $alias = 'm'): string
    {
        $hari = "CAST(SUBSTRING({$alias}.NIK, 7, 2) AS UNSIGNED)";

        return "(CASE WHEN {$hari} > 40 THEN {$hari} - 40 ELSE {$hari} END)";
    }

    /**
     * Statistik kesesuaian NIK atas SELURUH baris dalam cakupan
     * pengguna. Ketidakcocokan tanggal bersifat massal (warisan data
     * Dukcapil) sehingga disajikan sebagai statistik pemetaan, bukan
     * daftar kerja per orang.
     *
     * @return array<string, int|float>
     */
    private function statistikNik(): array
    {
        return $this->ambilCacheAnalisis('anomali_statistik_nik', function () {
        $db = db_connect();
        [$sentuh, $b] = $this->sentuh();
        $dd = $this->ekspresiHariNik('m');
        $sah = "m.NIK REGEXP '^[0-9]{16}$' AND m.TGL_LAHIR IS NOT NULL";

        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN m.NIK NOT REGEXP '^[0-9]{16}$' THEN 1 ELSE 0 END) AS non16,
                    SUM(CASE WHEN {$sah} THEN 1 ELSE 0 END) AS dinilai,
                    SUM(CASE WHEN {$sah} AND (
                        (CAST(SUBSTRING(m.NIK, 7, 2) AS UNSIGNED) NOT BETWEEN 1 AND 31
                         AND CAST(SUBSTRING(m.NIK, 7, 2) AS UNSIGNED) NOT BETWEEN 41 AND 71)
                        OR CAST(SUBSTRING(m.NIK, 9, 2) AS UNSIGNED) NOT BETWEEN 1 AND 12
                    ) THEN 1 ELSE 0 END) AS kode_rusak,
                    SUM(CASE WHEN {$sah} AND (
                        {$dd} <> DAY(m.TGL_LAHIR)
                        OR CAST(SUBSTRING(m.NIK, 9, 2) AS UNSIGNED) <> MONTH(m.TGL_LAHIR)
                        OR CAST(SUBSTRING(m.NIK, 11, 2) AS UNSIGNED) <> (YEAR(m.TGL_LAHIR) MOD 100)
                    ) THEN 1 ELSE 0 END) AS tgl_beda,
                    SUM(CASE WHEN {$sah} AND {$dd} <> DAY(m.TGL_LAHIR) THEN 1 ELSE 0 END) AS hari_beda,
                    SUM(CASE WHEN {$sah} AND CAST(SUBSTRING(m.NIK, 9, 2) AS UNSIGNED) <> MONTH(m.TGL_LAHIR) THEN 1 ELSE 0 END) AS bulan_beda,
                    SUM(CASE WHEN {$sah} AND CAST(SUBSTRING(m.NIK, 11, 2) AS UNSIGNED) <> (YEAR(m.TGL_LAHIR) MOD 100) THEN 1 ELSE 0 END) AS tahun_beda
                FROM mhc m
                JOIN kelurahan kel ON kel.id = m.kelurahan_id
                WHERE {$sentuh}";

        $r = $db->query($sql, $b)->getRow();
        $dinilai = (int) ($r->dinilai ?? 0);
        $beda    = (int) ($r->tgl_beda ?? 0);

        return [
            'total'      => (int) ($r->total ?? 0),
            'non16'      => (int) ($r->non16 ?? 0),
            'dinilai'    => $dinilai,
            'kode_rusak' => (int) ($r->kode_rusak ?? 0),
            'tgl_beda'   => $beda,
            'persen'     => $dinilai > 0 ? round($beda / $dinilai * 100, 1) : 0.0,
            'hari_beda'  => (int) ($r->hari_beda ?? 0),
            'bulan_beda' => (int) ($r->bulan_beda ?? 0),
            'tahun_beda' => (int) ($r->tahun_beda ?? 0),
        ];
        });
    }

    /**
     * Rincian statistik tanggal per kecamatan dalam cakupan pengguna.
     *
     * @return list<array{nama: string, dinilai: int, beda: int, persen: float}>
     */
    private function statistikPerKecamatan(): array
    {
        return $this->ambilCacheAnalisis('anomali_statistik_kec', function () {
        $db = db_connect();
        [$sentuh, $b] = $this->sentuh();
        $dd = $this->ekspresiHariNik('m');

        $sql = "SELECT kec.nama AS nama,
                    COUNT(*) AS dinilai,
                    SUM(CASE WHEN
                        {$dd} <> DAY(m.TGL_LAHIR)
                        OR CAST(SUBSTRING(m.NIK, 9, 2) AS UNSIGNED) <> MONTH(m.TGL_LAHIR)
                        OR CAST(SUBSTRING(m.NIK, 11, 2) AS UNSIGNED) <> (YEAR(m.TGL_LAHIR) MOD 100)
                    THEN 1 ELSE 0 END) AS beda
                FROM mhc m
                JOIN kelurahan kel ON kel.id = m.kelurahan_id
                JOIN kecamatan kec ON kec.id = kel.kecamatan_id
                WHERE {$sentuh}
                  AND m.NIK REGEXP '^[0-9]{16}$' AND m.TGL_LAHIR IS NOT NULL
                GROUP BY kec.nama
                ORDER BY kec.nama ASC";

        $hasil = [];
        foreach ($db->query($sql, $b)->getResult() as $baris) {
            $dinilai = (int) $baris->dinilai;
            $beda    = (int) $baris->beda;
            $hasil[] = [
                'nama'    => (string) $baris->nama,
                'dinilai' => $dinilai,
                'beda'    => $beda,
                'persen'  => $dinilai > 0 ? round($beda / $dinilai * 100, 1) : 0.0,
            ];
        }

        return $hasil;
        });
    }
}
