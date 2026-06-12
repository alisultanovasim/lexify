<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        $response = parent::render($request, $e);

        if ($request->header('X-Inertia') && in_array($response->status(), [404, 500, 503])) {
            $page = match ($response->status()) {
                404 => 'Error/NotFound',
                default => 'Error/ServerError',
            };
            return Inertia::render($page, ['status' => $response->status()])
                ->toResponse($request)
                ->setStatusCode($response->status());
        }

        return $response;
    }
}
