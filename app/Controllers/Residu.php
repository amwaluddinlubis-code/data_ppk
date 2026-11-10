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
            ->select('NIK, NKK, NAMA, JENIS_KELAMIN, KEC, DESA, TPS, KET, SUMBER')
            ->orderBy('NIK', 'ASC')
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('TPS', 'ASC');
        if ($niks === []) {
            $builder->where('1', '0', false);
        } else {
            $builder->whereIn('NIK', $niks);
        }

        $label = Coklit::labelKet();
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
        $kec      = $this->kecEfektif();
        $desa     = $this->desaPengguna();
        if ($kec !== null && $desa !== null) {
            $sentuh   = 'm.KEC = ? AND m.DESA = ?';
            $bindings = [$kec, $desa];
        } elseif ($kec !== null) {
            $sentuh   = 'm.KEC = ?';
            $bindings = [$kec];
        }

        $sql = "SELECT m.NIK AS nik,
                       CASE WHEN COUNT(DISTINCT m.KEC) > 1 THEN 'kecamatan' ELSE 'desa' END AS kategori
                FROM mhc m
                GROUP BY m.NIK
                HAVING (COUNT(DISTINCT m.KEC) > 1
                        OR COUNT(DISTINCT CONCAT(m.KEC, '|', m.DESA)) > 1)
                   AND SUM(CASE WHEN {$sentuh} THEN 1 ELSE 0 END) > 0";

        $peta = [];
        foreach ($db->query($sql, $bindings)->getResult() as $baris) {
            $peta[(string) $baris->nik] = (string) $baris->kategori;
        }

        return $peta;
    }
}
