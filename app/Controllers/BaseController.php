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
     * Untuk peran Pemeriksa (viewer), NIK & NKK pada jawaban DataTables
     * disamarkan. $kolom berisi indeks kolom pada baris data
     * (0 = nomor urut).
     */
    protected function samarkanUntukPemeriksa($response, array $kolom)
    {
        if (session()->get('pengguna_peran') !== 'viewer') {
            return $response;
        }

        $json = json_decode((string) $response->getBody(), true);
        if (! is_array($json) || ! isset($json['data']) || ! is_array($json['data'])) {
            return $response;
        }

        foreach ($json['data'] as &$baris) {
            foreach ($kolom as $idx) {
                if (isset($baris[$idx]) && is_string($baris[$idx])) {
                    $baris[$idx] = $this->samarkanNomor($baris[$idx]);
                }
            }
        }
        unset($baris);

        return $response->setJSON($json);
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
