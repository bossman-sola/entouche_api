<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use Illuminate\Routing\Controller;

abstract class BaseApiController extends Controller
{
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200)
    {
        return ApiResponse::success($data, $message, $status);
    }

    protected function created(mixed $data = null, string $message = 'Created successfully')
    {
        return ApiResponse::created($data, $message);
    }

    protected function error(string $message, mixed $errors = null, int $status = 400)
    {
        return ApiResponse::error($message, $errors, $status);
    }

    protected function paginated(mixed $paginator, string $message = 'Success')
    {
        return ApiResponse::paginated($paginator, $message);
    }
}
