<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // API requests should always return JSON
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with consistent JSON format.
     */
    protected function handleApiException($request, Throwable $e)
    {
        $status = 500;
        $message = 'Internal server error';
        $errors = null;

        // Validation errors
        if ($e instanceof ValidationException) {
            $status = 422;
            $message = 'Validation failed';
            $errors = $e->errors();
        }
        // Not found errors
        elseif ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            $status = 404;
            $message = 'Resource not found';
        }
        // Bad request errors
        elseif ($e instanceof \InvalidArgumentException) {
            $status = 400;
            $message = $e->getMessage();
        }
        // HTTP exceptions
        elseif (method_exists($e, 'getStatusCode')) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: 'An error occurred';
        }

        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        // Include debug info only in development
        if (config('app.debug') && $status === 500) {
            $response['debug'] = [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())->take(5)->toArray(),
            ];
        }

        return response()->json($response, $status);
    }
}