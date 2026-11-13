<?php

use App\Libraries\PeriodePdpb;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Tes penamaan periode PDPB triwulanan (PKPU 1/2025: rekap paling
 * sedikit 3 bulan sekali).
 *
 * @internal
 */
final class PeriodePdpbTest extends CIUnitTestCase
{
    public function testNamaBawaanPerTriwulan(): void
    {
        $kasus = [
            '2026-01-15' => 'PDPB Triwulan I 2026',
            '2026-03-31' => 'PDPB Triwulan I 2026',
            '2026-04-01' => 'PDPB Triwulan II 2026',
            '2026-08-20' => 'PDPB Triwulan III 2026',
            '2026-11-13' => 'PDPB Triwulan IV 2026',
            '2027-02-01' => 'PDPB Triwulan I 2027',
        ];
        foreach ($kasus as $tgl => $harap) {
            $this->assertSame($harap, PeriodePdpb::namaBawaan(new DateTimeImmutable($tgl)), $tgl);
        }
    }
}
