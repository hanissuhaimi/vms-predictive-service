<?php

namespace App\Repositories;

use App\Models\ServiceRequest;
use App\Models\VehicleProfile;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * Find active users with their workload
     */
    public function findActiveUsersWithWorkload(): Collection
    {
        return User::active()
                  ->with(['depot', 'primaryVehicles', 'secondaryVehicles'])
                  ->get()
                  ->map(function ($user) {
                      $user->workload_info = $user->getWorkloadInfo();
                      $user->performance_stats = $user->getPerformanceStats();
                      return $user;
                  });
    }
    
    /**
     * Get users by depot
     */
    public function getUsersByDepot(string $depotCode): Collection
    {
        return User::byDepot($depotCode)
                  ->active()
                  ->with('depot')
                  ->orderBy('FirstName')
                  ->get();
    }
    
    /**
     * Search users with filters
     */
    public function searchUsers(string $search, array $filters = []): Collection
    {
        $query = User::with('depot');
        
        if (!empty($search)) {
            $query->searchName($search);
        }
        
        if (!empty($filters['depot'])) {
            $query->byDepot($filters['depot']);
        }
        
        if (!empty($filters['role'])) {
            $query->byRole($filters['role']);
        }
        
        if (!empty($filters['designation'])) {
            $query->byDesignation($filters['designation']);
        }
        
        if (isset($filters['active']) && $filters['active']) {
            $query->active();
        }
        
        return $query->orderBy('FirstName')->get();
    }
    
    /**
     * Get user performance report
     */
    public function getUserPerformanceReport(string $userId, array $dateRange = []): array
    {
        $user = User::where('UID', $userId)->first();
        
        if (!$user) {
            return ['error' => 'User not found'];
        }
        
        $query = ServiceRequest::where('responsedBy', $userId);
        
        if (!empty($dateRange['start'])) {
            $query->where('responseDate', '>=', $dateRange['start']);
        }
        
        if (!empty($dateRange['end'])) {
            $query->where('responseDate', '<=', $dateRange['end']);
        }
        
        $services = $query->withValidDates()->get();
        
        return [
            'user_info' => [
                'name' => $user->full_name,
                'designation' => $user->Designation,
                'depot' => $user->depot_info
            ],
            'performance_metrics' => [
                'total_services_handled' => $services->count(),
                'completed_services' => $services->where('Status', 3)->count(),
                'average_response_time' => $this->calculateUserResponseTime($services),
                'workload_info' => $user->getWorkloadInfo()
            ],
            'service_breakdown' => $services->groupBy('MrType')->map->count(),
            'monthly_activity' => $this->getUserMonthlyActivity($services)
        ];
    }
    
    /**
     * Calculate user's average response time
     */
    private function calculateUserResponseTime(Collection $services): float
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
     * Get user's monthly activity
     */
    private function getUserMonthlyActivity(Collection $services): array
    {
        return $services->groupBy(function ($service) {
                           return Carbon::parse($service->responseDate ?? $service->Datereceived)->format('Y-m');
                       })
                       ->map(function ($group, $month) {
                           return [
                               'month' => $month,
                               'services_handled' => $group->count(),
                               'avg_priority' => round($group->avg('Priority'), 1)
                           ];
                       })
                       ->sortKeys()
                       ->values()
                       ->toArray();
    }
}