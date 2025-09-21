<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // CSRF protection me exception add karo
        $middleware->validateCsrfTokens(except: [
            'crud/*',
            'api/products',        // list
            'api/products/*',      // single product (update/delete)
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
