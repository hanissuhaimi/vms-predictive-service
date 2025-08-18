<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use App\Models\VehicleProfile;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class VehicleRepository
{
    /**
     * Find active vehicles with their latest service info
     */
    public function findActiveVehicles(array $options = []): Collection
    {
        $query = VehicleProfile::with(['depot', 'primaryStaff'])
                              ->active()
                              ->orderBy('vh_regno');
        
        if (isset($options['depot'])) {
            $query->byDepot($options['depot']);
        }
        
        if (isset($options['model'])) {
            $query->byModel($options['model']);
        }
        
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        return $query->get();
    }
    
    /**
     * Get vehicle with complete profile and recent services
     */
    public function getVehicleWithProfile(string $vehicleNumber): ?VehicleProfile
    {
        return VehicleProfile::with([
                    'depot',
                    'primaryStaff',
                    'secondaryStaff',
                    'serviceRequests' => function($query) {
                        $query->withValidDates()->orderByRecent()->limit(10);
                    }
                ])
                ->where('vh_regno', strtoupper(trim($vehicleNumber)))
                ->first();
    }
    
    /**
     * Get vehicles by depot with statistics
     */
    public function getVehiclesByDepot(string $depotCode): array
    {
        $vehicles = VehicleProfile::with(['primaryStaff', 'secondaryStaff'])
                                 ->byDepot($depotCode)
                                 ->orderBy('vh_regno')
                                 ->get();
        
        $stats = [
            'total_vehicles' => $vehicles->count(),
            'active_vehicles' => $vehicles->where('Status', 1)->count(),
            'under_maintenance' => $vehicles->where('UnderMaintenance', true)->count(),
            'by_model' => $vehicles->groupBy('ModelID')->map->count(),
            'by_status' => $vehicles->groupBy('Status')->map->count()
        ];
        
        return [
            'vehicles' => $vehicles,
            'statistics' => $stats,
            'depot_code' => $depotCode
        ];
    }
    
    /**
     * Search vehicles with various criteria
     */
    public function searchVehicles(string $search, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = VehicleProfile::with(['depot', 'primaryStaff']);
        
        // Search by registration
        if (!empty($search)) {
            $query->searchRegistration($search);
        }
        
        // Apply filters
        if (!empty($filters['depot'])) {
            $query->byDepot($filters['depot']);
        }
        
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->active();
            } elseif ($filters['status'] === 'maintenance') {
                $query->underMaintenance();
            } else {
                $query->where('Status', $filters['status']);
            }
        }
        
        if (!empty($filters['model'])) {
            $query->byModel($filters['model']);
        }
        
        if (!empty($filters['staff'])) {
            $query->where(function($q) use ($filters) {
                $q->where('staff_kod1', $filters['staff'])
                  ->orWhere('staff_kod2', $filters['staff']);
            });
        }
        
        return $query->orderBy('vh_regno')->paginate($perPage);
    }
    
    /**
     * Get vehicles needing maintenance
     */
    public function getVehiclesNeedingMaintenance(): Collection
    {
        $vehicles = VehicleProfile::active()
                                 ->with(['serviceRequests' => function($query) {
                                     $query->maintenanceOnly()
                                           ->withValidDates()
                                           ->orderByRecent()
                                           ->limit(5);
                                 }])
                                 ->get();
        
        return $vehicles->filter(function ($vehicle) {
            $needsMaintenance = $vehicle->needsMaintenance();
            return $needsMaintenance['needs'];
        });
    }
    
    /**
     * Get vehicle utilization statistics
     */
    public function getVehicleUtilizationStats(): array
    {
        $allVehicles = VehicleProfile::all();
        
        $stats = [
            'total_vehicles' => $allVehicles->count(),
            'active_vehicles' => $allVehicles->where('Status', 1)->count(),
            'inactive_vehicles' => $allVehicles->where('Status', '!=', 1)->count(),
            'under_maintenance' => $allVehicles->where('UnderMaintenance', true)->count(),
            'by_depot' => $allVehicles->groupBy('depot_kod')->map->count(),
            'by_model' => $allVehicles->groupBy('ModelID')->map->count(),
            'utilization_rate' => 0
        ];
        
        if ($stats['total_vehicles'] > 0) {
            $stats['utilization_rate'] = round(($stats['active_vehicles'] / $stats['total_vehicles']) * 100, 1);
        }
        
        return $stats;
    }
    
    /**
     * Get vehicles with expiring documents
     */
    public function getVehiclesWithExpiringDocuments(int $withinDays = 30): Collection
    {
        return VehicleProfile::active()
                            ->with('depot')
                            ->get()
                            ->filter(function ($vehicle) use ($withinDays) {
                                $expiring = $vehicle->getExpiringDocuments($withinDays);
                                return !empty($expiring);
                            });
    }
}