<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',  // IMPORTANT: Dapat naa ni!
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (Request $request) {
            return $request->is('api/*') ? null : '/';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'message' => 'Unauthenticated. Login first and send a Bearer token.',
                ], 401);
            }

            return null;
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($e);

            $status = match (true) {
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                $e instanceof ModelNotFoundException => 404,
                $e instanceof QueryException => 503,
                default => 500,
            };

            $message = config('app.debug')
                ? $e->getMessage()
                : match (true) {
                    $e instanceof ModelNotFoundException => 'The requested resource was not found.',
                    $e instanceof QueryException => 'Database is unavailable. Confirm migrations ran on Render PostgreSQL.',
                    default => 'Server error. Check the Laravel log for details.',
                };

            return response()->json(['message' => $message], $status);
        });
    })->create();
