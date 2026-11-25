<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ProxyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Route::get('/_uji-proxy', fn () => response()->json([
            'ip'     => request()->ip(),
            'secure' => request()->secure(),
            'url'    => url('/portal'),
        ]));
    }

    public function test_ip_dan_https_dibaca_dari_proxy_tepercaya(): void
    {
        $r = $this->withServerVariables([
            'REMOTE_ADDR'            => '172.21.0.1', // gateway Docker
            'HTTP_X_FORWARDED_FOR'   => '203.0.113.9',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_PORT'  => '443',
        ])->getJson('/_uji-proxy');

        $r->assertOk()->assertJson(['ip' => '203.0.113.9', 'secure' => true]);
        $this->assertStringStartsWith('https://', $r->json('url'));
    }

    public function test_header_palsu_dari_luar_jaringan_privat_diabaikan(): void
    {
        $r = $this->withServerVariables([
            'REMOTE_ADDR'          => '8.8.8.8',
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4',
        ])->getJson('/_uji-proxy');

        $r->assertOk()->assertJson(['ip' => '8.8.8.8']);
    }

    public function test_pengunjung_yang_memalsukan_header_tidak_menipu_ip(): void
    {
        // Pengunjung mengirim X-Forwarded-For palsu; Cloudflare menambahkan IP aslinya di belakang.
        $r = $this->withServerVariables([
            'REMOTE_ADDR'          => '172.21.0.1',
            'HTTP_X_FORWARDED_FOR' => '1.1.1.1, 203.0.113.9',
        ])->getJson('/_uji-proxy');

        $r->assertOk()->assertJson(['ip' => '203.0.113.9']);
    }
}
