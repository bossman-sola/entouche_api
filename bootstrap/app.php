<?php

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../routes/api.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::error('Validation failed.', $e->errors(), 422);
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::error('Unauthenticated.', null, 401);
        });

        $exceptions->render(function (Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $status === 500 && ! config('app.debug') ? 'Server error.' : $e->getMessage();

            return ApiResponse::error($message ?: 'Request failed.', null, $status);
        });
    })
    ->create();
