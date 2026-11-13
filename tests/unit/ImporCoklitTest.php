<?php

use App\Libraries\ImporCoklit;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes deteksi format berkas impor (Sidalih vs mhc) dari judul kolom.
 *
 * @internal
 */
final class ImporCoklitTest extends CIUnitTestCase
{
    public function testDeteksiSidalih(): void
    {
        $judul = ['KEC_ID', 'KECAMATAN', 'KEL_ID', 'KELURAHAN', 'TPS', 'DPID', 'NKK', 'NIK', 'NAMA', 'KETERANGAN', 'SUMBER'];
        $this->assertSame('sidalih', ImporCoklit::deteksiFormat($judul));
    }

    public function testDeteksiMhc(): void
    {
        $judul = ['KEC', 'DESA', 'TPS', 'NIK', 'NAMA', 'KET'];
        $this->assertSame('mhc', ImporCoklit::deteksiFormat($judul));
        $judul2 = ['KEC', 'KELURAHAN', 'TPS', 'NIK', 'NAMA', 'KET'];
        $this->assertSame('mhc', ImporCoklit::deteksiFormat($judul2));
    }

    public function testFormatTakDikenalNull(): void
    {
        $this->assertNull(ImporCoklit::deteksiFormat(['FOO', 'BAR']));
        $this->assertNull(ImporCoklit::deteksiFormat([]));
        // KET tanpa DESA/KELURAHAN bukan mhc.
        $this->assertNull(ImporCoklit::deteksiFormat(['NIK', 'NAMA', 'KET']));
    }

    public function testSidalihDidahulukan(): void
    {
        // Berkas yang memenuhi kedua pola dibaca sebagai Sidalih.
        $judul = ['KELURAHAN', 'DPID', 'KETERANGAN', 'KET', 'DESA'];
        $this->assertSame('sidalih', ImporCoklit::deteksiFormat($judul));
    }
}
