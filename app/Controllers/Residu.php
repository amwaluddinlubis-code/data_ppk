<?php

namespace App\Controllers;

use \Hermawan\DataTables\DataTable;

class Residu extends BaseController
{
    public function index()
    {
        helper('url');
        $db = db_connect();
        $data['title'] = 'DATA GANDA';
        $data['title_page'] = 'PEMILIH GANDA';
        $data['kecamatan'] = $this->kec;
        return view('ganda', $data);
    }

    public function getGanda()
    {
        $db = db_connect();

        #$builder = $query->result();
        $builder = $db->table('analisis_nik_ganda')->select('DESA, TPS, KET, NIK, NKK, NAMA');
        #print($db->getLastQuery());

        return DataTable::of($builder)
            ->addNumbering() //it will return data output with numbering on first column
            ->toJson();
    }
}