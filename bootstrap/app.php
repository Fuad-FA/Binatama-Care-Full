<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Aplikasi berjalan di belakang Cloudflare Tunnel dan Docker. Percayai header X-Forwarded-*
        // hanya dari jaringan privat (gateway Docker, localhost, LAN) agar IP pengunjung dan skema https terbaca benar.
        $middleware->trustProxies(
            at: ['127.0.0.1', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16'],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->alias([
            'role'              => \App\Http\Middleware\RoleMiddleware::class,
            'check.first.login' => \App\Http\Middleware\CheckFirstLogin::class,
            'patient.auth'      => \App\Http\Middleware\PatientAuth::class,
        ]);

        // Tamu / sesi habis diarahkan ke halaman login yang sesuai (bukan /auth yang 404)
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin/*')
            ? route('login.admin')
            : route('login.guru'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();