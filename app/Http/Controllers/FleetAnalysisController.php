<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use App\Models\FleetAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FleetAnalysisController extends Controller
{
    /**
     * Display the fleet analysis index page
     */
    public function index()
    {
        Log::info("Fleet Analysis - Index page accessed");
        
        // For now, return a blank page as requested
        // In the future, this will show fleet overview and analysis options
        return view('fleet.analysis.index');
    }

    /**
     * Analyze all vehicles in the fleet
     */
    public function analyzeAll()
    {
        Log::info("Fleet Analysis - Analyze All Vehicles started");
        
        try {
            // Get basic fleet statistics
            $fleetStats = $this->getFleetStatistics();
            
            // Get all unique vehicles
            $vehicles = $this->getAllUniqueVehicles();
            
            // Analyze vehicle performance patterns
            $performanceAnalysis = $this->analyzeFleetPerformance();
            
            // Get maintenance trends
            $maintenanceTrends = $this->getMaintenanceTrends();
            
            return view('fleet.analysis.all', compact(
                'fleetStats',
                'vehicles', 
                'performanceAnalysis',
                'maintenanceTrends'
            ));
            
        } catch (\Exception $e) {
            Log::error("Fleet Analysis error: " . $e->getMessage());
            
            return redirect()->route('fleet.analysis.index')
                ->with('error', 'Unable to analyze fleet. Please try again.');
        }
    }

    /**
     * Get basic fleet statistics from ServiceRequest_Sample data
     */
    private function getFleetStatistics()
    {
        return [
            'total_vehicles' => ServiceRequest::distinct('Vehicle')
                ->whereNotNull('Vehicle')
                ->where('Vehicle', '!=', '')
                ->where('Vehicle', '!=', '0')
                ->count(),
            
            'total_services' => ServiceRequest::count(),
            
            'maintenance_services' => ServiceRequest::whereIn('MrType', ['1', '3', '4'])
                ->count(), // Repairs, Maintenance, Inspections (excluding cleaning)
            
            'cleaning_services' => ServiceRequest::where('MrType', '2')
                ->count(),
                
            'active_vehicles' => ServiceRequest::distinct('Vehicle')
                ->whereNotNull('Vehicle')
                ->where('Vehicle', '!=', '')
                ->where('Vehicle', '!=', '0')
                ->whereNotNull('Datereceived')
                ->where('Datereceived', '>=', Carbon::now()->subMonths(6))
                ->count(),
                
            'recent_services' => ServiceRequest::whereNotNull('Datereceived')
                ->where('Datereceived', '>=', Carbon::now()->subDays(30))
                ->count(),
                
            'vehicles_with_odometer' => ServiceRequest::distinct('Vehicle')
                ->whereNotNull('Vehicle')
                ->where('Vehicle', '!=', '')
                ->where('Vehicle', '!=', '0')
                ->whereNotNull('Odometer')
                ->where('Odometer', '>', 0)
                ->count()
        ];
    }

    /**
     * Get all unique vehicles with service info from ServiceRequest_Sample data
     */
    private function getAllUniqueVehicles()
    {
        return ServiceRequest::select('Vehicle')
            ->selectRaw('COUNT(*) as total_services')
            ->selectRaw('COUNT(CASE WHEN MrType IN (\'1\', \'3\', \'4\') THEN 1 END) as maintenance_services')
            ->selectRaw('COUNT(CASE WHEN MrType = \'2\' THEN 1 END) as cleaning_services')
            ->selectRaw('MAX(Datereceived) as last_service')
            ->selectRaw('MAX(CAST(Odometer AS FLOAT)) as highest_mileage')
            ->selectRaw('MIN(CAST(Odometer AS FLOAT)) as lowest_mileage')
            ->selectRaw('AVG(CASE WHEN CAST(Odometer AS FLOAT) > 0 THEN CAST(Odometer AS FLOAT) END) as avg_mileage')
            ->selectRaw('COUNT(DISTINCT CASE WHEN Odometer IS NOT NULL AND CAST(Odometer AS FLOAT) > 0 THEN Odometer END) as odometer_records')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->groupBy('Vehicle')
            ->orderBy('total_services', 'desc')
            ->paginate(50);
    }

    /**
     * Analyze fleet performance patterns from ServiceRequest_Sample data
     */
    private function analyzeFleetPerformance()
    {
        // Get high maintenance vehicles (excluding cleaning)
        $highMaintenanceVehicles = ServiceRequest::select('Vehicle')
            ->selectRaw('COUNT(*) as maintenance_count')
            ->selectRaw('MAX(CAST(Odometer AS FLOAT)) as max_odometer')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->whereIn('MrType', ['1', '3', '4']) // Repairs, Maintenance, Inspections
            ->groupBy('Vehicle')
            ->having('maintenance_count', '>', 50) // Vehicles with more than 50 maintenance services
            ->orderBy('maintenance_count', 'desc')
            ->limit(10)
            ->get();

        // Get low maintenance vehicles
        $lowMaintenanceVehicles = ServiceRequest::select('Vehicle')
            ->selectRaw('COUNT(*) as maintenance_count')
            ->selectRaw('MAX(CAST(Odometer AS FLOAT)) as max_odometer')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->whereIn('MrType', ['1', '3', '4'])
            ->groupBy('Vehicle')
            ->having('maintenance_count', '<', 10) // Vehicles with less than 10 maintenance services
            ->orderBy('maintenance_count', 'asc')
            ->limit(10)
            ->get();

        // Calculate average service interval (rough estimate)
        $avgServiceInterval = ServiceRequest::whereNotNull('Odometer')
            ->where('Odometer', '>', 0)
            ->whereIn('MrType', ['1', '3', '4'])
            ->selectRaw('AVG(CAST(Odometer AS FLOAT)) as avg_mileage')
            ->first();

        // Service type distribution
        $serviceTypeDistribution = ServiceRequest::select('MrType')
            ->selectRaw('COUNT(*) as count')
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
                return [$type => $item->count];
            });

        return [
            'high_maintenance_vehicles' => $highMaintenanceVehicles,
            'low_maintenance_vehicles' => $lowMaintenanceVehicles,
            'average_odometer_reading' => round($avgServiceInterval->avg_mileage ?? 0),
            'service_type_distribution' => $serviceTypeDistribution,
            'fleet_efficiency_notes' => $this->generateFleetEfficiencyNotes($highMaintenanceVehicles, $lowMaintenanceVehicles)
        ];
    }

    /**
     * Generate fleet efficiency insights
     */
    private function generateFleetEfficiencyNotes($highMaintenance, $lowMaintenance)
    {
        $notes = [];
        
        if ($highMaintenance->count() > 0) {
            $notes[] = $highMaintenance->count() . " vehicles require frequent maintenance attention";
        }
        
        if ($lowMaintenance->count() > 0) {
            $notes[] = $lowMaintenance->count() . " vehicles show excellent maintenance efficiency";
        }
        
        return $notes;
    }

    /**
     * Get maintenance trends across the fleet from ServiceRequest_Sample data
     */
    private function getMaintenanceTrends()
    {
        // Monthly service trends (last 12 months)
        $monthlyTrends = ServiceRequest::selectRaw('
                YEAR(Datereceived) as year,
                MONTH(Datereceived) as month,
                COUNT(*) as total_services,
                COUNT(CASE WHEN MrType IN (\'1\', \'3\', \'4\') THEN 1 END) as maintenance_services,
                COUNT(CASE WHEN MrType = \'2\' THEN 1 END) as cleaning_services,
                COUNT(CASE WHEN MrType = \'1\' THEN 1 END) as repairs,
                COUNT(CASE WHEN MrType = \'3\' THEN 1 END) as maintenance,
                COUNT(CASE WHEN MrType = \'4\' THEN 1 END) as inspections
            ')
            ->whereNotNull('Datereceived')
            ->where('Datereceived', '>=', Carbon::now()->subYear())
            ->groupBy(DB::raw('YEAR(Datereceived), MONTH(Datereceived)'))
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Vehicle activity trends
        $vehicleActivityTrends = ServiceRequest::select('Vehicle')
            ->selectRaw('COUNT(*) as total_services')
            ->selectRaw('MAX(Datereceived) as last_service_date')
            ->selectRaw('DATEDIFF(CURDATE(), MAX(Datereceived)) as days_since_last_service')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->where('Vehicle', '!=', '0')
            ->whereNotNull('Datereceived')
            ->groupBy('Vehicle')
            ->orderBy('days_since_last_service', 'desc')
            ->limit(20)
            ->get();

        // Building/Location trends
        $buildingTrends = ServiceRequest::select('Building')
            ->selectRaw('COUNT(*) as service_count')
            ->selectRaw('COUNT(DISTINCT Vehicle) as vehicle_count')
            ->whereNotNull('Building')
            ->where('Building', '!=', '')
            ->groupBy('Building')
            ->orderBy('service_count', 'desc')
            ->limit(10)
            ->get();

        // Priority distribution trends
        $priorityTrends = ServiceRequest::select('Priority')
            ->selectRaw('COUNT(*) as count')
            ->whereNotNull('Priority')
            ->groupBy('Priority')
            ->orderBy('Priority')
            ->get()
            ->mapWithKeys(function ($item) {
                $priority = match($item->Priority) {
                    '1' => 'Critical',
                    '2' => 'High',
                    '3' => 'Normal',
                    '4' => 'Low',
                    default => 'Unknown'
                };
                return [$priority => $item->count];
            });

        return [
            'monthly_trends' => $monthlyTrends,
            'vehicle_activity_trends' => $vehicleActivityTrends,
            'building_trends' => $buildingTrends,
            'priority_distribution' => $priorityTrends,
            'trend_summary' => $this->generateTrendSummary($monthlyTrends, $vehicleActivityTrends)
        ];
    }

    /**
     * Generate trend analysis summary
     */
    private function generateTrendSummary($monthlyTrends, $vehicleActivity)
    {
        $summary = [];
        
        if ($monthlyTrends->count() > 0) {
            $avgMonthlyServices = $monthlyTrends->avg('total_services');
            $summary['avg_monthly_services'] = round($avgMonthlyServices);
            
            $recentMonth = $monthlyTrends->last();
            if ($recentMonth && $recentMonth->total_services > $avgMonthlyServices) {
                $summary['recent_trend'] = 'increasing';
            } else {
                $summary['recent_trend'] = 'stable';
            }
        }
        
        if ($vehicleActivity->count() > 0) {
            $vehiclesNeedingAttention = $vehicleActivity->where('days_since_last_service', '>', 90)->count();
            $summary['vehicles_needing_attention'] = $vehiclesNeedingAttention;
        }
        
        return $summary;
    }
}