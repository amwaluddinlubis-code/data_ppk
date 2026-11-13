<?php

use App\Libraries\Keterangan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes pustaka Keterangan — sumber tunggal kode KET pemilih.
 * Fokus: perbedaan label kode 5 & 8 antarrezim (temuan analisis PKPU).
 *
 * @internal
 */
final class KeteranganTest extends CIUnitTestCase
{
    public function testKodeMsDanTms(): void
    {
        $this->assertSame(['0', 'U', 'B'], Keterangan::MS);
        $this->assertSame(['1', '2', '3', '4', '5', '6', '7', '8'], Keterangan::TMS);
        $this->assertTrue(Keterangan::isMs('0'));
        $this->assertTrue(Keterangan::isMs('U'));
        $this->assertTrue(Keterangan::isMs('B'));
        $this->assertFalse(Keterangan::isMs('1'));
        $this->assertTrue(Keterangan::isTms('4'));
        $this->assertFalse(Keterangan::isTms('B'));
        $this->assertFalse(Keterangan::isTms('9'));
    }

    public function testLabelKode58RezimPdpb(): void
    {
        // PKPU 1/2025: 5 = WNA, 8 = Hak Politik Dicabut.
        $this->assertSame('WNA', Keterangan::nama('5', 'pdpb'));
        $this->assertSame('Hak Politik Dicabut', Keterangan::nama('8', 'pdpb'));
        $this->assertSame('5 — WNA', Keterangan::label('5', 'pdpb'));
    }

    public function testLabelKode58RezimPenyelenggaraan(): void
    {
        // PKPU 7/2022 (Model A): 5 = Tidak Dikenal, 8 = Salah Penempatan TPS.
        $this->assertSame('Tidak Dikenal', Keterangan::nama('5', 'pemilu'));
        $this->assertSame('Salah Penempatan TPS', Keterangan::nama('8', 'pemilu'));
        $this->assertSame('Tidak Dikenal', Keterangan::nama('5', 'pilkada'));
        $this->assertSame('Salah Penempatan TPS', Keterangan::nama('8', 'pilkada'));
    }

    public function testLabelKodeLainSamaDiSemuaRezim(): void
    {
        foreach (['pemilu', 'pilkada', 'pdpb'] as $rezim) {
            $this->assertSame('Meninggal', Keterangan::nama('1', $rezim));
            $this->assertSame('Pindah Domisili', Keterangan::nama('4', $rezim));
            $this->assertSame('Pemilih Baru', Keterangan::nama('B', $rezim));
        }
    }

    public function testKodeTakDikenalDikembalikanApaAdanya(): void
    {
        $this->assertSame('X', Keterangan::nama('X', 'pdpb'));
        $this->assertSame('X', Keterangan::label('X', 'pdpb'));
    }

    public function testDaftarMemuatSemuaKode(): void
    {
        $daftar = Keterangan::daftar('pdpb');
        $this->assertCount(11, $daftar);
        $this->assertSame('5 — WNA', $daftar['5']);
        $this->assertSame('8 — Hak Politik Dicabut', $daftar['8']);
    }

    public function testSqlMsTms(): void
    {
        $this->assertSame("'0','U','B'", Keterangan::sqlMs());
        $this->assertSame("'1','2','3','4','5','6','7','8'", Keterangan::sqlTms());
    }
}
