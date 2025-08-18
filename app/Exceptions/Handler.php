<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     */
    protected $dontReport = [
        ValidationException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom logging for maintenance system errors
            if (str_contains($e->getMessage(), 'prediction') || 
                str_contains($e->getMessage(), 'vehicle') ||
                str_contains($e->getMessage(), 'maintenance')) {
                
                Log::channel('maintenance')->error('Maintenance System Error', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        // Handle API requests
        if ($request->expectsJson()) {
            return $this->renderApiException($request, $e);
        }

        // Handle web requests
        return $this->renderWebException($request, $e);
    }

    /**
     * Render API exceptions
     */
    private function renderApiException(Request $request, Throwable $e)
    {
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);
        }

        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'error' => 'Resource not found'
            ], 404);
        }

        // Generic API error
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        return response()->json([
            'success' => false,
            'error' => app()->environment('production') ? 
                      'An error occurred' : 
                      $e->getMessage()
        ], $statusCode);
    }

    /**
     * Render web exceptions
     */
    private function renderWebException(Request $request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return response()->view('errors.404', [], 404);
        }

        // Let Laravel handle other exceptions
        return parent::render($request, $e);
    }
}