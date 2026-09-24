<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\RunDueScheduledTasks;
use App\Http\Middleware\TrackVisitors;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Platforms such as Railway terminate TLS at their edge and forward the
        // request on. Without trusting that proxy, every visitor would be
        // recorded as the load balancer and generated URLs would use http.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            TrackVisitors::class,
            RunDueScheduledTasks::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
