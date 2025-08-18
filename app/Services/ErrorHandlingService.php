<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorHandlingService
{
    /**
     * Log prediction errors with context
     */
    public static function logPredictionError(Throwable $e, array $context = []): void
    {
        Log::channel('maintenance')->error('Prediction Error', [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'context' => $context,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log vehicle service errors
     */
    public static function logVehicleServiceError(Throwable $e, string $vehicleNumber = null): void
    {
        Log::channel('maintenance')->error('Vehicle Service Error', [
            'error_message' => $e->getMessage(),
            'vehicle_number' => $vehicleNumber,
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log ML service errors
     */
    public static function logMLServiceError(Throwable $e, array $mlData = []): void
    {
        Log::channel('maintenance')->error('ML Service Error', [
            'error_message' => $e->getMessage(),
            'ml_data_sample' => array_slice($mlData, 0, 5), // Log only first 5 items
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log database errors
     */
    public static function logDatabaseError(Throwable $e, string $query = null): void
    {
        Log::channel('maintenance')->error('Database Error', [
            'error_message' => $e->getMessage(),
            'query' => $query,
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Generate user-friendly error message
     */
    public static function getUserFriendlyMessage(Throwable $e): string
    {
        $message = $e->getMessage();

        // Vehicle not found
        if (str_contains($message, 'not found') || str_contains($message, 'Model not found')) {
            return 'Vehicle not found in the system. Please check the vehicle number.';
        }

        // Validation errors
        if (str_contains($message, 'validation')) {
            return 'Please check your input and try again.';
        }

        // ML service errors
        if (str_contains($message, 'prediction') || str_contains($message, 'ML')) {
            return 'Prediction service is temporarily unavailable. Using fallback analysis.';
        }

        // Database errors
        if (str_contains($message, 'database') || str_contains($message, 'connection')) {
            return 'Database connection issue. Please try again in a moment.';
        }

        // Generic error
        return 'An unexpected error occurred. Please try again or contact support.';
    }

    /**
     * Check if error should be shown to user
     */
    public static function shouldShowToUser(Throwable $e): bool
    {
        // Show validation errors
        if (str_contains($e->getMessage(), 'validation')) {
            return true;
        }

        // Show vehicle not found errors
        if (str_contains($e->getMessage(), 'not found')) {
            return true;
        }

        // Hide system errors in production
        return !app()->environment('production');
    }
}