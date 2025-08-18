<?php

namespace App\Services;

use App\Models\VehicleProfile;
use App\Models\ServiceRequest;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    private ReferenceDataService $referenceService;
    
    public function __construct(ReferenceDataService $referenceService)
    {
        $this->referenceService = $referenceService;
    }
    
    /**
     * Make maintenance prediction for vehicle
     */
    public function makePrediction(array $vehicleData, int $currentMileage): array
    {
        $vehicle = $vehicleData['vehicle'];
        $stats = $vehicleData['stats'];
        
        // Prepare data for ML prediction
        $mlData = $this->preparePredictionData($vehicle, $currentMileage, $stats);
        
        // Try ML prediction first
        $mlResult = $this->callMLPrediction($mlData);
        
        // If ML fails, use rule-based fallback
        if (isset($mlResult['error'])) {
            Log::warning("ML prediction failed: " . $mlResult['error']);
            $prediction = $this->getRuleBasedPrediction($vehicleData, $currentMileage);
        } else {
            $prediction = $mlResult;
        }
        
        // Analyze parts that need attention
        $partsAnalysis = $this->analyzePartsCondition($vehicle, $currentMileage);
        
        // Calculate cost estimates
        $costEstimate = $this->calculateCostEstimate($partsAnalysis);
        
        return [
            'prediction' => $prediction,
            'parts_analysis' => $partsAnalysis,
            'cost_estimate' => $costEstimate,
            'vehicle_info' => $vehicle->formatted_info ?? [],
            'recommendations' => $this->generateRecommendations($prediction, $partsAnalysis)
        ];
    }
    
    /**
     * Prepare data for ML prediction
     */
    private function preparePredictionData(VehicleProfile $vehicle, int $currentMileage, array $stats): array
    {
        return [
            'Vehicle' => $vehicle->vh_regno,
            'Odometer' => $currentMileage,
            'service_count' => $stats['maintenance_count'],
            'cleaning_count' => $stats['cleaning_count'],
            'tire_services_count' => $stats['tire_services_count'],
            'days_since_last_service' => $stats['days_since_last_service'] ?? 0,
            'days_since_last_maintenance' => $stats['days_since_last_maintenance'] ?? 0,
            'average_interval' => $vehicle->getAverageMaintenanceInterval() ?? 5000,
            'service_frequency' => $vehicle->getServiceFrequency(),
            'depot_code' => $vehicle->depot_kod,
            'vehicle_model' => $vehicle->ModelID,
            'usage_pattern' => $vehicle->getUsagePattern(),
            'Description' => 'Vehicle maintenance prediction request',
            'Priority' => 2,
            'Status' => 2,
            'MrType' => 1, // Senggaraan (Maintenance)
            'Building' => $vehicle->depot_kod
        ];
    }
    
    /**
     * Call ML prediction service
     */
    private function callMLPrediction(array $data): array
    {
        try {
            // This would call your Python ML service
            // For now, return a simulated response
            return [
                'prediction' => $this->simulateMLPrediction($data),
                'confidence' => 0.85,
                'method' => 'ml_prediction',
                'source' => 'Enhanced ML Model'
            ];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Simulate ML prediction (replace with actual ML call)
     */
    private function simulateMLPrediction(array $data): string
    {
        $serviceCount = $data['service_count'] ?? 0;
        $daysSinceLast = $data['days_since_last_maintenance'] ?? 0;
        $frequency = $data['service_frequency'] ?? 0;
        
        if ($frequency > 8 || $serviceCount > 200) {
            return 'high_maintenance_vehicle';
        } elseif ($daysSinceLast > 60 || $frequency > 4) {
            return 'routine_maintenance';
        } else {
            return 'routine_service';
        }
    }
    
    /**
     * Get rule-based prediction as fallback
     */
    private function getRuleBasedPrediction(array $vehicleData, int $currentMileage): array
    {
        $stats = $vehicleData['stats'];
        $vehicle = $vehicleData['vehicle'];
        
        // High maintenance frequency
        if ($vehicle->getServiceFrequency() > 8) {
            return [
                'prediction' => 'high_maintenance_vehicle',
                'confidence' => 0.80,
                'method' => 'rule_based',
                'source' => 'Frequency Analysis',
                'reason' => 'High service frequency detected'
            ];
        }
        
        // Long time since maintenance
        if (($stats['days_since_last_maintenance'] ?? 0) > 90) {
            return [
                'prediction' => 'routine_maintenance',
                'confidence' => 0.75,
                'method' => 'rule_based',
                'source' => 'Time Analysis',
                'reason' => 'Overdue for maintenance'
            ];
        }
        
        // High mileage vehicle
        if ($currentMileage > 500000) {
            return [
                'prediction' => 'routine_maintenance',
                'confidence' => 0.70,
                'method' => 'rule_based',
                'source' => 'Mileage Analysis',
                'reason' => 'High mileage vehicle needs regular maintenance'
            ];
        }
        
        return [
            'prediction' => 'routine_service',
            'confidence' => 0.65,
            'method' => 'rule_based',
            'source' => 'Standard Analysis',
            'reason' => 'Regular maintenance schedule'
        ];
    }
    
    /**
     * Analyze parts condition based on service history
     */
    private function analyzePartsCondition(VehicleProfile $vehicle, int $currentMileage): array
    {
        $serviceHistory = $vehicle->serviceRequests()
                                 ->maintenanceOnly()
                                 ->withValidDates()
                                 ->orderByRecent()
                                 ->get();
        
        $partsData = $this->getPartsMaintenanceSchedule();
        $analysis = ['immediate' => [], 'soon' => [], 'routine' => []];
        
        foreach ($partsData as $partName => $partInfo) {
            $lastService = $this->findLastServiceForPart($serviceHistory, $partInfo['keywords']);
            $lastServiceKm = $lastService ? floatval($lastService->Odometer ?? 0) : 0;
            $kmSinceService = $currentMileage - $lastServiceKm;
            $kmRemaining = $partInfo['interval_km'] - $kmSinceService;
            
            $partCondition = [
                'part' => $partName,
                'last_service' => $lastService,
                'last_service_km' => $lastServiceKm,
                'km_since_service' => $kmSinceService,
                'km_remaining' => $kmRemaining,
                'interval_km' => $partInfo['interval_km'],
                'priority' => $partInfo['priority'],
                'cost_range' => $partInfo['cost_range'],
                'is_critical' => $partInfo['is_critical'] ?? false
            ];
            
            // Categorize based on urgency
            if ($kmRemaining <= 0) {
                $partCondition['status'] = 'overdue';
                $analysis['immediate'][] = $partCondition;
            } elseif ($kmRemaining <= ($partInfo['interval_km'] * 0.1)) {
                $partCondition['status'] = 'due_soon';
                $analysis['soon'][] = $partCondition;
            } else {
                $partCondition['status'] = 'scheduled';
                $analysis['routine'][] = $partCondition;
            }
        }
        
        return $analysis;
    }
    
    /**
     * Get parts maintenance schedule data
     */
    private function getPartsMaintenanceSchedule(): array
    {
        return [
            'Engine Oil & Filter' => [
                'interval_km' => 10000,
                'priority' => 1,
                'keywords' => ['minyak enjin', 'engine oil', 'oil change', 'filter minyak'],
                'cost_range' => ['min' => 80, 'max' => 150],
                'is_critical' => true
            ],
            'Air Filter' => [
                'interval_km' => 15000,
                'priority' => 2,
                'keywords' => ['air filter', 'filter udara'],
                'cost_range' => ['min' => 30, 'max' => 60]
            ],
            'Brake System' => [
                'interval_km' => 20000,
                'priority' => 1,
                'keywords' => ['brake', 'brek', 'brake pad', 'brake fluid'],
                'cost_range' => ['min' => 150, 'max' => 400],
                'is_critical' => true
            ],
            'Tires & Wheels' => [
                'interval_km' => 25000,
                'priority' => 1,
                'keywords' => ['tayar', 'tire', 'tyre', 'tukar tayar'],
                'cost_range' => ['min' => 180, 'max' => 350],
                'is_critical' => true
            ],
            'Transmission Service' => [
                'interval_km' => 30000,
                'priority' => 2,
                'keywords' => ['gearbox', 'transmission', 'gear oil'],
                'cost_range' => ['min' => 200, 'max' => 500]
            ]
        ];
    }
    
    /**
     * Find last service for specific part
     */
    private function findLastServiceForPart($serviceHistory, array $keywords)
    {
        foreach ($serviceHistory as $service) {
            if ($service->matchesKeywords($keywords)) {
                return $service;
            }
        }
        
        return null;
    }
    
    /**
     * Calculate cost estimate based on parts analysis
     */
    private function calculateCostEstimate(array $partsAnalysis): array
    {
        $totalMin = 0;
        $totalMax = 0;
        $breakdown = [];
        
        foreach (['immediate', 'soon'] as $urgency) {
            foreach ($partsAnalysis[$urgency] as $part) {
                $totalMin += $part['cost_range']['min'];
                $totalMax += $part['cost_range']['max'];
                
                $breakdown[] = [
                    'item' => $part['part'],
                    'urgency' => $urgency,
                    'cost_range' => $part['cost_range']
                ];
            }
        }
        
        // Ensure minimum service cost
        if ($totalMin < 80) {
            $totalMin = 80;
            $totalMax = max(150, $totalMax);
        }
        
        return [
            'total_min' => $totalMin,
            'total_max' => $totalMax,
            'breakdown' => $breakdown,
            'confidence' => count($breakdown) > 0 ? 'high' : 'medium'
        ];
    }
    
    /**
     * Generate maintenance recommendations
     */
    private function generateRecommendations(array $prediction, array $partsAnalysis): array
    {
        $recommendations = [];
        
        // Immediate attention items
        if (!empty($partsAnalysis['immediate'])) {
            $recommendations[] = [
                'priority' => 'high',
                'type' => 'immediate_action',
                'title' => 'Immediate Maintenance Required',
                'description' => count($partsAnalysis['immediate']) . ' items need immediate attention',
                'action' => 'Schedule service appointment within 1-2 days'
            ];
        }
        
        // Soon items
        if (!empty($partsAnalysis['soon'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'upcoming_maintenance',
                'title' => 'Upcoming Maintenance',
                'description' => count($partsAnalysis['soon']) . ' items will need service soon',
                'action' => 'Plan service within 2-4 weeks'
            ];
        }
        
        // Based on prediction
        if ($prediction['prediction'] === 'high_maintenance_vehicle') {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'monitoring',
                'title' => 'Enhanced Monitoring Recommended',
                'description' => 'Vehicle shows high maintenance frequency',
                'action' => 'Consider more frequent inspections'
            ];
        }
        
        return $recommendations;
    }
}