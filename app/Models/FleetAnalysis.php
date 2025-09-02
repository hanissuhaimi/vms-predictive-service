<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FleetAnalysis extends Model
{
    protected $table = 'service_requests'; // Uses existing ServiceRequest table
    
    /**
     * Get fleet-wide vehicle statistics
     */
    public static function getFleetOverview()
    {
        return [
            'total_vehicles' => self::getTotalVehicles(),
            'total_services' => self::getTotalServices(),
            'average_services_per_vehicle' => self::getAverageServicesPerVehicle(),
            'fleet_health_score' => self::calculateFleetHealthScore()
        ];
    }
    
    /**
     * Get total number of unique vehicles from ServiceRequest_Sample data
     */
    public static function getTotalVehicles()
    {
        return ServiceRequest::distinct('Vehicle')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->count();
    }
    
    /**
     * Get total services across all vehicles from ServiceRequest_Sample data
     */
    public static function getTotalServices()
    {
        return ServiceRequest::count();
    }
    
    /**
     * Get maintenance services only (excluding cleaning)
     */
    public static function getMaintenanceServices()
    {
        return ServiceRequest::whereIn('MrType', ['1', '3', '4'])
            ->count(); // Repairs, Maintenance, Inspections
    }
    
    /**
     * Calculate average services per vehicle (maintenance only)
     */
    public static function getAverageServicesPerVehicle()
    {
        $totalVehicles = self::getTotalVehicles();
        $maintenanceServices = self::getMaintenanceServices();
        
        return $totalVehicles > 0 ? round($maintenanceServices / $totalVehicles, 1) : 0;
    }
    
    /**
     * Calculate fleet health score based on ServiceRequest_Sample data (0-100)
     */
    public static function calculateFleetHealthScore()
    {
        try {
            // Count vehicles with recent maintenance activity (last 60 days)
            $recentMaintenanceVehicles = ServiceRequest::distinct('Vehicle')
                ->whereNotNull('Vehicle')
                ->where('Vehicle', '!=', '')
                ->where('Vehicle', '!=', '0')
                ->whereNotNull('Datereceived')
                ->where('Datereceived', '>=', Carbon::now()->subDays(60))
                ->whereIn('MrType', ['1', '3', '4']) // Maintenance activities only
                ->count();
            
            $totalVehicles = self::getTotalVehicles();
            
            if ($totalVehicles == 0) return 0;
            
            // Base score on recent maintenance activity
            $activityScore = ($recentMaintenanceVehicles / $totalVehicles) * 100;
            
            // Adjust score based on service quality indicators
            $totalMaintenanceServices = self::getMaintenanceServices();
            $totalCleaningServices = ServiceRequest::where('MrType', '2')->count();
            
            // Higher cleaning ratio indicates good preventive care
            $cleaningRatio = $totalMaintenanceServices > 0 ? 
                $totalCleaningServices / $totalMaintenanceServices : 0;
            
            // Bonus for good cleaning practices (up to 10 points)
            $cleaningBonus = min(10, $cleaningRatio * 20);
            
            $finalScore = min(100, $activityScore + $cleaningBonus);
            
            return round($finalScore);
            
        } catch (\Exception $e) {
            return 50; // Default middle score if calculation fails
        }
    }
    
    /**
     * Get vehicles with high maintenance frequency from ServiceRequest_Sample data
     */
    public static function getHighMaintenanceVehicles($limit = 10)
    {
        return ServiceRequest::select('Vehicle')
            ->selectRaw('COUNT(*) as service_count')
            ->selectRaw('COUNT(CASE WHEN MrType IN (\'1\', \'3\', \'4\') THEN 1 END) as maintenance_count')
            ->selectRaw('COUNT(CASE WHEN MrType = \'2\' THEN 1 END) as cleaning_count')
            ->selectRaw('MAX(Datereceived) as last_service')
            ->selectRaw('MAX(CAST(Odometer AS FLOAT)) as highest_mileage')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->groupBy('Vehicle')
            ->orderBy('maintenance_count', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get maintenance cost trends based on ServiceRequest_Sample data
     */
    public static function getCostTrends()
    {
        // Calculate estimated costs based on service types and frequency
        $serviceTypeCosts = [
            '1' => 350, // Repair - estimated average cost
            '2' => 80,  // Cleaning - estimated cost
            '3' => 250, // Maintenance - estimated cost
            '4' => 150  // Inspection - estimated cost
        ];
        
        $monthlyCosts = ServiceRequest::selectRaw('
                YEAR(Datereceived) as year,
                MONTH(Datereceived) as month,
                COUNT(CASE WHEN MrType = \'1\' THEN 1 END) * 350 +
                COUNT(CASE WHEN MrType = \'2\' THEN 1 END) * 80 +
                COUNT(CASE WHEN MrType = \'3\' THEN 1 END) * 250 +
                COUNT(CASE WHEN MrType = \'4\' THEN 1 END) * 150 as estimated_cost
            ')
            ->whereNotNull('Datereceived')
            ->where('Datereceived', '>=', Carbon::now()->subMonths(6))
            ->groupBy(DB::raw('YEAR(Datereceived), MONTH(Datereceived)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get();
        
        $avgMonthlyCost = $monthlyCosts->avg('estimated_cost');
        $totalVehicles = self::getTotalVehicles();
        
        return [
            'estimated_monthly_cost' => round($avgMonthlyCost ?? 0),
            'cost_per_vehicle' => $totalVehicles > 0 ? round(($avgMonthlyCost ?? 0) / $totalVehicles) : 0,
            'trend' => $monthlyCosts->count() > 1 ? 'variable' : 'stable',
            'monthly_breakdown' => $monthlyCosts
        ];
    }
    
    /**
     * Get service type distribution across fleet from ServiceRequest_Sample data
     */
    public static function getServiceTypeDistribution()
    {
        return ServiceRequest::select('MrType')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM ServiceRequest)), 1) as percentage')
            ->whereNotNull('MrType')
            ->groupBy('MrType')
            ->get()
            ->mapWithKeys(function ($item) {
                $type = match($item->MrType) {
                    '1' => 'Repair',
                    '2' => 'Cleaning/Washing', 
                    '3' => 'Maintenance',
                    '4' => 'Inspection',
                    default => 'Other'
                };
                return [$type => [
                    'count' => $item->count,
                    'percentage' => $item->percentage
                ]];
            });
    }
    
    /**
     * Get vehicles that may need attention based on ServiceRequest_Sample data
     */
    public static function getVehiclesNeedingAttention($limit = 15)
    {
        return ServiceRequest::select('Vehicle')
            ->selectRaw('MAX(Datereceived) as last_service_date')
            ->selectRaw('DATEDIFF(CURDATE(), MAX(Datereceived)) as days_since_service')
            ->selectRaw('COUNT(CASE WHEN MrType IN (\'1\', \'3\', \'4\') THEN 1 END) as maintenance_count')
            ->selectRaw('MAX(CAST(Odometer AS FLOAT)) as last_odometer')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->whereNotNull('Datereceived')
            ->groupBy('Vehicle')
            ->having('days_since_service', '>', 90) // No service in 90+ days
            ->orderBy('days_since_service', 'desc')
            ->limit($limit)
            ->get();
    }
}