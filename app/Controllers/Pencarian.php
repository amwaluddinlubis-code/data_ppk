<?php

namespace App\Controllers;

use App\Libraries\Keterangan;

/**
 * Pencarian global (Paket Menengah): satu kotak cari di navbar untuk
 * mencari pemilih berdasarkan nama, NIK, atau NKK di SELURUH data
 * coklit (mhc), tanpa harus tahu pemilih itu berada di daftar mana.
 *
 * Hasil tetap terlingkup wilayah pengguna dan NIK/NKK Pemeriksa
 * tersamar; setiap baris ditandai golongannya (MS/Ubah/Baru/TMS
 * beserta label keterangannya). Hasil dibatasi 200 baris pertama,
 * dengan jumlah keseluruhan ditampilkan.
 */
class Pencarian extends BaseController
{
    public function index()
    {
        helper('url');
        $q = trim((string) $this->request->getGet('q'));

        $data = [
            'title'     => 'PENCARIAN GLOBAL',
            'title_page' => 'PENCARIAN PEMILIH',
            'kecamatan' => $this->kec,
            'kabupaten' => $this->kab,
            'q'         => $q,
            'rows'      => [],
            'total'     => 0,
        ];

        if (mb_strlen($q) >= 3) {
            $db      = db_connect();
            $builder = $db->table('mhc')
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->select('mhc.NIK, mhc.NKK, mhc.NAMA, mhc.TGLLHR, mhc.JENIS_KELAMIN, kecamatan.nama AS KEC, kelurahan.nama AS DESA, mhc.TPS, mhc.KET', false);
            \App\Libraries\Wilayah::saringId($builder);
            $builder->groupStart()
                ->like('mhc.NAMA', $q)
                ->orLike('mhc.NIK', $q)
                ->orLike('mhc.NKK', $q)
                ->groupEnd();

            $data['total'] = $builder->countAllResults(false);
            $rows = $builder
                ->orderBy('KEC', 'ASC')->orderBy('DESA', 'ASC')
                ->orderBy('CAST(mhc.TPS AS UNSIGNED)', 'ASC', false)
                ->orderBy('mhc.NAMA', 'ASC')
                ->get(200)->getResultArray();

            $label = Keterangan::daftar();
            foreach ($rows as &$r) {
                $ket = (string) $r['KET'];
                $r['GOLONGAN'] = match (true) {
                    $ket === '0' => 'Pemilih Sesuai (MS)',
                    $ket === 'U' => 'Pemilih Ubah (MS)',
                    $ket === 'B' => 'Pemilih Baru (MS)',
                    default      => 'Tersaring (TMS)',
                };
                $r['KET_LABEL'] = $label[$ket] ?? $ket;
            }
            unset($r);
            $data['rows'] = $this->samarkanBarisPemeriksa($rows, ['NIK', 'NKK']);
        }

        return view('pencarian', $data);
    }
}
