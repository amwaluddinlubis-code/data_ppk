<?php

namespace App\Libraries;

/**
 * Jenis dokumen bukti pendukung perubahan data pemilih
 * (PKPU 1/2025 Pasal 17 ayat (4): perubahan dibuktikan dengan
 * dokumen administrasi kependudukan atau dokumen pendukung lain
 * yang sah). Dipakai formulir coklit dan tercatat di riwayat.
 */
final class DokumenBukti
{
    /** @var list<string> */
    public const DAFTAR = [
        'KTP-el / KK',
        'Akta Kematian',
        'Surat Keterangan Kematian',
        'Surat Pindah / SKPD',
        'Surat Keterangan Domisili',
        'SK Pensiun / Pemberhentian TNI-Polri',
        'Putusan Pengadilan',
        'Surat Keterangan RT/RW',
        'Lainnya',
    ];

    /** Nilai sah dari masukan pengguna; null bila kosong/tidak dikenal. */
    public static function saring(?string $jenis): ?string
    {
        $jenis = trim((string) $jenis);

        return in_array($jenis, self::DAFTAR, true) ? $jenis : null;
    }
}
