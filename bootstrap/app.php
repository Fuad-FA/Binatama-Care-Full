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