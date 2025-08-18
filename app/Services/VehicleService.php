<?php

namespace App\Services;

use App\Models\VehicleProfile;
use App\Models\ServiceRequest;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class VehicleService
{
    /**
     * Get vehicle with complete information
     */
    public function getVehicleWithHistory(string $vehicleNumber): array
    {
        $vehicleNumber = strtoupper(trim($vehicleNumber));
        
        // Get vehicle profile
        $vehicle = VehicleProfile::with(['depot', 'primaryStaff', 'secondaryStaff'])
                                ->where('vh_regno', $vehicleNumber)
                                ->active()
                                ->first();
        
        if (!$vehicle) {
            throw new \Exception("Vehicle {$vehicleNumber} not found or not active");
        }
        
        // Get maintenance statistics
        $stats = $vehicle->getMaintenanceStats();
        
        // Get service patterns
        $serviceFrequency = $vehicle->getServiceFrequency();
        $usagePattern = $vehicle->getUsagePattern();
        $averageInterval = $vehicle->getAverageMaintenanceInterval();
        
        return [
            'vehicle' => $vehicle,
            'stats' => $stats,
            'service_frequency' => $serviceFrequency,
            'usage_pattern' => $usagePattern,
            'average_interval' => $averageInterval,
            'needs_maintenance' => $vehicle->needsMaintenance(),
            'expiring_documents' => $vehicle->getExpiringDocuments()
        ];
    }

    /**
     * Validate vehicle and mileage data
     */
    public function validateVehicleData(string $vehicleNumber, int $currentMileage): array
    {
        $vehicleNumber = strtoupper(trim($vehicleNumber));
        
        // Basic validation
        if (empty($vehicleNumber)) {
            return [
                'valid' => false,
                'message' => 'Vehicle number is required',
                'code' => 'VEHICLE_REQUIRED'
            ];
        }
        
        if ($currentMileage < 0 || $currentMileage > 10000000) {
            return [
                'valid' => false,
                'message' => 'Invalid mileage. Must be between 0 and 10,000,000 KM',
                'code' => 'INVALID_MILEAGE'
            ];
        }
        
        // Check if vehicle exists
        $vehicle = VehicleProfile::where('vh_regno', $vehicleNumber)->first();
        
        if (!$vehicle) {
            return [
                'valid' => false,
                'message' => "Vehicle {$vehicleNumber} not found in system",
                'code' => 'VEHICLE_NOT_FOUND'
            ];
        }
        
        if (!$vehicle->is_active) {
            return [
                'valid' => false,
                'message' => "Vehicle {$vehicleNumber} is not active",
                'code' => 'VEHICLE_INACTIVE'
            ];
        }
        
        // Validate mileage against history
        $lastService = $vehicle->serviceRequests()
                              ->withOdometer()
                              ->orderByRecent()
                              ->first();
        
        if ($lastService) {
            $lastMileage = floatval($lastService->Odometer);
            
            if ($currentMileage < $lastMileage) {
                return [
                    'valid' => false,
                    'message' => "Current mileage ({$currentMileage} KM) cannot be less than last service mileage ({$lastMileage} KM)",
                    'code' => 'MILEAGE_BACKWARDS'
                ];
            }
            
            $mileageDiff = $currentMileage - $lastMileage;
            $daysSinceService = Carbon::parse($lastService->Datereceived)->diffInDays(now());
            
            // Check for unrealistic mileage increase
            if ($daysSinceService > 0 && ($mileageDiff / $daysSinceService) > 1000) {
                return [
                    'valid' => false,
                    'message' => "Mileage increase seems unrealistic. Please verify the odometer reading.",
                    'code' => 'UNREALISTIC_MILEAGE',
                    'warning' => true
                ];
            }
        }
        
        return [
            'valid' => true,
            'message' => 'Vehicle data is valid',
            'vehicle' => $vehicle
        ];
    }

    /**
     * Search vehicles by various criteria
     */
    public function searchVehicles(string $search, array $filters = []): array
    {
        $query = VehicleProfile::with(['depot', 'primaryStaff']);
        
        // Search by registration number
        if (!empty($search)) {
            $query->searchRegistration($search);
        }
        
        // Apply filters
        if (isset($filters['depot'])) {
            $query->byDepot($filters['depot']);
        }
        
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'maintenance') {
                $query->underMaintenance();
            }
        }
        
        if (isset($filters['model'])) {
            $query->byModel($filters['model']);
        }
        
        $vehicles = $query->orderBy('vh_regno')->paginate(20);
        
        return [
            'vehicles' => $vehicles,
            'search_term' => $search,
            'filters_applied' => $filters
        ];
    }
}