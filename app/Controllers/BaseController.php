<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Class BaseController
 *
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 * Extend this class in any new controllers:
 *     class Home extends BaseController
 *
 * For security be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     *
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * An array of helpers to be loaded automatically upon
     * class instantiation. These helpers will be available
     * to all other controllers that extend BaseController.
     *
     * @var array
     */
    protected $helpers = [];

    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */
    // protected $session;

    /**
     * Nama kecamatan yang sedang AKTIF sebagai saringan wilayah pada
     * sesi ini (Fase 7), diisi di initController. Null berarti tampilan
     * seluruh kabupaten (hanya untuk pengguna tanpa cakupan kecamatan,
     * yaitu Admin Kabupaten).
     */
    protected $kec;

    /** Nama kabupaten dari data coklit (kolom KAB), null bila data kosong. */
    protected $kab;

    /** @var list<string>|null Cache daftar kecamatan pada permintaan ini. */
    private $daftarKecCache = null;

    /** Pengguna yang sedang masuk (dari sesi), atau null bila belum masuk. */
    protected $pengguna;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Fase 7: satu basis data kabupaten. Kecamatan adalah saringan
        // wilayah: pengguna PPK/PPS terkunci ke cakupannya (kolom
        // users.kecamatan/desa); Admin Kabupaten bebas memilih saringan
        // lewat sesi `kec_filter` (null = seluruh kabupaten).
        try {
            $db       = db_connect();
            $qKab     = $db->table('mhc')->select('KAB')->where('KAB IS NOT NULL')->where('KAB !=', '')->limit(1)->get()->getRow();
            $this->kab = $qKab->KAB ?? null;
        } catch (\Throwable) {
            $this->kab = null;
        }
        $this->kec = $this->kecEfektif();

        $session = session();
        $this->pengguna = $session->get('pengguna_id') ? [
            'id'        => $session->get('pengguna_id'),
            'nama'      => $session->get('pengguna_nama'),
            'username'  => $session->get('pengguna_username'),
            'peran'     => $session->get('pengguna_peran'),
            'kecamatan' => $session->get('pengguna_kecamatan'),
            'desa'      => $session->get('pengguna_desa'),
        ] : null;
        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
    }

    /** Kecamatan cakupan akun yang masuk (PPK/PPS); null = kabupaten. */
    protected function kecPengguna(): ?string
    {
        $kec = session()->get('pengguna_kecamatan');

        return ($kec === null || $kec === '') ? null : (string) $kec;
    }

    /** Desa cakupan akun yang masuk (PPS); null = seluruh kecamatan. */
    protected function desaPengguna(): ?string
    {
        $desa = session()->get('pengguna_desa');

        return ($desa === null || $desa === '') ? null : (string) $desa;
    }

    /**
     * Kecamatan yang berlaku efektif untuk semua kueri halaman ini:
     * cakupan akun bila ada; selain itu saringan sesi (admin); null
     * berarti seluruh kabupaten.
     */
    protected function kecEfektif(): ?string
    {
        $kecUser = $this->kecPengguna();
        if ($kecUser !== null) {
            return $kecUser;
        }
        $filter = session()->get('kec_filter');

        return ($filter === null || $filter === '') ? null : (string) $filter;
    }

    /** Terapkan saringan kecamatan efektif ke builder (bila ada). */
    protected function saringKec($builder, string $kolom = 'KEC')
    {
        $kec = $this->kecEfektif();
        if ($kec !== null) {
            $builder->where($kolom, $kec);
        }

        return $builder;
    }

    /**
     * Terapkan saringan wilayah penuh: kecamatan efektif + desa
     * cakupan akun (PPS hanya boleh melihat desanya sendiri).
     */
    protected function saringWilayah($builder, string $kolomKec = 'KEC', string $kolomDesa = 'DESA')
    {
        $this->saringKec($builder, $kolomKec);
        $desa = $this->desaPengguna();
        if ($desa !== null) {
            $builder->where($kolomDesa, $desa);
        }

        return $builder;
    }

    /** @return list<string> Daftar kecamatan yang ada di data coklit. */
    protected function daftarKecamatan(): array
    {
        if ($this->daftarKecCache === null) {
            try {
                $rows = db_connect()->table('mhc')->select('KEC')->distinct()
                    ->where('KEC IS NOT NULL')->where('KEC !=', '')
                    ->orderBy('KEC', 'ASC')->get()->getResultArray();
                $this->daftarKecCache = array_map(static fn ($r) => (string) $r['KEC'], $rows);
            } catch (\Throwable) {
                $this->daftarKecCache = [];
            }
        }

        return $this->daftarKecCache;
    }

    /**
     * @return list<string> Daftar desa pada kecamatan efektif (atau
     * kecamatan yang diberikan). Akun PPS hanya mendapat desanya sendiri.
     */
    protected function daftarDesa(?string $kec = null): array
    {
        $desaUser = $this->desaPengguna();
        if ($desaUser !== null) {
            return [$desaUser];
        }
        $kec = $kec ?? $this->kecEfektif();
        try {
            $builder = db_connect()->table('mhc')->select('DESA')->distinct()
                ->where('DESA IS NOT NULL')->where('DESA !=', '');
            if ($kec !== null) {
                $builder->where('KEC', $kec);
            }
            $rows = $builder->orderBy('DESA', 'ASC')->get()->getResultArray();

            return array_map(static fn ($r) => (string) $r['DESA'], $rows);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Fase 5 — Catat akses data sensitif ke `akses_log` di basis data
     * UTAMA (grup 'default'), apa pun kecamatan yang sedang aktif.
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
     * Fase 5 — Untuk peran Pemeriksa (viewer), NIK & NKK pada jawaban
     * DataTables disamarkan (UU PDP: tampilkan seperlunya saja).
     * $kolom berisi indeks kolom pada baris data (0 = nomor urut).
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
     * Fase 5 — Samarkan NIK & NKK pada baris hasil cetak (array asosiatif)
     * bila pengguna adalah Pemeriksa.
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
