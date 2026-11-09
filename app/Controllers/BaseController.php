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
}
