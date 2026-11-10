<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Impor cepat data coklit dari konsol (10 Nov 2026).
 *
 * Untuk memasukkan data SATU KABUPATEN sekaligus ke basis data lokal —
 * pekerjaan yang berat lewat formulir web per kecamatan. Berkas boleh
 * satu berkas besar atau folder berisi banyak berkas (satu per
 * kecamatan); kecamatan setiap baris diambil dari kolom KEC berkas.
 *
 * Aturan baris sama persis dengan impor web: baris judul = nama kolom
 * mhc; kolom wajib NIK, NAMA, DESA, TPS, KET; NIK harus 16 angka.
 *
 * Contoh:
 *   php spark impor:massal D:\data-coklit
 *   php spark impor:massal berkas.csv --mode=ganti
 *   php spark impor:massal berkas-tanpa-kec.csv --kecamatan="RANTO BAEK"
 *
 * Mode tambah: baris yang persis sama sudah ada dilewati (aman
 * dijalankan ulang). Kunci pengecekannya gabungan UID+KET+DESA+TPS,
 * BUKAN UID saja — pada data coklit, satu UID dipakai bersama oleh
 * baris tersaring dan baris baru orang yang sama (pada data contoh:
 * 14.284 baris tetapi hanya 11.751 UID unik), jadi menyaring berdasar
 * UID saja akan membuang ±18% baris yang sah.
 * Mode ganti: data lama setiap kecamatan yang muncul di berkas
 * dicadangkan dulu ke writable/backup/ lalu diganti isi berkas.
 */
class ImporMassal extends BaseCommand
{
    protected $group       = 'Pemilih';
    protected $name        = 'impor:massal';
    protected $description = 'Impor cepat data coklit (mhc) dari satu berkas atau folder berisi banyak berkas CSV/XLSX.';
    protected $usage       = 'impor:massal <path> [--mode=tambah|ganti] [--kecamatan=NAMA]';
    protected $arguments   = [
        'path' => 'Berkas CSV/XLSX, atau folder berisi berkas-berkas tersebut.',
    ];
    protected $options = [
        '--mode'      => 'tambah (bawaan) atau ganti.',
        '--kecamatan' => 'Kecamatan tujuan bila berkas tidak mempunyai kolom KEC.',
    ];

    private const KOLOM_MHC = [
        'UID', 'ID', 'NKK', 'NIK', 'NAMA', 'TMPTLHR', 'TGLLHR', 'STATUS',
        'JENIS_KELAMIN', 'ALAMAT', 'RT', 'RW', 'DISABILITAS', 'EKTP', 'KET',
        'SUMBER', 'TPS', 'LAST_UPDATE', 'LAT', 'LONG', 'PROV', 'KAB', 'KEC',
        'DESA', 'TGL_COKLIT',
    ];

    private array $kunciAda  = [];
    private int $idBerikut   = 0;
    private string $mode     = 'tambah';
    private ?string $kecPaksa = null;

    public function run(array $params)
    {
        // Terima juga bentuk --mode=ganti (parser CLI menyerahkannya
        // sebagai kunci "mode=ganti" berval null, bukan opsi bernama).
        foreach ($params as $k => $v) {
            if (is_string($k) && preg_match('/^(mode|kecamatan)=(.+)$/', $k, $cocok)) {
                $params[$cocok[1]] = $cocok[2];
            }
            if (is_string($v) && preg_match('/^--(mode|kecamatan)=(.+)$/', $v, $cocok)) {
                $params[$cocok[1]] = $cocok[2];
            }
        }
        // sebagai segmen utuh, bukan opsi bernama).
        foreach ($params as $v) {
            if (is_string($v) && preg_match('/^--(mode|kecamatan)=(.+)$/', $v, $cocok)) {
                $params[$cocok[1]] = $cocok[2];
            }
        }
        $path = (string) ($params['path'] ?? $params[0] ?? '');
        $this->mode = strtolower((string) ($params['mode'] ?? CLI::getOption('mode') ?? 'tambah'));
        $kec = $params['kecamatan'] ?? CLI::getOption('kecamatan');
        $this->kecPaksa = ($kec === null || trim((string) $kec) === '') ? null : strtoupper(trim((string) $kec));

        if (! in_array($this->mode, ['tambah', 'ganti'], true)) {
            CLI::error('Mode harus "tambah" atau "ganti".');
            return EXIT_ERROR;
        }
        if ($path === '' || (! is_file($path) && ! is_dir($path))) {
            CLI::error('Path tidak ditemukan: ' . $path);
            return EXIT_ERROR;
        }

        $berkas = [];
        if (is_dir($path)) {
            foreach (['csv', 'xlsx', 'xls'] as $ext) {
                foreach (glob(rtrim($path, '/\\') . '/*.' . $ext) ?: [] as $f) {
                    $berkas[] = $f;
                }
            }
            sort($berkas);
        } else {
            $berkas = [$path];
        }
        if ($berkas === []) {
            CLI::error('Tidak ada berkas CSV/XLSX di: ' . $path);
            return EXIT_ERROR;
        }

        $db = db_connect();
        $this->idBerikut = (int) ($db->table('mhc')->selectMax('ID')->get()->getRowArray()['ID'] ?? 0);
        if ($this->mode === 'tambah') {
            foreach ($db->table('mhc')->select('UID, KET, DESA, TPS')->get()->getResultArray() as $r) {
                $this->kunciAda[self::kunciBaris($r['UID'], $r['KET'], $r['DESA'], $r['TPS'])] = true;
            }
        }

        CLI::write('Impor massal mode ' . strtoupper($this->mode) . ' — ' . count($berkas) . ' berkas.', 'yellow');
        $kecTersentuh = [];
        $totalMasuk = 0;
        $totalGagal = 0;
        foreach ($berkas as $f) {
            $hasil = $this->prosesBerkas($f);
            if ($hasil === null) {
                continue;
            }
            $totalMasuk += $hasil['masuk'];
            $totalGagal += $hasil['gagal'];
            foreach ($hasil['kecamatan'] as $k) {
                $kecTersentuh[$k] = true;
            }
        }

        CLI::write('');
        CLI::write('SELESAI. Total masuk: ' . number_format($totalMasuk) . ' baris; gagal/dilewati: ' . number_format($totalGagal) . ' baris.', 'green');
        if ($kecTersentuh !== []) {
            CLI::write('');
            CLI::write('Rekonsiliasi basis data (kecamatan yang tersentuh):', 'yellow');
            $daftar = array_keys($kecTersentuh);
            sort($daftar);
            $rows = $db->table('mhc')
                ->select("KEC, COUNT(*) AS total,
                    SUM(KET IN ('0','U','B')) AS ms,
                    SUM(KET = 'B') AS baru,
                    SUM(KET IN ('1','2','3','4','5','6','7','8')) AS tersaring,
                    SUM(KET = '8') AS pindah")
                ->whereIn('KEC', $daftar)
                ->groupBy('KEC')
                ->get()->getResultArray();
            foreach ($rows as $r) {
                CLI::write(sprintf(
                    '- %s: total %s | MS %s | baru %s | tersaring %s | pindah TPS %s',
                    $r['KEC'],
                    number_format((int) $r['total']),
                    number_format((int) $r['ms']),
                    number_format((int) $r['baru']),
                    number_format((int) $r['tersaring']),
                    number_format((int) $r['pindah'])
                ));
            }
        }

        return EXIT_SUCCESS;
    }

    /** @return array{masuk:int, gagal:int, kecamatan:list<string>}|null */
    private function prosesBerkas(string $pathBerkas): ?array
    {
        $nama = basename($pathBerkas);
        $awal = microtime(true);
        CLI::write('');
        CLI::write('Memproses ' . $nama . ' ...');

        $baris = $this->bacaBerkas($pathBerkas);
        if ($baris === null) {
            CLI::error('  Dilewati: berkas tidak bisa dibaca / kosong.');
            return null;
        }

        $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $baris[0]);
        $peta  = [];
        foreach (self::KOLOM_MHC as $kolom) {
            $idx = array_search($kolom, $judul, true);
            if ($idx !== false) {
                $peta[$kolom] = $idx;
            }
        }
        foreach (['NIK', 'NAMA', 'DESA', 'TPS', 'KET'] as $wajib) {
            if (! isset($peta[$wajib])) {
                CLI::error('  Dilewati: kolom wajib "' . $wajib . '" tidak ada di baris judul.');
                return null;
            }
        }

        // Kecamatan yang muncul di berkas (untuk mode ganti & laporan)
        $ambilKec = function (array $mentah) use ($peta) {
            if (! isset($peta['KEC'])) {
                return $this->kecPaksa;
            }
            $v = trim((string) ($mentah[$peta['KEC']] ?? ''));
            return $v === '' ? $this->kecPaksa : strtoupper($v);
        };
        $kecSet = [];
        for ($i = 1; $i < count($baris); $i++) {
            $k = $ambilKec($baris[$i]);
            if ($k !== null) {
                $kecSet[$k] = true;
            }
        }
        if ($kecSet === []) {
            CLI::error('  Dilewati: tidak ada kecamatan yang bisa ditentukan (kolom KEC kosong dan --kecamatan tidak diberikan).');
            return null;
        }

        $db = db_connect();

        // Mode ganti: cadangkan + kosongkan per kecamatan dulu
        $cadangan = [];
        if ($this->mode === 'ganti') {
            foreach (array_keys($kecSet) as $k) {
                $c = $this->cadangkanMhc($k);
                if ($c === null) {
                    CLI::error('  DIBATALKAN: gagal membuat cadangan data lama kecamatan ' . $k . '.');
                    return null;
                }
                $cadangan[] = $c;
            }
        }

        $siap  = [];
        $gagal = [];
        $db->transStart();
        try {
            if ($this->mode === 'ganti') {
                $db->table('mhc')->whereIn('KEC', array_keys($kecSet))->delete();
            }
            for ($i = 1; $i < count($baris); $i++) {
                $mentah = $baris[$i];
                $ambil  = static function (string $kolom) use ($peta, $mentah) {
                    if (! isset($peta[$kolom])) {
                        return null;
                    }
                    $v = $mentah[$peta[$kolom]] ?? null;
                    if (is_float($v) || is_int($v)) {
                        $v = number_format((float) $v, 0, '', '');
                    }
                    $v = trim((string) $v);
                    return $v === '' ? null : $v;
                };

                $nik = (string) $ambil('NIK');
                if (! preg_match('/^\d{16}$/', $nik)) {
                    $gagal[] = 'Baris ' . ($i + 1) . ': NIK tidak sah — dilewati.';
                    continue;
                }
                $uid = $ambil('UID');
                if ($uid === null) {
                    $uid = strtoupper(bin2hex(random_bytes(8)));
                }

                $row = [];
                foreach (self::KOLOM_MHC as $kolom) {
                    $row[$kolom] = $ambil($kolom);
                }
                $row['UID'] = $uid;
                if ($this->mode === 'tambah') {
                    $kunci = self::kunciBaris($row['UID'], $row['KET'], $row['DESA'], $row['TPS']);
                    if (isset($this->kunciAda[$kunci])) {
                        $gagal[] = 'Baris ' . ($i + 1) . ': baris yang sama (UID+KET+Desa+TPS) sudah ada — dilewati.';
                        continue;
                    }
                    $this->kunciAda[$kunci] = true;
                }
                $idFile = $ambil('ID');
                $row['ID'] = ($this->mode === 'tambah' || $idFile === null) ? ++$this->idBerikut : (int) $idFile;
                if ($this->mode === 'ganti' && $idFile !== null) {
                    $this->idBerikut = max($this->idBerikut, (int) $idFile);
                }
                $row['TGL_COKLIT'] = $this->normalisasiTanggal($row['TGL_COKLIT']);
                $row['KEC'] = $ambilKec($mentah) ?? $row['KEC'];

                $siap[] = $row;
                if (count($siap) >= 500) {
                    $db->table('mhc')->insertBatch($siap);
                    $siap = [];
                }
            }
            if ($siap !== []) {
                $db->table('mhc')->insertBatch($siap);
            }
            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('  GAGAL total (transaksi dibatalkan): ' . $e->getMessage());
            return null;
        }
        if ($db->transStatus() === false) {
            CLI::error('  GAGAL: transaksi basis data tidak berhasil.');
            return null;
        }

        $masuk = (count($baris) - 1) - count($gagal);
        $db->table('impor_log')->insert([
            'jenis'             => 'mhc',
            'mode'              => $this->mode,
            'nama_berkas'       => $nama,
            'jumlah_baris'      => $masuk,
            'jumlah_gagal'      => count($gagal),
            'berkas_cadangan'   => $cadangan === [] ? null : implode(', ', $cadangan),
            'pengguna_id'       => null,
            'pengguna_username' => 'konsol',
            'created_at'        => date('Y-m-d H:i:s'),
        ]);

        CLI::write(sprintf(
            '  Masuk %s baris; gagal/dilewati %s; kecamatan: %s; %.1f detik.',
            number_format($masuk),
            number_format(count($gagal)),
            implode(', ', array_keys($kecSet)),
            microtime(true) - $awal
        ), 'green');
        foreach (array_slice($gagal, 0, 5) as $g) {
            CLI::write('  - ' . $g);
        }
        if (count($gagal) > 5) {
            CLI::write('  - ... dan ' . (count($gagal) - 5) . ' baris gagal lainnya.');
        }

        return ['masuk' => $masuk, 'gagal' => count($gagal), 'kecamatan' => array_keys($kecSet)];
    }

    /** Kunci identitas satu baris coklit untuk mode tambah. */
    private static function kunciBaris($uid, $ket, $desa, $tps): string
    {
        return implode('|', [
            strtoupper(trim((string) $uid)),
            strtoupper(trim((string) $ket)),
            strtoupper(trim((string) $desa)),
            trim((string) $tps),
        ]);
    }

    /** Baca CSV (mengalir, hemat memori) atau XLSX/XLS (PhpSpreadsheet). */
    private function bacaBerkas(string $pathBerkas): ?array
    {
        $ext = strtolower(pathinfo($pathBerkas, PATHINFO_EXTENSION));
        try {
            if ($ext === 'csv') {
                $fh = fopen($pathBerkas, 'r');
                if ($fh === false) {
                    return null;
                }
                $kepala = fgets($fh);
                if ($kepala === false) {
                    fclose($fh);
                    return null;
                }
                $pemisah = substr_count($kepala, ';') > substr_count($kepala, ',') ? ';' : ',';
                rewind($fh);
                $baris = [];
                while (($r = fgetcsv($fh, 0, $pemisah)) !== false) {
                    $kosong = true;
                    foreach ($r as $v) {
                        if ($v !== null && trim((string) $v) !== '') {
                            $kosong = false;
                            break;
                        }
                    }
                    if (! $kosong) {
                        $baris[] = $r;
                    }
                }
                fclose($fh);
            } else {
                $reader = IOFactory::createReaderForFile($pathBerkas);
                $reader->setReadDataOnly(true);
                $sheet = $reader->load($pathBerkas)->getActiveSheet();
                $baris = array_values(array_filter(
                    $sheet->toArray(null, true, false, false),
                    static function ($r) {
                        foreach ((array) $r as $v) {
                            if ($v !== null && trim((string) $v) !== '') {
                                return true;
                            }
                        }
                        return false;
                    }
                ));
            }
        } catch (\Throwable) {
            return null;
        }

        return count($baris) >= 2 ? $baris : null;
    }

    private function normalisasiTanggal($nilai): ?string
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

    /** Ekspor mhc satu kecamatan ke CSV cadangan. Null bila gagal. */
    private function cadangkanMhc(string $kec): ?string
    {
        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $kec);
        $nama = 'mhc-' . $slug . '-sebelum-impor-massal-' . date('Ymd-His') . '.csv';
        $path = $folder . '/' . $nama;
        $fh   = fopen($path, 'w');
        if ($fh === false) {
            return null;
        }
        try {
            fputcsv($fh, self::KOLOM_MHC);
            $query = db_connect()->table('mhc')
                ->select(implode(',', self::KOLOM_MHC))
                ->where('KEC', $kec)
                ->get();
            while ($row = $query->getUnbufferedRow('array')) {
                $urut = [];
                foreach (self::KOLOM_MHC as $k) {
                    $urut[] = $row[$k] ?? null;
                }
                fputcsv($fh, $urut);
            }
        } catch (\Throwable) {
            fclose($fh);
            @unlink($path);
            return null;
        }
        fclose($fh);

        return $nama;
    }
}
