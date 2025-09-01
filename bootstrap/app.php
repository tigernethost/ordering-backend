<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->validateCsrfTokens(except: [
            'device/*',
            '/*',
            // 'http://example.com/foo/bar',
            // 'http://example.com/foo/*',
        ]);

        $middleware->append(HandleCors::class);

        $middleware->alias([
            'client_credentials' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
