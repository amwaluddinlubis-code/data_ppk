<?php

namespace App\Libraries;

/**
 * Aturan bersama impor data coklit — dipakai impor web & impor konsol.
 *
 * Bangun ulang 2026: sebelumnya aturan ini ditulis dua kali (controller
 * Impor dan perintah ImporMassal) dan bisa menyimpang diam-diam —
 * contohnya penyaringan duplikat berdasar UID saja di impor web.
 * Sekarang:
 *
 * - Pemetaan kolom untuk dua format berkas: format aplikasi (judul =
 *   nama kolom mhc) dan format ekspor Sidalih (KELURAHAN, DPID,
 *   KETERANGAN, ...).
 * - Wilayah TIDAK lagi kolom mhc: berkas membawa KEC_ID/KECAMATAN/
 *   KEL_ID/KELURAHAN (Sidalih) atau KEC/DESA (aplikasi), yang dipetakan
 *   terpisah oleh petaWilayah() lalu diselesaikan ke tabel master oleh
 *   App\Libraries\WilayahMaster menjadi `kelurahan_id`.
 * - Kunci identitas baris untuk mode tambah: gabungan
 *   UID+KET+KELURAHAN+TPS. Satu UID pada data coklit dipakai bersama
 *   oleh baris tersaring dan baris baru orang yang sama, jadi UID saja
 *   BUKAN identitas baris.
 * - Normalisasi tanggal berkas Excel/teks.
 */
final class ImporCoklit
{
    /** @var list<string> Semua kolom tabel mhc, urut skema final. */
    public const KOLOM_MHC = [
        'UID', 'ID', 'NKK', 'NIK', 'NAMA', 'TMPTLHR', 'TGLLHR', 'TGL_LAHIR', 'STATUS',
        'JENIS_KELAMIN', 'ALAMAT', 'RT', 'RW', 'DISABILITAS', 'EKTP', 'KET',
        'SUMBER', 'kelurahan_id', 'TPS', 'LAST_UPDATE', 'TGL_COKLIT',
    ];

    /** @var list<string> Kolom mhc yang diisi langsung dari kolom berkas. */
    public const KOLOM_ISI = [
        'UID', 'NKK', 'NIK', 'NAMA', 'TMPTLHR', 'TGLLHR', 'STATUS',
        'JENIS_KELAMIN', 'ALAMAT', 'RT', 'RW', 'DISABILITAS', 'EKTP', 'KET',
        'SUMBER', 'TPS', 'LAST_UPDATE', 'TGL_COKLIT',
    ];

    /** @var list<string> Kolom data yang wajib ada agar berkas bisa diimpor. */
    public const KOLOM_WAJIB = ['NIK', 'NAMA', 'KET', 'TPS'];

    /**
     * @var list<string> Judul kolom berkas cadangan/ekspor mhc: wilayah
     * ditulis sebagai kode + nama master (KEC_ID dst.) agar berkas
     * cadangan dapat diimpor kembali tanpa kehilangan wilayah.
     */
    public const KOLOM_EKSPOR = [
        'KEC_ID', 'KECAMATAN', 'KEL_ID', 'KELURAHAN', 'TPS', 'UID', 'NKK', 'NIK',
        'NAMA', 'TMPTLHR', 'TGLLHR', 'STATUS', 'JENIS_KELAMIN', 'ALAMAT', 'RT',
        'RW', 'DISABILITAS', 'EKTP', 'KET', 'SUMBER', 'LAST_UPDATE', 'TGL_COKLIT',
    ];

    /** @var array<string, string> Judul kolom Sidalih => kolom mhc. */
    private const PETA_SIDALIH = [
        'DPID'          => 'UID',
        'KETERANGAN'    => 'KET',
        'TEMPAT LAHIR'  => 'TMPTLHR',
        'TANGGAL LAHIR' => 'TGLLHR',
        'STS KAWIN'     => 'STATUS',
        'KELAMIN'       => 'JENIS_KELAMIN',
        'UPDATED_AT'    => 'LAST_UPDATE',
    ];

    /** @var list<string> Kolom Sidalih yang sama nama dengan kolom mhc. */
    private const SIDALIH_SAMA = [
        'NKK', 'NIK', 'NAMA', 'ALAMAT', 'RT', 'RW', 'DISABILITAS', 'EKTP',
        'SUMBER', 'TPS',
    ];

    /**
     * Kenali format berkas dari baris judulnya (huruf besar semua).
     * Mengembalikan 'sidalih', 'mhc', atau null bila tak dikenal.
     *
     * @param list<string> $judul
     */
    public static function deteksiFormat(array $judul): ?string
    {
        if (in_array('KELURAHAN', $judul, true)
            && in_array('DPID', $judul, true) && in_array('KETERANGAN', $judul, true)) {
            return 'sidalih';
        }
        if (in_array('KET', $judul, true)
            && (in_array('DESA', $judul, true) || in_array('KELURAHAN', $judul, true))) {
            return 'mhc';
        }

        return null;
    }

    /**
     * Bangun peta kolom mhc => indeks pada baris berkas, untuk format
     * yang diberikan. Kolom yang tidak ada di berkas tidak masuk peta.
     * Wilayah tidak termasuk — lihat petaWilayah().
     *
     * @param list<string> $judul
     * @return array<string, int>
     */
    public static function petaKolom(array $judul, string $format): array
    {
        $peta = [];
        if ($format === 'sidalih') {
            foreach (self::PETA_SIDALIH as $judulSidalih => $kolom) {
                $idx = array_search($judulSidalih, $judul, true);
                if ($idx !== false) {
                    $peta[$kolom] = $idx;
                }
            }
            foreach (self::SIDALIH_SAMA as $kolom) {
                $idx = array_search($kolom, $judul, true);
                if ($idx !== false) {
                    $peta[$kolom] = $idx;
                }
            }

            return $peta;
        }

        foreach (self::KOLOM_ISI as $kolom) {
            $idx = array_search($kolom, $judul, true);
            if ($idx !== false) {
                $peta[$kolom] = $idx;
            }
        }

        return $peta;
    }

    /**
     * Peta kolom wilayah berkas => indeks baris. Kunci yang dikembalikan:
     * kec_kode (KEC_ID), kec_nama (KECAMATAN atau KEC), kel_kode
     * (KEL_ID), kel_nama (KELURAHAN atau DESA) — hanya yang ada.
     *
     * @param list<string> $judul
     * @return array<string, int>
     */
    public static function petaWilayah(array $judul): array
    {
        $calon = [
            'kec_kode' => ['KEC_ID'],
            'kec_nama' => ['KECAMATAN', 'KEC'],
            'kel_kode' => ['KEL_ID'],
            'kel_nama' => ['KELURAHAN', 'DESA'],
        ];
        $peta = [];
        foreach ($calon as $kunci => $namaKolom) {
            foreach ($namaKolom as $nama) {
                $idx = array_search($nama, $judul, true);
                if ($idx !== false) {
                    $peta[$kunci] = $idx;
                    break;
                }
            }
        }

        return $peta;
    }

    /** Kunci identitas satu baris coklit (mode tambah anti-duplikat). */
    public static function kunciBaris($uid, $ket, $kelurahanId, $tps): string
    {
        return implode('|', [
            strtoupper(trim((string) $uid)),
            strtoupper(trim((string) $ket)),
            (string) (int) $kelurahanId,
            trim((string) $tps),
        ]);
    }

    /**
     * Ubah teks tanggal lahir (DD|MM|YYYY, DD-MM-YYYY, DD/MM/YYYY,
     * atau ISO) menjadi tanggal ISO untuk kolom DATE TGL_LAHIR.
     * Penguraian eksplisit per bagian — BUKAN strtotime, yang akan
     * membaca DD-MM-YYYY secara Amerika (bulan-tanggal tertukar).
     * Mengembalikan null bila pola tidak dikenal/tidak sah.
     */
    public static function tanggalLahirIso($nilai): ?string
    {
        if ($nilai === null) {
            return null;
        }
        $s = trim((string) $nilai);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? $s : null;
        }
        if (preg_match('/^(\d{1,2})[|\/-](\d{1,2})[|\/-](\d{4})$/', $s, $m)) {
            $d  = (int) $m[1];
            $bl = (int) $m[2];
            $th = (int) $m[3];

            return checkdate($bl, $d, $th) ? sprintf('%04d-%02d-%02d', $th, $bl, $d) : null;
        }

        return null;
    }

    /** Normalisasi tanggal Excel (nomor seri) / teks menjadi Y-m-d. */
    public static function normalisasiTanggal($nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }
        if (is_numeric($nilai)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        $ts = strtotime((string) $nilai);

        return $ts === false ? null : date('Y-m-d', $ts);
    }
}
