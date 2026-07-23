<?php

use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Middleware\AuthenticateVoter;
use App\Http\Middleware\CheckLicense;
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
        $middleware->alias([
            'auth.admin' => AuthenticateAdmin::class,
            'auth.voter' => AuthenticateVoter::class,
        ]);

        // Gates the whole app behind license activation. The license
        // routes themselves are excluded (see CheckLicense + the
        // /license/activate routes registered before this middleware
        // would otherwise apply), so an unactivated install can still
        // reach the activation screen.
        $middleware->web(append: [
            CheckLicense::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
