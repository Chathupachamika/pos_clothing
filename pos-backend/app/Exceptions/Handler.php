<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    // Other methods...

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return response()->json(['message' => 'Unauthorized. Please log in first.'], 401);
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof AuthenticationException) {
            return $this->unauthenticated($request, $exception);
        }

        return parent::render($request, $exception);
    }
}
