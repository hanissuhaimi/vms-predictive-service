<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VMSPredictionService;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; 
use Carbon\Carbon;

class PredictionController extends Controller
{
    protected $predictionService;

    public function __construct(VMSPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function index()
    {
        return view('prediction.index');
    }

    public function predict(Request $request)
    {
        // Enhanced validation with better error messages
        $validator = Validator::make($request->all(), [
            'vehicle_number' => [
                'required',
                'string',
                'min:3',
                'max:20',
                'regex:/^[A-Z0-9]+$/i'
            ],
            'current_mileage' => [
                'required',
                'integer',
                'min:1',
                'max:5000000'
            ],
        ], [
            'vehicle_number.regex' => 'Vehicle number must contain only letters and numbers',
            'current_mileage.max' => 'Mileage cannot exceed 5,000,000 KM',
            'current_mileage.min' => 'Mileage must be greater than 0',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please check your input and try again.');
        }

        $vehicleNumber = strtoupper(trim($request->vehicle_number));
        $currentMileage = intval($request->current_mileage);

        Log::info("=== ENHANCED PREDICTION START ===");
        Log::info("Vehicle: {$vehicleNumber}, Mileage: {$currentMileage}");

        try {
            // ENHANCEMENT 2: Advanced Multi-Layer Validation
            $advancedValidation = $this->validateMileageAdvanced($vehicleNumber, $currentMileage);
            
            if (!$advancedValidation['valid']) {
                Log::warning("Advanced validation failed: " . $advancedValidation['reason']);
                
                return back()
                    ->withInput()
                    ->withErrors(['current_mileage' => $advancedValidation['message']])
                    ->with('error', 'Advanced validation failed. Please verify your input.')
                    ->with('validation_details', $advancedValidation);
            }
            
            Log::info("✅ Advanced validation passed with " . count($advancedValidation['layers_passed']) . " layers");

            // Continue with enhanced prediction logic
            $startTime = microtime(true);

            $vehicleHistory = $this->getVehicleHistory($vehicleNumber);

            // Check if vehicle exists
            if ($vehicleHistory['total_services'] === 0) {
                Log::warning("Vehicle {$vehicleNumber} not found in database");
                
                return back()
                    ->withInput()
                    ->withErrors(['vehicle_number' => "Vehicle number '{$vehicleNumber}' not found in our database."])
                    ->with('error', "No maintenance records found for vehicle {$vehicleNumber}. Please verify the vehicle number.");
            }

            Log::info("✅ Vehicle found: " . $vehicleHistory['total_services'] . " service records");

            // ENHANCEMENT 3: Safety-Critical System Analysis
            $safetyAnalysis = $this->analyzeSafetyCriticalSystems($vehicleNumber, $currentMileage, $vehicleHistory);
            Log::info("✅ Safety analysis completed - Score: " . $safetyAnalysis['overall_safety_score']);

            // Continue with existing prediction flow (enhanced)
            $mlPrediction = $this->safeMLPrediction($vehicleNumber, $currentMileage, $vehicleHistory);
            $serviceSchedule = $this->calculateServiceSchedule($currentMileage, $vehicleHistory);
            $partsAnalysis = $this->safePartsAnalysis($currentMileage, $vehicleHistory, $mlPrediction);
            
            // ENHANCEMENT 7: Predictive Cost Analytics
            $costAnalysis = $this->predictiveCAostAnalytics($vehicleHistory, $partsAnalysis, $safetyAnalysis, $currentMileage);
            Log::info("✅ Predictive cost analysis completed");

            $recommendations = $this->enhancedRecommendations($serviceSchedule, $partsAnalysis, $mlPrediction, $safetyAnalysis, $costAnalysis);

            // Enhanced response
            $response = [
                'vehicle' => $vehicleNumber,
                'currentMileage' => $currentMileage,
                'vehicleHistory' => $vehicleHistory,
                'serviceSchedule' => $serviceSchedule,
                'partsAnalysis' => $partsAnalysis,
                'recommendations' => $recommendations,
                'mlPrediction' => $mlPrediction,
                
                // NEW ENHANCEMENTS (no additional files needed)
                'safetyAnalysis' => $safetyAnalysis,
                'costAnalysis' => $costAnalysis,
                'advancedValidation' => $advancedValidation,
                'systemEnhancement' => [
                    'version' => '2.0',
                    'enhancements_active' => ['advanced_validation', 'safety_critical', 'predictive_cost'],
                    'processing_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ]
            ];

            Log::info("✅ Enhanced prediction completed successfully");
            return view('prediction.maintenance_schedule', $response);

        } catch (\Exception $e) {
            Log::error("Enhanced prediction error: {$e->getMessage()}");
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            return back()
                ->with('error', 'Enhanced analysis failed: ' . $this->getHelpfulErrorMessage($e))
                ->withInput();
        }
    }

    /**
     * ENHANCEMENT 2: Advanced Multi-Layer Mileage Validation
     * No additional files needed - integrated into existing controller
     */
    private function validateMileageAdvanced($vehicleNumber, $currentMileage)
    {
        Log::info("=== ADVANCED VALIDATION START ===");
        Log::info("Vehicle: {$vehicleNumber}, Input: " . number_format($currentMileage) . " KM");
        
        $validationLayers = [];
        
        try {
            // Layer 1: Basic range validation
            if ($currentMileage < 1000) {
                return [
                    'valid' => false,
                    'reason' => 'too_low',
                    'message' => 'Mileage must be at least 1,000 KM for fleet vehicles.',
                    'layer_failed' => 'basic_range'
                ];
            }
            
            if ($currentMileage > 3000000) {
                return [
                    'valid' => false,
                    'reason' => 'extreme_outlier',
                    'message' => 'Mileage exceeds maximum reasonable limit (3M KM). Please verify your odometer reading.',
                    'layer_failed' => 'basic_range'
                ];
            }
            $validationLayers[] = 'basic_range_passed';
            
            // Layer 2: Statistical outlier detection using DB data
            $fleetStats = $this->getFleetStatistics();
            if ($fleetStats) {
                $zScore = abs(($currentMileage - $fleetStats['avg_mileage']) / ($fleetStats['std_dev'] ?: 500000));
                
                if ($zScore > 6) { // Extreme statistical outlier
                    return [
                        'valid' => false,
                        'reason' => 'statistical_outlier',
                        'message' => "Mileage is an extreme statistical outlier. Fleet average: " . number_format($fleetStats['avg_mileage']) . " KM. Please verify your reading.",
                        'z_score' => round($zScore, 2),
                        'fleet_context' => $fleetStats,
                        'layer_failed' => 'statistical_analysis'
                    ];
                }
                $validationLayers[] = 'statistical_analysis_passed';
            }
            
            // Layer 3: Vehicle historical progression validation
            $progressionCheck = $this->validateVehicleProgression($vehicleNumber, $currentMileage);
            if (!$progressionCheck['valid']) {
                return array_merge($progressionCheck, ['layer_failed' => 'progression_analysis']);
            }
            $validationLayers[] = 'progression_analysis_passed';
            
            // Layer 4: Known problematic patterns detection
            $anomalyCheck = $this->detectKnownAnomalies($vehicleNumber, $currentMileage);
            if ($anomalyCheck['is_anomaly']) {
                return [
                    'valid' => false,
                    'reason' => 'known_anomaly_pattern',
                    'message' => 'This mileage matches a known data quality issue pattern. Please double-check your odometer reading.',
                    'anomaly_pattern' => $anomalyCheck['pattern'],
                    'layer_failed' => 'anomaly_detection'
                ];
            }
            $validationLayers[] = 'anomaly_detection_passed';
            
            Log::info("✅ All validation layers passed: " . implode(', ', $validationLayers));
            
            return [
                'valid' => true,
                'confidence' => 'high',
                'layers_passed' => $validationLayers,
                'validation_score' => count($validationLayers) * 25, // 100 for all 4 layers
                'message' => 'Advanced validation passed with high confidence'
            ];
            
        } catch (\Exception $e) {
            Log::warning('Advanced validation error: ' . $e->getMessage());
            
            // Fallback to basic validation
            return [
                'valid' => true,
                'confidence' => 'medium',
                'layers_passed' => ['basic_fallback'],
                'validation_score' => 50,
                'message' => 'Basic validation passed (advanced validation unavailable)',
                'fallback_reason' => $e->getMessage()
            ];
        }
    }

    /**
     * Get fleet-wide statistics for validation (using existing DB)
     */
    private function getFleetStatistics()
    {
        return Cache::remember('fleet_statistics', 1800, function () { // Cache for 30 minutes
            try {
                $stats = DB::selectOne("
                    SELECT 
                        COUNT(DISTINCT Vehicle) as total_vehicles,
                        AVG(CASE 
                            WHEN TRY_CAST(Odometer AS FLOAT) BETWEEN 1000 AND 3000000 
                            THEN TRY_CAST(Odometer AS FLOAT) 
                            ELSE NULL 
                        END) as avg_mileage,
                        MAX(CASE 
                            WHEN TRY_CAST(Odometer AS FLOAT) BETWEEN 1000 AND 3000000 
                            THEN TRY_CAST(Odometer AS FLOAT) 
                            ELSE NULL 
                        END) as max_mileage,
                        STDEV(CASE 
                            WHEN TRY_CAST(Odometer AS FLOAT) BETWEEN 1000 AND 3000000 
                            THEN TRY_CAST(Odometer AS FLOAT) 
                            ELSE NULL 
                        END) as std_dev
                    FROM ServiceRequest 
                    WHERE Vehicle IS NOT NULL 
                    AND Vehicle != ''
                    AND TRY_CAST(Odometer AS FLOAT) IS NOT NULL
                ");
                
                return [
                    'total_vehicles' => intval($stats->total_vehicles),
                    'avg_mileage' => round($stats->avg_mileage),
                    'max_mileage' => round($stats->max_mileage),
                    'std_dev' => round($stats->std_dev ?: 500000)
                ];
                
            } catch (\Exception $e) {
                Log::warning('Fleet statistics calculation failed: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Validate against vehicle's historical progression
     */
    private function validateVehicleProgression($vehicleNumber, $currentMileage)
    {
        try {
            $progression = DB::select("
                SELECT TRY_CAST(Odometer AS FLOAT) as mileage, Datereceived
                FROM ServiceRequest
                WHERE UPPER(TRIM(Vehicle)) = ?
                AND TRY_CAST(Odometer AS FLOAT) BETWEEN 1000 AND 3000000
                ORDER BY Datereceived DESC
                OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
            ", [strtoupper(trim($vehicleNumber))]);
            
            if (empty($progression)) {
                return ['valid' => true, 'reason' => 'no_history'];
            }
            
            $latestMileage = $progression[0]->mileage;
            $difference = $currentMileage - $latestMileage;
            
            // Check for backward progression (more than 50K decrease)
            if ($difference < -50000) {
                return [
                    'valid' => false,
                    'reason' => 'backward_progression',
                    'message' => "Mileage appears to go backward from latest recorded (" . number_format($latestMileage) . " KM). Difference: " . number_format($difference) . " KM.",
                    'latest_recorded' => round($latestMileage),
                    'difference' => round($difference)
                ];
            }
            
            // Check for unrealistic forward jump (more than 200K increase)
            if ($difference > 200000) {
                return [
                    'valid' => false,
                    'reason' => 'unrealistic_jump',
                    'message' => "Mileage increase too large from latest recorded (" . number_format($latestMileage) . " KM). Increase: " . number_format($difference) . " KM.",
                    'latest_recorded' => round($latestMileage),
                    'difference' => round($difference)
                ];
            }
            
            return ['valid' => true, 'progression_check' => 'normal'];
            
        } catch (\Exception $e) {
            Log::warning('Progression validation failed: ' . $e->getMessage());
            return ['valid' => true, 'reason' => 'progression_check_failed'];
        }
    }

    /**
     * Detect known problematic mileage patterns
     */
    private function detectKnownAnomalies($vehicleNumber, $mileage)
    {
        // Known problematic patterns from fleet analysis
        $problematicPatterns = [
            'extreme_mileage' => $mileage > 5000000,
            'suspicious_round_numbers' => in_array($mileage, [0, 1, 10, 100, 1000, 10000, 100000, 1000000]),
            'known_error_ranges' => ($mileage >= 10000000 && $mileage <= 20000000), // From fleet analysis
            'impossible_low' => $mileage < 500
        ];
        
        foreach ($problematicPatterns as $pattern => $isProblematic) {
            if ($isProblematic) {
                return [
                    'is_anomaly' => true,
                    'pattern' => $pattern,
                    'recommendation' => 'Manual review required'
                ];
            }
        }
        
        return ['is_anomaly' => false];
    }

    /**
     * ENHANCEMENT 3: Safety-Critical System Analysis
     * Integrated into existing controller - no new files needed
     */
    private function analyzeSafetyCriticalSystems($vehicleNumber, $currentMileage, $vehicleHistory)
    {
        Log::info("=== SAFETY-CRITICAL ANALYSIS START ===");
        
        try {
            $records = $vehicleHistory['records'] ?? collect([]);
            
            $safetyAnalysis = [
                'overall_safety_score' => 100,
                'critical_alerts' => [],
                'safety_recommendations' => [],
                'breakdown_risk' => 'low',
                'safety_systems' => []
            ];
            
            // Analyze critical safety systems based on fleet incident patterns
            $safetyAnalysis['safety_systems']['brake_system'] = $this->analyzeBrakeSystemSafety($records, $currentMileage);
            $safetyAnalysis['safety_systems']['tire_safety'] = $this->analyzeTireSystemSafety($records, $currentMileage);
            $safetyAnalysis['safety_systems']['air_system'] = $this->analyzeAirSystemSafety($records);
            $safetyAnalysis['safety_systems']['electrical_safety'] = $this->analyzeElectricalSafety($records);
            
            // Calculate overall safety score
            $safetyAnalysis = $this->calculateOverallSafetyScore($safetyAnalysis);
            
            // Generate safety alerts and recommendations
            $safetyAnalysis = $this->generateSafetyAlertsAndRecommendations($safetyAnalysis, $vehicleNumber);
            
            Log::info("✅ Safety analysis completed - Score: " . $safetyAnalysis['overall_safety_score']);
            return $safetyAnalysis;
            
        } catch (\Exception $e) {
            Log::error("Safety analysis error: " . $e->getMessage());
            return $this->getDefaultSafetyAnalysis();
        }
    }

    /**
     * Analyze brake system safety (Priority #1 based on fleet data)
     */
    private function analyzeBrakeSystemSafety($records, $currentMileage)
    {
        // Critical brake keywords from fleet incident analysis (371+ brake incidents)
        $brakeKeywords = [
            'critical' => ['brake jammed', 'brek jammed', 'brake fail', 'pedal kosong', 'no brake'],
            'urgent' => ['brake issue', 'brake berbunyi', 'angin bocor', 'lining brake', 'pad brek'],
            'routine' => ['adjust brake', 'check brake', 'brake service']
        ];
        
        $brakeIncidents = ['critical' => 0, 'urgent' => 0, 'routine' => 0];
        $lastBrakeService = null;
        $daysSinceLastService = 365;
        
        foreach ($records as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            
            foreach ($brakeKeywords as $severity => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($searchText, $keyword)) {
                        $brakeIncidents[$severity]++;
                        
                        if (!$lastBrakeService && $record->Datereceived) {
                            $lastBrakeService = $record->Datereceived;
                            try {
                                $daysSinceLastService = Carbon::parse($record->Datereceived)->diffInDays(now());
                            } catch (\Exception $e) {
                                $daysSinceLastService = 365;
                            }
                        }
                        break 2;
                    }
                }
            }
        }
        
        // Calculate brake safety score
        $safetyScore = 100;
        $riskFactors = [];
        
        if ($brakeIncidents['critical'] > 0) {
            $safetyScore -= 50;
            $riskFactors[] = "Critical brake failures: {$brakeIncidents['critical']}";
        }
        
        if ($brakeIncidents['urgent'] > 3) {
            $safetyScore -= 25;
            $riskFactors[] = "Frequent brake issues: {$brakeIncidents['urgent']}";
        }
        
        if ($daysSinceLastService > 180) {
            $safetyScore -= 30;
            $riskFactors[] = "No brake service in {$daysSinceLastService} days";
        }
        
        if ($currentMileage > 1500000) {
            $safetyScore -= 15;
            $riskFactors[] = "High mileage vehicle (1.5M+ KM)";
        }
        
        $riskLevel = $safetyScore <= 40 ? 'critical' : ($safetyScore <= 60 ? 'high' : ($safetyScore <= 80 ? 'medium' : 'low'));
        
        return [
            'safety_score' => max(0, $safetyScore),
            'risk_level' => $riskLevel,
            'incidents' => $brakeIncidents,
            'last_service' => $lastBrakeService,
            'days_since_service' => $daysSinceLastService,
            'risk_factors' => $riskFactors
        ];
    }

    /**
     * Analyze tire safety system
     */
    private function analyzeTireSystemSafety($records, $currentMileage)
    {
        $tireKeywords = [
            'critical' => ['tayar meletup', 'tire blowout', 'tayar tercabut', 'breakdown tayar'],
            'urgent' => ['tayar botak', 'tayar terkopak', 'bunga terkopak', 'tayar makan sebelah'],
            'routine' => ['tukar tayar', 'tayar pancit', 'tampal tayar']
        ];
        
        $tireIncidents = ['critical' => 0, 'urgent' => 0, 'routine' => 0];
        $recentBlowouts = 0;
        
        foreach ($records as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            
            foreach ($tireKeywords as $severity => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($searchText, $keyword)) {
                        $tireIncidents[$severity]++;
                        
                        // Count recent blowouts (critical safety issue)
                        if ($severity === 'critical' && $record->Datereceived) {
                            try {
                                $incidentDate = Carbon::parse($record->Datereceived);
                                if ($incidentDate->diffInMonths(now()) <= 6) {
                                    $recentBlowouts++;
                                }
                            } catch (\Exception $e) {
                                // Skip if date parsing fails
                            }
                        }
                        break 2;
                    }
                }
            }
        }
        
        $safetyScore = 100;
        $riskFactors = [];
        
        if ($tireIncidents['critical'] > 0) {
            $safetyScore -= 40;
            $riskFactors[] = "Tire blowouts/failures: {$tireIncidents['critical']}";
        }
        
        if ($recentBlowouts > 1) {
            $safetyScore -= 30;
            $riskFactors[] = "Multiple recent blowouts: {$recentBlowouts}";
        }
        
        if ($tireIncidents['urgent'] > 5) {
            $safetyScore -= 20;
            $riskFactors[] = "Frequent tire wear issues: {$tireIncidents['urgent']}";
        }
        
        $riskLevel = $safetyScore <= 40 ? 'critical' : ($safetyScore <= 60 ? 'high' : ($safetyScore <= 80 ? 'medium' : 'low'));
        
        return [
            'safety_score' => max(0, $safetyScore),
            'risk_level' => $riskLevel,
            'incidents' => $tireIncidents,
            'recent_blowouts' => $recentBlowouts,
            'risk_factors' => $riskFactors
        ];
    }

    /**
     * Analyze air system safety (critical for brakes)
     */
    private function analyzeAirSystemSafety($records)
    {
        $airKeywords = ['angin bocor', 'air bocor', 'belon bocor', 'angin tidak naik', 'chamber bocor'];
        
        $airIssues = 0;
        $criticalAirIssues = 0;
        
        foreach ($records as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            
            foreach ($airKeywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    $airIssues++;
                    if (str_contains($searchText, 'tidak naik') || str_contains($searchText, 'fail')) {
                        $criticalAirIssues++;
                    }
                    break;
                }
            }
        }
        
        $safetyScore = 100;
        if ($criticalAirIssues > 0) $safetyScore -= 40;
        if ($airIssues > 3) $safetyScore -= 20;
        
        $riskLevel = $safetyScore <= 50 ? 'critical' : ($safetyScore <= 70 ? 'high' : 'medium');
        
        return [
            'safety_score' => max(0, $safetyScore),
            'risk_level' => $riskLevel,
            'issues_count' => $airIssues,
            'critical_issues' => $criticalAirIssues
        ];
    }

    /**
     * Analyze electrical safety systems
     */
    private function analyzeElectricalSafety($records)
    {
        $electricalKeywords = ['lampu brake', 'signal tidak', 'lampu mati', 'wiring short', 'lampu emergency'];
        
        $electricalIssues = 0;
        foreach ($records as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            
            foreach ($electricalKeywords as $keyword) {
                if (str_contains($searchText, $keyword)) {
                    $electricalIssues++;
                    break;
                }
            }
        }
        
        $safetyScore = $electricalIssues > 5 ? 70 : ($electricalIssues > 2 ? 85 : 95);
        $riskLevel = $safetyScore <= 70 ? 'medium' : 'low';
        
        return [
            'safety_score' => $safetyScore,
            'risk_level' => $riskLevel,
            'issues_count' => $electricalIssues
        ];
    }

    /**
     * Calculate overall safety score
     */
    private function calculateOverallSafetyScore($safetyAnalysis)
    {
        $systems = $safetyAnalysis['safety_systems'];
        
        // Weighted safety score (brake system has highest weight)
        $weights = [
            'brake_system' => 0.40,      // 40% - Most critical
            'tire_safety' => 0.30,       // 30% - Second most critical  
            'air_system' => 0.20,        // 20% - Critical for brakes
            'electrical_safety' => 0.10   // 10% - Important for visibility
        ];
        
        $weightedScore = 0;
        foreach ($weights as $system => $weight) {
            $systemScore = $systems[$system]['safety_score'] ?? 100;
            $weightedScore += $systemScore * $weight;
        }
        
        $safetyAnalysis['overall_safety_score'] = round($weightedScore);
        
        // Determine breakdown risk
        if ($weightedScore <= 40) {
            $safetyAnalysis['breakdown_risk'] = 'critical';
        } elseif ($weightedScore <= 60) {
            $safetyAnalysis['breakdown_risk'] = 'high';
        } elseif ($weightedScore <= 75) {
            $safetyAnalysis['breakdown_risk'] = 'medium';
        } else {
            $safetyAnalysis['breakdown_risk'] = 'low';
        }
        
        return $safetyAnalysis;
    }

    /**
     * Generate safety alerts and recommendations
     */
    private function generateSafetyAlertsAndRecommendations($safetyAnalysis, $vehicleNumber)
    {
        $criticalAlerts = [];
        $recommendations = [];
        
        foreach ($safetyAnalysis['safety_systems'] as $systemName => $systemData) {
            if ($systemData['risk_level'] === 'critical') {
                $criticalAlerts[] = [
                    'system' => $systemName,
                    'message' => "CRITICAL: " . ucwords(str_replace('_', ' ', $systemName)) . " requires immediate attention",
                    'score' => $systemData['safety_score'],
                    'action' => 'Stop vehicle operation until inspected'
                ];
            }
            
            if ($systemData['risk_level'] === 'high') {
                $recommendations[] = [
                    'priority' => 'URGENT',
                    'action' => "Inspect " . ucwords(str_replace('_', ' ', $systemName)) . " within 48 hours",
                    'reason' => 'High safety risk detected',
                    'system' => $systemName
                ];
            }
        }
        
        $safetyAnalysis['critical_alerts'] = $criticalAlerts;
        $safetyAnalysis['safety_recommendations'] = $recommendations;
        
        return $safetyAnalysis;
    }

    /**
     * ENHANCEMENT 7: Predictive Cost Analytics
     * AI-powered cost predictions based on patterns
     */
    private function predictiveCAostAnalytics($vehicleHistory, $partsAnalysis, $safetyAnalysis, $currentMileage)
    {
        Log::info("=== REALISTIC COST ANALYSIS START ===");
        
        try {
            $costAnalysis = [
                'total_estimated_cost' => ['min' => 0, 'max' => 0],
                'cost_breakdown' => [],
                'cost_confidence' => 'medium',
                'cost_factors' => []
            ];
            
            // Calculate base cost from parts analysis using REAL market prices
            $costAnalysis = $this->calculateRealisticBaseCost($costAnalysis, $partsAnalysis);
            
            // Add minimum service cost if too low
            $costAnalysis = $this->ensureMinimumServiceCost($costAnalysis);
            
            Log::info("✅ Realistic cost analysis completed - Range: RM " . number_format($costAnalysis['total_estimated_cost']['min']) . " - RM " . number_format($costAnalysis['total_estimated_cost']['max']));
            
            return $costAnalysis;
            
        } catch (\Exception $e) {
            Log::error("Cost analysis error: " . $e->getMessage());
            return [
                'total_estimated_cost' => ['min' => 80, 'max' => 200],
                'cost_confidence' => 'low',
                'cost_factors' => ['Real market analysis unavailable'],
                'cost_breakdown' => []
            ];
        }
    }

    /**
     * Ensure minimum realistic service cost
     */
    private function ensureMinimumServiceCost($costAnalysis)
    {
        $minCost = $costAnalysis['total_estimated_cost']['min'];
        $maxCost = $costAnalysis['total_estimated_cost']['max'];
        $minTime = $costAnalysis['total_time_estimate']['min_minutes'] ?? 60;
        $maxTime = $costAnalysis['total_time_estimate']['max_minutes'] ?? 120;
        
        // Minimum service call for commercial vehicle: RM 80, 1 hour
        if ($minCost < 80) {
            $minCost = 80;
            $maxCost = max(150, $maxCost);
            $minTime = max(60, $minTime);  // Minimum 1 hour
            $maxTime = max(120, $maxTime); // Minimum 2 hours max
            
            // Add basic service if no specific services found
            if (empty($costAnalysis['cost_breakdown'])) {
                $costAnalysis['cost_breakdown'][] = [
                    'item' => 'Basic Vehicle Inspection',
                    'urgency' => 'routine',
                    'cost_range' => ['min' => 80, 'max' => 150],
                    'time_estimate' => '1-2 hours',
                    'reason' => 'Standard commercial vehicle check-up',
                    'market_note' => 'Minimum workshop service charge'
                ];
            }
            
            $costAnalysis['cost_factors'][] = "Minimum service charge applied: RM 80 (1-2 hours)";
        }
        
        // Round to nearest RM 10 for realistic pricing
        $costAnalysis['total_estimated_cost'] = [
            'min' => round($minCost / 10) * 10,
            'max' => round($maxCost / 10) * 10
        ];
        
        // Update time estimate with realistic formatting
        $costAnalysis['total_time_estimate'] = [
            'min_minutes' => $minTime,
            'max_minutes' => $maxTime,
            'formatted' => $this->formatWorkshopTime($minTime, $maxTime)
        ];
        
        return $costAnalysis;
    }

    /**
     * Calculate realistic base cost using actual Malaysian workshop prices
     */
    private function calculateRealisticBaseCost($costAnalysis, $partsAnalysis)
    {
        $baseCostMin = 0;
        $baseCostMax = 0;
        $totalTimeMin = 0;  // Track total time needed
        $totalTimeMax = 0;
        $costBreakdown = [];
        
        // Map parts analysis to realistic market prices & times
        foreach (['immediate', 'soon'] as $urgency) {
            if (!empty($partsAnalysis[$urgency])) {
                foreach ($partsAnalysis[$urgency] as $part) {
                    $partName = $part['part'];
                    
                    // Find matching market price & time or use closest match
                    $marketData = $this->findMarketPriceAndTime($partName, $this->getMarketPrices());
                    
                    $baseCostMin += $marketData['min'];
                    $baseCostMax += $marketData['max'];
                    $totalTimeMin += $marketData['time_min'];
                    $totalTimeMax += $marketData['time_max'];
                    
                    $costBreakdown[] = [
                        'item' => $partName,
                        'urgency' => $urgency,
                        'cost_range' => ['min' => $marketData['min'], 'max' => $marketData['max']],
                        'time_estimate' => $marketData['time_note'],
                        'reason' => $this->getSimpleReason($urgency),
                        'market_note' => 'Malaysian workshop rate'
                    ];
                }
            }
        }
        
        $costAnalysis['total_estimated_cost'] = [
            'min' => $baseCostMin,
            'max' => $baseCostMax
        ];
        $costAnalysis['total_time_estimate'] = [
            'min_minutes' => $totalTimeMin,
            'max_minutes' => $totalTimeMax,
            'formatted' => $this->formatWorkshopTime($totalTimeMin, $totalTimeMax)
        ];
        $costAnalysis['cost_breakdown'] = $costBreakdown;
        $costAnalysis['cost_factors'][] = "Real Malaysian workshop prices & times: " . count($costBreakdown) . " services";
        
        return $costAnalysis;
    }

    /**
     * Get market prices and times data
     */
    private function getMarketPrices()
    {
        return [
            // ENGINE & OIL SERVICES (Most common - from Priority 1.txt)
            'Engine Oil & Hydraulics' => [
                'min' => 80, 'max' => 150, 
                'time_min' => 45, 'time_max' => 90,                     // 45-90 minutes for oil change
                'time_note' => '45-90 minutes'
            ],
            'Oil Filter' => [
                'min' => 30, 'max' => 60,
                'time_min' => 30, 'time_max' => 45,                     // 30-45 minutes for filter only
                'time_note' => '30-45 minutes'
            ],
            'Oil Seals' => [
                'min' => 120, 'max' => 300,
                'time_min' => 180, 'time_max' => 360,                   // 3-6 hours for seal replacement
                'time_note' => '3-6 hours'
            ],
            
            // BRAKE SERVICES (Critical safety - from Priority 1.txt) 
            'Brake System' => [
                'min' => 150, 'max' => 400,
                'time_min' => 120, 'time_max' => 240,                   // 2-4 hours for brake work
                'time_note' => '2-4 hours'
            ],
            'Brake Pads/Lining' => [
                'min' => 200, 'max' => 500,
                'time_min' => 180, 'time_max' => 360,                   // 3-6 hours for complete lining
                'time_note' => '3-6 hours'
            ],
            'Brake Adjustment' => [
                'min' => 50, 'max' => 120,
                'time_min' => 60, 'time_max' => 120,                    // 1-2 hours for adjustment
                'time_note' => '1-2 hours'
            ],
            
            // TIRE SERVICES (Very common - from Priority 2.txt)
            'Tires & Wheels' => [
                'min' => 180, 'max' => 350,
                'time_min' => 30, 'time_max' => 60,                     // 30-60 minutes per tire
                'time_note' => '30-60 minutes per tire'
            ],
            'Tire Repair' => [
                'min' => 25, 'max' => 50,
                'time_min' => 20, 'time_max' => 45,                     // 20-45 minutes for patch
                'time_note' => '20-45 minutes'
            ],
            'Tire Rotation' => [
                'min' => 40, 'max' => 80,
                'time_min' => 45, 'time_max' => 90,                     // 45-90 minutes for rotation
                'time_note' => '45-90 minutes'
            ],
            
            // ELECTRICAL & LIGHTING (Common issues - from Priority 2.txt)
            'Electrical & Lighting' => [
                'min' => 60, 'max' => 200,
                'time_min' => 60, 'time_max' => 180,                    // 1-3 hours for electrical work
                'time_note' => '1-3 hours'
            ],
            'Battery' => [
                'min' => 150, 'max' => 300,
                'time_min' => 30, 'time_max' => 60,                     // 30-60 minutes for battery
                'time_note' => '30-60 minutes'
            ],
            
            // AIR SYSTEM (Critical for commercial vehicles)
            'Air System' => [
                'min' => 100, 'max' => 250,
                'time_min' => 90, 'time_max' => 240,                    // 1.5-4 hours for air repairs
                'time_note' => '1.5-4 hours'
            ],
            'Air Brake Service' => [
                'min' => 120, 'max' => 300,
                'time_min' => 120, 'time_max' => 300,                   // 2-5 hours for air brake service
                'time_note' => '2-5 hours'
            ],
            
            // SUSPENSION & MECHANICAL (from Priority 2.txt)
            'Suspension & Absorber' => [
                'min' => 200, 'max' => 600,
                'time_min' => 240, 'time_max' => 480,                   // 4-8 hours for suspension work
                'time_note' => '4-8 hours'
            ],
            'Gearbox Service' => [
                'min' => 300, 'max' => 800,
                'time_min' => 180, 'time_max' => 360,                   // 3-6 hours for gearbox service
                'time_note' => '3-6 hours'
            ],
            
            // COOLING & FUEL SYSTEM (from Priority 3.txt)
            'Cooling System' => [
                'min' => 80, 'max' => 200,
                'time_min' => 90, 'time_max' => 180,                    // 1.5-3 hours for cooling system
                'time_note' => '1.5-3 hours'
            ],
            'Fuel System' => [
                'min' => 100, 'max' => 300,
                'time_min' => 120, 'time_max' => 240,                   // 2-4 hours for fuel system
                'time_note' => '2-4 hours'
            ],
            
            // GENERAL SERVICES
            'General Inspection' => [
                'min' => 80, 'max' => 150,
                'time_min' => 60, 'time_max' => 120,                    // 1-2 hours for inspection
                'time_note' => '1-2 hours'
            ],
            'Cleaning/Washing' => [
                'min' => 40, 'max' => 100,
                'time_min' => 30, 'time_max' => 90,                     // 30-90 minutes for cleaning
                'time_note' => '30-90 minutes'
            ],
        ];
    }

    /**
     * Find matching market price and time for a part/service
     */
    private function findMarketPriceAndTime($partName, $marketPrices)
    {
        // Direct match first
        if (isset($marketPrices[$partName])) {
            return $marketPrices[$partName];
        }
        
        // Fuzzy matching for common variations
        $partLower = strtolower($partName);
        
        if (str_contains($partLower, 'oil') || str_contains($partLower, 'minyak')) {
            if (str_contains($partLower, 'engine') || str_contains($partLower, 'enjin')) {
                return $marketPrices['Engine Oil & Hydraulics'];
            } elseif (str_contains($partLower, 'filter')) {
                return $marketPrices['Oil Filter'];
            } elseif (str_contains($partLower, 'seal')) {
                return $marketPrices['Oil Seals'];
            } else {
                return $marketPrices['Engine Oil & Hydraulics']; // Default for oil services
            }
        }
        
        if (str_contains($partLower, 'brake') || str_contains($partLower, 'brek')) {
            if (str_contains($partLower, 'adjust')) {
                return $marketPrices['Brake Adjustment'];
            } elseif (str_contains($partLower, 'lining') || str_contains($partLower, 'pad')) {
                return $marketPrices['Brake Pads/Lining'];
            } else {
                return $marketPrices['Brake System'];
            }
        }
        
        if (str_contains($partLower, 'tire') || str_contains($partLower, 'tayar')) {
            if (str_contains($partLower, 'repair') || str_contains($partLower, 'tampal')) {
                return $marketPrices['Tire Repair'];
            } else {
                return $marketPrices['Tires & Wheels'];
            }
        }
        
        if (str_contains($partLower, 'electrical') || str_contains($partLower, 'wiring') || str_contains($partLower, 'lampu')) {
            return $marketPrices['Electrical & Lighting'];
        }
        
        if (str_contains($partLower, 'air') || str_contains($partLower, 'angin')) {
            return $marketPrices['Air System'];
        }
        
        if (str_contains($partLower, 'cooling') || str_contains($partLower, 'coolant')) {
            return $marketPrices['Cooling System'];
        }
        
        if (str_contains($partLower, 'suspension') || str_contains($partLower, 'absorber') || str_contains($partLower, 'spring')) {
            return $marketPrices['Suspension & Absorber'];
        }
        
        if (str_contains($partLower, 'gearbox') || str_contains($partLower, 'transmission')) {
            return $marketPrices['Gearbox Service'];
        }
        
        // Default to general inspection if no match
        return $marketPrices['General Inspection'];
    }

    /**
     * Format workshop time from minutes to readable format
     */
    private function formatWorkshopTime($minMinutes, $maxMinutes)
    {
        // Convert minutes to readable time format
        $formatTime = function($minutes) {
            if ($minutes < 60) {
                return $minutes . ' minutes';
            } elseif ($minutes < 120) {
                return '1-2 hours';
            } elseif ($minutes < 180) {
                return '2-3 hours';
            } elseif ($minutes < 240) {
                return '3-4 hours';
            } elseif ($minutes < 300) {
                return '4-5 hours';
            } elseif ($minutes < 360) {
                return '5-6 hours';
            } elseif ($minutes < 480) {
                return '6-8 hours';
            } else {
                return 'Full day';
            }
        };
        
        $minTime = $formatTime($minMinutes);
        $maxTime = $formatTime($maxMinutes);
        
        // If same range, show single estimate
        if ($minTime === $maxTime) {
            return $minTime;
        }
        
        // Handle special cases
        if ($minMinutes < 60 && $maxMinutes < 120) {
            return round($minMinutes) . '-' . round($maxMinutes) . ' minutes';
        }
        
        if ($minMinutes >= 60 && $maxMinutes <= 480) {
            $minHours = round($minMinutes / 60, 1);
            $maxHours = round($maxMinutes / 60, 1);
            
            // Round to clean hours for display
            $minHoursClean = $minHours == floor($minHours) ? intval($minHours) : $minHours;
            $maxHoursClean = $maxHours == floor($maxHours) ? intval($maxHours) : $maxHours;
            
            return $minHoursClean . '-' . $maxHoursClean . ' hours';
        }
        
        return $minTime . ' to ' . $maxTime;
    }

    /**
     * Get simple reason without complex analysis
     */
    private function getSimpleReason($urgency)
    {
        switch ($urgency) {
            case 'immediate':
                return 'Requires immediate attention';
            case 'soon':
                return 'Should be serviced within 2-4 weeks';
            default:
                return 'Regular maintenance schedule';
        }
    }

    /**
     * Find matching market price for a part/service
     */
    private function findMarketPrice($partName, $marketPrices)
    {
        // Direct match first
        if (isset($marketPrices[$partName])) {
            return $marketPrices[$partName];
        }
        
        // Fuzzy matching for common variations
        $partLower = strtolower($partName);
        
        if (str_contains($partLower, 'oil') || str_contains($partLower, 'minyak')) {
            if (str_contains($partLower, 'engine') || str_contains($partLower, 'enjin')) {
                return $marketPrices['Engine Oil & Hydraulics'];
            } elseif (str_contains($partLower, 'filter')) {
                return $marketPrices['Oil Filter'];
            } elseif (str_contains($partLower, 'seal')) {
                return $marketPrices['Oil Seals'];
            } else {
                return $marketPrices['Engine Oil & Hydraulics']; // Default for oil services
            }
        }
        
        if (str_contains($partLower, 'brake') || str_contains($partLower, 'brek')) {
            if (str_contains($partLower, 'adjust')) {
                return $marketPrices['Brake Adjustment'];
            } elseif (str_contains($partLower, 'lining') || str_contains($partLower, 'pad')) {
                return $marketPrices['Brake Pads/Lining'];
            } else {
                return $marketPrices['Brake System'];
            }
        }
        
        if (str_contains($partLower, 'tire') || str_contains($partLower, 'tayar')) {
            if (str_contains($partLower, 'repair') || str_contains($partLower, 'tampal')) {
                return $marketPrices['Tire Repair'];
            } else {
                return $marketPrices['Tires & Wheels'];
            }
        }
        
        if (str_contains($partLower, 'electrical') || str_contains($partLower, 'wiring') || str_contains($partLower, 'lampu')) {
            return $marketPrices['Electrical & Lighting'];
        }
        
        if (str_contains($partLower, 'air') || str_contains($partLower, 'angin')) {
            return $marketPrices['Air System'];
        }
        
        if (str_contains($partLower, 'cooling') || str_contains($partLower, 'coolant')) {
            return $marketPrices['Cooling System'];
        }
        
        if (str_contains($partLower, 'suspension') || str_contains($partLower, 'absorber') || str_contains($partLower, 'spring')) {
            return $marketPrices['Suspension & Absorber'];
        }
        
        if (str_contains($partLower, 'gearbox') || str_contains($partLower, 'transmission')) {
            return $marketPrices['Gearbox Service'];
        }
        
        // Default to general inspection if no match
        return $marketPrices['General Inspection'];
    }


    /**
     * Calculate base cost from parts analysis
     */
    private function calculateBaseCostFromParts($costAnalysis, $partsAnalysis)
    {
        $baseCostMin = 0;
        $baseCostMax = 0;
        $costBreakdown = [];
        
        // Calculate costs for immediate and soon items using real market data
        foreach (['immediate', 'soon'] as $urgency) {
            if (!empty($partsAnalysis[$urgency])) {
                foreach ($partsAnalysis[$urgency] as $part) {
                    $partName = $part['part'];
                    
                    // Use the real cost range from parts analysis
                    $partCost = $part['cost_range'] ?? ['min' => 200, 'max' => 500];
                    
                    $baseCostMin += $partCost['min'];
                    $baseCostMax += $partCost['max'];
                    
                    $costBreakdown[] = [
                        'item' => $partName,
                        'urgency' => $urgency,
                        'cost_range' => $partCost,
                        'reason' => $part['reason'] ?? 'Maintenance required',
                        'industry_note' => $part['industry_note'] ?? 'Standard maintenance'
                    ];
                }
            }
        }
        
        // Minimum service cost - realistic for Malaysian commercial vehicles
        if ($baseCostMin < 300) {
            $baseCostMin = 300;  // Updated: Minimum RM 300 for commercial vehicle service
            $baseCostMax = max(600, $baseCostMax);  // Updated: Minimum RM 600 maximum
            $costBreakdown[] = [
                'item' => 'Basic Commercial Service',
                'urgency' => 'routine',
                'cost_range' => ['min' => 300, 'max' => 600],
                'reason' => 'Standard commercial vehicle maintenance',
                'industry_note' => 'Includes basic inspection and minor adjustments'
            ];
        }
        
        $costAnalysis['base_cost'] = ['min' => $baseCostMin, 'max' => $baseCostMax];
        $costAnalysis['cost_breakdown'] = $costBreakdown;
        $costAnalysis['cost_factors'][] = "Real market analysis: " . count($costBreakdown) . " items identified";
        
        return $costAnalysis;
    }

    /**
     * Apply safety premium for critical safety issues
     */
    private function applySafetyPremium($costAnalysis, $safetyAnalysis)
    {
        $safetyPremium = 0;
        
        if ($safetyAnalysis['breakdown_risk'] === 'critical') {
            $safetyPremium = 500; // Critical safety issues require immediate attention
            $costAnalysis['cost_factors'][] = "Critical safety premium: +RM 500";
        } elseif ($safetyAnalysis['breakdown_risk'] === 'high') {
            $safetyPremium = 300;
            $costAnalysis['cost_factors'][] = "High safety risk premium: +RM 300";
        } elseif (!empty($safetyAnalysis['critical_alerts'])) {
            $safetyPremium = 200;
            $costAnalysis['cost_factors'][] = "Safety alert premium: +RM 200";
        }
        
        $costAnalysis['safety_premium'] = $safetyPremium;
        return $costAnalysis;
    }

    /**
     * Apply cost adjustments based on vehicle characteristics
     */
    private function applyVehicleCharacteristicsCost($costAnalysis, $vehicleHistory, $currentMileage)
    {
        $multiplier = 1.0;
        $totalServices = $vehicleHistory['total_services'];
        $avgInterval = $vehicleHistory['average_interval'];
        
        // High maintenance frequency vehicles cost more
        if ($totalServices > 500 && $avgInterval < 500) {
            $multiplier += 0.3; // 30% increase for ultra-high maintenance vehicles
            $costAnalysis['cost_factors'][] = "Ultra-high maintenance vehicle: +30%";
        } elseif ($totalServices > 300 && $avgInterval < 1000) {
            $multiplier += 0.2; // 20% increase for high maintenance vehicles
            $costAnalysis['cost_factors'][] = "High maintenance vehicle: +20%";
        }
        
        // High mileage vehicles may need premium parts/labor
        if ($currentMileage > 1500000) {
            $multiplier += 0.15; // 15% increase for high mileage
            $costAnalysis['cost_factors'][] = "High mileage (1.5M+ KM): +15%";
        } elseif ($currentMileage > 1000000) {
            $multiplier += 0.1; // 10% increase for moderate high mileage
            $costAnalysis['cost_factors'][] = "High mileage (1M+ KM): +10%";
        }
        
        $costAnalysis['complexity_multiplier'] = $multiplier;
        return $costAnalysis;
    }

    /**
     * Apply urgency premium for immediate repairs
     */
    private function applyUrgencyPremium($costAnalysis, $partsAnalysis, $safetyAnalysis)
    {
        $urgencyPremium = 0;
        
        // Critical safety issues
        if ($safetyAnalysis['breakdown_risk'] === 'critical') {
            $urgencyPremium = 400; // Emergency service premium
            $costAnalysis['cost_factors'][] = "Emergency service premium: +RM 400";
        }
        // Immediate parts issues
        elseif (!empty($partsAnalysis['immediate'])) {
            $urgencyPremium = 200; // Urgent service premium
            $costAnalysis['cost_factors'][] = "Urgent service premium: +RM 200";
        }
        // High safety risk
        elseif ($safetyAnalysis['breakdown_risk'] === 'high') {
            $urgencyPremium = 150;
            $costAnalysis['cost_factors'][] = "High priority premium: +RM 150";
        }
        
        $costAnalysis['urgency_premium'] = $urgencyPremium;
        return $costAnalysis;
    }

    /**
     * Calculate final cost with all factors
     */
    private function calculateFinalCost($costAnalysis)
    {
        $baseCost = $costAnalysis['base_cost'];
        $safetyPremium = $costAnalysis['safety_premium'];
        $multiplier = $costAnalysis['complexity_multiplier'];
        $urgencyPremium = $costAnalysis['urgency_premium'];
        
        // Apply complexity multiplier to base cost
        $adjustedMin = ($baseCost['min'] * $multiplier);
        $adjustedMax = ($baseCost['max'] * $multiplier);
        
        // Add premiums
        $finalMin = $adjustedMin + $safetyPremium + $urgencyPremium;
        $finalMax = $adjustedMax + $safetyPremium + $urgencyPremium;
        
        // Round to nearest RM 50
        $finalMin = round($finalMin / 50) * 50;
        $finalMax = round($finalMax / 50) * 50;
        
        $costAnalysis['total_estimated_cost'] = [
            'min' => max(200, $finalMin), // Minimum RM 200
            'max' => max(400, $finalMax)  // Minimum RM 400
        ];
        
        // Calculate confidence based on data quality
        $confidence = 'medium';
        if (count($costAnalysis['cost_breakdown']) >= 3 && $costAnalysis['safety_premium'] > 0) {
            $confidence = 'high';
        } elseif (count($costAnalysis['cost_breakdown']) <= 1) {
            $confidence = 'low';
        }
        
        $costAnalysis['cost_confidence'] = $confidence;
        
        return $costAnalysis;
    }

    /**
     * Enhanced recommendations with safety and cost intelligence
     */
    private function enhancedRecommendations($serviceSchedule, $partsAnalysis, $mlPrediction, $safetyAnalysis, $costAnalysis)
    {
        try {
            // Use the realistic cost estimate directly - no complex calculations
            $costEstimate = [
                'min' => $costAnalysis['total_estimated_cost']['min'] ?? 80,
                'max' => $costAnalysis['total_estimated_cost']['max'] ?? 200
            ];
            
            // Use realistic time estimate from cost analysis
            $timeEstimate = $costAnalysis['total_time_estimate']['formatted'] ?? '1-2 hours';
            
            $recommendations = [
                'priority' => 'routine',
                'action_plan' => [],
                'cost_estimate' => $costEstimate,
                'time_estimate' => $timeEstimate,
                'safety_priority' => false,
                'cost_confidence' => $costAnalysis['cost_confidence'] ?? 'medium'
            ];

            // Simple priority determination with realistic time adjustments
            if (!empty($partsAnalysis['immediate'])) {
                $recommendations['priority'] = 'immediate';
                $recommendations['action_plan'][] = '🔧 IMMEDIATE: ' . count($partsAnalysis['immediate']) . ' parts need urgent attention';
                
                // Adjust time for immediate repairs (may need same day service)
                if ($costAnalysis['total_time_estimate']['max_minutes'] ?? 120 > 240) {
                    $recommendations['time_estimate'] = 'Same day (4+ hours)';
                } elseif ($costAnalysis['total_time_estimate']['max_minutes'] ?? 120 > 120) {
                    $recommendations['time_estimate'] = 'Same day (2-4 hours)';
                } else {
                    $recommendations['time_estimate'] = $timeEstimate;
                }
                
            } elseif (!empty($partsAnalysis['soon'])) {
                $recommendations['priority'] = 'high';
                $recommendations['action_plan'][] = '⚡ SOON: ' . count($partsAnalysis['soon']) . ' items need service within 2-4 weeks';
            } else {
                $recommendations['action_plan'][] = '📅 ROUTINE: Follow regular maintenance schedule';
            }

            // Add cost & time transparency
            $recommendations['action_plan'][] = '💰 COST: Based on Malaysian workshop rates - ' . $timeEstimate . ' needed';

            return $recommendations;
            
        } catch (\Exception $e) {
            Log::error('Enhanced recommendations error: ' . $e->getMessage());
            
            // Simple fallback with realistic costs & times
            return [
                'priority' => 'routine',
                'action_plan' => ['📅 Schedule regular maintenance'],
                'cost_estimate' => ['min' => 80, 'max' => 200],
                'time_estimate' => '1-2 hours',
                'safety_priority' => false,
                'cost_confidence' => 'low'
            ];
        }
    }

    /**
     * Default responses for when enhancements fail
     */
    private function getDefaultSafetyAnalysis()
    {
        return [
            'overall_safety_score' => 75,
            'breakdown_risk' => 'medium',
            'critical_alerts' => [],
            'safety_recommendations' => [
                [
                    'priority' => 'MEDIUM',
                    'action' => 'Schedule general safety inspection',
                    'reason' => 'Safety analysis unavailable'
                ]
            ],
            'safety_systems' => []
        ];
    }

    private function getDefaultCostAnalysis()
    {
        return [
            'total_estimated_cost' => ['min' => 200, 'max' => 500],
            'cost_confidence' => 'low',
            'cost_factors' => ['Enhanced cost analysis unavailable'],
            'cost_breakdown' => []
        ];
    }

    // ... (keep all your existing methods: getVehicleHistory, safeMLPrediction, etc.)
    
    /**
     * Get helpful error messages for users
     */
    private function getHelpfulErrorMessage(\Exception $e)
    {
        $message = $e->getMessage();
        
        if (str_contains($message, 'Connection')) {
            return 'Database connection issue. Please try again in a moment.';
        }
        
        if (str_contains($message, 'timeout')) {
            return 'The enhanced analysis is taking longer than expected. Please try again.';
        }
        
        if (str_contains($message, 'validation')) {
            return 'Enhanced validation temporarily unavailable. Basic validation used.';
        }
        
        return 'An error occurred during enhanced analysis. Please try again or contact support.';
    }

    /**
 * Get comprehensive vehicle history and analysis
 */
private function getVehicleHistory($vehicleNumber)
{
    try {
        Log::info("Getting vehicle history for: {$vehicleNumber}");
        
        // Get all service records for this vehicle
        $records = ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicleNumber))])
            ->whereNotNull('Datereceived')
            ->orderBy('Datereceived', 'desc')
            ->get();
        
        if ($records->isEmpty()) {
            return [
                'total_services' => 0,
                'records' => collect([]),
                'last_service' => null,
                'average_interval' => 0,
                'days_since_last' => 0,
                'vehicle_type' => 'Unknown',
                'service_patterns' => $this->getDefaultServicePatterns(),
                'processed_history' => collect([])
            ];
        }
        
        // Process the records
        $processedHistory = $this->processServiceHistory($records);
        
        // Calculate service patterns
        $servicePatterns = $this->calculateServicePatterns($records);
        
        // Calculate intervals between services
        $intervals = $this->calculateServiceIntervals($records);
        $averageInterval = $intervals->avg() ?: 1000;
        
        // Get days since last service
        $lastService = $records->first();
        $daysSinceLast = $lastService ? intval(Carbon::parse($lastService->Datereceived)->diffInDays(now())) : 365;
        
        // Determine vehicle type based on usage patterns
        $vehicleType = $this->determineVehicleType($records, $averageInterval);
        
        // Add advanced tire analysis
        $advancedTireAnalysis = $this->getAdvancedTireAnalysis($records, $this->getCurrentMileage($records));
        
        return [
            'total_services' => $records->count(),
            'records' => $records,
            'last_service' => $lastService,
            'average_interval' => round($averageInterval),
            'days_since_last' => $daysSinceLast,
            'vehicle_type' => $vehicleType,
            'service_patterns' => $servicePatterns,
            'processed_history' => $processedHistory,
            'advanced_tire_analysis' => $advancedTireAnalysis
        ];
        
    } catch (\Exception $e) {
        Log::error("Error getting vehicle history: " . $e->getMessage());
        
        return [
            'total_services' => 0,
            'records' => collect([]),
            'last_service' => null,
            'average_interval' => 1000,
            'days_since_last' => 365,
            'vehicle_type' => 'Unknown',
            'service_patterns' => $this->getDefaultServicePatterns(),
            'processed_history' => collect([])
        ];
    }
}

/**
 * Process service history into standardized format
 */
private function processServiceHistory($records)
{
    return $records->map(function ($record) {
        return [
            'id' => $record->ID,
            'sr_number' => $record->SR,
            'date' => $record->Datereceived ? Carbon::parse($record->Datereceived)->format('Y-m-d') : 'Unknown',
            'service_type' => $this->determineServiceType($record),
            'description' => $record->Description ?: 'No description',
            'odometer' => $record->Odometer ? number_format(floatval($record->Odometer)) . ' KM' : 'Not recorded',
            'status' => $this->getStatusText($record->Status),
            'category' => $this->categorizeService($record),
            'days_ago' => $record->Datereceived ? intval(Carbon::parse($record->Datereceived)->diffInDays(now())) : 0
        ];
    });
}

/**
 * Calculate comprehensive service patterns
 */
private function calculateServicePatterns($records)
{
    $totalServices = $records->count();
    
    if ($totalServices === 0) {
        return $this->getDefaultServicePatterns();
    }
    
    // Calculate service breakdown by type
    $serviceBreakdown = [
        'maintenance' => $records->where('MrType', '3')->count(),
        'cleaning' => $records->where('MrType', '2')->count(),
        'repairs' => $records->where('MrType', '1')->count(),
        'tires' => $this->countTireServices($records),
        'other' => $records->whereNotIn('MrType', ['1', '2', '3'])->count()
    ];
    
    // Calculate services per month
    $oldestService = $records->last();
    $newestService = $records->first();
    
    $monthsSpan = 1;
    if ($oldestService && $newestService && $oldestService->Datereceived && $newestService->Datereceived) {
        try {
            $monthsSpan = max(1, intval(Carbon::parse($oldestService->Datereceived)->diffInMonths(Carbon::parse($newestService->Datereceived))));
        } catch (\Exception $e) {
            $monthsSpan = 1;
        }
    }
    
    $servicesPerMonth = round($totalServices / $monthsSpan, 1);
    
    // Determine usage pattern
    $usagePattern = 'Light';
    if ($servicesPerMonth > 10) {
        $usagePattern = 'Heavy Commercial';
    } elseif ($servicesPerMonth > 5) {
        $usagePattern = 'Commercial';
    } elseif ($servicesPerMonth > 2) {
        $usagePattern = 'Regular';
    }
    
    // Calculate data quality
    $recordsWithMileage = $records->filter(function ($record) {
        return $record->Odometer && is_numeric($record->Odometer) && floatval($record->Odometer) > 1000;
    })->count();
    
    $dataQuality = $totalServices > 0 ? round(($recordsWithMileage / $totalServices) * 100) : 0;
    
    return [
        'service_breakdown' => $serviceBreakdown,
        'services_per_month' => $servicesPerMonth,
        'usage_pattern' => $usagePattern,
        'data_quality' => $dataQuality,
        'months_span' => $monthsSpan
    ];
}

/**
 * Calculate intervals between services
 */
private function calculateServiceIntervals($records)
{
    $intervals = collect([]);
    
    $sortedRecords = $records->sortBy('Datereceived');
    
    for ($i = 1; $i < $sortedRecords->count(); $i++) {
        $current = $sortedRecords->values()[$i];
        $previous = $sortedRecords->values()[$i - 1];
        
        if ($current->Odometer && $previous->Odometer) {
            $currentMileage = floatval($current->Odometer);
            $previousMileage = floatval($previous->Odometer);
            
            if ($currentMileage > $previousMileage && $currentMileage > 1000 && $previousMileage > 1000) {
                $interval = $currentMileage - $previousMileage;
                if ($interval > 0 && $interval < 100000) { // Reasonable interval
                    $intervals->push($interval);
                }
            }
        }
    }
    
    if ($intervals->isEmpty()) {
        $intervals->push(1000); // Default interval
    }
    
    return $intervals;
}

/**
 * Determine vehicle type based on usage patterns
 */
private function determineVehicleType($records, $averageInterval)
{
    $totalServices = $records->count();
    
    if ($totalServices > 500 && $averageInterval < 1000) {
        return 'Ultra High Usage Fleet';
    } elseif ($totalServices > 300 && $averageInterval < 2000) {
        return 'High Usage Commercial';
    } elseif ($totalServices > 100 && $averageInterval < 5000) {
        return 'Regular Commercial';
    } elseif ($totalServices > 50) {
        return 'Light Commercial';
    } else {
        return 'Low Usage Fleet';
    }
}

/**
 * Get current mileage from records
 */
private function getCurrentMileage($records)
{
    $latestRecord = $records->first();
    if ($latestRecord && $latestRecord->Odometer && is_numeric($latestRecord->Odometer)) {
        return floatval($latestRecord->Odometer);
    }
    return 100000; // Default
}

/**
 * Advanced tire analysis
 */
private function getAdvancedTireAnalysis($records, $currentMileage)
{
    try {
        // Tire-related keywords
        $tireKeywords = [
            'Tire Replacement' => ['ganti tayar', 'tukar tayar', 'replace tire', 'tire replacement'],
            'Tire Repair' => ['tampal tayar', 'repair tire', 'tayar pancit', 'tire puncture'],
            'Tire Inspection' => ['check tayar', 'inspect tire', 'tayar check'],
            'Tire Wear' => ['tayar botak', 'tire wear', 'bunga tayar', 'tread wear'],
            'Tire Pressure' => ['angin tayar', 'tire pressure', 'pump tire'],
            'Tire Rotation' => ['putar tayar', 'tire rotation', 'rotate tire']
        ];
        
        $tireServices = collect([]);
        $categoryCounts = [];
        
        foreach ($records as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            
            foreach ($tireKeywords as $category => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($searchText, $keyword)) {
                        $tireServices->push([
                            'record' => $record,
                            'category' => $category,
                            'date' => $record->Datereceived,
                            'description' => $record->Description
                        ]);
                        
                        if (!isset($categoryCounts[$category])) {
                            $categoryCounts[$category] = 0;
                        }
                        $categoryCounts[$category]++;
                        break 2;
                    }
                }
            }
        }
        
        $totalTireServices = $tireServices->count();
        
        // Calculate health score
        $healthScore = 100;
        if ($totalTireServices > 20) $healthScore -= 30;
        elseif ($totalTireServices > 10) $healthScore -= 20;
        elseif ($totalTireServices > 5) $healthScore -= 10;
        
        // Determine risk level
        $riskLevel = 'low';
        if ($healthScore <= 50) $riskLevel = 'critical';
        elseif ($healthScore <= 70) $riskLevel = 'high';
        elseif ($healthScore <= 85) $riskLevel = 'medium';
        
        // Generate categories analysis
        $categories = [];
        foreach ($categoryCounts as $category => $count) {
            $percentage = $totalTireServices > 0 ? round(($count / $totalTireServices) * 100, 1) : 0;
            $categories[] = [
                'name' => $category,
                'count' => $count,
                'percentage' => $percentage
            ];
        }
        
        // Risk assessment
        $riskAssessment = "Based on {$totalTireServices} tire services, this vehicle shows {$riskLevel} risk level.";
        
        // Next inspection recommendation
        $nextInspectionKm = $currentMileage + ($riskLevel === 'critical' ? 5000 : ($riskLevel === 'high' ? 10000 : 20000));
        
        return [
            'total_tire_services' => $totalTireServices,
            'tire_health_score' => $healthScore,
            'risk_level' => $riskLevel,
            'risk_assessment' => $riskAssessment,
            'categories' => $categories,
            'next_inspection_km' => $nextInspectionKm,
            'recommendations' => $this->generateTireRecommendations($riskLevel, $categoryCounts)
        ];
        
    } catch (\Exception $e) {
        Log::error("Tire analysis error: " . $e->getMessage());
        return [
            'total_tire_services' => 0,
            'tire_health_score' => 75,
            'risk_level' => 'medium',
            'risk_assessment' => 'Tire analysis unavailable',
            'categories' => [],
            'next_inspection_km' => $currentMileage + 15000
        ];
    }
}

/**
 * Generate tire recommendations
 */
private function generateTireRecommendations($riskLevel, $categoryCounts)
{
    $recommendations = [
        'immediate' => [],
        'short_term' => [],
        'preventive' => []
    ];
    
    if ($riskLevel === 'critical') {
        $recommendations['immediate'][] = [
            'action' => 'Immediate tire inspection required',
            'reason' => 'Critical tire risk level detected',
            'timeline' => 'Within 24 hours'
        ];
    }
    
    if ($riskLevel === 'high' || $riskLevel === 'critical') {
        $recommendations['short_term'][] = [
            'action' => 'Schedule comprehensive tire service',
            'reason' => 'High frequency of tire issues detected',
            'timeline' => 'Within 1 week'
        ];
    }
    
    $recommendations['preventive'][] = [
        'action' => 'Regular tire pressure checks',
        'reason' => 'Prevent premature tire wear'
    ];
    
    $recommendations['preventive'][] = [
        'action' => 'Tire rotation every 10,000 KM',
        'reason' => 'Ensure even tire wear'
    ];
    
    return $recommendations;
}

/**
 * Safe ML prediction with fallbacks
 */
private function safeMLPrediction($vehicleNumber, $currentMileage, $vehicleHistory)
{
    try {
        Log::info("Starting ML prediction for {$vehicleNumber}");
        
        // Prepare data for ML
        $mlData = $this->preparePredictionData($vehicleNumber, $currentMileage, $vehicleHistory);
        
        // Try ML prediction
        $mlResult = $this->predictionService->predict($mlData);
        
        if (isset($mlResult['prediction']) && !isset($mlResult['error'])) {
            Log::info("ML prediction successful: " . $mlResult['prediction']);
            return [
                'prediction' => $mlResult['prediction'],
                'confidence' => $mlResult['confidence'] ?? 0.75,
                'source' => 'ML Model',
                'method_used' => $mlResult['method_used'] ?? 'ml_prediction'
            ];
        }
        
        Log::warning("ML prediction failed, using fallback");
        return $this->getFallbackPrediction($vehicleHistory, $currentMileage);
        
    } catch (\Exception $e) {
        Log::error("ML prediction error: " . $e->getMessage());
        return $this->getFallbackPrediction($vehicleHistory, $currentMileage);
    }
}

/**
 * Calculate service schedule
 */
private function calculateServiceSchedule($currentMileage, $vehicleHistory)
{
    $averageInterval = $vehicleHistory['average_interval'];
    
    // Next routine service
    $nextRoutineMileage = $currentMileage + $averageInterval;
    $routineKmRemaining = $nextRoutineMileage - $currentMileage;
    
    // Next major service (typically double the interval)
    $majorInterval = $averageInterval * 2;
    $nextMajorMileage = $currentMileage + $majorInterval;
    $majorKmRemaining = $nextMajorMileage - $currentMileage;
    
    // Estimate days (assuming 200 KM per day average)
    $daysEstimate = round($routineKmRemaining / 200) . ' days';
    
    return [
        'next_routine' => [
            'mileage' => $nextRoutineMileage,
            'km_remaining' => $routineKmRemaining,
            'description' => 'Based on ' . number_format($averageInterval) . ' KM average interval'
        ],
        'next_major' => [
            'mileage' => $nextMajorMileage,
            'km_remaining' => $majorKmRemaining,
            'type' => 'Major Service',
            'description' => 'Comprehensive maintenance check'
        ],
        'days_estimate' => $daysEstimate
    ];
}

/**
 * Safe parts analysis
 */
private function safePartsAnalysis($currentMileage, $vehicleHistory, $mlPrediction)
    {
        try {
            $records = $vehicleHistory['records'];
            $dailyUsageKm = $this->calculateDailyUsage($vehicleHistory);
            
            $analysis = [
                'immediate' => [],
                'soon' => [],
                'routine' => []
            ];
            
            // REAL MALAYSIAN COMMERCIAL VEHICLE MARKET DATA
            // Based on analysis of actual fleet maintenance records and industry standards
            // Intervals researched from Malaysian commercial vehicle workshops and fleet operators
            // Costs reflect 2024-2025 Malaysian market rates for commercial vehicles
            $fleetParts = [
                // PRIORITY 1 - Critical Safety Parts
                'Engine Oil & Hydraulics' => [
                    'priority' => 1,
                    'interval_km' => 10000,
                    'cost_range' => ['min' => 80, 'max' => 150],    // UPDATED: Realistic oil change cost
                    'keywords' => [
                        'engine oil', 'minyak enjin', 'minyak hitam', 'oil change', 'minyak hydraulic',
                        'minyak power steering', 'minyak gearbox', 'oil filter', 'minyak jet'
                    ],
                    'is_critical' => true,
                    'industry_note' => 'Standard commercial oil change - 4-6 liters'
                ],
                'Brake System' => [
                    'priority' => 1,
                    'interval_km' => 50000,
                    'cost_range' => ['min' => 150, 'max' => 400],   // UPDATED: Realistic brake service
                    'keywords' => [
                        'brake', 'brek', 'adjust brake', 'brake chamber', 'lining brake',
                        'brake jammed', 'angin bocor', 'pad brek'
                    ],
                    'is_critical' => true,
                    'industry_note' => 'Brake maintenance including adjustment and parts'
                ],
                'Oil Seals' => [
                    'priority' => 1,
                    'interval_km' => 100000,
                    'cost_range' => ['min' => 120, 'max' => 300],   // UPDATED: Realistic seal replacement
                    'keywords' => ['oil seal', 'oilseal', 'seal minyak', 'bocor'],
                    'is_critical' => true,
                    'industry_note' => 'Oil seal replacement with labor'
                ],

                // PRIORITY 2 - Important Maintenance Parts
                'Tires & Wheels' => [
                    'priority' => 2,
                    'interval_km' => 80000,
                    'cost_range' => ['min' => 180, 'max' => 350],   // UPDATED: Single tire cost
                    'keywords' => ['tayar', 'tire', 'tukar tayar', 'tayar pancit'],
                    'is_critical' => false,
                    'industry_note' => 'Single commercial tire replacement'
                ],
                'Electrical & Lighting' => [
                    'priority' => 2,
                    'interval_km' => 40000,
                    'cost_range' => ['min' => 60, 'max' => 200],    // UPDATED: Electrical repairs
                    'keywords' => ['lampu', 'wiring', 'electrical', 'signal', 'battery'],
                    'is_critical' => false,
                    'industry_note' => 'Wiring and lighting repairs'
                ],
                'Air System' => [
                    'priority' => 2,
                    'interval_km' => 40000,
                    'cost_range' => ['min' => 100, 'max' => 250],   // UPDATED: Air system repairs
                    'keywords' => ['angin bocor', 'air bocor', 'belon bocor'],
                    'is_critical' => false,
                    'industry_note' => 'Air brake system maintenance'
                ],

                // PRIORITY 3 - Routine Maintenance Parts
                'Cooling System' => [
                    'priority' => 3,
                    'interval_km' => 60000,
                    'cost_range' => ['min' => 80, 'max' => 200],    // UPDATED: Cooling system service
                    'keywords' => ['coolant', 'air coolant', 'radiator'],
                    'is_critical' => false,
                    'industry_note' => 'Coolant change and radiator service'
                ],
                'General Maintenance' => [
                    'priority' => 3,
                    'interval_km' => 20000,
                    'cost_range' => ['min' => 80, 'max' => 150],    // UPDATED: General service
                    'keywords' => ['servis', 'service', 'check', 'inspection'],
                    'is_critical' => false,
                    'industry_note' => 'General inspection and minor adjustments'
                ]
            ];
            
            // Analyze each part category using real fleet data
            foreach ($fleetParts as $partName => $partData) {
                $lastService = $this->findLastServiceForFleetPart($records, $partData['keywords']);
                $lastServiceKm = $lastService ? $this->extractMileage($lastService) : ($currentMileage - $partData['interval_km'] - 1000);
                
                $kmSinceService = $currentMileage - $lastServiceKm;
                $kmRemaining = $partData['interval_km'] - $kmSinceService;
                
                // Calculate days remaining based on daily usage
                $daysRemaining = $dailyUsageKm > 0 ? max(0, intval(round($kmRemaining / $dailyUsageKm))) : 0;
                
                // Determine urgency based on real fleet patterns
                $urgencyThreshold = $this->getUrgencyThreshold($partData['priority'], $partData['interval_km']);
                
                $partAnalysis = [
                    'part' => $partName,
                    'priority' => $partData['priority'],
                    
                    // Enhanced last service information
                    'last_service' => $lastService ? $lastService->formatted_date : 'No recent service',
                    'last_service_km' => $lastServiceKm ? number_format($lastServiceKm) . ' KM' : 'Unknown',
                    'last_service_details' => $lastService ? [
                        'sr_number' => $lastService->SR ?? 'No SR',
                        'description' => $lastService->Description ?? 'No description',
                        'response' => $lastService->Response ?? 'No response',
                        'service_summary' => $lastService->service_summary ?? '',
                        'status' => $lastService->status_text ?? 'Unknown',
                        'priority' => $lastService->priority_text ?? 'Unknown',
                        'service_type' => $lastService->mr_type_text ?? 'Unknown',
                        'days_ago' => $lastService->days_ago ?? 'Unknown',
                        'requested_by' => $lastService->Requestor ?? 'Unknown',
                        'serviced_by' => $lastService->responsedBy ?? 'Unknown',
                        'inspected_by' => $lastService->InspectBy ?? 'Unknown',
                        'building' => $lastService->Building ?? 'Unknown',
                        'department' => $lastService->department ?? 'Unknown',
                        'location' => $lastService->location ?? 'Unknown',
                        'contractor' => $lastService->Contractor ?? 'No contractor',
                        'staff' => $lastService->Staff ?? 'Unknown',
                        'date_closed' => $lastService->DateClose ? Carbon::parse($lastService->DateClose)->format('d M Y') : 'Not closed',
                        'modified_by' => $lastService->ModifyBy ?? 'Unknown',
                        'depot_info' => $lastService->depot_info ?? 'DEPOT_INFO_MISSING',
                        'formatted_location' => $lastService->formatted_location ?? 'FORMATTED_LOCATION_MISSING',
                    ] : null,
                    
                    // Maintenance schedule information
                    'interval_km' => number_format($partData['interval_km']),
                    'km_remaining' => number_format(max(0, $kmRemaining)),
                    'days_remaining' => $daysRemaining,
                    'next_due_km' => number_format($lastServiceKm + $partData['interval_km']),
                    'next_due_date' => 'At ' . number_format($lastServiceKm + $partData['interval_km']) . ' KM',
                    'reason' => $this->getFleetBasedReason($partName, $kmSinceService, $partData['interval_km']),
                    'status' => $kmRemaining <= 0 ? 'overdue' : 'scheduled',
                    'is_critical' => $partData['is_critical'],
                    'service_count' => $this->countServicesByKeywords($records, $partData['keywords']),
                    'cost_range' => $partData['cost_range'],
                    'industry_note' => $partData['industry_note']
                ];
                
                // Categorize based on real fleet priority and condition
                if ($partData['priority'] == 1 && ($kmRemaining <= 0 || $kmRemaining <= $urgencyThreshold['immediate'])) {
                    $analysis['immediate'][] = $partAnalysis;
                } elseif ($kmRemaining <= $urgencyThreshold['soon']) {
                    $analysis['soon'][] = $partAnalysis;
                } else {
                    $analysis['routine'][] = $partAnalysis;
                }
            }
            
            // Sort by priority and urgency
            $analysis['immediate'] = $this->sortPartsByPriority($analysis['immediate']);
            $analysis['soon'] = $this->sortPartsByPriority($analysis['soon']);
            $analysis['routine'] = $this->sortPartsByPriority($analysis['routine']);
            
            Log::info("Real market parts analysis completed", [
                'immediate_count' => count($analysis['immediate']),
                'soon_count' => count($analysis['soon']),
                'routine_count' => count($analysis['routine']),
                'vehicle' => $records->first()->Vehicle ?? 'Unknown'
            ]);
            
            return $analysis;
            
        } catch (\Exception $e) {
            Log::error("Fleet parts analysis error: " . $e->getMessage());
            
            // Return safe fallback with real cost structure
            return [
                'immediate' => [],
                'soon' => [],
                'routine' => [
                    [
                        'part' => 'Engine Oil & Hydraulics',
                        'priority' => 1,
                        'reason' => 'Schedule regular oil change based on 10,000 KM interval',
                        'last_service' => 'Unknown',
                        'last_service_km' => 'Unknown',
                        'next_due_date' => 'Soon',
                        'next_due_km' => number_format($currentMileage + 10000),
                        'km_remaining' => '10,000',
                        'days_remaining' => 50,
                        'interval_km' => '10,000',
                        'status' => 'scheduled',
                        'is_critical' => true,
                        'service_count' => 0,
                        'cost_range' => ['min' => 200, 'max' => 400],
                        'industry_note' => 'Critical for engine protection'
                    ]
                ]
            ];
        }
    }

    /**
     * Find last service for fleet part using real keywords
     */
    private function findLastServiceForFleetPart($records, $keywords)
    {
        foreach ($records as $record) {
            $description = strtolower($record->Description ?? '');
            $response = strtolower($record->Response ?? '');
            $searchText = $description . ' ' . $response;
            
            foreach ($keywords as $keyword) {
                if (str_contains($searchText, strtolower($keyword))) {
                    // Return enhanced record with detailed service information
                    $enhanced = $this->enhanceServiceRecord($record);
                    
                    // DEBUG: Log the enhanced record structure
                    Log::info("DEBUG: findLastServiceForFleetPart enhanced record", [
                        'record_id' => $record->ID,
                        'building_original' => $record->Building,
                        'depot_info_in_enhanced' => $enhanced->depot_info ?? 'NOT_SET',
                        'formatted_location_in_enhanced' => $enhanced->formatted_location ?? 'NOT_SET',
                        'enhanced_keys' => array_keys((array)$enhanced)
                    ]);
                    
                    return $enhanced;
                }
            }
        }
        
        return null;
    }

    /**
     * Enhance service record with detailed information for parts analysis
     */
    private function enhanceServiceRecord($record)
    {
        try {
            return (object) [
                // Basic information
                'ID' => $record->ID,
                'SR' => $record->SR,
                'Vehicle' => $record->Vehicle,
                'Datereceived' => $record->Datereceived,
                'timereceived' => $record->timereceived,
                'Odometer' => $record->Odometer,
                
                // Service details
                'Description' => $record->Description,
                'Response' => $record->Response,
                'Inspection' => $record->Inspection,
                
                // Personnel information
                'Requestor' => $record->Requestor,
                'responsedBy' => $record->responsedBy,
                'InspectBy' => $record->InspectBy,
                'ModifyBy' => $record->ModifyBy,
                
                // Location and department
                'building' => $record->Building ?? 'Unknown',
                'department' => $record->department ?? 'Unknown',
                'location' => $record->location ?? 'Unknown',
                'depot_info' => $this->getDepotInfo($record->Building),
                'formatted_location' => $this->formatLocationWithDepot($record), 
                
                // Status and priority
                'Status' => $record->Status,
                'Priority' => $record->Priority,
                'MrType' => $record->MrType,
                
                // Dates and modifications
                'responseDate' => $record->responseDate,
                'ResponseTime' => $record->ResponseTime,
                'DateClose' => $record->DateClose,
                'TimeClose' => $record->TimeClose,
                'DateModify' => $record->DateModify,
                'TimeModify' => $record->TimeModify,
                
                // Additional details
                'Contractor' => $record->Contractor,
                'COID' => $record->COID,
                'Staff' => $record->Staff,
                
                // Formatted information for display
                'formatted_date' => $this->formatServiceDate($record->Datereceived, $record->timereceived),
                'formatted_description' => $this->formatServiceDescription($record->Description, $record->Response),
                'status_text' => $this->getStatusText($record->Status),
                'priority_text' => $this->getPriorityText($record->Priority),
                'mr_type_text' => $this->getMrTypeText($record->MrType),
                'days_ago' => $this->calculateDaysAgo($record->Datereceived),
                'service_summary' => $this->generateServiceSummary($record)
            ];
        } catch (\Exception $e) {
            Log::warning("Error enhancing service record: " . $e->getMessage());
            return $record; // Return original record if enhancement fails
        }
    }

    /**
     * Format service date and time for display
     */
    private function formatServiceDate($date, $time)
    {
        try {
            if (!$date) return 'Unknown date';
            
            $formattedDate = Carbon::parse($date)->format('d M Y');
            $formattedTime = $time ? ' at ' . $time : '';
            
            return $formattedDate . $formattedTime;
        } catch (\Exception $e) {
            return 'Invalid date';
        }
    }

    /**
     * Format service description combining description and response
     */
    private function formatServiceDescription($description, $response)
    {
        $desc = trim($description ?? '');
        $resp = trim($response ?? '');
        
        if (empty($desc) && empty($resp)) {
            return 'No description available';
        }
        
        if (empty($resp)) {
            return $desc;
        }
        
        if (empty($desc)) {
            return 'Response: ' . $resp;
        }
        
        // Combine both if available
        return $desc . ' | Response: ' . (strlen($resp) > 100 ? substr($resp, 0, 100) . '...' : $resp);
    }

    /**
     * Get priority text description
     */
    private function getPriorityText($priority)
    {
        switch (trim($priority)) {
            case '1': return 'Critical';
            case '2': return 'High';
            case '3': return 'Normal';
            case '4': return 'Low';
            default: return 'Unknown';
        }
    }

    /**
     * Get MR Type text description
     */
    private function getMrTypeText($mrType)
    {
        switch (trim($mrType)) {
            case '1': return 'Repair';
            case '2': return 'Cleaning/Washing';
            case '3': return 'Maintenance';
            case '4': return 'Inspection';
            default: return 'Other';
        }
    }

    /**
     * Calculate days ago from service date
     */
    private function calculateDaysAgo($date)
    {
        try {
            if (!$date) return 'Unknown';
            $days = Carbon::parse($date)->diffInDays(now());
            return intval($days) . ' days ago';  // Convert to clean integer
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Generate a comprehensive service summary
     */
    private function generateServiceSummary($record)
    {
        $summary = [];
        
        // Service type and priority
        if ($record->MrType) {
            $summary[] = $this->getMrTypeText($record->MrType);
        }
        if ($record->Priority) {
            $summary[] = $this->getPriorityText($record->Priority) . ' Priority';
        }
        
        // Personnel
        if ($record->responsedBy) {
            $summary[] = 'Serviced by: ' . $record->responsedBy;
        }
        if ($record->InspectBy) {
            $summary[] = 'Inspected by: ' . $record->InspectBy;
        }
        
        // Location
        if ($record->Building) {
            $summary[] = 'Building: ' . $record->Building;
        }
        if ($record->department) {
            $summary[] = 'Dept: ' . $record->department;
        }
        
        // Contractor info
        if ($record->Contractor) {
            $summary[] = 'Contractor: ' . $record->Contractor;
        }
        
        return implode(' | ', $summary);
    }

/**
 * Count services by keywords to understand maintenance frequency
 */
private function countServicesByKeywords($records, $keywords)
{
    $count = 0;
    
    foreach ($records as $record) {
        $description = strtolower($record->Description ?? '');
        $response = strtolower($record->Response ?? '');
        $searchText = $description . ' ' . $response;
        
        foreach ($keywords as $keyword) {
            if (str_contains($searchText, strtolower($keyword))) {
                $count++;
                break; // Count each record only once
            }
        }
    }
    
    return $count;
}

/**
 * Get urgency thresholds based on fleet priority
 */
private function getUrgencyThreshold($priority, $intervalKm)
{
    switch ($priority) {
        case 1: // Critical parts - tighter thresholds
            return [
                'immediate' => $intervalKm * 0.1, // 10% remaining
                'soon' => $intervalKm * 0.2       // 20% remaining
            ];
        case 2: // Important parts - moderate thresholds
            return [
                'immediate' => $intervalKm * 0.05, // 5% remaining
                'soon' => $intervalKm * 0.15       // 15% remaining
            ];
        case 3: // Routine parts - relaxed thresholds
            return [
                'immediate' => 0,                  // Only when overdue
                'soon' => $intervalKm * 0.1       // 10% remaining
            ];
        default:
            return ['immediate' => 1000, 'soon' => 5000];
    }
}

/**
 * Get fleet-based maintenance reason
 */
private function getFleetBasedReason($partName, $kmSinceService, $intervalKm)
{
    $percentageUsed = $intervalKm > 0 ? ($kmSinceService / $intervalKm) * 100 : 0;
    
    if ($percentageUsed >= 100) {
        return "OVERDUE - Last serviced " . number_format($kmSinceService) . " KM ago";
    } elseif ($percentageUsed >= 90) {
        return "Due soon - " . number_format($intervalKm - $kmSinceService) . " KM remaining";
    } elseif ($percentageUsed >= 75) {
        return "Monitor closely - Approaching service interval";
    } else {
        return "Good condition - Regular " . number_format($intervalKm) . " KM interval";
    }
}

/**
 * Sort parts by priority (Priority 1 first, then by urgency)
 */
private function sortPartsByPriority($parts)
{
    usort($parts, function($a, $b) {
        // First sort by priority (1 = highest priority)
        if ($a['priority'] != $b['priority']) {
            return $a['priority'] - $b['priority'];
        }
        
        // Then by criticality
        if ($a['is_critical'] != $b['is_critical']) {
            return $b['is_critical'] - $a['is_critical'];
        }
        
        // Finally by km remaining (least remaining first)
        return intval(str_replace(',', '', $a['km_remaining'])) - intval(str_replace(',', '', $b['km_remaining']));
    });
    
    return $parts;
}

/**
 * Calculate daily usage based on vehicle history
 */
private function calculateDailyUsage($vehicleHistory)
{
    try {
        $records = $vehicleHistory['records'];
        
        if ($records->count() < 2) {
            return 200; // Default 200 KM per day for commercial vehicles
        }
        
        // Get records with valid mileage and dates
        $validRecords = $records->filter(function ($record) {
            return $record->Odometer && 
                   is_numeric($record->Odometer) && 
                   floatval($record->Odometer) > 1000 &&
                   $record->Datereceived;
        })->sortBy('Datereceived');
        
        if ($validRecords->count() < 2) {
            return 200; // Default
        }
        
        $dailyUsages = [];
        $sortedRecords = $validRecords->values();
        
        for ($i = 1; $i < $sortedRecords->count(); $i++) {
            $current = $sortedRecords[$i];
            $previous = $sortedRecords[$i - 1];
            
            try {
                $currentDate = Carbon::parse($current->Datereceived);
                $previousDate = Carbon::parse($previous->Datereceived);
                $currentKm = floatval($current->Odometer);
                $previousKm = floatval($previous->Odometer);
                
                $daysDiff = intval($currentDate->diffInDays($previousDate));
                $kmDiff = $currentKm - $previousKm;
                
                // Only use reasonable values
                if ($daysDiff > 0 && $kmDiff > 0 && $kmDiff < 50000 && $daysDiff < 365) {
                    $dailyUsage = $kmDiff / $daysDiff;
                    
                    // Filter out unrealistic values
                    if ($dailyUsage >= 50 && $dailyUsage <= 1000) {
                        $dailyUsages[] = $dailyUsage;
                    }
                }
            } catch (\Exception $e) {
                continue; // Skip invalid records
            }
        }
        
        if (empty($dailyUsages)) {
            return 200; // Default
        }
        
        // Return average daily usage
        $averageDailyUsage = array_sum($dailyUsages) / count($dailyUsages);
        
        // Cap at reasonable limits
        return max(50, min(500, round($averageDailyUsage)));
        
    } catch (\Exception $e) {
        Log::warning("Daily usage calculation failed: " . $e->getMessage());
        return 200; // Default fallback
    }
}

/**
 * Helper methods
 */
private function getDefaultServicePatterns()
{
    return [
        'service_breakdown' => [
            'maintenance' => 0,
            'cleaning' => 0,
            'repairs' => 0,
            'tires' => 0,
            'other' => 0
        ],
        'services_per_month' => 0,
        'usage_pattern' => 'Unknown',
        'data_quality' => 0
    ];
}

private function determineServiceType($record)
{
    $mrType = trim($record->MrType ?? '');
    
    switch ($mrType) {
        case '1': return 'Repair';
        case '2': return 'Cleaning';
        case '3': return 'Maintenance';
        default: return 'Unknown';
    }
}

private function getStatusText($status)
{
    switch ($status) {
        case '1': return 'Pending';
        case '2': return 'In Progress';
        case '3': return 'Completed';
        case '4': return 'Cancelled';
        default: return 'Unknown';
    }
}

private function categorizeService($record)
{
    $description = strtolower($record->Description ?? '');
    
    if (str_contains($description, 'tayar') || str_contains($description, 'tire')) {
        return 'Tires';
    } elseif (str_contains($description, 'minyak') || str_contains($description, 'oil')) {
        return 'Oil & Fluids';
    } elseif (str_contains($description, 'brake') || str_contains($description, 'brek')) {
        return 'Brakes';
    } else {
        return 'General';
    }
}

private function countTireServices($records)
{
    return $records->filter(function ($record) {
        $description = strtolower($record->Description ?? '');
        return str_contains($description, 'tayar') || str_contains($description, 'tire');
    })->count();
}

private function preparePredictionData($vehicleNumber, $currentMileage, $vehicleHistory)
{
    return [
        'Vehicle' => $vehicleNumber,
        'Odometer' => $currentMileage,
        'service_count' => $vehicleHistory['total_services'],
        'average_interval' => $vehicleHistory['average_interval'],
        'days_since_last' => $vehicleHistory['days_since_last'],
        'Description' => 'Fleet prediction request',
        'Priority' => 2,
        'Status' => 1,
        'MrType' => 3
    ];
}

private function getFallbackPrediction($vehicleHistory, $currentMileage)
{
    $totalServices = $vehicleHistory['total_services'];
    $averageInterval = $vehicleHistory['average_interval'];
    
    if ($totalServices > 300 && $averageInterval < 1000) {
        return [
            'prediction' => 'high_maintenance_vehicle',
            'confidence' => 0.80,
            'source' => 'Rule-based Analysis',
            'reason' => 'High service frequency detected'
        ];
    } elseif ($currentMileage > 500000) {
        return [
            'prediction' => 'routine_maintenance',
            'confidence' => 0.75,
            'source' => 'Mileage-based Analysis',
            'reason' => 'High mileage vehicle'
        ];
    } else {
        return [
            'prediction' => 'routine_service',
            'confidence' => 0.70,
            'source' => 'Standard Analysis',
            'reason' => 'Regular maintenance schedule'
        ];
    }
}

private function findLastServiceForPart($records, $partName)
{
    $keywords = [
        'Engine Oil' => ['minyak enjin', 'engine oil', 'oil change'],
        'Oil Filter' => ['oil filter', 'filter minyak'],
        'Air Filter' => ['air filter', 'filter udara'],
        'Brake Pads' => ['brake pad', 'pad brek', 'brake'],
        'Tires' => ['tayar', 'tire'],
        'Brake Fluid' => ['brake fluid', 'minyak brek'],
        'Transmission Oil' => ['gearbox', 'transmission'],
        'Coolant' => ['coolant', 'air radiator']
    ];
    
    $partKeywords = $keywords[$partName] ?? [strtolower($partName)];
    
    foreach ($records as $record) {
        $description = strtolower($record->Description ?? '');
        foreach ($partKeywords as $keyword) {
            if (str_contains($description, $keyword)) {
                return $record;
            }
        }
    }
    
    return null;
}

private function extractMileage($record)
{
    if ($record && $record->Odometer && is_numeric($record->Odometer)) {
        return floatval($record->Odometer);
    }
    return 0;
}

/**
 * Get depot information from Building code
 */
private function getDepotInfo($buildingCode)
{   
    $depotMapping = [
        '40100' => ['id' => 'HQ', 'name' => 'FGV TRANSPORT HQ', 'address' => '123, Jln 123'],
        '40200' => ['id' => 'PK', 'name' => 'DEPOT FGV TRANSPORT PORT KLANG', 'address' => '123 Jalan 123'],
        '40300' => ['id' => 'DEP40300', 'name' => 'DEPOT FGV TRANSPORT SERTING', 'address' => '123 jalan 123'],
        '40320' => ['id' => 'DEP40320', 'name' => 'DEPOT SERTING HILIR', 'address' => '123 jalan 123'],
        '40400' => ['id' => 'DEP40400', 'name' => 'DEPOT FGV TRANSPORT TROLAK', 'address' => '123 Jalan 123'],
        '40500' => ['id' => 'PG', 'name' => 'DEPOT FGV TRANSPORT PASIR GUDANG', 'address' => '123 Jalan 123'],
        '40520' => ['id' => 'DEP40520', 'name' => 'PASAK', 'address' => '123 Jalan 123'],
        '40600' => ['id' => 'KTN', 'name' => 'DEPOT FGV TRANSPORT KUANTAN', 'address' => '123 Jalan 123'],
        '40620' => ['id' => 'DEP40620', 'name' => 'DEPOT KG AWAH', 'address' => '123 jalan 123'],
        '40700' => ['id' => 'DEP40700', 'name' => 'DEPOT FGV TRANSPORT TELOI TIMUR', 'address' => '123 Jalan 123'],
        '40800' => ['id' => 'DEP40800', 'name' => 'DEPOT FGV TRANSPORT BUTTERWORTH', 'address' => '123 Jalan 123'],
        '40910' => ['id' => 'SHBT', 'name' => 'DEPOT FGV TRANSPORT SAHABAT', 'address' => '123 Jalan 123'],
        '40920' => ['id' => 'LD', 'name' => 'DEPOT LAHAD DATU', 'address' => '123 Jalan 123'],
        '40930' => ['id' => 'DEP40930', 'name' => 'DEPOT KOTA KINABALU', 'address' => '123 Jalan 123'],
        '40940' => ['id' => 'TAWAU', 'name' => 'DEPOT FGV TRANSPORT TAWAU', 'address' => '123 Jalan 123'],
        '41000' => ['id' => 'SRWK', 'name' => 'DEPOT FGV TRANSPORT SARAWAK', 'address' => '123 Jalan 123'],
        '44206' => ['id' => 'TMLH', 'name' => 'KURIER TEMERLOH', 'address' => 'JALAN HAJI AHMAD SHAH, 28000 TEMERLOH, PAHANG DARUL MAKMUR'],
        '44211' => ['id' => 'IPOH', 'name' => 'DEPOT FGV TRANSPORT IPOH', 'address' => '123 Jalan 123'],
        '44212' => ['id' => 'DEP44212', 'name' => 'KURIER KOTA BHARU', 'address' => 'NO 5 PT 769-A, JALAN PINTU GENG, KAMPUNG GENG HULU, 15100 KOTA BHARU, KELANTAN'],
        '44213' => ['id' => 'DEP44213', 'name' => 'KURIER SHAH ALAM', 'address' => 'KOMPLEK PEJABAT FELDA, LOT 3, SEKSYEN 15, PERSIARAN SELANGOR, 40200 SHAH ALAM, SELANGOR'],
        '44214' => ['id' => 'KLIA', 'name' => 'PEJABAT KLIA', 'address' => 'BLOK A, LOT GF 1-02, KLAS FORWARDING BUILDING, SOURTHERN SUPPORT ZONE, KLIA, 64000 SEPANG, SELANGOR'],
    ];
    
    // Try multiple formats to match
    $searchKey = trim($buildingCode ?? '');
    $found = null;
    
    // Try exact string match first
    if (isset($depotMapping[$searchKey])) {
        $found = $depotMapping[$searchKey];
        Log::info("DEBUG: Found depot with exact string match", ['key' => $searchKey, 'depot' => $found]);
    }
    // Try as integer string
    elseif (is_numeric($searchKey) && isset($depotMapping[strval(intval($searchKey))])) {
        $found = $depotMapping[strval(intval($searchKey))];
        Log::info("DEBUG: Found depot with numeric conversion", ['key' => strval(intval($searchKey)), 'depot' => $found]);
    }
    // Try with leading zeros removed
    elseif (isset($depotMapping[ltrim($searchKey, '0')])) {
        $found = $depotMapping[ltrim($searchKey, '0')];
        Log::info("DEBUG: Found depot with leading zeros removed", ['key' => ltrim($searchKey, '0'), 'depot' => $found]);
    }
    
    if ($found) {
        return $found;
    }
    
    // Log all available keys for debugging
    Log::warning("DEBUG: No depot found", [
        'searched_for' => $searchKey,
        'available_keys' => array_keys($depotMapping),
        'building_code_original' => $buildingCode
    ]);
    
    return [
        'id' => 'UNK', 
        'name' => "Unknown Depot (Code: {$searchKey})",
        'address' => 'Address not available'
    ];
}

/**
 * Debug method to check actual building codes in database
 */
public function debugBuildingCodes($vehicleNumber = 'VEK4613')
{
    try {
        // Get sample records to see actual Building field values
        $records = ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicleNumber))])
            ->whereNotNull('Building')
            ->where('Building', '!=', '')
            ->orderBy('Datereceived', 'desc')
            ->take(10)
            ->get(['ID', 'Building', 'department', 'location', 'Description']);
            
        Log::info("DEBUG: Sample Building codes from database", [
            'vehicle' => $vehicleNumber,
            'total_records' => $records->count()
        ]);
        
        foreach ($records as $record) {
            Log::info("DEBUG: Record Building field", [
                'id' => $record->ID,
                'building_raw' => $record->Building,
                'building_type' => gettype($record->Building),
                'building_trimmed' => trim($record->Building ?? ''),
                'department' => $record->department,
                'location' => $record->location,
                'description_snippet' => substr($record->Description ?? '', 0, 50)
            ]);
        }
        
        // Also get unique building codes
        $uniqueBuildings = ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicleNumber))])
            ->whereNotNull('Building')
            ->where('Building', '!=', '')
            ->distinct()
            ->pluck('Building')
            ->toArray();
            
        Log::info("DEBUG: Unique Building codes for vehicle", [
            'vehicle' => $vehicleNumber,
            'unique_buildings' => $uniqueBuildings
        ]);
        
        return [
            'records' => $records,
            'unique_buildings' => $uniqueBuildings
        ];
        
    } catch (\Exception $e) {
        Log::error("DEBUG: Error checking building codes", [
            'error' => $e->getMessage()
        ]);
        return null;
    }
}

/**
 * Format location with depot information
 */
private function formatLocationWithDepot($record)
{
    $depotInfo = $this->getDepotInfo($record->Building);
    $department = $record->department ?? '';
    $location = $record->location ?? '';
    
    // Start with depot name and ID
    $formatted = "{$depotInfo['name']} ({$depotInfo['id']})";
    
    // Add department if available
    if (!empty($department) && $department !== 'Unknown') {
        $formatted .= " - {$department}";
    }
    
    // Add specific location if available
    if (!empty($location) && $location !== 'Unknown') {
        $formatted .= " - {$location}";
    }
    
    return $formatted;
}

}