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
     * Nama kecamatan aktif (dari tabel `kec`), diisi di initController
     * dan dipakai semua controller turunan untuk judul halaman.
     * Bila basis data belum ada / tabel `kec` kosong (mis. halaman login
     * sebelum impor data), nilainya null dan tampilan menanganinya.
     */
    protected $kec;

    /** Pengguna yang sedang masuk (dari sesi), atau null bila belum masuk. */
    protected $pengguna;

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Do Not Edit This Line
        parent::initController($request, $response, $logger);

        // Fase 6: terapkan kecamatan aktif dari sesi SEBELUM koneksi
        // basis data pertama dibuat pada permintaan ini. Pengguna &
        // log akses tetap memakai grup 'default' secara eksplisit.
        $grupSesi = session()->get('grup_db');
        if ($grupSesi !== null && $grupSesi !== 'default') {
            $entri = config('Kecamatan')->cari((string) $grupSesi);
            if ($entri !== null && $entri['aktif']) {
                config('Database')->defaultGroup = (string) $grupSesi;
            } else {
                session()->remove('grup_db');
            }
        }

        try {
            $db    = db_connect();
            $qKec  = $db->table('kec')->select('kec')->get()->getRow();
            $this->kec = $qKec->kec ?? null;
        } catch (\Throwable) {
            $this->kec = null;
        }

        $session = session();
        $this->pengguna = $session->get('pengguna_id') ? [
            'id'       => $session->get('pengguna_id'),
            'nama'     => $session->get('pengguna_nama'),
            'username' => $session->get('pengguna_username'),
            'peran'    => $session->get('pengguna_peran'),
            'desa'     => $session->get('pengguna_desa'),
        ] : null;
        // Preload any models, libraries, etc, here.

        // E.g.: $this->session = \Config\Services::session();
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
