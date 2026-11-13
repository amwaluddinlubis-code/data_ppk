<?php

use App\Libraries\AlasanPindah;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes daftar alasan pindah memilih (DPTb) — daftar putih pengajuan.
 *
 * @internal
 */
final class AlasanPindahTest extends CIUnitTestCase
{
    public function testSepuluhAlasanResmi(): void
    {
        $this->assertCount(10, AlasanPindah::DAFTAR);
        $this->assertContains('Pindah domisili', AlasanPindah::DAFTAR);
    }

    public function testAlasanSahDanTidakSah(): void
    {
        $this->assertTrue(AlasanPindah::sah('Pindah domisili'));
        $this->assertTrue(AlasanPindah::sah('Tertimpa bencana alam'));
        $this->assertFalse(AlasanPindah::sah('pindah domisili')); // peka huruf besar/kecil
        $this->assertFalse(AlasanPindah::sah('Liburan'));
        $this->assertFalse(AlasanPindah::sah(''));
    }
}
