<?php

use App\Libraries\Pengaturan;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes keputusan versi halaman masuk dari nilai pengaturan.
 *
 * @internal
 */
final class PengaturanTest extends CIUnitTestCase
{
    public function testVersiDuaHanyaBilaDiaturDua(): void
    {
        $this->assertSame('2', Pengaturan::versiLoginDari('2'));
    }

    public function testNilaiLainJatuhKeVersiSatu(): void
    {
        $this->assertSame('1', Pengaturan::versiLoginDari('1'));
        $this->assertSame('1', Pengaturan::versiLoginDari('3'));
        $this->assertSame('1', Pengaturan::versiLoginDari(''));
        $this->assertSame('1', Pengaturan::versiLoginDari(null));
    }
}
