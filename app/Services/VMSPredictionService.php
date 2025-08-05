<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class VMSPredictionService
{
    private $pythonScriptPath;
    private $modelPath;
    private $pythonExecutable;
    private $timeout = 45; // 45 second timeout
    private $mlMinConfidence = 0.3; // Lower threshold to trust ML more

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python/predict.py');
        $this->modelPath = base_path('model_training_output/maintenance_prediction_model.pkl');
        $this->pythonExecutable = $this->detectPythonPath();
    }

    public function predict(array $data)
    {
        Log::info('=== AI-FIRST VMS PREDICTION START ===');
        Log::info('Input data: ' . json_encode($data, JSON_PRETTY_PRINT));
        
        // PRIORITY 1: Enhanced ML Prediction (Multiple Attempts)
        $mlResult = $this->enhancedMLPrediction($data);
        if ($mlResult && $this->isHighQualityPrediction($mlResult)) {
            Log::info("✅ HIGH-QUALITY ML PREDICTION SUCCESS");
            return $mlResult;
        }
        
        // PRIORITY 2: ML with Lower Confidence (Still prefer AI)
        if ($mlResult && $this->isAcceptableMLPrediction($mlResult)) {
            Log::info("✅ ACCEPTABLE ML PREDICTION SUCCESS");
            return $mlResult;
        }
        
        // PRIORITY 3: Hybrid AI-Enhanced Rule Based (Only if ML completely fails)
        Log::warning("ML failed, using AI-enhanced rules");
        return $this->aiEnhancedRuleBased($data, $mlResult);
    }

    /**
     * Enhanced ML prediction with multiple strategies
     */
    private function enhancedMLPrediction(array $data)
    {
        // Strategy 1: Try with current data
        $result = $this->attemptMLPrediction($data);
        if ($result && !isset($result['error'])) {
            return $result;
        }
        
        // Strategy 2: Normalize Status to what model expects (if Status=2 is causing issues)
        $normalizedData = $data;
        $normalizedData['Status'] = 1; // Try with Status=1 that model was trained on
        $result = $this->attemptMLPrediction($normalizedData);
        if ($result && !isset($result['error'])) {
            $result['data_normalized'] = true;
            return $result;
        }
        
        // Strategy 3: Fill missing features with intelligent defaults
        $enhancedData = $this->enhanceDataForML($data);
        $result = $this->attemptMLPrediction($enhancedData);
        if ($result && !isset($result['error'])) {
            $result['data_enhanced'] = true;
            return $result;
        }
        
        // Strategy 4: Try with minimal required features only
        $minimalData = $this->getMinimalMLData($data);
        $result = $this->attemptMLPrediction($minimalData);
        if ($result && !isset($result['error'])) {
            $result['minimal_features'] = true;
            return $result;
        }
        
        Log::error("All ML strategies failed");
        return $result; // Return last result with error info
    }

    /**
     * Standard ML prediction attempt with Windows compatibility
     */
    private function attemptMLPrediction(array $data)
    {
        // Check cache first
        $cacheKey = 'ml_prediction_' . md5(json_encode($data));
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info('✅ Using cached ML prediction');
            $cached['from_cache'] = true;
            return $cached;
        }

        // Enhanced prerequisites check
        if (!$this->validateMLPrerequisitesEnhanced()) {
            return ['error' => 'ML prerequisites not met'];
        }

        // Create temporary file with better error handling
        $tempFile = $this->createTempFile($data);
        if (!$tempFile) {
            return ['error' => 'Could not create temporary file'];
        }

        try {
            // Execute with Windows-compatible command
            $result = $this->executePythonScriptWindows($tempFile);
            
            if ($result && !isset($result['error'])) {
                // Cache successful results for 2 hours
                Cache::put($cacheKey, $result, 7200);
                Log::info("✅ ML prediction success: " . ($result['prediction'] ?? 'unknown'));
                return $result;
            }
            
            Log::warning("ML execution returned error: " . json_encode($result));
            return $result;
            
        } finally {
            // Always clean up
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Windows-compatible Python script execution
     */
    private function executePythonScriptWindows($tempFile)
    {
        // Detect Windows and use appropriate command
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        if ($isWindows) {
            // Windows command without timeout (timeout syntax is different)
            $command = sprintf(
                'cd /d "%s" && %s "%s" "%s" "%s" 2>&1',
                base_path(),
                $this->pythonExecutable,
                $this->pythonScriptPath,
                $tempFile,
                $this->modelPath
            );
        } else {
            // Unix/Linux/Mac command with timeout
            $command = sprintf(
                'cd %s && timeout %d %s "%s" "%s" "%s" 2>&1',
                base_path(),
                $this->timeout,
                $this->pythonExecutable,
                $this->pythonScriptPath,
                $tempFile,
                $this->modelPath
            );
        }

        Log::info("Executing ML command (Windows-compatible): {$command}");
        
        $startTime = microtime(true);
        $output = shell_exec($command);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::info("Python execution time: {$executionTime}ms");
        
        if (empty($output)) {
            Log::error("Python script returned empty output");
            return ['error' => 'Empty output from Python script'];
        }
        
        Log::info("Python output: " . substr($output, 0, 500) . (strlen($output) > 500 ? '...' : ''));
        
        $result = json_decode(trim($output), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JSON decode error: " . json_last_error_msg());
            return ['error' => 'Invalid JSON response from Python script', 'raw_output' => $output];
        }
        
        return $result;
    }

    /**
     * Enhance data with intelligent defaults for better ML success
     */
    private function enhanceDataForML(array $data)
    {
        $enhanced = $data;
        
        // Add missing time features if not present
        if (!isset($enhanced['request_hour'])) {
            $enhanced['request_hour'] = 10; // Default business hour
        }
        if (!isset($enhanced['request_day_of_week'])) {
            $enhanced['request_day_of_week'] = 2; // Tuesday
        }
        if (!isset($enhanced['request_month'])) {
            $enhanced['request_month'] = date('n'); // Current month
        }
        
        // Encode building if string
        if (isset($enhanced['Building']) && !is_numeric($enhanced['Building'])) {
            $enhanced['Building_encoded'] = $this->hashToNumeric($enhanced['Building']);
        }
        
        // Encode vehicle if string  
        if (isset($enhanced['Vehicle']) && !is_numeric($enhanced['Vehicle'])) {
            $enhanced['Vehicle_encoded'] = $this->hashToNumeric($enhanced['Vehicle']);
        }
        
        // Default values for critical features
        $enhanced['Status_encoded'] = $enhanced['Status'] ?? 2;
        $enhanced['MrType_encoded'] = $enhanced['MrType'] ?? 3;
        $enhanced['Priority'] = $enhanced['Priority'] ?? 2;
        
        // Response days calculation
        if (!isset($enhanced['response_days'])) {
            $enhanced['response_days'] = 1; // Default 1 day response
        }
        
        Log::info("Data enhanced for ML: " . json_encode($enhanced, JSON_PRETTY_PRINT));
        return $enhanced;
    }

    /**
     * Get minimal data required for ML prediction
     */
    private function getMinimalMLData(array $data)
    {
        return [
            'Vehicle' => $data['Vehicle'] ?? 'UNKNOWN',
            'Odometer' => $data['Odometer'] ?? 100000,
            'service_count' => $data['service_count'] ?? 50,
            'average_interval' => $data['average_interval'] ?? 5000,
            'days_since_last' => $data['days_since_last'] ?? 30,
            'Description' => $data['Description'] ?? 'Vehicle prediction request',
            'Priority' => 2,
            'Status' => 2,
            'MrType' => 3,
            'Status_encoded' => 2,
            'MrType_encoded' => 3,
            'Vehicle_encoded' => $this->hashToNumeric($data['Vehicle'] ?? 'UNKNOWN'),
            'Building_encoded' => 1,
            'request_hour' => 10,
            'request_day_of_week' => 2,
            'request_month' => date('n'),
            'response_days' => 1
        ];
    }

    /**
     * AI-Enhanced rule-based prediction (uses ML insights)
     */
    private function aiEnhancedRuleBased(array $data, $mlResult = null)
    {
        Log::info('Using AI-enhanced rule-based prediction');
        
        $odometer = $data['Odometer'] ?? 100000;
        $serviceCount = $data['service_count'] ?? 50;
        $averageInterval = $data['average_interval'] ?? 5000;
        $daysSinceLast = $data['days_since_last'] ?? 30;
        $description = strtolower($data['Description'] ?? '');
        
        // Enhanced logic with ML-inspired features
        $riskScore = $this->calculateEnhancedRiskScore($odometer, $serviceCount, $averageInterval, $daysSinceLast, $description);
        
        // More sophisticated prediction categories
        $prediction = $this->determineAdvancedPrediction($riskScore, $description, $odometer, $serviceCount);
        
        $confidence = min(0.85, max(0.60, $riskScore));
        
        $result = [
            'prediction' => $prediction['category'],
            'confidence' => $confidence,
            'source' => 'AI-Enhanced Rules',
            'method_used' => 'ai_enhanced_rules',
            'risk_score' => round($riskScore, 3),
            'reasoning' => $prediction['reasoning'],
            'ml_attempted' => $mlResult ? true : false
        ];
        
        // Add ML error info if available
        if ($mlResult && isset($mlResult['error'])) {
            $result['ml_error'] = $mlResult['error'];
        }
        
        return $result;
    }

    /**
     * Calculate enhanced risk score using multiple factors
     */
    private function calculateEnhancedRiskScore($odometer, $serviceCount, $averageInterval, $daysSinceLast, $description)
    {
        $score = 0;
        
        // Mileage risk (0-0.3)
        if ($odometer > 1000000) $score += 0.3;
        elseif ($odometer > 800000) $score += 0.25;
        elseif ($odometer > 500000) $score += 0.2;
        elseif ($odometer > 300000) $score += 0.15;
        else $score += 0.1;
        
        // Service frequency risk (0-0.25)
        $serviceRate = $serviceCount / max(1, $odometer / 10000); // services per 10k km
        if ($serviceRate > 10) $score += 0.25;
        elseif ($serviceRate > 5) $score += 0.2;
        elseif ($serviceRate > 2) $score += 0.15;
        else $score += 0.1;
        
        // Interval pattern risk (0-0.2)
        if ($averageInterval < 2000) $score += 0.2;
        elseif ($averageInterval < 5000) $score += 0.15;
        elseif ($averageInterval < 10000) $score += 0.1;
        else $score += 0.05;
        
        // Time since last service (0-0.15)
        if ($daysSinceLast > 365) $score += 0.15;
        elseif ($daysSinceLast > 180) $score += 0.1;
        elseif ($daysSinceLast > 90) $score += 0.05;
        
        // Description-based risk (0-0.1)
        $criticalKeywords = ['brake', 'brek', 'engine', 'enjin', 'emergency', 'urgent', 'critical'];
        foreach ($criticalKeywords as $keyword) {
            if (str_contains($description, $keyword)) {
                $score += 0.1;
                break;
            }
        }
        
        return min(1.0, $score);
    }

    /**
     * Determine advanced prediction category
     */
    private function determineAdvancedPrediction($riskScore, $description, $odometer, $serviceCount)
    {
        // Description-based prediction (highest priority)
        if (str_contains($description, 'brake') || str_contains($description, 'brek')) {
            return [
                'category' => 'brake_system',
                'reasoning' => 'Brake-related maintenance detected in description'
            ];
        }
        
        if (str_contains($description, 'tayar') || str_contains($description, 'tire')) {
            return [
                'category' => 'tire_service',
                'reasoning' => 'Tire-related maintenance detected in description'
            ];
        }
        
        if (str_contains($description, 'engine') || str_contains($description, 'enjin')) {
            return [
                'category' => 'engine_repair',
                'reasoning' => 'Engine-related maintenance detected in description'
            ];
        }
        
        // Risk-based prediction
        if ($riskScore > 0.8) {
            return [
                'category' => 'critical_maintenance',
                'reasoning' => 'Very high risk score indicates critical maintenance needed'
            ];
        } elseif ($riskScore > 0.6) {
            return [
                'category' => 'major_service',
                'reasoning' => 'High risk score indicates major service required'
            ];
        } elseif ($riskScore > 0.4) {
            return [
                'category' => 'routine_maintenance',
                'reasoning' => 'Moderate risk score indicates routine maintenance'
            ];
        } else {
            return [
                'category' => 'preventive_service',
                'reasoning' => 'Low risk score suggests preventive maintenance'
            ];
        }
    }

    // Helper methods
    private function isHighQualityPrediction($result)
    {
        return is_array($result) && 
               isset($result['prediction']) && 
               !empty($result['prediction']) &&
               !isset($result['error']) &&
               ($result['confidence'] ?? 0) >= 0.7;
    }
    
    private function isAcceptableMLPrediction($result)
    {
        return is_array($result) && 
               isset($result['prediction']) && 
               !empty($result['prediction']) &&
               !isset($result['error']) &&
               ($result['confidence'] ?? 0) >= $this->mlMinConfidence;
    }

    private function detectPythonPath()
    {
        // For Windows, try common Python paths
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $pythonPaths = ['python', 'python.exe', 'py'];
        } else {
            $pythonPaths = ['python3', 'python', 'python3.11', 'python3.10', 'python3.9'];
        }
        
        foreach ($pythonPaths as $python) {
            $output = shell_exec("{$python} --version 2>&1");
            if ($output && str_contains(strtolower($output), 'python')) {
                Log::info("Detected Python: {$python}");
                return $python;
            }
        }
        
        return 'python'; // fallback
    }

    private function validateMLPrerequisitesEnhanced()
    {
        $checks = [
            'python_script' => file_exists($this->pythonScriptPath),
            'model_file' => file_exists($this->modelPath),
            'model_size' => file_exists($this->modelPath) && filesize($this->modelPath) > 10000, // Increased minimum size
            'python_available' => $this->isPythonAvailable(),
            'temp_dir_writable' => is_writable(sys_get_temp_dir())
        ];
        
        $passed = 0;
        foreach ($checks as $check => $result) {
            if ($result) {
                $passed++;
                Log::info("✅ ML prerequisite passed: {$check}");
            } else {
                Log::warning("❌ ML prerequisite failed: {$check}");
            }
        }
        
        Log::info("ML Prerequisites: {$passed}/" . count($checks) . " passed");
        return $passed >= 4; // Allow 1 failure
    }

    private function isPythonAvailable()
    {
        $output = shell_exec("{$this->pythonExecutable} --version 2>&1");
        return $output && str_contains(strtolower($output), 'python');
    }

    private function createTempFile(array $data)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'vms_prediction_');
        if (!$tempFile) {
            Log::error("Failed to create temp file");
            return false;
        }
        
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        $written = file_put_contents($tempFile, $jsonData);
        
        if (!$written) {
            Log::error("Failed to write to temp file");
            return false;
        }
        
        Log::info("Created temp file: {$tempFile} ({$written} bytes)");
        return $tempFile;
    }

    private function hashToNumeric($string)
    {
        return abs(crc32($string)) % 10000; // Convert string to number 0-9999
    }
}