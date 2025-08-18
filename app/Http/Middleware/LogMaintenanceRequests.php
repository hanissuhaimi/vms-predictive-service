<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogMaintenanceRequests
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        // Log maintenance-related requests
        if ($this->isMaintenanceRequest($request)) {
            Log::channel('maintenance')->info('Maintenance Request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'input' => $this->sanitizeInput($request->all()),
                'timestamp' => now()->toISOString(),
            ]);
        }

        $response = $next($request);

        // Log response for maintenance requests
        if ($this->isMaintenanceRequest($request)) {
            Log::channel('maintenance')->info('Maintenance Response', [
                'status_code' => $response->getStatusCode(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $response;
    }

    /**
     * Check if this is a maintenance-related request
     */
    private function isMaintenanceRequest(Request $request): bool
    {
        $maintenancePaths = [
            'prediction',
            'maintenance',
            'analytics',
            'api/v1/prediction',
            'api/v1/analytics'
        ];

        foreach ($maintenancePaths as $path) {
            if (str_starts_with($request->path(), $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize input for logging (remove sensitive data)
     */
    private function sanitizeInput(array $input): array
    {
        $sensitiveKeys = ['password', 'token', 'api_key'];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($input[$key])) {
                $input[$key] = '***REDACTED***';
            }
        }

        return $input;
    }
}