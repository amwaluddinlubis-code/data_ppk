<?php

namespace App\Models;

use CodeIgniter\Model;

class PemilihModel extends Model
{
    // ...
    public function pemilihMS($clause = array())
    {
        $db = db_connect();
        $builder = $db->table('pemilih_active');
        $builder->select('NKK, NKKENCODE, NAMA, UMUR, JK, STATUS, KET')->orderBy('NKK, UMUR DESC');
        if(count($clause) == 0) {
            return $builder->get()->getResultArray();
            //return $builder->getWhere($clause)->getResultArray();
        } else {
            //return $builder->get()->getResultArray();
            return $builder->getWhere($clause)->getResultArray();
        }
    }
}