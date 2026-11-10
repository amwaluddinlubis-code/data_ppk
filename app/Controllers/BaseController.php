<?php

namespace App\Controllers;

use App\Libraries\Wilayah;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Kelas dasar semua controller.
 *
 * Bangun ulang 2026: logika cakupan wilayah dipindah ke
 * App\Libraries\Wilayah (satu tempat, dipakai juga di luar controller)
 * dan definisi kode keterangan ke App\Libraries\Keterangan. Kelas ini
 * hanya menyimpan keadaan sesi bersama, daftar wilayah, pencatatan
 * akses, dan penyamaran data untuk Pemeriksa.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /** @var array */
    protected $helpers = [];

    /**
     * Nama kecamatan yang sedang AKTIF sebagai saringan wilayah pada
     * sesi ini, diisi di initController. Null berarti tampilan seluruh
     * kabupaten (hanya untuk pengguna tanpa cakupan kecamatan, yaitu
     * Admin Kabupaten).
     */
    protected $kec;

    /** Nama kabupaten dari data coklit (kolom KAB), null bila data kosong. */
    protected $kab;

    /** @var list<string>|null Cache daftar kecamatan pada permintaan ini. */
    private $daftarKecCache = null;

    /** Pengguna yang sedang masuk (dari sesi), atau null bila belum masuk. */
    protected $pengguna;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        try {
            $qKab = db_connect()->table('pengaturan')->select('nilai')
                ->where('kunci', 'kabupaten')->get()->getRow();
            $this->kab = $qKab->nilai ?? null;
        } catch (\Throwable) {
            $this->kab = null;
        }
        $this->kec = Wilayah::kecEfektif();

        $session        = session();
        $this->pengguna = $session->get('pengguna_id') ? [
            'id'           => $session->get('pengguna_id'),
            'nama'         => $session->get('pengguna_nama'),
            'username'     => $session->get('pengguna_username'),
            'peran'        => $session->get('pengguna_peran'),
            'kecamatan'    => $session->get('pengguna_kecamatan'),
            'desa'         => $session->get('pengguna_desa'),
            'kecamatan_id' => $session->get('pengguna_kecamatan_id'),
            'kelurahan_id' => $session->get('pengguna_kelurahan_id'),
        ] : null;
    }

    // -- Cakupan wilayah (delegasi ke App\Libraries\Wilayah) ---------

    protected function kecPengguna(): ?string
    {
        return Wilayah::kecPengguna();
    }

    protected function desaPengguna(): ?string
    {
        return Wilayah::desaPengguna();
    }

    protected function kecEfektif(): ?string
    {
        return Wilayah::kecEfektif();
    }

    protected function saringKec($builder, string $kolom = 'KEC')
    {
        return Wilayah::saringKec($builder, $kolom);
    }

    protected function saringWilayah($builder, string $kolomKec = 'KEC', string $kolomDesa = 'DESA')
    {
        return Wilayah::saring($builder, $kolomKec, $kolomDesa);
    }

    /** Saringan wilayah berdasar ID untuk kueri langsung ke mhc. */
    protected function saringWilayahId($builder, string $kolomKelurahan = 'mhc.kelurahan_id')
    {
        return Wilayah::saringId($builder, $kolomKelurahan);
    }

    // -- Cache analisis (optimasi performance, 11 Nov 2026) ------------
    //
    // Hasil analisis berat (kartu dasbor, Deteksi Anomali, NIK Ganda,
    // Laporan) hanya berubah bila DATA berubah, bukan bila halaman
    // dibuka. Hasilnya disimpan di cache berkas dengan kunci yang
    // memuat VERSI DATA (pengaturan 'versi_data') dan cakupan wilayah
    // pengguna. Setiap mutasi data (impor, CRUD coklit, pulih
    // riwayat) menaikkan versi lewat sentuhVersiData() sehingga cache
    // lama otomatis tidak terpakai lagi.

    private ?int $versiDataMemo = null;

    /** Nomor versi data saat ini (pengaturan 'versi_data', bawaan 0). */
    protected function versiData(): int
    {
        if ($this->versiDataMemo === null) {
            $baris = db_connect()->table('pengaturan')
                ->getWhere(['kunci' => 'versi_data'])->getRow();
            $this->versiDataMemo = $baris ? (int) $baris->nilai : 0;
        }

        return $this->versiDataMemo;
    }

    /** Naikkan versi data — memanggil ini membatalkan semua cache analisis. */
    protected function sentuhVersiData(): void
    {
        db_connect()->query(
            "INSERT INTO pengaturan (kunci, nilai) VALUES ('versi_data', '1')
             ON DUPLICATE KEY UPDATE nilai = CAST(nilai AS UNSIGNED) + 1"
        );
        $this->versiDataMemo = null;
    }

    /**
     * Ambil hasil analisis dari cache; hitung + simpan bila belum ada.
     *
     * @template T
     * @param callable(): T $hitung
     * @return T
     */
    protected function ambilCacheAnalisis(string $nama, callable $hitung, bool $pakaiAcuan = false)
    {
        $cakupan = 'kab';
        $kelId   = \App\Libraries\Wilayah::kelurahanIdPengguna();
        $kecId   = \App\Libraries\Wilayah::kecEfektifId();
        if ($kelId !== null) {
            $cakupan = 'kel' . $kelId;
        } elseif ($kecId !== null) {
            $cakupan = 'kec' . $kecId;
        }

        $kunci = 'analisis_v' . $this->versiData() . '_' . $nama . '_' . $cakupan;
        if ($pakaiAcuan) {
            $baris = db_connect()->table('pengaturan')
                ->getWhere(['kunci' => 'tanggal_pemungutan'])->getRow();
            $kunci .= '_' . ($baris ? (string) $baris->nilai : '2024-02-14');
        }

        $cache = \Config\Services::cache();
        $nilai = $cache->get($kunci);
        if ($nilai === null) {
            $nilai = $hitung();
            $cache->save($kunci, $nilai, 30 * 24 * 3600);
        }

        return $nilai;
    }

    // -- Tabel HTMX (pola bersama sejak konversi HTMX 10 Nov 2026) -----

    /**
     * Nomor TPS yang benar-benar ada pada data sesuai desa/sumber,
     * tercakup wilayah pengguna. Sumber adalah nama halaman daftar
     * ('aktif', 'baru', 'tersaring', 'pindah') atau lainnya untuk
     * tabel induk mhc.
     *
     * @return list<string>
     */
    protected function daftarTpsDari(string $desa, string $sumber): array
    {
        $db = db_connect();

        // Halaman daftar memakai view yang berbeda-beda; TPS harus
        // diambil dari view yang sama agar nomor yang ditawarkan
        // benar-benar menghasilkan baris pada halaman itu.
        $view = [
            'aktif'    => 'pemilih_active',
            'baru'     => 'pemilihbaru2',
            'tersaring' => 'tersaring',
            'pindah'   => 'pindahtps',
        ][$sumber] ?? null;

        if ($view !== null) {
            $builder = $db->table($view)->distinct()->select('TPS');
            $this->saringWilayah($builder);
            if ($desa !== '') {
                $builder->where('DESA', $desa);
            }
            $rows = $builder->orderBy('CAST(TPS AS UNSIGNED)', 'ASC', false)
                ->get()->getResultArray();
        } else {
            $builder = $db->table('mhc')->distinct()->select('mhc.TPS')
                ->join('kelurahan', 'kelurahan.id = mhc.kelurahan_id')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
            $this->saringWilayahId($builder);
            if ($desa !== '') {
                $kel = $db->table('kelurahan')->select('kelurahan.id AS id')
                    ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id')
                    ->where('kelurahan.nama', $desa);
                $kecId = \App\Libraries\Wilayah::kecEfektifId();
                if ($kecId !== null) {
                    $kel->where('kelurahan.kecamatan_id', $kecId);
                }
                $ids = array_map(
                    static fn ($r) => (int) $r['id'],
                    $kel->get()->getResultArray()
                );
                $builder->whereIn('mhc.kelurahan_id', $ids === [] ? [-1] : $ids);
            }
            $rows = $builder->orderBy('CAST(mhc.TPS AS UNSIGNED)', 'ASC', false)
                ->get()->getResultArray();
        }

        $daftar = [];
        foreach ($rows as $r) {
            $tps = trim((string) ($r['TPS'] ?? ''));
            if ($tps !== '') {
                $daftar[] = $tps;
            }
        }

        return array_values(array_unique($daftar));
    }

    /**
     * Ambil satu halaman dari builder yang sudah tersaring & terurut:
     * hitung total, jepit nomor halaman, lalu ambil barisnya.
     *
     * @return array{0: list<array<string, mixed>>, 1: int, 2: int, 3: int}
     *               [baris, total, halaman, totalHalaman]
     */
    protected function halamanTabel($builder, int $halaman, int $perHal): array
    {
        $perHal   = max(1, $perHal);
        $total    = $builder->countAllResults(false);
        $totalHal = max(1, (int) ceil($total / $perHal));
        $halaman  = min(max(1, $halaman), $totalHal);
        $rows     = $builder->get($perHal, ($halaman - 1) * $perHal)->getResultArray();

        return [$rows, $total, $halaman, $totalHal];
    }

    /**
     * Kirim unduhan ekspor tabel sebagai CSV atau XLSX (Paket Menengah).
     * $judul = label kolom; $baris = baris nilai sesuai urutan $judul.
     * Penyamaran untuk Pemeriksa dilakukan pemanggil sebelumnya lewat
     * samarkanBarisPemeriksa().
     *
     * @param list<string>              $judul
     * @param list<list<mixed>>         $baris
     */
    protected function kirimEkspor(array $judul, array $baris, string $namaDasar, string $format)
    {
        $nama = $namaDasar . '-' . date('Ymd-His');

        // Batas wajar Excel (optimasi 11 Nov 2026): PhpSpreadsheet
        // membangun seluruh workbook di memori — pada data kabupaten
        // penuh (338 ribu baris) itu melampaui batas waktu eksekusi.
        // Di atas 50 ribu baris, unduhan dialihkan ke CSV (isi sama,
        // jauh lebih cepat) dan nama berkasnya berakhiran .csv.
        if ($format === 'xlsx' && count($baris) > 50000) {
            $format = 'csv';
        }

        if ($format === 'xlsx') {
            @set_time_limit(120);
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->fromArray($judul, null, 'A1');
            $sheet->fromArray($baris, null, 'A2');
            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true);
            foreach (range('A', $sheet->getHighestColumn()) as $kol) {
                $sheet->getColumnDimension($kol)->setAutoSize(true);
            }
            $path = tempnam((new \Config\Paths())->writableDirectory, 'ekspor_');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
            $isi = (string) file_get_contents($path);
            unlink($path);

            return $this->response
                ->setContentType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '.xlsx"')
                ->setBody($isi);
        }

        $alir = fopen('php://temp', 'r+b');
        fwrite($alir, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8 dengan benar
        fputcsv($alir, $judul, ';');
        foreach ($baris as $b) {
            fputcsv($alir, $b, ';');
        }
        rewind($alir);
        $isi = (string) stream_get_contents($alir);
        fclose($alir);

        return $this->response
            ->setContentType('text/csv; charset=UTF-8')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $nama . '.csv"')
            ->setBody($isi);
    }

    /**
     * Samarkan kolom-kolom sensitif pada baris hasil bila peran yang
     * masuk adalah Pemeriksa (untuk keluaran partial HTMX berbentuk
     * larik).
     *
     * @param list<array<string, mixed>> $rows
     * @param list<string>               $kolom
     * @return list<array<string, mixed>>
     */
    protected function samarkanBarisPemeriksa(array $rows, array $kolom): array
    {
        if (session()->get('pengguna_peran') !== 'viewer') {
            return $rows;
        }
        foreach ($rows as &$r) {
            foreach ($kolom as $k) {
                if (array_key_exists($k, $r)) {
                    $r[$k] = $this->samarkanNomor($r[$k]);
                }
            }
        }
        unset($r);

        return $rows;
    }

    // -- Daftar wilayah (dari tabel master) ---------------------------

    /** @return list<string> Daftar nama kecamatan pada tabel master. */
    protected function daftarKecamatan(): array
    {
        return array_map(
            static fn ($r) => (string) $r['nama'],
            $this->daftarKecamatanLengkap()
        );
    }

    /** @return list<array{id: int, kode: ?string, nama: string}> Master kecamatan urut nama. */
    protected function daftarKecamatanLengkap(): array
    {
        if ($this->daftarKecCache === null) {
            try {
                $rows = db_connect()->table('kecamatan')->select('id, kode, nama')
                    ->orderBy('nama', 'ASC')->get()->getResultArray();
                $this->daftarKecCache = array_map(static fn ($r) => [
                    'id'   => (int) $r['id'],
                    'kode' => $r['kode'],
                    'nama' => (string) $r['nama'],
                ], $rows);
            } catch (\Throwable) {
                $this->daftarKecCache = [];
            }
        }

        return $this->daftarKecCache;
    }

    /**
     * @return list<string> Daftar nama desa pada kecamatan efektif
     * (atau kecamatan yang diberikan), dari tabel master kelurahan.
     * Akun PPS hanya mendapat desanya sendiri.
     */
    protected function daftarDesa(?string $kec = null): array
    {
        $desaUser = Wilayah::desaPengguna();
        if ($desaUser !== null) {
            return [$desaUser];
        }
        $kec = $kec ?? Wilayah::kecEfektif();
        try {
            $builder = db_connect()->table('kelurahan')->select('kelurahan.nama AS nama')
                ->join('kecamatan', 'kecamatan.id = kelurahan.kecamatan_id');
            if ($kec !== null) {
                $builder->where('kecamatan.nama', $kec);
            }
            $rows = $builder->orderBy('kelurahan.nama', 'ASC')->get()->getResultArray();

            return array_map(static fn ($r) => (string) $r['nama'], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /** @return list<array{id: int, kode: ?string, nama: string}> Master kelurahan satu kecamatan. */
    protected function daftarDesaLengkap(int $kecId): array
    {
        try {
            $rows = db_connect()->table('kelurahan')->select('id, kode, nama')
                ->where('kecamatan_id', $kecId)
                ->orderBy('nama', 'ASC')->get()->getResultArray();

            return array_map(static fn ($r) => [
                'id'   => (int) $r['id'],
                'kode' => $r['kode'],
                'nama' => (string) $r['nama'],
            ], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    // -- Pencatatan akses ---------------------------------------------

    /**
     * Catat akses data sensitif ke `akses_log` di basis data utama.
     * Kegagalan mencatat tidak boleh menggagalkan pekerjaan utama.
     */
    protected function catatAkses(string $aksi, ?string $detail = null, ?string $username = null, ?int $userId = null): void
    {
        try {
            \Config\Database::connect('default')->table('akses_log')->insert([
                'pengguna_id'       => $userId ?? session()->get('pengguna_id'),
                'pengguna_username' => $username ?? session()->get('pengguna_username'),
                'aksi'              => $aksi,
                'detail'            => $detail,
                'ip_address'        => $this->request->getIPAddress(),
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Abaikan: log tidak boleh memblokir alur utama.
        }
    }

    // -- Penyamaran untuk Pemeriksa (UU PDP) ---------------------------

    /** Samarkan satu nilai NIK/NKK: 4 angka awal + 4 angka akhir. */
    protected function samarkanNomor($nilai): string
    {
        $nilai = (string) $nilai;
        if (preg_match('/^\d{16}$/', $nilai)) {
            return substr($nilai, 0, 4) . '********' . substr($nilai, -4);
        }

        return $nilai;
    }

    /**
     * Samarkan NIK & NKK pada baris hasil cetak (array asosiatif) bila
     * pengguna adalah Pemeriksa.
     */
    protected function samarkanBarisCetak(array $rows): array
    {
        if (session()->get('pengguna_peran') !== 'viewer') {
            return $rows;
        }

        foreach ($rows as &$r) {
            foreach (['NIK', 'NKK'] as $k) {
                if (isset($r[$k])) {
                    $r[$k] = $this->samarkanNomor($r[$k]);
                }
            }
        }
        unset($r);

        return $rows;
    }
}
