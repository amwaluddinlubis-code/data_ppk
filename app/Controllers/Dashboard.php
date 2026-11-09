<?php

namespace App\Controllers;

use \Hermawan\DataTables\DataTable;

class Dashboard extends BaseController
{
    public function index()
    {
        helper('url');
        $db = db_connect();
        $builder = $db->table('desa')->select('DESA');
        $data['title'] = 'DAFTAR HASIL COKLIT PANTARLIH';
        $data['title_page'] = 'DAFTAR HASIL COKLIT PANTARLIH';
        $data['arrDesa'] = $builder->get();
        $data['kecamatan'] = $this->kec;
        return view('dashboard', $data);
    }

    public function getPemilih()
    {
        $db = db_connect();
        $desa = $this->request->getPost('desa');
        $tps = $this->request->getPost('tps');
        $ket = $this->request->getPost('tms');
        $lp = $this->request->getPost('lp');
        $builder = $db->table('mhc')->select('DESA, TPS, NKK, NIK, NAMA, TGLLHR, LEFT(LAST_UPDATE,10) AS LU, KET, JENIS_KELAMIN');
        if ($desa != '') $builder->where('DESA', $desa);
        if ($tps != '') $builder->where('TPS', $tps);
        if ($lp != '') $builder->where('JENIS_KELAMIN', $lp);
        if ($ket != '') {
            if ($ket == 0 or $ket == 'B' or $ket == 'U') {
                $builder->where('KET', $ket);
            } elseif ($ket == 'TMS') {
                $builder->whereIn('KET', ['0','B','U']);
            } else {
                $builder->whereIn('KET', [1, 2, 3, 6, 7, 8]);
            }
        }
        //$builder->orderBy('NAMA');

        // if ($ket != '') $builder->where('kET', $ket);

        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }
}