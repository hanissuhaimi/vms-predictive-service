<?php
// STEP 1: Add detailed debugging to VMSPredictionService
// Update your app/Services/VMSPredictionService.php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class VMSPredictionService
{
    private $pythonScriptPath;
    private $modelPath;
    private $pythonExecutable;

    public function __construct()
    {
        $this->pythonScriptPath = base_path('python/predict.py');
        $this->modelPath = base_path('model_training_output/maintenance_prediction_model.pkl');
        $this->pythonExecutable = 'python';
    }

    public function predict(array $data)
    {
        Log::info('=== VMS PREDICTION DEBUG START ===');
        Log::info('Input data:', $data);
        
        try {
            // Step 1: Check if files exist
            Log::info('Checking files...');
            Log::info('Python script path: ' . $this->pythonScriptPath);
            Log::info('Python script exists: ' . (file_exists($this->pythonScriptPath) ? 'YES' : 'NO'));
            Log::info('Model path: ' . $this->modelPath);
            Log::info('Model exists: ' . (file_exists($this->modelPath) ? 'YES' : 'NO'));
            
            if (!file_exists($this->pythonScriptPath)) {
                Log::error('Python script not found: ' . $this->pythonScriptPath);
                return null;
            }

            if (!file_exists($this->modelPath)) {
                Log::error('Model file not found: ' . $this->modelPath);
                return null;
            }

            // Step 2: Check model file size
            $modelSize = filesize($this->modelPath);
            Log::info('Model file size: ' . $modelSize . ' bytes');
            
            if ($modelSize < 1000) {
                Log::error('Model file too small, likely corrupted: ' . $modelSize . ' bytes');
                return null;
            }

            // Step 3: Create temporary JSON file
            $tempFile = tempnam(sys_get_temp_dir(), 'vms_prediction_');
            if (!$tempFile) {
                Log::error('Could not create temporary file');
                return null;
            }

            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
            Log::info('JSON data to send to Python:', ['json' => $jsonData]);
            
            if (file_put_contents($tempFile, $jsonData) === false) {
                Log::error('Could not write to temporary file: ' . $tempFile);
                return null;
            }

            // Step 4: Execute Python script with detailed logging
            $command = sprintf(
                '%s "%s" "%s" "%s" 2>&1',
                $this->pythonExecutable,
                $this->pythonScriptPath,
                $tempFile,
                $this->modelPath
            );

            Log::info('Executing command: ' . $command);
            
            // Execute with timeout
            $output = shell_exec($command);
            
            // Clean up temporary file
            unlink($tempFile);

            Log::info('Python script raw output: ' . $output);

            if (empty($output)) {
                Log::error('No output from Python script');
                return null;
            }

            // Step 5: Parse JSON response
            $result = json_decode(trim($output), true);
            $jsonError = json_last_error();
            
            Log::info('JSON decode error code: ' . $jsonError);
            Log::info('JSON decode error message: ' . json_last_error_msg());
            
            if ($jsonError !== JSON_ERROR_NONE) {
                Log::error('Invalid JSON response from Python script', [
                    'output' => $output,
                    'json_error' => json_last_error_msg()
                ]);
                return null;
            }

            Log::info('Parsed result:', $result);

            // Step 6: Check for errors in result
            if (isset($result['error'])) {
                Log::error('Python script returned error: ' . $result['error']);
                return null;
            }

            // Step 7: Validate result structure
            if (!isset($result['prediction'])) {
                Log::error('Python script result missing prediction field', $result);
                return null;
            }

            Log::info('=== VMS PREDICTION SUCCESS ===');
            Log::info('Prediction: ' . $result['prediction']);
            Log::info('Confidence: ' . ($result['confidence'] ?? 'unknown'));
            
            return $result;

        } catch (\Exception $e) {
            Log::error('Prediction service exception: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            return null;
        } finally {
            Log::info('=== VMS PREDICTION DEBUG END ===');
        }
    }
}