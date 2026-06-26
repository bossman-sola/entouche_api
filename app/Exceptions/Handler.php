<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($request->is('api/*')) {
            if ($e instanceof ValidationException) {
                return ApiResponse::error('Validation failed.', $e->errors(), 422);
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponse::error('Unauthenticated.', null, 401);
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            $message = $status === 500 && ! config('app.debug') ? 'Server error.' : $e->getMessage();

            return ApiResponse::error($message ?: 'Request failed.', null, $status);
        }

        return parent::render($request, $e);
    }
}
