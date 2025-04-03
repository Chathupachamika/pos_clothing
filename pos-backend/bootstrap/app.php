<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api/v1',
        health: 'up',
    )
    ->withMiddleware(function (Middleware $middleware) {})
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
