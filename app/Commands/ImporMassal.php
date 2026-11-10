<?php

namespace App\Commands;

use App\Libraries\ImporCoklit;
use App\Libraries\Keterangan;
use App\Libraries\PembacaXlsx;
use App\Libraries\WilayahMaster;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Impor cepat data coklit dari konsol.
 *
 * Untuk memasukkan data SATU KABUPATEN sekaligus ke basis data lokal —
 * pekerjaan yang berat lewat formulir web per kecamatan. Berkas boleh
 * satu berkas besar atau folder berisi banyak berkas.
 *
 * Dua format berkas dikenali otomatis dari baris judulnya:
 * - Format aplikasi: judul adalah nama kolom mhc (KEC, DESA, KET, ...).
 * - Format ekspor Sidalih: KEC_ID, KECAMATAN, KEL_ID, KELURAHAN, DPID,
 *   KETERANGAN, ... (dipetakan oleh App\Libraries\ImporCoklit).
 *
 * WILAYAH TERNORMALISASI (Skema Final 2026): kecamatan & kelurahan
 * disimpan di tabel master `kecamatan`/`kelurahan`; baris pemilih
 * hanya menyimpan `kelurahan_id`. Saat impor, wilayah setiap baris
 * diselesaikan ke master oleh App\Libraries\WilayahMaster — berdasar
 * kode resmi Sidalih bila ada, berdasar nama bila tidak. Nama
 * provinsi/kabupaten (--prov/--kab) disimpan sekali di tabel
 * `pengaturan`, bukan lagi per baris.
 *
 * Semua berkas dibaca MENGALIR baris demi baris (CSV lewat fgetcsv,
 * XLSX lewat App\Libraries\PembacaXlsx), jadi berkas ratusan ribu
 * baris tidak membebani memori.
 *
 * Contoh:
 *   php spark impor:massal D:\data-coklit
 *   php spark impor:massal berkas.csv --mode=ganti
 *   php spark impor:massal MADINA.xlsx --prov="SUMATERA UTARA" --kab="MANDAILING NATAL"
 *
 * Mode tambah: baris yang persis sama sudah ada dilewati (aman
 * dijalankan ulang). Kunci pengecekannya gabungan
 * UID+KET+KELURAHAN+TPS, BUKAN UID saja — satu UID dipakai bersama
 * oleh baris tersaring dan baris baru orang yang sama.
 *
 * NIK adalah identitas unik pemilih: untuk baris AKTIF (MS) hanya
 * baris pertama per NIK yang diterima; baris aktif berikutnya dengan
 * NIK yang sama dialihkan (dihitung & dilaporkan sebagai baris
 * teralih) karena satu NIK hanya boleh mempunyai satu baris aktif di
 * seluruh kabupaten — sesuai kolom ter-generate NIK_AKTIF + indeks
 * unik pada basis data. Baris tersaring tidak dibatasi NIK.
 * Mode ganti: data lama setiap kecamatan yang muncul di berkas
 * dicadangkan dulu ke writable/backup/ lalu diganti isi berkas.
 */
class ImporMassal extends BaseCommand
{
    protected $group       = 'Pemilih';
    protected $name        = 'impor:massal';
    protected $description = 'Impor cepat data coklit (mhc) dari berkas/folder CSV/XLSX, format aplikasi atau ekspor Sidalih.';
    protected $usage       = 'impor:massal <path> [--mode=tambah|ganti] [--kecamatan=NAMA] [--format=auto|mhc|sidalih] [--prov=NAMA] [--kab=NAMA]';
    protected $arguments   = [
        'path' => 'Berkas CSV/XLSX, atau folder berisi berkas-berkas tersebut.',
    ];
    protected $options = [
        '--mode'      => 'tambah (bawaan) atau ganti.',
        '--kecamatan' => 'Kecamatan tujuan bila berkas tidak mempunyai kolom kecamatan.',
        '--format'    => 'auto (bawaan), mhc, atau sidalih.',
        '--prov'      => 'Nama provinsi — disimpan ke tabel pengaturan (identitas kabupaten).',
        '--kab'       => 'Nama kabupaten — disimpan ke tabel pengaturan (identitas kabupaten).',
    ];

    /** @var array<string, true> */
    private array $kunciAda = [];

    /** @var array<string, true> NIK yang sudah mempunyai baris aktif. */
    private array $nikAktifAda = [];
    private string $mode = 'tambah';
    private ?string $kecPaksa = null;
    private string $formatPaksa = 'auto';
    private ?string $provIsi = null;
    private ?string $kabIsi = null;

    public function run(array $params)
    {
        // Terima juga bentuk --opsi=nilai (parser CLI menyerahkannya
        // sebagai kunci "opsi=nilai" berval null, bukan opsi bernama).
        foreach ($params as $k => $v) {
            if (is_string($k) && preg_match('/^(mode|kecamatan|format|prov|kab)=(.+)$/', $k, $cocok)) {
                $params[$cocok[1]] = $cocok[2];
            }
            if (is_string($v) && preg_match('/^--(mode|kecamatan|format|prov|kab)=(.+)$/', $v, $cocok)) {
                $params[$cocok[1]] = $cocok[2];
            }
        }
        $ambil = static function (string $nama) use ($params) {
            $v = $params[$nama] ?? CLI::getOption($nama);
            return ($v === null || trim((string) $v) === '') ? null : trim((string) $v);
        };

        $path              = (string) ($params['path'] ?? $params[0] ?? '');
        $this->mode        = strtolower($ambil('mode') ?? 'tambah');
        $this->kecPaksa    = ($k = $ambil('kecamatan')) === null ? null : strtoupper($k);
        $this->formatPaksa = strtolower($ambil('format') ?? 'auto');
        $this->provIsi     = ($p = $ambil('prov')) === null ? null : strtoupper($p);
        $this->kabIsi      = ($p = $ambil('kab')) === null ? null : strtoupper($p);

        if (! in_array($this->mode, ['tambah', 'ganti'], true)) {
            CLI::error('Mode harus "tambah" atau "ganti".');
            return EXIT_ERROR;
        }
        if (! in_array($this->formatPaksa, ['auto', 'mhc', 'sidalih'], true)) {
            CLI::error('Format harus "auto", "mhc", atau "sidalih".');
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
        if ($this->mode === 'tambah') {
            $query = $db->table('mhc')->select('UID, KET, kelurahan_id, TPS')->get();
            while ($r = $query->getUnbufferedRow('array')) {
                $this->kunciAda[ImporCoklit::kunciBaris($r['UID'], $r['KET'], $r['kelurahan_id'], $r['TPS'])] = true;
            }
        }

        // Identitas kabupaten (sekali, di tabel pengaturan).
        foreach (['provinsi' => $this->provIsi, 'kabupaten' => $this->kabIsi] as $kunci => $nilai) {
            if ($nilai !== null) {
                $db->table('pengaturan')->replace(['kunci' => $kunci, 'nilai' => $nilai]);
            }
        }

        CLI::write('Impor massal mode ' . strtoupper($this->mode) . ' — ' . count($berkas) . ' berkas.', 'yellow');
        $kecTersentuh = [];
        $totalMasuk = 0;
        $totalGagal = 0;
        $totalTeralih = 0;
        foreach ($berkas as $f) {
            $hasil = $this->prosesBerkas($f);
            if ($hasil === null) {
                continue;
            }
            $totalMasuk += $hasil['masuk'];
            $totalGagal += $hasil['gagal'];
            $totalTeralih += $hasil['teralih'] ?? 0;
            foreach ($hasil['kecamatan'] as $k) {
                $kecTersentuh[$k] = true;
            }
        }

        CLI::write('');
        CLI::write('SELESAI. Total masuk: ' . number_format($totalMasuk) . ' baris; gagal/dilewati: ' . number_format($totalGagal) . ' baris.', 'green');
        if ($totalTeralih > 0) {
            CLI::write('  ' . number_format($totalTeralih) . ' baris di antaranya TERALIH karena NIK-nya sudah mempunyai baris aktif (kegandaan antar wilayah — baris pertama yang dipertahankan).', 'yellow');
        }
        if ($kecTersentuh !== []) {
            CLI::write('');
            CLI::write('Rekonsiliasi basis data (kecamatan yang tersentuh):', 'yellow');
            $daftar = array_keys($kecTersentuh);
            sort($daftar);
            $rows = $db->table('mhc')
                ->select('kecamatan.nama AS KEC, COUNT(*) AS total,
                    SUM(mhc.KET IN (' . Keterangan::sqlMs() . ')) AS ms,
                    SUM(mhc.KET = \'B\') AS baru,
                    SUM(mhc.KET IN (' . Keterangan::sqlTms() . ')) AS tersaring,
                    SUM(mhc.KET = \'8\') AS pindah', false)
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->whereIn('kecamatan.nama', $daftar)
                ->groupBy('kecamatan.nama')
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

    /** @return array{masuk: int, gagal: int, teralih: int, kecamatan: list<string>}|null */
    private function prosesBerkas(string $pathBerkas): ?array
    {
        $nama = basename($pathBerkas);
        $awal = microtime(true);
        CLI::write('');
        CLI::write('Memproses ' . $nama . ' ...');

        $db = db_connect();
        $master = new WilayahMaster($db);

        // -- Lintasan 1: judul, format, wilayah yang muncul -------------
        $judul = null;
        $format = null;
        $peta = [];
        $petaW = [];
        /** @var array<string, int> tuple wilayah => kelurahan_id */
        $petaKelurahan = [];
        /** @var array<int, string> kecamatan_id => nama */
        $kecSet = [];
        foreach ($this->sumberBaris($pathBerkas) as $mentah) {
            if ($judul === null) {
                if ($this->barisKosong($mentah)) {
                    continue;
                }
                $judul = array_map(static fn ($c) => strtoupper(trim((string) $c)), $mentah);
                $format = $this->formatPaksa !== 'auto' ? $this->formatPaksa : ImporCoklit::deteksiFormat($judul);
                if ($format === null) {
                    CLI::error('  Dilewati: format berkas tidak dikenal (judul bukan format aplikasi maupun Sidalih).');
                    return null;
                }
                $peta = ImporCoklit::petaKolom($judul, $format);
                $petaW = ImporCoklit::petaWilayah($judul);
                foreach (ImporCoklit::KOLOM_WAJIB as $wajib) {
                    if (! isset($peta[$wajib])) {
                        CLI::error('  Dilewati: kolom wajib "' . $wajib . '" tidak ada di berkas.');
                        return null;
                    }
                }
                if (! isset($petaW['kel_nama']) && $this->kecPaksa === null) {
                    CLI::error('  Dilewati: berkas tidak mempunyai kolom desa/kelurahan.');
                    return null;
                }
                continue;
            }
            if ($this->barisKosong($mentah)) {
                continue;
            }
            $kelId = $this->selesaikanKelurahan($mentah, $petaW, $master, $petaKelurahan);
            if ($kelId !== null) {
                $kel = $master->kelurahan($kelId);
                if ($kel !== null) {
                    $kecSet[(int) $kel['kecamatan_id']] = $master->namaKecamatanDariKelurahan($kelId) ?? '';
                }
            }
        }
        if ($judul === null) {
            CLI::error('  Dilewati: berkas kosong / tidak bisa dibaca.');
            return null;
        }
        if ($kecSet === []) {
            CLI::error('  Dilewati: tidak ada wilayah yang bisa ditentukan (kolom wilayah kosong dan --kecamatan tidak diberikan).');
            return null;
        }
        CLI::write('  Format: ' . strtoupper((string) $format) . '; kecamatan: ' . implode(', ', array_values($kecSet)));

        // Mode ganti: cadangkan data lama per kecamatan dulu.
        $cadangan = [];
        if ($this->mode === 'ganti') {
            foreach ($kecSet as $kecId => $kecNama) {
                $c = $this->cadangkanMhc((int) $kecId, (string) $kecNama);
                if ($c === null) {
                    CLI::error('  DIBATALKAN: gagal membuat cadangan data lama kecamatan ' . $kecNama . '.');
                    return null;
                }
                $cadangan[] = $c;
            }
        }

        // -- Lintasan 2: impor ------------------------------------------
        $masuk   = 0;
        $teralih = 0;
        $gagal   = [];
        $siap    = [];
        $db->transStart();
        try {
            if ($this->mode === 'ganti') {
                $kelurahanIds = [];
                foreach (array_keys($kecSet) as $kecId) {
                    foreach ($master->kelurahanDi((int) $kecId) as $kel) {
                        $kelurahanIds[] = (int) $kel['id'];
                    }
                }
                if ($kelurahanIds !== []) {
                    $db->table('mhc')->whereIn('kelurahan_id', $kelurahanIds)->delete();
                }
            }
            // NIK yang masih mempunyai baris aktif (di luar yang baru
            // diganti) menjadi penanda pengalihan baris aktif ganda.
            $this->nikAktifAda = [];
            $qAktif = $db->table('mhc')->select('NIK')
                ->whereIn('KET', Keterangan::MS)->get();
            while ($rA = $qAktif->getUnbufferedRow('array')) {
                $this->nikAktifAda[(string) $rA['NIK']] = true;
            }
            $sudahJudul = false;
            $nomor = 0;
            foreach ($this->sumberBaris($pathBerkas) as $mentah) {
                $nomor++;
                if (! $sudahJudul) {
                    // Baris judul = baris tidak kosong pertama.
                    if ($this->barisKosong($mentah)) {
                        continue;
                    }
                    $sudahJudul = true;
                    continue;
                }
                if ($this->barisKosong($mentah)) {
                    continue;
                }
                $ambil = static function (string $kolom) use ($peta, $mentah) {
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
                    $gagal[] = 'Baris ' . $nomor . ': NIK tidak sah — dilewati.';
                    continue;
                }
                if ($ambil('NAMA') === null || $ambil('TPS') === null) {
                    $gagal[] = 'Baris ' . $nomor . ': NAMA/TPS kosong — dilewati.';
                    continue;
                }
                $ket = (string) $ambil('KET');
                if (! array_key_exists($ket, Keterangan::daftar())) {
                    $gagal[] = 'Baris ' . $nomor . ': kode keterangan "' . $ket . '" tidak dikenal — dilewati.';
                    continue;
                }
                $kelId = $this->selesaikanKelurahan($mentah, $petaW, $master, $petaKelurahan);
                if ($kelId === null) {
                    $gagal[] = 'Baris ' . $nomor . ': wilayah (kecamatan/desa) tidak bisa ditentukan — dilewati.';
                    continue;
                }

                $row = [];
                foreach (ImporCoklit::KOLOM_ISI as $kolom) {
                    $row[$kolom] = $ambil($kolom);
                }
                $row['UID'] ??= strtoupper(bin2hex(random_bytes(8)));
                $row['kelurahan_id'] = $kelId;
                $row['TGL_COKLIT'] = ImporCoklit::normalisasiTanggal($row['TGL_COKLIT']);
                if ($format === 'sidalih') {
                    $row['LAST_UPDATE'] = ImporCoklit::normalisasiTanggal($row['LAST_UPDATE']);
                }
                $row['TGL_LAHIR'] = ImporCoklit::tanggalLahirIso($row['TGLLHR']);

                if (Keterangan::isMs($ket)) {
                    if (isset($this->nikAktifAda[$nik])) {
                        $gagal[] = 'Baris ' . $nomor . ': NIK sudah mempunyai baris aktif — dialihkan (satu NIK satu baris aktif).';
                        $teralih++;
                        continue;
                    }
                    $this->nikAktifAda[$nik] = true;
                }
                if ($this->mode === 'tambah') {
                    $kunci = ImporCoklit::kunciBaris($row['UID'], $row['KET'], $row['kelurahan_id'], $row['TPS']);
                    if (isset($this->kunciAda[$kunci])) {
                        $gagal[] = 'Baris ' . $nomor . ': baris yang sama (UID+KET+Kelurahan+TPS) sudah ada — dilewati.';
                        continue;
                    }
                    $this->kunciAda[$kunci] = true;
                }

                $siap[] = $row;
                $masuk++;
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
            '  Masuk %s baris; gagal/dilewati %s (termasuk %s teralih NIK aktif ganda); %.1f detik.',
            number_format($masuk),
            number_format(count($gagal)),
            number_format($teralih),
            microtime(true) - $awal
        ), 'green');
        foreach (array_slice($gagal, 0, 5) as $g) {
            CLI::write('  - ' . $g);
        }
        if (count($gagal) > 5) {
            CLI::write('  - ... dan ' . (count($gagal) - 5) . ' baris gagal lainnya.');
        }

        return ['masuk' => $masuk, 'gagal' => count($gagal), 'teralih' => $teralih, 'kecamatan' => array_values($kecSet)];
    }

    /**
     * Selesaikan wilayah satu baris berkas menjadi kelurahan_id lewat
     * master (kode Sidalih didahulukan, nama sebagai cadangan;
     * kecamatan paksaan --kecamatan dipakai bila berkas tak memuatnya).
     *
     * @param array<string, int> $petaKelurahan cache tuple => id (diisi)
     */
    private function selesaikanKelurahan(array $mentah, array $petaW, WilayahMaster $master, array &$petaKelurahan): ?int
    {
        $ambilW = static function (string $kunci) use ($petaW, $mentah) {
            if (! isset($petaW[$kunci])) {
                return null;
            }
            $v = trim((string) ($mentah[$petaW[$kunci]] ?? ''));
            return $v === '' ? null : $v;
        };
        $kecKode = $ambilW('kec_kode');
        $kecNama = $ambilW('kec_nama') ?? $this->kecPaksa;
        $kelKode = $ambilW('kel_kode');
        $kelNama = $ambilW('kel_nama');
        if ($kelNama === null || $kecNama === null) {
            return null;
        }
        $tuple = implode('|', [$kecKode ?? '', WilayahMaster::rapi($kecNama), $kelKode ?? '', WilayahMaster::rapi($kelNama)]);
        if (! isset($petaKelurahan[$tuple])) {
            $kecId = $master->kecamatanId($kecKode, $kecNama);
            $petaKelurahan[$tuple] = $master->kelurahanId($kecId, $kelKode, $kelNama);
        }

        return $petaKelurahan[$tuple];
    }

    private function barisKosong(array $mentah): bool
    {
        foreach ($mentah as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * Sumber baris mengalir: CSV lewat fgetcsv, XLSX lewat PembacaXlsx.
     * XLS lama (.xls) ditolak dengan pesan jelas (konversi dulu).
     *
     * @return \Generator<int, array>
     */
    private function sumberBaris(string $pathBerkas): \Generator
    {
        $ext = strtolower(pathinfo($pathBerkas, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $fh = fopen($pathBerkas, 'r');
            if ($fh === false) {
                return;
            }
            $kepala = fgets($fh);
            if ($kepala === false) {
                fclose($fh);
                return;
            }
            $pemisah = substr_count($kepala, ';') > substr_count($kepala, ',') ? ';' : ',';
            rewind($fh);
            while (($r = fgetcsv($fh, 0, $pemisah)) !== false) {
                yield $r;
            }
            fclose($fh);
            return;
        }
        if ($ext === 'xlsx') {
            $pembaca = new PembacaXlsx($pathBerkas);
            yield from $pembaca->baris();
            return;
        }
        CLI::error('  Dilewati: format .' . $ext . ' tidak didukung perintah ini (pakai CSV/XLSX).');
    }

    /** Ekspor mhc satu kecamatan ke CSV cadangan. Null bila gagal. */
    private function cadangkanMhc(int $kecId, string $kecNama): ?string
    {
        $folder = WRITEPATH . 'backup';
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $kecNama);
        $nama = 'mhc-' . $slug . '-sebelum-impor-massal-' . date('Ymd-His') . '.csv';
        $path = $folder . '/' . $nama;
        $fh   = fopen($path, 'w');
        if ($fh === false) {
            return null;
        }
        try {
            fputcsv($fh, ImporCoklit::KOLOM_EKSPOR);
            $query = db_connect()->table('mhc')
                ->select('kecamatan.kode AS KEC_ID, kecamatan.nama AS KECAMATAN,
                    kelurahan.kode AS KEL_ID, kelurahan.nama AS KELURAHAN,
                    mhc.TPS, mhc.UID, mhc.NKK, mhc.NIK, mhc.NAMA, mhc.TMPTLHR,
                    mhc.TGLLHR, mhc.STATUS, mhc.JENIS_KELAMIN, mhc.ALAMAT, mhc.RT,
                    mhc.RW, mhc.DISABILITAS, mhc.EKTP, mhc.KET, mhc.SUMBER,
                    mhc.LAST_UPDATE, mhc.TGL_COKLIT', false)
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                ->where('kecamatan.id', $kecId)
                ->get();
            while ($row = $query->getUnbufferedRow('array')) {
                $urut = [];
                foreach (ImporCoklit::KOLOM_EKSPOR as $k) {
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
