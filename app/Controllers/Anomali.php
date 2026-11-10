<?php

namespace App\Controllers;

use \Hermawan\DataTables\DataTable;

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
    }

    /** NIK baris aktif yang termasuk pasangan terselubung tertentu. */
    private function nikTerselubung(array $pasangan): array
    {
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
    }

    /** @return array{0: int, 1: int} [jumlah kelompok, jumlah baris] */
    private function ringkasanTerselubung(): array
    {
        $pasangan = $this->pasanganTerselubung();

        return [count($pasangan), count($this->nikTerselubung($pasangan))];
    }

    public function getTerselubung()
    {
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

        $label = \App\Libraries\Keterangan::daftar();
        $json  = DataTable::of($builder)
            ->addNumbering()
            ->edit('TGL_LAHIR', static fn ($row) => date('d-m-Y', strtotime((string) $row->TGL_LAHIR)))
            ->edit('KET', static fn ($row) => $label[(string) $row->KET] ?? (string) $row->KET)
            ->toJson();

        // Kolom: 0 No, 1 NIK, 2 NKK, 3 Nama, 4 Tgl Lahir, 5 L/P,
        // 6 Kecamatan, 7 Desa, 8 TPS, 9 Keterangan, 10 Sumber.
        return $this->samarkanUntukPemeriksa($json, [1, 2]);
    }

    /**
     * Peta KK tersebar dalam cakupan pengguna: NKK => 'desa' | 'tps'.
     *
     * @return array<string, string>
     */
    private function petaKkTersebar(): array
    {
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
    }

    public function getKkTersebar()
    {
        $db       = db_connect();
        $peta     = $this->petaKkTersebar();
        $kategori = (string) $this->request->getPost('kategori');

        $nkks = [];
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

        $label = \App\Libraries\Keterangan::daftar();
        $json  = DataTable::of($builder)
            ->addNumbering()
            ->edit('KET', static fn ($row) => $label[(string) $row->KET] ?? (string) $row->KET)
            ->add('KATEGORI', static fn ($row) => ($peta[$row->NKK] ?? 'tps') === 'desa' ? 'Antar Desa' : 'Beda TPS')
            ->toJson();

        // Kolom: 0 No, 1 NKK, 2 NIK, 3 Nama, 4 L/P, 5 Kecamatan,
        // 6 Desa, 7 TPS, 8 Keterangan, 9 Kategori.
        return $this->samarkanUntukPemeriksa($json, [1, 2]);
    }
}
