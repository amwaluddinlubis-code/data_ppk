<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Fase 1 — Filter otentikasi.
 *
 * Dipakai sebagai `auth` pada semua rute aplikasi kecuali login/logout.
 * Argumen peran opsional, mis. `auth:admin` — dipakai bila suatu rute
 * hanya untuk peran tertentu (saat ini penegakan peran admin untuk
 * halaman Pengguna dilakukan di controller; argumen tersedia untuk
 * rute Fase 2 nanti).
 *
 * Aturan:
 * - Belum login  -> arahkan ke /login.
 * - Peran tidak cocok -> kembali dengan pesan galat.
 * - Akun bertanda harus_ganti_sandi -> arahkan paksa ke /ganti-sandi
 *   (kecuali halaman ganti sandi & keluar itu sendiri).
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (! $session->get('pengguna_id')) {
            return redirect()->to('login')->with('galat', 'Silakan masuk dahulu.');
        }

        if (! empty($arguments)) {
            $peran = $session->get('pengguna_peran');
            if (! in_array($peran, $arguments, true)) {
                return redirect()->to('/')->with('galat', 'Peran Anda tidak berhak membuka halaman itu.');
            }
        }

        // Jalur bisa berawalan subfolder pemasangan (mis. /pemilurb/...),
        // jadi bandingkan ujung jalurnya saja.
        $jalur = trim($request->getUri()->getPath(), '/');
        if ($session->get('pengguna_harus_ganti_sandi')
            && ! str_ends_with($jalur, 'ganti-sandi')
            && ! str_ends_with($jalur, 'logout')
        ) {
            return redirect()->to('ganti-sandi')
                ->with('info', 'Demi keamanan, ganti dulu kata sandi awal Anda.');
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
