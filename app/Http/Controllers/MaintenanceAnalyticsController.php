<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MaintenanceAnalyticsController extends Controller
{
    /**
     * Simplified Analytics Dashboard using ALL data (no date filtering)
     */
    public function dashboard(Request $request)
    {
        // Override execution time for large dataset processing
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '1024M');
        set_time_limit(0);
        
        try {
            Log::info("Analytics Dashboard accessed - Processing ALL fleet data (88K+ records)");

            // Get fleet overview using direct DB queries
            $fleetOverview = $this->getFleetOverviewDirect();
            
            // Get ALL service statistics (no date filtering)
            $allServicesData = $this->getAllServicesDataDirect();

            // Get maintenance trends using ALL historical data
            $maintenanceTrends = $this->getAllMaintenanceTrendsDirect();
            
            // Get service type breakdown using ALL data
            $serviceTypeBreakdown = $this->getAllServiceTypeBreakdownDirect();

            Log::info("Analytics Dashboard completed - Analyzed " . number_format($allServicesData['total_count']) . " total records");

            return view('analytics.dashboard', [
                'fleet_overview' => $fleetOverview,
                'services_data' => $allServicesData,
                'maintenance_trends' => $maintenanceTrends,
                'service_type_breakdown' => $serviceTypeBreakdown,
                'date_range' => [
                    'start' => $allServicesData['earliest_service'] ?? 'N/A',
                    'end' => $allServicesData['latest_service'] ?? 'N/A'
                ],
                'page_title' => 'Fleet Analytics Dashboard - Complete Analysis',
                'processing_message' => 'Successfully analyzed ' . number_format($allServicesData['total_count']) . ' maintenance records (complete database)'
            ]);

        } catch (\Exception $e) {
            Log::error("Analytics dashboard failed: " . $e->getMessage());

            // Return simple error page
            return view('analytics.dashboard', [
                'error' => 'Unable to process fleet analytics: ' . $e->getMessage(),
                'page_title' => 'Fleet Analytics Dashboard - Error',
                'fleet_overview' => $this->getDefaultFleetOverview(),
                'services_data' => ['total_count' => 0],
                'maintenance_trends' => ['monthly_trends' => []],
                'service_type_breakdown' => [],
                'date_range' => [
                    'start' => 'N/A',
                    'end' => 'N/A'
                ]
            ]);
        }
    }

    /**
     * Get fleet overview using ALL data (no date filtering)
     */
    private function getFleetOverviewDirect()
    {
        try {
            Log::info("Getting fleet overview with ALL data");
            
            // Total vehicles
            $totalVehicles = DB::table('Vehicle_profile')
                ->where('Status', 1)
                ->count();
            
            // Total services (ALL TIME)
            $totalServicesAllTime = DB::table('ServiceRequest')->count();
            
            // Maintenance services (MrType = 1) - ALL TIME
            $maintenanceServices = DB::table('ServiceRequest')
                ->where('MrType', 1)
                ->count();
            
            // Cleaning services (MrType = 2) - ALL TIME
            $cleaningServices = DB::table('ServiceRequest')
                ->where('MrType', 2)
                ->count();
            
            // Tire services (MrType = 3) - ALL TIME
            $tireServices = DB::table('ServiceRequest')
                ->where('MrType', 3)
                ->count();
            
            // Calculate fleet health score (simple calculation)
            $fleetHealthScore = $totalVehicles > 0 ? 
                max(0, min(100, 90 - (($maintenanceServices / max($totalVehicles * 10, 1)) * 100))) : 100;
            
            return [
                'total_vehicles' => $totalVehicles,
                'active_vehicles' => $totalVehicles,
                'vehicles_under_maintenance' => 0,
                'vehicles_needing_maintenance' => 0,
                'utilization_rate' => 85,
                'fleet_health_score' => round($fleetHealthScore),
                'total_services_all_time' => $totalServicesAllTime,
                'maintenance_services_all_time' => $maintenanceServices,
                'cleaning_services_all_time' => $cleaningServices,
                'tire_services_all_time' => $tireServices,
                'service_efficiency' => $totalServicesAllTime > 0 ? 
                    round(($maintenanceServices / $totalServicesAllTime) * 100, 1) : 0
            ];
            
        } catch (\Exception $e) {
            Log::error("Fleet overview error: " . $e->getMessage());
            return $this->getDefaultFleetOverview();
        }
    }

    /**
     * Get ALL services data (no date filtering)
     */
    private function getAllServicesDataDirect()
    {
        try {
            Log::info("Getting ALL services data from complete database");
            
            // Total services in entire database
            $totalCount = DB::table('ServiceRequest')->count();
            
            // Services by MR Type (all time)
            $servicesByType = DB::table('ServiceRequest')
                ->select('MrType', DB::raw('COUNT(*) as count'))
                ->groupBy('MrType')
                ->pluck('count', 'MrType')
                ->toArray();
            
            // Get date range of all data
            $dateRange = DB::table('ServiceRequest')
                ->selectRaw('MIN(CAST(Datereceived AS DATE)) as earliest, MAX(CAST(Datereceived AS DATE)) as latest')
                ->first();
            
            return [
                'total_count' => $totalCount,
                'by_mr_type' => $servicesByType,
                'earliest_service' => $dateRange->earliest ?? 'Unknown',
                'latest_service' => $dateRange->latest ?? 'Unknown'
            ];
            
        } catch (\Exception $e) {
            Log::error("All services data error: " . $e->getMessage());
            return ['total_count' => 0, 'by_mr_type' => [], 'earliest_service' => 'N/A', 'latest_service' => 'N/A'];
        }
    }

    /**
     * Get ALL maintenance trends (complete historical data)
     */
    private function getAllMaintenanceTrendsDirect()
    {
        try {
            Log::info("Getting ALL maintenance trends from complete historical data");
            
            // Get monthly trends for ALL data
            $monthlyTrends = DB::table('ServiceRequest')
                ->select(
                    DB::raw('YEAR(Datereceived) as year'),
                    DB::raw('MONTH(Datereceived) as month'),
                    DB::raw('COUNT(*) as total_services'),
                    DB::raw('COUNT(CASE WHEN MrType = 1 THEN 1 END) as maintenance_services'),
                    DB::raw('COUNT(CASE WHEN MrType = 2 THEN 1 END) as cleaning_services'),
                    DB::raw('COUNT(CASE WHEN MrType = 3 THEN 1 END) as tire_services')
                )
                ->whereNotNull('Datereceived')
                ->groupBy(DB::raw('YEAR(Datereceived)'), DB::raw('MONTH(Datereceived)'))
                ->orderBy('year')
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    // Convert stdClass to array and add month_name
                    return [
                        'year' => $item->year,
                        'month' => $item->month,
                        'month_name' => Carbon::create($item->year, $item->month, 1)->format('M Y'),
                        'total_services' => $item->total_services,
                        'maintenance_services' => $item->maintenance_services,
                        'cleaning_services' => $item->cleaning_services,
                        'tire_services' => $item->tire_services
                    ];
                })
                ->toArray();
            
            return [
                'monthly_trends' => $monthlyTrends,
                'trend_direction' => $this->calculateTrendDirection($monthlyTrends),
                'average_monthly_services' => $this->calculateAverageMonthlyServices($monthlyTrends),
                'total_months_analyzed' => count($monthlyTrends)
            ];
            
        } catch (\Exception $e) {
            Log::error("All maintenance trends error: " . $e->getMessage());
            return [
                'monthly_trends' => [],
                'trend_direction' => 'stable',
                'average_monthly_services' => 0,
                'total_months_analyzed' => 0
            ];
        }
    }

    /**
     * Get ALL service type breakdown (complete database)
     */
    private function getAllServiceTypeBreakdownDirect()
    {
        try {
            Log::info("Getting service type breakdown from ALL data");
            
            // Total services in entire database
            $totalServices = DB::table('ServiceRequest')->count();
            
            // Breakdown by type for ALL data
            $breakdown = DB::table('ServiceRequest')
                ->select('MrType', DB::raw('COUNT(*) as count'))
                ->groupBy('MrType')
                ->orderBy('count', 'desc')
                ->get()
                ->map(function ($item) use ($totalServices) {
                    $typeName = $this->getMrTypeName($item->MrType);
                    return [
                        'type_id' => $item->MrType,
                        'type_name' => $typeName,
                        'count' => $item->count,
                        'percentage' => $totalServices > 0 ? round(($item->count / $totalServices) * 100, 1) : 0
                    ];
                })
                ->toArray();
            
            return $breakdown;
            
        } catch (\Exception $e) {
            Log::error("All service type breakdown error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get MR Type name mapping
     */
    private function getMrTypeName($mrType)
    {
        $types = [
            1 => 'Maintenance',
            2 => 'Cleaning/Washing',
            3 => 'Tires',
            4 => 'Rental',
            5 => 'Operation'
        ];
        
        return $types[$mrType] ?? "Type {$mrType}";
    }

    /**
     * Calculate trend direction with safe array handling
     */
    private function calculateTrendDirection($monthlyTrends)
    {
        try {
            if (count($monthlyTrends) < 2) return 'stable';
            
            $recent = array_slice($monthlyTrends, -3); // Last 3 months
            $older = array_slice($monthlyTrends, -6, 3); // 3 months before that
            
            if (empty($recent) || empty($older)) return 'stable';
            
            $recentAvg = array_sum(array_column($recent, 'total_services')) / count($recent);
            $olderAvg = array_sum(array_column($older, 'total_services')) / count($older);
            
            if ($olderAvg == 0) return 'stable';
            
            $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;
            
            if ($change > 10) return 'increasing';
            if ($change < -10) return 'decreasing';
            return 'stable';
            
        } catch (\Exception $e) {
            Log::error("Trend direction calculation error: " . $e->getMessage());
            return 'stable';
        }
    }

    /**
     * Calculate average monthly services with safe handling
     */
    private function calculateAverageMonthlyServices($monthlyTrends)
    {
        try {
            if (empty($monthlyTrends)) return 0;
            
            $total = array_sum(array_column($monthlyTrends, 'total_services'));
            return round($total / count($monthlyTrends), 1);
            
        } catch (\Exception $e) {
            Log::error("Average monthly services calculation error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get default fleet overview when data fails to load
     */
    private function getDefaultFleetOverview()
    {
        return [
            'total_vehicles' => 0,
            'active_vehicles' => 0,
            'vehicles_under_maintenance' => 0,
            'vehicles_needing_maintenance' => 0,
            'utilization_rate' => 0,
            'fleet_health_score' => 0,
            'total_services_all_time' => 0,
            'maintenance_services_all_time' => 0,
            'cleaning_services_all_time' => 0,
            'tire_services_all_time' => 0,
            'service_efficiency' => 0
        ];
    }
}