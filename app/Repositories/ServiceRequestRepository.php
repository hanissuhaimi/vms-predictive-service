<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use App\Models\VehicleProfile;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ServiceRequestRepository
{
    /**
     * Find service requests by vehicle
     */
    public function findByVehicle(string $vehicleNumber, array $options = []): Collection
    {
        $query = ServiceRequest::forVehicle($vehicleNumber)
                              ->with(['vehicleProfile', 'depot', 'requestor', 'responder'])
                              ->withValidDates()
                              ->orderByRecent();
        
        // Apply filters
        if (isset($options['mr_type'])) {
            $query->byMrType($options['mr_type']);
        }
        
        if (isset($options['status'])) {
            $query->byStatus($options['status']);
        }
        
        if (isset($options['priority'])) {
            $query->byPriority($options['priority']);
        }
        
        if (isset($options['date_from'])) {
            $query->where('Datereceived', '>=', $options['date_from']);
        }
        
        if (isset($options['date_to'])) {
            $query->where('Datereceived', '<=', $options['date_to']);
        }
        
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        return $query->get();
    }
    
    /**
     * Get maintenance records only (exclude cleaning)
     */
    public function getMaintenanceRecords(string $vehicleNumber, array $options = []): Collection
    {
        $options['exclude_cleaning'] = true;
        
        $query = ServiceRequest::forVehicle($vehicleNumber)
                              ->maintenanceOnly()
                              ->withValidDates()
                              ->orderByRecent();
        
        if (isset($options['limit'])) {
            $query->limit($options['limit']);
        }
        
        return $query->get();
    }
    
    /**
     * Get service statistics for vehicle
     */
    public function getServiceStatistics(string $vehicleNumber): array
    {
        $allServices = ServiceRequest::forVehicle($vehicleNumber)->withValidDates();
        
        $stats = [
            'total_services' => $allServices->count(),
            'by_mr_type' => [],
            'by_status' => [],
            'by_priority' => [],
            'by_year' => [],
            'recent_activity' => []
        ];
        
        // Group by MR Type
        $byMrType = $allServices->get()->groupBy('MrType');
        foreach ($byMrType as $mrType => $services) {
            $stats['by_mr_type'][$mrType] = [
                'count' => $services->count(),
                'percentage' => round(($services->count() / $stats['total_services']) * 100, 1)
            ];
        }
        
        // Group by Status
        $byStatus = $allServices->get()->groupBy('Status');
        foreach ($byStatus as $status => $services) {
            $stats['by_status'][$status] = $services->count();
        }
        
        // Group by Priority
        $byPriority = $allServices->get()->groupBy('Priority');
        foreach ($byPriority as $priority => $services) {
            $stats['by_priority'][$priority] = $services->count();
        }
        
        // Group by Year
        $byYear = $allServices->get()->groupBy(function ($service) {
            return Carbon::parse($service->Datereceived)->format('Y');
        });
        foreach ($byYear as $year => $services) {
            $stats['by_year'][$year] = $services->count();
        }
        
        // Recent activity (last 30 days)
        $stats['recent_activity'] = $allServices->recent(30)->count();
        
        return $stats;
    }
    
    /**
     * Search service requests with filters
     */
    public function searchServiceRequests(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = ServiceRequest::with(['vehicleProfile', 'depot', 'requestor', 'responder'])
                              ->withValidDates()
                              ->orderByRecent();
        
        // Vehicle filter
        if (!empty($filters['vehicle'])) {
            $query->forVehicle($filters['vehicle']);
        }
        
        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('Datereceived', '>=', $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $query->where('Datereceived', '<=', $filters['date_to']);
        }
        
        // MR Type filter
        if (!empty($filters['mr_type'])) {
            $query->byMrType($filters['mr_type']);
        }
        
        // Status filter
        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }
        
        // Priority filter
        if (!empty($filters['priority'])) {
            $query->byPriority($filters['priority']);
        }
        
        // Depot filter
        if (!empty($filters['depot'])) {
            $query->where('Building', $filters['depot']);
        }
        
        // Description search
        if (!empty($filters['description'])) {
            $query->where(function($q) use ($filters) {
                $q->whereRaw('LOWER(Description) LIKE ?', ['%' . strtolower($filters['description']) . '%'])
                  ->orWhereRaw('LOWER(Response) LIKE ?', ['%' . strtolower($filters['description']) . '%'])
                  ->orWhereRaw('LOWER(SR) LIKE ?', ['%' . strtolower($filters['description']) . '%']);
            });
        }
        
        return $query->paginate($perPage);
    }
    
    /**
     * Get services for parts analysis
     */
    public function getServicesForPartsAnalysis(string $vehicleNumber, array $keywords): Collection
    {
        $services = $this->getMaintenanceRecords($vehicleNumber);
        
        return $services->filter(function ($service) use ($keywords) {
            return $service->matchesKeywords($keywords);
        });
    }
    
    /**
     * Get service trends over time
     */
    public function getServiceTrends(string $vehicleNumber, string $period = 'monthly'): array
    {
        $services = ServiceRequest::forVehicle($vehicleNumber)
                                 ->withValidDates()
                                 ->orderBy('Datereceived')
                                 ->get();
        
        $groupFormat = match($period) {
            'yearly' => 'Y',
            'monthly' => 'Y-m',
            'weekly' => 'Y-W',
            'daily' => 'Y-m-d',
            default => 'Y-m'
        };
        
        $trends = $services->groupBy(function ($service) use ($groupFormat) {
            return Carbon::parse($service->Datereceived)->format($groupFormat);
        })->map(function ($group, $period) {
            return [
                'period' => $period,
                'total_services' => $group->count(),
                'maintenance_services' => $group->where('MrType', '!=', 2)->count(),
                'cleaning_services' => $group->where('MrType', 2)->count(),
                'tire_services' => $group->where('MrType', 3)->count(),
                'average_priority' => round($group->avg('Priority'), 1)
            ];
        });
        
        return $trends->toArray();
    }
    
    /**
     * Get overdue service requests
     */
    public function getOverdueServiceRequests(): Collection
    {
        return ServiceRequest::with(['vehicleProfile', 'depot', 'requestor'])
                            ->where('Status', '<', 3) // Not closed
                            ->withValidDates()
                            ->get()
                            ->filter(function ($service) {
                                return $service->isOverdueForResponse();
                            });
    }
    
    /**
     * Get services by date range with statistics
     */
    public function getServicesByDateRange(string $startDate, string $endDate, array $options = []): array
    {
        $query = ServiceRequest::with(['vehicleProfile', 'depot'])
                              ->whereBetween('Datereceived', [$startDate, $endDate])
                              ->orderByRecent();
        
        if (isset($options['depot'])) {
            $query->where('Building', $options['depot']);
        }
        
        if (isset($options['mr_type'])) {
            $query->byMrType($options['mr_type']);
        }
        
        $services = $query->get();
        
        return [
            'services' => $services,
            'total_count' => $services->count(),
            'by_depot' => $services->groupBy('Building')->map->count(),
            'by_mr_type' => $services->groupBy('MrType')->map->count(),
            'by_status' => $services->groupBy('Status')->map->count(),
            'date_range' => ['start' => $startDate, 'end' => $endDate]
        ];
    }
}