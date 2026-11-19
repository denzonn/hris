<?php

use App\Http\Middleware\CekOtoritas;
use App\Http\Middleware\WajibGantiPassword;
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
            'otoritas' => CekOtoritas::class,
        ]);
        $middleware->web(append: [WajibGantiPassword::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
