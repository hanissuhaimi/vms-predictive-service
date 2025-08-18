<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\ServiceRequest;

class FleetAnalysis extends Model
{
    protected $table = 'ServiceRequest';
    
    /**
     * Get fleet-wide vehicle statistics with corrected logic
     */
    public static function getFleetOverview()
    {
        return [
            'total_active_vehicles' => self::getTotalActiveVehicles(),
            'total_vehicles_with_services' => self::getTotalVehiclesWithServices(),
            'vehicles_skipped_major_service' => self::getVehiclesSkippedMajorService(),
            'vehicles_skipped_minor_service' => self::getVehiclesSkippedMinorService(),
            'breakdown_service_types' => self::getBreakdownServiceTypes(),
            'total_services' => self::getTotalServices(),
            'average_services_per_vehicle' => self::getAverageServicesPerVehicle(),
            'fleet_health_score' => self::calculateFleetHealthScore()
        ];
    }
    
    /**
     * Get total number of active vehicles with any service requests
     * Based on Vehicle_profile.Status = 1 and having any ServiceRequest records
     */
    public static function getTotalActiveVehicles()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('vp.Status', 1)
            ->distinct('vp.vh_regno')
            ->count('vp.vh_regno');
    }
    
    /**
     * CORRECTED: Get total number of active vehicles that have undergone services (Status = 2)
     * Based on Vehicle_profile.Status = 1 and ServiceRequest.Status = 2
     */
    public static function getTotalVehiclesWithServices()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2)
            ->where('vp.Status', 1)
            ->distinct('vp.vh_regno')
            ->count('vp.vh_regno');
    }
    
    /**
     * CORRECTED: Get active vehicles that have skipped major services
     * Logic: Active vehicles that exist but haven't had recent major service
     */
    public static function getVehiclesSkippedMajorService()
    {
        try {
            // Get all active vehicles that have service records
            $activeVehiclesWithServices = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('vp.Status', 1)
                ->distinct('vp.vh_regno')
                ->pluck('vp.vh_regno');
            
            if ($activeVehiclesWithServices->isEmpty()) {
                return 0;
            }
            
            // Get vehicles that had major services in the last 12 months
            $vehiclesWithRecentMajorService = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->whereIn('vp.vh_regno', $activeVehiclesWithServices)
                ->where('vp.Status', 1)
                ->where('sr.Status', 2) // Services that have been completed
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['1'])  // Repair
                        ->orWhereRaw('TRIM(sr.MrType) = ?', ['3']); // Maintenance
                })
                ->where('sr.Datereceived', '>=', Carbon::now()->subMonths(12))
                ->whereNotNull('sr.Datereceived')
                ->distinct('vp.vh_regno')
                ->pluck('vp.vh_regno');
            
            // Return count of active vehicles that haven't had recent major service
            $skippedCount = $activeVehiclesWithServices->count() - $vehiclesWithRecentMajorService->count();
            return max(0, $skippedCount);
            
        } catch (\Exception $e) {
            \Log::error('Error in getVehiclesSkippedMajorService: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * CORRECTED: Get active vehicles that have skipped minor services
     * Logic: Active vehicles that exist but haven't had recent minor service
     */
    public static function getVehiclesSkippedMinorService()
    {
        try {
            // Get all active vehicles that have service records
            $activeVehiclesWithServices = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('vp.Status', 1)
                ->distinct('vp.vh_regno')
                ->pluck('vp.vh_regno');
            
            if ($activeVehiclesWithServices->isEmpty()) {
                return 0;
            }
            
            // Get vehicles that had minor services in the last 6 months
            $vehiclesWithRecentMinorService = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->whereIn('vp.vh_regno', $activeVehiclesWithServices)
                ->where('vp.Status', 1)
                ->where('sr.Status', 2) // Services that have been completed
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['2'])  // Cleaning/Washing
                        ->orWhereRaw('TRIM(sr.MrType) = ?', ['4']); // Inspection
                })
                ->where('sr.Datereceived', '>=', Carbon::now()->subMonths(6))
                ->whereNotNull('sr.Datereceived')
                ->distinct('vp.vh_regno')
                ->pluck('vp.vh_regno');
            
            // Return count of active vehicles that haven't had recent minor service
            $skippedCount = $activeVehiclesWithServices->count() - $vehiclesWithRecentMinorService->count();
            return max(0, $skippedCount);
            
        } catch (\Exception $e) {
            \Log::error('Error in getVehiclesSkippedMinorService: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * CORRECTED: Get service types most commonly performed on vehicles with frequent services
     * Now correctly interprets Status = 2 as completed services
     */
    public static function getBreakdownServiceTypes()
    {
        try {
            // Get all active vehicles with their completed service counts
            $vehicleServiceCounts = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->groupBy('vp.vh_regno')
                ->selectRaw('vp.vh_regno as Vehicle, COUNT(*) as service_count')
                ->get();
            
            if ($vehicleServiceCounts->isEmpty()) {
                return [];
            }
            
            // Calculate average services per vehicle
            $avgServicesPerVehicle = $vehicleServiceCounts->avg('service_count');
            
            // Get vehicles with above-average service frequency (potentially problematic vehicles)
            $frequentServiceVehicles = $vehicleServiceCounts
                ->where('service_count', '>', $avgServicesPerVehicle)
                ->pluck('Vehicle');
            
            if ($frequentServiceVehicles->isEmpty()) {
                // If no vehicles are above average, get top 25% of vehicles by service count
                $threshold = $vehicleServiceCounts->sortByDesc('service_count')
                    ->take(ceil($vehicleServiceCounts->count() * 0.25))
                    ->min('service_count');
                
                $frequentServiceVehicles = $vehicleServiceCounts
                    ->where('service_count', '>=', $threshold)
                    ->pluck('Vehicle');
            }
            
            if ($frequentServiceVehicles->isEmpty()) {
                return [];
            }
            
            // Get service type distribution for vehicles with frequent services
            $serviceTypeData = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->whereIn('vp.vh_regno', $frequentServiceVehicles)
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->whereNotNull('sr.MrType')
                ->groupBy(DB::raw('TRIM(sr.MrType)'))
                ->selectRaw('TRIM(sr.MrType) as clean_mr_type, COUNT(*) as count')
                ->get();
            
            $totalServices = $serviceTypeData->sum('count');
            
            if ($totalServices == 0) {
                return [];
            }
            
            return $serviceTypeData->mapWithKeys(function ($item) use ($totalServices) {
                $type = match(trim($item->clean_mr_type)) {
                    '1' => 'Repair',
                    '2' => 'Cleaning/Washing',
                    '3' => 'Maintenance',
                    '4' => 'Inspection',
                    '5' => 'Other Service',
                    default => 'Unknown'
                };
                
                $percentage = round(($item->count / $totalServices) * 100, 1);
                
                return [$type => [
                    'count' => $item->count,
                    'percentage' => $percentage
                ]];
            })->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Error in getBreakdownServiceTypes: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * CORRECTED: Get detailed analysis of vehicles with frequent services
     */
    public static function getFrequentServiceVehicles($limit = 20)
    {
        try {
            // Get vehicle service counts for active vehicles with completed services
            $vehicleServiceCounts = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->groupBy('vp.vh_regno')
                ->selectRaw('vp.vh_regno as Vehicle, COUNT(*) as service_count')
                ->get();

            if ($vehicleServiceCounts->isEmpty()) {
                return collect([]);
            }

            $avgServicesPerVehicle = $vehicleServiceCounts->avg('service_count');
            $threshold = max(1, round($avgServicesPerVehicle));

            return DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->groupBy('vp.vh_regno')
                ->selectRaw('vp.vh_regno as Vehicle')
                ->selectRaw('COUNT(*) as total_services')
                ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) = "1" THEN 1 END) as repairs')
                ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) = "2" THEN 1 END) as cleaning_services')
                ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) = "3" THEN 1 END) as maintenance')
                ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) = "4" THEN 1 END) as inspections')
                ->selectRaw('MAX(sr.Datereceived) as last_service')
                ->selectRaw('MAX(CAST(sr.Odometer AS UNSIGNED)) as highest_mileage')
                ->havingRaw('COUNT(*) >= ?', [$threshold])
                ->orderBy('total_services', 'desc')
                ->limit($limit)
                ->get();
                
        } catch (\Exception $e) {
            \Log::error('Error in getFrequentServiceVehicles: ' . $e->getMessage());
            return collect([]);
        }
    }
    
     /**
     * CORRECTED: Get vehicles needing major service attention (SQL Server compatible)
     */
    public static function getVehiclesNeedingMajorService()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2) // Requested services
            ->where('vp.Status', 1) // Active vehicles
            ->groupBy('vp.vh_regno')
            ->selectRaw('vp.vh_regno as Vehicle')
            ->selectRaw('MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'1\',\'3\') THEN sr.Datereceived END) as last_major_service')
            ->selectRaw('DATEDIFF(day, MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'1\',\'3\') THEN sr.Datereceived END), GETDATE()) as days_since_major')
            ->selectRaw('COUNT(*) as total_services')
            ->havingRaw('DATEDIFF(day, MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'1\',\'3\') THEN sr.Datereceived END), GETDATE()) > 365 OR MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'1\',\'3\') THEN sr.Datereceived END) IS NULL')
            ->orderBy('days_since_major', 'desc')
            ->get();
    }

    /**
     * CORRECTED: Get vehicles needing minor service attention (SQL Server compatible)
     */
    public static function getVehiclesNeedingMinorService()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2) // Requested services
            ->where('vp.Status', 1) // Active vehicles
            ->groupBy('vp.vh_regno')
            ->selectRaw('vp.vh_regno as Vehicle')
            ->selectRaw('MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'2\',\'4\') THEN sr.Datereceived END) as last_minor_service')
            ->selectRaw('DATEDIFF(day, MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'2\',\'4\') THEN sr.Datereceived END), GETDATE()) as days_since_minor')
            ->selectRaw('COUNT(*) as total_services')
            ->havingRaw('DATEDIFF(day, MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'2\',\'4\') THEN sr.Datereceived END), GETDATE()) > 180 OR MAX(CASE WHEN LTRIM(RTRIM(sr.MrType)) IN (\'2\',\'4\') THEN sr.Datereceived END) IS NULL')
            ->orderBy('days_since_minor', 'desc')
            ->get();
    }
    
    /**
     * CORRECTED: Get total completed services
     */
    public static function getTotalServices()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->count();
    }
    
    /**
     * CORRECTED: Get completed maintenance services only (excluding cleaning)
     */
    public static function getMaintenanceServices()
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->where(function($query) {
                $query->whereRaw('TRIM(sr.MrType) = ?', ['1'])
                      ->orWhereRaw('TRIM(sr.MrType) = ?', ['3'])
                      ->orWhereRaw('TRIM(sr.MrType) = ?', ['4']);
            })
            ->count();
    }
    
    /**
     * CORRECTED: Calculate average services per vehicle using vehicles with completed services
     */
    public static function getAverageServicesPerVehicle()
    {
        $totalVehiclesWithServices = self::getTotalVehiclesWithServices();
        $maintenanceServices = self::getMaintenanceServices();
        
        return $totalVehiclesWithServices > 0 ? round($maintenanceServices / $totalVehiclesWithServices, 1) : 0;
    }
    
    /**
     * CORRECTED: Calculate fleet health score using vehicles with completed services
     */
    public static function calculateFleetHealthScore()
    {
        try {
            $totalVehiclesWithServices = self::getTotalVehiclesWithServices();
            
            if ($totalVehiclesWithServices == 0) return 0;
            
            $scores = [];
            
            // 1. Recent maintenance activity (40% weight) - Extended to 4 months
            $recentMaintenanceVehicles = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->whereNotNull('sr.Datereceived')
                ->where('sr.Datereceived', '>=', Carbon::now()->subMonths(4))
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['1'])
                          ->orWhereRaw('TRIM(sr.MrType) = ?', ['3'])
                          ->orWhereRaw('TRIM(sr.MrType) = ?', ['4']);
                })
                ->distinct('vp.vh_regno')
                ->count('vp.vh_regno');
            
            $scores['recent_activity'] = ($recentMaintenanceVehicles / $totalVehiclesWithServices) * 40;
            
            // 2. Major service compliance (30% weight)
            $vehiclesSkippedMajor = self::getVehiclesSkippedMajorService();
            $totalActiveVehicles = self::getTotalActiveVehicles();
            $majorCompliance = $totalActiveVehicles > 0 ? 
                max(0, (($totalActiveVehicles - $vehiclesSkippedMajor) / $totalActiveVehicles) * 30) : 0;
            $scores['major_compliance'] = $majorCompliance;
            
            // 3. Minor service compliance (20% weight)
            $vehiclesSkippedMinor = self::getVehiclesSkippedMinorService();
            $minorCompliance = $totalActiveVehicles > 0 ? 
                max(0, (($totalActiveVehicles - $vehiclesSkippedMinor) / $totalActiveVehicles) * 20) : 0;
            $scores['minor_compliance'] = $minorCompliance;
            
            // 4. Service variety/balance (10% weight)
            $serviceTypes = DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->whereNotNull('sr.MrType')
                ->groupBy(DB::raw('TRIM(sr.MrType)'))
                ->count();
            
            $varietyScore = min(10, $serviceTypes * 2.5);
            $scores['variety'] = $varietyScore;
            
            $finalScore = array_sum($scores);
            
            return round(min(100, max(0, $finalScore)));
            
        } catch (\Exception $e) {
            \Log::error('Error calculating fleet health score: ' . $e->getMessage());
            return 50;
        }
    }
    
    /**
     * CORRECTED: Get service type distribution for completed services with clean percentages
     */
    public static function getServiceTypeDistribution()
    {
        $totalServices = DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->count();
            
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->selectRaw('LTRIM(RTRIM(sr.MrType)) as clean_mr_type') // SQL Server compatible
            ->selectRaw('COUNT(*) as count')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->whereNotNull('sr.MrType')
            ->groupBy(DB::raw('LTRIM(RTRIM(sr.MrType))')) // SQL Server compatible
            ->get()
            ->mapWithKeys(function ($item) use ($totalServices) {
                $type = match(trim($item->clean_mr_type)) {
                    '1' => 'Repair',
                    '2' => 'Cleaning/Washing', 
                    '3' => 'Maintenance',
                    '4' => 'Inspection',
                    '5' => 'Other Service',
                    default => 'Unknown'
                };
                
                // Calculate percentage with proper formatting
                $percentage = $totalServices > 0 ? ($item->count / $totalServices) * 100 : 0;
                
                return [$type => [
                    'count' => $item->count,
                    'percentage' => round($percentage, 1) // Clean rounding to 1 decimal place
                ]];
            });
    }

    /**
     * CORRECTED: Enhanced debug method with corrected metrics
     */
    public static function getDebugInfo()
    {
        $sixMonthsAgo = Carbon::now()->subMonths(6);
        $twelveMonthsAgo = Carbon::now()->subMonths(12);
        
        return [
            'total_records' => ServiceRequest::count(),
            'total_active_vehicles_in_profile' => DB::table('Vehicle_profile')->where('Status', 1)->count(),
            'total_active_vehicles_with_service_requests' => self::getTotalActiveVehicles(),
            'active_vehicles_with_completed_services' => self::getTotalVehiclesWithServices(),
            'vehicles_skipped_major_service' => self::getVehiclesSkippedMajorService(),
            'vehicles_skipped_minor_service' => self::getVehiclesSkippedMinorService(),
            'mr_types_trimmed' => DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->groupBy(DB::raw('TRIM(sr.MrType)'))
                ->selectRaw('TRIM(sr.MrType) as clean_type, COUNT(*) as count')
                ->get(),
            'recent_major_services_6mo' => DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['1'])
                          ->orWhereRaw('TRIM(sr.MrType) = ?', ['3']);
                })
                ->where('sr.Datereceived', '>=', $sixMonthsAgo)
                ->whereNotNull('sr.Datereceived')
                ->count(),
            'recent_major_services_12mo' => DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['1'])
                          ->orWhereRaw('TRIM(sr.MrType) = ?', ['3']);
                })
                ->where('sr.Datereceived', '>=', $twelveMonthsAgo)
                ->whereNotNull('sr.Datereceived')
                ->count(),
            'recent_minor_services_6mo' => DB::table('ServiceRequest as sr')
                ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
                ->where('sr.Status', 2) // Completed services
                ->where('vp.Status', 1) // Active vehicles
                ->where(function($query) {
                    $query->whereRaw('TRIM(sr.MrType) = ?', ['2'])
                          ->orWhereRaw('TRIM(sr.MrType) = ?', ['4']);
                })
                ->where('sr.Datereceived', '>=', $sixMonthsAgo)
                ->whereNotNull('sr.Datereceived')
                ->count(),
            'date_ranges' => [
                'current' => Carbon::now()->format('Y-m-d'),
                'six_months_ago' => $sixMonthsAgo->format('Y-m-d'),
                'twelve_months_ago' => $twelveMonthsAgo->format('Y-m-d')
            ]
        ];
    }
    
    // CORRECTED: Other existing methods with proper logic
    public static function getTotalVehicles()
    {
        return DB::table('Vehicle_profile')->where('Status', 1)->count();
    }
    
    public static function getHighMaintenanceVehicles($limit = 10)
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->select('vp.vh_regno as Vehicle')
            ->selectRaw('COUNT(*) as service_count')
            ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) IN ("1", "3", "4") THEN 1 END) as maintenance_count')
            ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) = "2" THEN 1 END) as cleaning_count')
            ->selectRaw('MAX(sr.Datereceived) as last_service')
            ->selectRaw('MAX(CAST(sr.Odometer AS UNSIGNED)) as highest_mileage')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->groupBy('vp.vh_regno')
            ->orderBy('maintenance_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    public static function getVehiclesNeedingAttention($limit = 15)
    {
        return DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->select('vp.vh_regno as Vehicle')
            ->selectRaw('MAX(sr.Datereceived) as last_service_date')
            ->selectRaw('DATEDIFF(CURDATE(), MAX(sr.Datereceived)) as days_since_service')
            ->selectRaw('COUNT(CASE WHEN TRIM(sr.MrType) IN ("1", "3", "4") THEN 1 END) as maintenance_count')
            ->selectRaw('MAX(CAST(sr.Odometer AS UNSIGNED)) as last_odometer')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->whereNotNull('sr.Datereceived')
            ->groupBy('vp.vh_regno')
            ->having('days_since_service', '>', 90)
            ->orderBy('days_since_service', 'desc')
            ->limit($limit)
            ->get();
    }
    
    public static function getCostTrends()
    {
        $serviceTypeCosts = [
            '1' => 450, // Repair
            '2' => 80,  // Cleaning/Washing
            '3' => 350, // Maintenance
            '4' => 150, // Inspection
            '5' => 200  // Other Service
        ];
        
        $monthlyCosts = DB::table('ServiceRequest as sr')
            ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
            ->selectRaw('
                YEAR(sr.Datereceived) as year,
                MONTH(sr.Datereceived) as month,
                COUNT(CASE WHEN TRIM(sr.MrType) = "1" THEN 1 END) * 450 +
                COUNT(CASE WHEN TRIM(sr.MrType) = "2" THEN 1 END) * 80 +
                COUNT(CASE WHEN TRIM(sr.MrType) = "3" THEN 1 END) * 350 +
                COUNT(CASE WHEN TRIM(sr.MrType) = "4" THEN 1 END) * 150 +
                COUNT(CASE WHEN TRIM(sr.MrType) = "5" THEN 1 END) * 200 as estimated_cost
            ')
            ->where('sr.Status', 2) // Completed services
            ->where('vp.Status', 1) // Active vehicles
            ->whereNotNull('sr.Datereceived')
            ->where('sr.Datereceived', '>=', Carbon::now()->subMonths(6))
            ->groupBy(DB::raw('YEAR(sr.Datereceived), MONTH(sr.Datereceived)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        $avgMonthlyCost = $monthlyCosts->avg('estimated_cost');
        $totalVehicles = self::getTotalVehiclesWithServices();
        
        return [
            'estimated_monthly_cost' => round($avgMonthlyCost ?? 0),
            'cost_per_vehicle' => $totalVehicles > 0 ? round(($avgMonthlyCost ?? 0) / $totalVehicles) : 0,
            'trend' => $monthlyCosts->count() > 1 ? 'variable' : 'stable',
            'monthly_breakdown' => $monthlyCosts
        ];
    }
}