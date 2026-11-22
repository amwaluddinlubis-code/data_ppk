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

    public function testNormalisasiTanggalFormatIndonesia(): void
    {
        // DD/MM/YYYY dengan tanggal di atas 12 tidak boleh jadi null.
        $this->assertSame('2023-02-15', ImporCoklit::normalisasiTanggal('15/02/2023'));
        // Tanggal ambigu tidak boleh tertukar bulan-tanggal (gaya Amerika).
        $this->assertSame('2023-02-05', ImporCoklit::normalisasiTanggal('05/02/2023'));
        $this->assertSame('2023-02-15', ImporCoklit::normalisasiTanggal('15-02-2023'));
    }

    public function testNormalisasiTanggalIsoDanExcel(): void
    {
        $this->assertSame('2023-02-15', ImporCoklit::normalisasiTanggal('2023-02-15'));
        // Bagian jam pada LAST_UPDATE dibuang.
        $this->assertSame('2023-03-01', ImporCoklit::normalisasiTanggal('2023-03-01 08:00:00'));
        // Nomor seri Excel: 44972 = 15 Februari 2023.
        $this->assertSame('2023-02-15', ImporCoklit::normalisasiTanggal(44972));
    }

    public function testNormalisasiTanggalTidakSah(): void
    {
        $this->assertNull(ImporCoklit::normalisasiTanggal('31/02/2023'));
        $this->assertNull(ImporCoklit::normalisasiTanggal('bukan tanggal'));
        $this->assertNull(ImporCoklit::normalisasiTanggal(''));
        $this->assertNull(ImporCoklit::normalisasiTanggal(null));
    }
}
