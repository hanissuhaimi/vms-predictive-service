<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use App\Models\VehicleProfile;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class DepotRepository
{
    /**
     * Get all depots with statistics
     */
    public function getAllDepotsWithStats(): Collection
    {
        return Depot::with(['vehicles', 'users'])
                   ->get()
                   ->map(function ($depot) {
                       $depot->statistics = $depot->getDepotStats();
                       return $depot;
                   });
    }
    
    /**
     * Find depot by code with complete information
     */
    public function findDepotWithInfo(string $depotCode): ?Depot
    {
        return Depot::with(['vehicles.primaryStaff', 'users'])
                   ->where('depot_kod', $depotCode)
                   ->first();
    }
    
    /**
     * Get depot performance metrics
     */
    public function getDepotPerformanceMetrics(string $depotCode, array $dateRange = []): array
    {
        $depot = Depot::where('depot_kod', $depotCode)->first();
        
        if (!$depot) {
            return ['error' => 'Depot not found'];
        }
        
        $query = ServiceRequest::where('Building', $depotCode);
        
        if (!empty($dateRange['start'])) {
            $query->where('Datereceived', '>=', $dateRange['start']);
        }
        
        if (!empty($dateRange['end'])) {
            $query->where('Datereceived', '<=', $dateRange['end']);
        }
        
        $services = $query->withValidDates()->get();
        
        return [
            'depot_info' => $depot->formatted_info,
            'total_services' => $services->count(),
            'completed_services' => $services->where('Status', 3)->count(),
            'average_response_time' => $this->calculateAverageResponseTime($services),
            'service_breakdown' => $services->groupBy('MrType')->map->count(),
            'monthly_trends' => $this->getMonthlyServiceTrends($services),
            'performance_score' => $this->calculatePerformanceScore($services)
        ];
    }
    
    /**
     * Calculate average response time for services
     */
    private function calculateAverageResponseTime(Collection $services): float
    {
        $responseTimes = $services->filter(function ($service) {
                                     return $service->Datereceived && $service->responseDate;
                                 })
                                 ->map(function ($service) {
                                     return Carbon::parse($service->Datereceived)
                                                  ->diffInHours(Carbon::parse($service->responseDate));
                                 });
        
        return $responseTimes->count() > 0 ? round($responseTimes->avg(), 1) : 0;
    }
    
    /**
     * Get monthly service trends for depot
     */
    private function getMonthlyServiceTrends(Collection $services): array
    {
        return $services->groupBy(function ($service) {
                           return Carbon::parse($service->Datereceived)->format('Y-m');
                       })
                       ->map(function ($group, $month) {
                           return [
                               'month' => $month,
                               'total_services' => $group->count(),
                               'maintenance_services' => $group->where('MrType', '!=', 2)->count(),
                               'cleaning_services' => $group->where('MrType', 2)->count()
                           ];
                       })
                       ->sortKeys()
                       ->values()
                       ->toArray();
    }
    
    /**
     * Calculate performance score for depot
     */
    private function calculatePerformanceScore(Collection $services): array
    {
        $totalServices = $services->count();
        
        if ($totalServices === 0) {
            return ['score' => 0, 'grade' => 'N/A', 'factors' => []];
        }
        
        $completedServices = $services->where('Status', 3)->count();
        $completionRate = ($completedServices / $totalServices) * 100;
        
        $overdueServices = $services->filter(function ($service) {
            return $service->isOverdueForResponse();
        })->count();
        
        $overdueRate = ($overdueServices / $totalServices) * 100;
        
        // Calculate score (0-100)
        $score = $completionRate - ($overdueRate * 2);
        $score = max(0, min(100, $score));
        
        $grade = match(true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F'
        };
        
        return [
            'score' => round($score, 1),
            'grade' => $grade,
            'factors' => [
                'completion_rate' => round($completionRate, 1),
                'overdue_rate' => round($overdueRate, 1),
                'total_services' => $totalServices
            ]
        ];
    }
}