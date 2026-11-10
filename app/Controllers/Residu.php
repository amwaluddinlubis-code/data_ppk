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
            ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
            ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
            ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET, mhc.SUMBER', false)
            ->orderBy('mhc.NIK', 'ASC')
            ->orderBy('KEC', 'ASC')
            ->orderBy('DESA', 'ASC')
            ->orderBy('mhc.TPS', 'ASC');
        if ($niks === []) {
            $builder->where('1', '0', false);
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
