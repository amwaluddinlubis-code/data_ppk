<?php

use App\Libraries\Konteks;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes pustaka Konteks — batas TPS per rezim (Pemilu 300, Pilkada 600,
 * PDPB tidak membentuk TPS). Nilai konteks disuntik lewat memo statis
 * agar tes tidak bergantung basis data.
 *
 * @internal
 */
final class KonteksTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        $this->aturJenis(null);
        parent::tearDown();
    }

    private function aturJenis(?string $jenis): void
    {
        $ref = new ReflectionProperty(Konteks::class, 'memo');
        $ref->setAccessible(true);
        $ref->setValue(null, $jenis === null ? [] : ['konteks_jenis' => $jenis, 'konteks_nama' => '']);
    }

    public function testBawaanPdpbTanpaBatasTps(): void
    {
        $this->aturJenis('pdpb');
        $this->assertSame('pdpb', Konteks::jenis());
        $this->assertNull(Konteks::batasTps());
    }

    public function testPemiluBatas300(): void
    {
        $this->aturJenis('pemilu');
        $this->assertSame(300, Konteks::batasTps());
    }

    public function testPilkadaBatas600(): void
    {
        $this->aturJenis('pilkada');
        $this->assertSame(600, Konteks::batasTps());
    }

    public function testJenisTakDikenalJatuhKePdpb(): void
    {
        $this->aturJenis('ngawur');
        $this->assertSame('pdpb', Konteks::jenis());
        $this->assertNull(Konteks::batasTps());
    }

    public function testJenisTerdaftar(): void
    {
        foreach (['pemilu', 'pilkada', 'pdpb', 'pemilu_nasional', 'pemilu_daerah'] as $jenis) {
            $this->assertArrayHasKey($jenis, Konteks::JENIS);
        }
    }

    public function testPemiluNasionalBatas300(): void
    {
        $this->aturJenis('pemilu_nasional');
        $this->assertSame(300, Konteks::batasTps());
    }

    public function testPemiluDaerahBatas600(): void
    {
        $this->aturJenis('pemilu_daerah');
        $this->assertSame(600, Konteks::batasTps());
    }
}
