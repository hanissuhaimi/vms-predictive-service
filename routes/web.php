<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;

Route::get('/', [PredictionController::class, 'index'])->name('prediction.index');
Route::post('/predict', [PredictionController::class, 'predict'])->name('prediction.predict');

Route::get('/ml-debug', function() {
    echo "<h1>🔍 VMS ML Debug Tool</h1><hr>";
    
    // Python Check
    echo "<h3>1. Python Check</h3>";
    $pythonVersion = shell_exec('python --version 2>&1');
    echo "Python: " . ($pythonVersion ? "✅ " . trim($pythonVersion) : "❌ Not found") . "<br>";
    
    // Files Check
    echo "<h3>2. Required Files</h3>";
    $pythonScript = base_path('python/predict.py');
    $modelFile = base_path('model_training_output/maintenance_prediction_model.pkl');
    $serviceFile = base_path('app/Services/VMSPredictionService.php');
    
    echo "Python script: " . (file_exists($pythonScript) ? "✅ Found" : "❌ Missing") . "<br>";
    echo "Model file: " . (file_exists($modelFile) ? "✅ Found" : "❌ Missing") . "<br>";  
    echo "Service file: " . (file_exists($serviceFile) ? "✅ Found" : "❌ Missing") . "<br>";
    
    // Test Python
    echo "<h3>3. Python Test</h3>";
    $simpleTest = shell_exec('python -c "print(\'Hello\')" 2>&1');
    echo "Test: " . (trim($simpleTest) === 'Hello' ? "✅ Working" : "❌ " . $simpleTest) . "<br>";
});

Route::get('/ml-logs', function() {
    $logFile = storage_path('logs/laravel.log');
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $recentLogs = array_slice(explode("\n", $logs), -50); // Last 50 lines
        
        echo "<h1>Recent Laravel Logs</h1>";
        echo "<pre style='background: #f4f4f4; padding: 20px; font-size: 12px;'>";
        echo implode("\n", $recentLogs);
        echo "</pre>";
    } else {
        echo "No log file found";
    }
});

Route::get('/test-python-direct', function() {
    echo "<h1>Direct Python Test</h1>";
    
    // Test 1: Check if Python works
    echo "<h3>1. Python Version Check</h3>";
    $pythonVersion = shell_exec('python --version 2>&1');
    echo "Python version: " . ($pythonVersion ? trim($pythonVersion) : "❌ Not found") . "<br>";
    
    // Test 2: Check files
    echo "<h3>2. File Check</h3>";
    $pythonScript = base_path('python/predict.py');
    $modelFile = base_path('model_training_output/maintenance_prediction_model.pkl');
    
    echo "Python script: " . ($pythonScript) . "<br>";
    echo "Exists: " . (file_exists($pythonScript) ? "✅ YES" : "❌ NO") . "<br>";
    echo "Model file: " . ($modelFile) . "<br>";
    echo "Exists: " . (file_exists($modelFile) ? "✅ YES" : "❌ NO") . "<br>";
    
    if (file_exists($modelFile)) {
        echo "Model size: " . filesize($modelFile) . " bytes<br>";
    }
    
    // Test 3: Try to run Python script manually
    if (file_exists($pythonScript) && file_exists($modelFile)) {
        echo "<h3>3. Manual Python Test</h3>";
        
        // Create test data
        $testData = [
            "Description" => "brake noise when stopping",
            "Odometer" => 200000,
            "Priority" => 1,
            "service_count" => 150,
            "Building_encoded" => 2,
            "Vehicle_encoded" => 693,
            "Status_encoded" => 3,
            "MrType_encoded" => 0,
            "request_date" => "2025-06-05 10:30:00",
            "response_days" => 1,
            "request_hour" => 10,
            "request_day_of_week" => 4,
            "request_month" => 6
        ];
        
        // Create temp file
        $tempFile = tempnam(sys_get_temp_dir(), 'manual_test_');
        file_put_contents($tempFile, json_encode($testData));
        
        // Run command
        $command = sprintf('python "%s" "%s" "%s" 2>&1', $pythonScript, $tempFile, $modelFile);
        echo "Command: " . $command . "<br>";
        
        $output = shell_exec($command);
        echo "Output: <pre>" . htmlspecialchars($output) . "</pre>";
        
        // Parse result
        $result = json_decode(trim($output), true);
        if ($result) {
            echo "Parsed result: <pre>" . print_r($result, true) . "</pre>";
            
            if (isset($result['prediction'])) {
                echo "<h4 style='color: green;'>✅ SUCCESS! Python ML is working!</h4>";
                echo "Prediction: " . $result['prediction'] . "<br>";
                echo "Confidence: " . ($result['confidence'] ?? 'unknown') . "<br>";
            } else if (isset($result['error'])) {
                echo "<h4 style='color: red;'>❌ Python Error: " . $result['error'] . "</h4>";
            }
        } else {
            echo "<h4 style='color: red;'>❌ Could not parse JSON output</h4>";
        }
        
        // Clean up
        unlink($tempFile);
    }
    
    echo "<hr>";
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li>If Python test shows SUCCESS, the issue is in Laravel service integration</li>";
    echo "<li>If Python test shows error, fix the Python script or model first</li>";
    echo "<li>Check Laravel logs after testing a prediction: /ml-logs</li>";
    echo "</ul>";
});