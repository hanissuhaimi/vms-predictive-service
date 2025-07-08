<?php
// Enhanced VMSPredictionService.php with multiple fallback layers

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VMSPredictionService
{
    private $pythonScriptPath;
    private $modelPath;
    private $pythonExecutable;
    private $timeout = 30; // 30 second timeout

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python/predict.py');
        $this->modelPath = base_path('model_training_output/maintenance_prediction_model.pkl');
        $this->pythonExecutable = 'python';
    }

    public function predict(array $data)
    {
        Log::info('=== ENHANCED VMS PREDICTION START ===');
        
        // Try multiple prediction methods in order of preference
        $methods = [
            'ml_prediction' => [$this, 'attemptMLPrediction'],
            'rule_based' => [$this, 'ruleBasedPrediction'],
            'statistical' => [$this, 'statisticalFallback'],
            'emergency' => [$this, 'emergencyFallback']
        ];
        
        foreach ($methods as $methodName => $method) {
            try {
                Log::info("Attempting prediction method: {$methodName}");
                $result = $method($data);
                
                if ($result && $this->isValidPrediction($result)) {
                    $result['method_used'] = $methodName;
                    Log::info("Prediction successful using: {$methodName}");
                    return $result;
                }
                
            } catch (\Exception $e) {
                Log::warning("Method {$methodName} failed: " . $e->getMessage());
                continue;
            }
        }
        
        Log::error('All prediction methods failed');
        return $this->emergencyFallback($data);
    }

    /**
     * Attempt ML prediction with enhanced error handling
     */
    private function attemptMLPrediction(array $data)
    {
        // Check cache first
        $cacheKey = 'ml_prediction_' . md5(json_encode($data));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('Using cached ML prediction');
            return $cached;
        }

        // Validate prerequisites
        if (!$this->validateMLPrerequisites()) {
            throw new \Exception('ML prerequisites not met');
        }

        // Create temporary file with better error handling
        $tempFile = $this->createTempFile($data);
        if (!$tempFile) {
            throw new \Exception('Could not create temporary file');
        }

        try {
            // Execute with timeout
            $result = $this->executePythonScript($tempFile);
            
            if ($result) {
                // Cache successful result for 1 hour
                Cache::put($cacheKey, $result, 3600);
                return $result;
            }
            
            throw new \Exception('ML execution failed');
            
        } finally {
            // Always clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Rule-based prediction using maintenance patterns
     */
    private function ruleBasedPrediction(array $data)
    {
        Log::info('Using rule-based prediction');
        
        $odometer = $data['Odometer'] ?? 0;
        $serviceCount = $data['service_count'] ?? 0;
        $description = strtolower($data['Description'] ?? '');
        
        // High mileage rules
        if ($odometer > 500000) {
            return [
                'prediction' => 'engine_major',
                'confidence' => 0.85,
                'reason' => 'High mileage vehicle (500K+ km)',
                'priority' => 'high'
            ];
        }
        
        // High service frequency rules
        if ($serviceCount > 500) {
            return [
                'prediction' => 'frequent_maintenance',
                'confidence' => 0.80,
                'reason' => 'Heavy commercial use pattern',
                'priority' => 'medium'
            ];
        }
        
        // Description-based rules
        if (str_contains($description, 'brake') || str_contains($description, 'brek')) {
            return [
                'prediction' => 'brake_system',
                'confidence' => 0.75,
                'reason' => 'Brake-related maintenance detected',
                'priority' => 'high'
            ];
        }
        
        if (str_contains($description, 'engine') || str_contains($description, 'enjin')) {
            return [
                'prediction' => 'engine_service',
                'confidence' => 0.75,
                'reason' => 'Engine-related maintenance detected',
                'priority' => 'medium'
            ];
        }
        
        // Default rule-based prediction
        return [
            'prediction' => 'routine_maintenance',
            'confidence' => 0.60,
            'reason' => 'Standard maintenance schedule',
            'priority' => 'routine'
        ];
    }

    /**
     * Statistical fallback based on historical patterns
     */
    private function statisticalFallback(array $data)
    {
        Log::info('Using statistical fallback prediction');
        
        $odometer = $data['Odometer'] ?? 0;
        $serviceCount = $data['service_count'] ?? 0;
        
        // Calculate risk score
        $riskScore = 0;
        
        // Mileage risk
        if ($odometer > 800000) $riskScore += 0.4;
        elseif ($odometer > 500000) $riskScore += 0.3;
        elseif ($odometer > 200000) $riskScore += 0.2;
        else $riskScore += 0.1;
        
        // Service frequency risk
        if ($serviceCount > 1000) $riskScore += 0.3;
        elseif ($serviceCount > 500) $riskScore += 0.2;
        elseif ($serviceCount > 100) $riskScore += 0.1;
        
        // Time-based risk (current month)
        $monthRisk = [1 => 0.1, 2 => 0.15, 3 => 0.2, 4 => 0.15, 5 => 0.1, 6 => 0.1,
                     7 => 0.15, 8 => 0.2, 9 => 0.15, 10 => 0.1, 11 => 0.15, 12 => 0.2];
        $riskScore += $monthRisk[now()->month] ?? 0.1;
        
        // Determine prediction based on risk
        if ($riskScore > 0.7) {
            $prediction = 'high_risk_maintenance';
            $priority = 'immediate';
        } elseif ($riskScore > 0.5) {
            $prediction = 'moderate_risk_service';
            $priority = 'high';
        } else {
            $prediction = 'routine_maintenance';
            $priority = 'routine';
        }
        
        return [
            'prediction' => $prediction,
            'confidence' => min(0.90, $riskScore),
            'risk_score' => round($riskScore, 3),
            'reason' => 'Statistical analysis based on fleet patterns',
            'priority' => $priority
        ];
    }

    /**
     * Emergency fallback - always returns a valid response
     */
    private function emergencyFallback(array $data)
    {
        Log::warning('Using emergency fallback prediction');
        
        return [
            'prediction' => 'general_maintenance',
            'confidence' => 0.50,
            'reason' => 'Emergency fallback - manual inspection recommended',
            'priority' => 'routine',
            'warning' => 'Prediction system unavailable - using default recommendation'
        ];
    }

    // Helper methods
    private function validateMLPrerequisites()
    {
        $checks = [
            'python_script' => file_exists($this->pythonScriptPath),
            'model_file' => file_exists($this->modelPath),
            'model_size' => file_exists($this->modelPath) && filesize($this->modelPath) > 1000,
            'python_available' => $this->isPythonAvailable()
        ];
        
        foreach ($checks as $check => $passed) {
            if (!$passed) {
                Log::warning("ML prerequisite failed: {$check}");
                return false;
            }
        }
        
        return true;
    }

    private function isPythonAvailable()
    {
        $output = shell_exec('python --version 2>&1');
        return $output && str_contains(strtolower($output), 'python');
    }

    private function createTempFile(array $data)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'vms_prediction_');
        if (!$tempFile) return false;
        
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        return file_put_contents($tempFile, $jsonData) ? $tempFile : false;
    }

    private function executePythonScript($tempFile)
    {
        $command = sprintf(
            'timeout %d %s "%s" "%s" "%s" 2>&1',
            $this->timeout,
            $this->pythonExecutable,
            $this->pythonScriptPath,
            $tempFile,
            $this->modelPath
        );

        Log::info('Executing: ' . $command);
        $output = shell_exec($command);
        
        if (empty($output)) return null;
        
        $result = json_decode(trim($output), true);
        
        return (json_last_error() === JSON_ERROR_NONE) ? $result : null;
    }

    private function isValidPrediction($result)
    {
        return is_array($result) && 
               isset($result['prediction']) && 
               !empty($result['prediction']) &&
               !isset($result['error']);
    }
}