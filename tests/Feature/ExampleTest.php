<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Halaman utama ('/') sengaja 404; tiap peran punya pintu masuk sendiri.
     */
    public function test_root_is_hidden_and_landing_pages_load(): void
    {
        $this->get('/')->assertStatus(404);

        foreach (['/sik-admin-secure', '/staff', '/student', '/portal'] as $url) {
            $this->get($url)->assertStatus(200);
        }
    }
}
