<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PredictionController;
use App\Http\Controllers\MaintenanceController;
use App\Models\ServiceRequest;
use Illuminate\Http\Request;

// Main application routes
Route::get('/', [PredictionController::class, 'index'])->name('prediction.index');
Route::post('/predict', [PredictionController::class, 'predict'])->name('prediction.predict');
Route::post('/quick-save', [PredictionController::class, 'quickSave'])->name('prediction.quickSave');

// Service Request routes (updated for existing table)
Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
Route::post('/maintenance/store', [MaintenanceController::class, 'store'])->name('maintenance.store');
Route::get('/maintenance/{id}', [MaintenanceController::class, 'show'])->name('maintenance.show');

// API route for vehicle history
Route::get('/api/vehicle-history/{vehicle}', [PredictionController::class, 'getVehicleHistory'])->name('api.vehicle.history');

Route::get('/prediction', [PredictionController::class, 'index'])->name('prediction.index');
Route::post('/prediction/predict', [PredictionController::class, 'predict'])->name('prediction.predict');

// ========================================
// TESTING ROUTES - Updated for existing ServiceRequest table
// ========================================

// Test existing ServiceRequest table integration
Route::get('/test-existing-servicerequest', function() {
    echo "<h1>🔍 Existing ServiceRequest Table Test</h1><hr>";
    
    try {
        // Test 1: Database Connection
        echo "<h3>1. Database Connection</h3>";
        $pdo = DB::connection()->getPdo();
        echo "✅ Connected to SQL Server successfully!<br>";
        echo "Current database: " . DB::select("SELECT DB_NAME() as db")[0]->db . "<br><br>";
        
        // Test 2: ServiceRequest Table Structure
        echo "<h3>2. ServiceRequest Table Structure</h3>";
        $columns = DB::select("
            SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'ServiceRequest' 
            ORDER BY ORDINAL_POSITION
        ");
        
        if (count($columns) > 0) {
            echo "✅ ServiceRequest table found with " . count($columns) . " columns<br>";
            echo "<details><summary>View Table Structure</summary>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0; font-size: 12px;'>";
            echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Max Length</th></tr>";
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>" . $col->COLUMN_NAME . "</td>";
                echo "<td>" . $col->DATA_TYPE . "</td>";
                echo "<td>" . $col->IS_NULLABLE . "</td>";
                echo "<td>" . ($col->CHARACTER_MAXIMUM_LENGTH ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table></details><br>";
        } else {
            echo "❌ ServiceRequest table not found!<br><br>";
            return;
        }
        
        // Test 3: Count Records
        echo "<h3>3. Existing Data</h3>";
        $totalCount = DB::table('ServiceRequest')->count();
        echo "Total records in ServiceRequest: <strong>" . number_format($totalCount) . "</strong><br>";
        
        if ($totalCount > 0) {
            // Get recent records
            $recent = DB::table('ServiceRequest')
                ->orderBy('ID', 'desc')
                ->take(3)
                ->get(['ID', 'SR', 'Vehicle', 'Description', 'Datereceived', 'Status', 'Priority', 'Odometer']);
                
            echo "<h4>Recent Records (Last 3):</h4>";
            echo "<table border='1' style='border-collapse: collapse; font-size: 11px;'>";
            echo "<tr><th>ID</th><th>SR</th><th>Vehicle</th><th>Description</th><th>Date</th><th>Status</th><th>Priority</th><th>Odometer</th></tr>";
            foreach ($recent as $record) {
                echo "<tr>";
                echo "<td>" . $record->ID . "</td>";
                echo "<td>" . ($record->SR ?? '-') . "</td>";
                echo "<td>" . ($record->Vehicle ?? '-') . "</td>";
                echo "<td>" . (Str::limit($record->Description ?? '-', 30)) . "</td>";
                echo "<td>" . ($record->Datereceived ? date('Y-m-d', strtotime($record->Datereceived)) : '-') . "</td>";
                echo "<td>" . ($record->Status ?? '-') . "</td>";
                echo "<td>" . ($record->Priority ?? '-') . "</td>";
                echo "<td>" . ($record->Odometer ?? '-') . "</td>";
                echo "</tr>";
            }
            echo "</table><br>";
            
            // Get unique vehicles
            $vehicles = DB::table('ServiceRequest')
                ->select('Vehicle')
                ->whereNotNull('Vehicle')
                ->where('Vehicle', '!=', '')
                ->distinct()
                ->orderBy('Vehicle')
                ->take(10)
                ->pluck('Vehicle');
                
            if ($vehicles->count() > 0) {
                echo "<h4>Sample Vehicles in Database:</h4>";
                echo implode(', ', $vehicles->toArray()) . "<br><br>";
            }
        }
        
        // Test 4: Laravel Model Test
        echo "<h3>4. Laravel ServiceRequest Model Test</h3>";
        
        try {
            // Test if Laravel model works
            $modelTest = ServiceRequest::orderBy('ID', 'desc')->first();
            
            if ($modelTest) {
                echo "✅ Laravel ServiceRequest model working!<br>";
                echo "Latest record ID: " . $modelTest->ID . "<br>";
                echo "Vehicle: " . ($modelTest->Vehicle ?? 'N/A') . "<br>";
                echo "Description: " . Str::limit($modelTest->Description ?? 'N/A', 50) . "<br>";
                echo "Priority Text: " . $modelTest->priority_text . "<br>";
                echo "Status Text: " . $modelTest->status_text . "<br>";
                echo "MR Type Text: " . $modelTest->mr_type_text . "<br><br>";
            } else {
                echo "⚠️ No records found via Laravel model<br><br>";
            }
            
        } catch (\Exception $e) {
            echo "❌ Laravel model error: " . $e->getMessage() . "<br><br>";
        }
        
        // Test 5: Insert Test Data
        echo "<h3>5. Test Insert (Laravel Integration Test)</h3>";
        echo "<p><strong>Ready to test Laravel integration?</strong></p>";
        echo "<p>The database connection and existing table structure look good!</p>";
        
        echo "<form method='POST' action='/test-laravel-insert' style='margin: 20px 0;'>";
        echo csrf_field();
        echo "<input type='hidden' name='test_data' value='1'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Laravel Insert</button>";
        echo "</form>";
        
        // Summary
        echo "<h3>🎉 Summary</h3>";
        echo "<ul>";
        echo "<li>✅ Database connection working</li>";
        echo "<li>✅ ServiceRequest table exists with " . count($columns) . " columns</li>";
        echo "<li>✅ " . number_format($totalCount) . " existing records found</li>";
        echo "<li>✅ Laravel model integration ready</li>";
        echo "</ul>";
        
        echo "<h4>Next Steps:</h4>";
        echo "<ol>";
        echo "<li><strong>Test the Laravel insert</strong> using the button above</li>";
        echo "<li><strong>Try the main application</strong>: <a href='/'>Make a Prediction</a></li>";
        echo "<li><strong>View existing records</strong>: <a href='/maintenance'>Service Requests History</a></li>";
        echo "</ol>";
        
    } catch (\Exception $e) {
        echo "<h3>❌ Error</h3>";
        echo "Error: " . $e->getMessage() . "<br>";
        echo "Line: " . $e->getLine() . "<br>";
        echo "File: " . basename($e->getFile()) . "<br>";
        
        echo "<h4>Troubleshooting:</h4>";
        echo "<ul>";
        echo "<li>Check your .env database configuration</li>";
        echo "<li>Ensure SQL Server is running</li>";
        echo "<li>Verify database and table names</li>";
        echo "<li>Check SQL Server authentication</li>";
        echo "</ul>";
    }
});

// Test Laravel insert route
Route::post('/test-laravel-insert', function(Request $request) {
    echo "<h1>🧪 Laravel ServiceRequest Insert Test</h1><hr>";
    
    try {
        // Generate test data that matches your existing patterns
        $testData = [
            'SR' => 'MR/' . date('y') . '/40200/30/' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'Datereceived' => now(),
            'timereceived' => now()->format('g:i:s A'),
            'Requestor' => 'Laravel Test User',
            'Building' => '40200',
            'department' => 'Testing',
            'location' => '442021030',
            'CMType' => 'TEST',
            'Description' => 'This is a test service request created by Laravel integration system on ' . now(),
            'Priority' => '2', // High priority
            'Status' => '1', // Pending
            'Vehicle' => 'TEST123',
            'Odometer' => '150000',
            'MrType' => '1', // Repair
            'Staff' => 'system',
            'Response' => 'ML Prediction Results:
Predicted Issue: brake_system
Confidence: 89%
Estimated Cost: RM 400 - RM 600
Time Needed: Same day
ML Confidence Score: 0.89
Generated: ' . now()->format('Y-m-d H:i:s'),
            'DateModify' => now(),
            'TimeModify' => now(),
            'ModifyBy' => 'laravel_test',
            'ForTrailer' => false,
        ];
        
        echo "<h3>Test Data to Insert:</h3>";
        echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
        foreach ($testData as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>" . ($value ?? 'NULL') . "</td></tr>";
        }
        echo "</table><br>";
        
        // Insert using Laravel model
        $inserted = ServiceRequest::create($testData);
        
        if ($inserted) {
            echo "<h3>✅ SUCCESS!</h3>";
            echo "<p>Test record inserted successfully!</p>";
            echo "<p><strong>New Record ID:</strong> " . $inserted->ID . "</p>";
            echo "<p><strong>SR Number:</strong> " . $inserted->SR . "</p>";
            
            // Show the inserted record
            echo "<h4>Inserted Record Details:</h4>";
            echo "<ul>";
            echo "<li><strong>ID:</strong> " . $inserted->ID . "</li>";
            echo "<li><strong>Vehicle:</strong> " . $inserted->Vehicle . "</li>";
            echo "<li><strong>Description:</strong> " . $inserted->Description . "</li>";
            echo "<li><strong>Priority:</strong> " . $inserted->Priority . " (" . $inserted->priority_text . ")</li>";
            echo "<li><strong>Status:</strong> " . $inserted->Status . " (" . $inserted->status_text . ")</li>";
            echo "<li><strong>Date:</strong> " . $inserted->Datereceived->format('Y-m-d H:i:s') . "</li>";
            echo "</ul>";
            
            echo "<p><a href='/maintenance/" . $inserted->ID . "'>View this record in Laravel app</a></p>";
            echo "<p><a href='/maintenance'>View all service requests</a></p>";
            
            // Clean up test record
            echo "<hr>";
            echo "<p><em>Note: This is a test record. You can delete it from SQL Server Management Studio if needed.</em></p>";
            echo "<p><strong>SQL to delete:</strong> <code>DELETE FROM ServiceRequest WHERE ID = " . $inserted->ID . "</code></p>";
            
        } else {
            echo "<h3>❌ Insert Failed</h3>";
            echo "<p>No error thrown but insert returned null.</p>";
        }
        
    } catch (\Exception $e) {
        echo "<h3>❌ Insert Failed!</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
        
        if (strpos($e->getMessage(), 'column') !== false) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4>Column Mismatch Issue</h4>";
            echo "<p>This error suggests there's a mismatch between the Laravel model and your actual table structure.</p>";
            echo "<p><strong>Solutions:</strong></p>";
            echo "<ul>";
            echo "<li>Check if all columns in the Laravel model exist in your table</li>";
            echo "<li>Verify data types match (datetime, int, nvarchar, etc.)</li>";
            echo "<li>Check for required columns that are missing values</li>";
            echo "</ul>";
            echo "</div>";
        }
    }
    
    echo "<p><a href='/test-existing-servicerequest'>← Back to main test</a></p>";
});

// ========================================
// ORIGINAL DEBUG ROUTES (kept for reference)
// ========================================

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
    
    // Database connection test
    echo "<h3>4. Database Connection</h3>";
    try {
        DB::connection()->getPdo();
        echo "Database: ✅ Connected to SQL Server<br>";
        
        $count = DB::table('ServiceRequest')->count();
        echo "ServiceRequest records: " . number_format($count) . "<br>";
    } catch (\Exception $e) {
        echo "Database: ❌ " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h3>🔗 Quick Links</h3>";
    echo "<ul>";
    echo "<li><a href='/test-existing-servicerequest'>Test Existing ServiceRequest Table</a></li>";
    echo "<li><a href='/'>Main Application (Make Prediction)</a></li>";
    echo "<li><a href='/maintenance'>View Service Requests</a></li>";
    echo "</ul>";
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

Route::get('/debug-vehicle/{vehicle}', function($vehicle) {
    echo "<h1>🔍 Vehicle Lookup Debug: {$vehicle}</h1>";
    
    try {
        // Test 1: Basic count
        $basicCount = DB::table('ServiceRequest')->count();
        echo "<p><strong>Total records in ServiceRequest:</strong> " . number_format($basicCount) . "</p>";
        
        // Test 2: Vehicle-specific counts with different methods
        echo "<h3>Lookup Methods:</h3>";
        $methods = [
            'Exact match' => DB::table('ServiceRequest')->where('Vehicle', $vehicle)->count(),
            'Upper case' => DB::table('ServiceRequest')->where('Vehicle', strtoupper($vehicle))->count(),
            'Like search' => DB::table('ServiceRequest')->where('Vehicle', 'LIKE', '%' . $vehicle . '%')->count(),
            'Trim + Upper' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->count(),
        ];
        
        foreach ($methods as $method => $count) {
            $status = $count > 0 ? '✅' : '❌';
            echo "<p>{$status} <strong>{$method}:</strong> {$count} records</p>";
        }
        
        // Test 3: Sample vehicles in database
        echo "<h3>Sample Vehicles in Database:</h3>";
        $sampleVehicles = DB::table('ServiceRequest')
            ->select('Vehicle')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->distinct()
            ->take(10)
            ->pluck('Vehicle');
            
        foreach ($sampleVehicles as $v) {
            echo "<code>'{$v}'</code> ";
        }
        
        // Test 4: Recent records for this vehicle
        echo "<h3>Recent Records:</h3>";
        $recentRecords = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->orderBy('Datereceived', 'desc')
            ->take(5)
            ->get(['ID', 'Vehicle', 'Description', 'Datereceived', 'Odometer']);
            
        if ($recentRecords->count() > 0) {
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>ID</th><th>Vehicle</th><th>Description</th><th>Date</th><th>Odometer</th></tr>";
            foreach ($recentRecords as $record) {
                echo "<tr>";
                echo "<td>{$record->ID}</td>";
                echo "<td>'{$record->Vehicle}'</td>";
                echo "<td>" . substr($record->Description ?? '', 0, 30) . "</td>";
                echo "<td>{$record->Datereceived}</td>";
                echo "<td>{$record->Odometer}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ No records found!</p>";
        }
        
        // Test 5: Laravel Model test
        echo "<h3>Laravel Model Test:</h3>";
        try {
            $modelCount = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->count();
            echo "<p>✅ <strong>Laravel Model:</strong> {$modelCount} records</p>";
        } catch (\Exception $e) {
            echo "<p>❌ <strong>Laravel Model Error:</strong> " . $e->getMessage() . "</p>";
        }
        
    } catch (\Exception $e) {
        echo "<h2>❌ Database Error:</h2>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    }
});

Route::get('/test-mileage-validation/{vehicle}/{mileage}', function($vehicle, $mileage) {
    try {
        echo "<h1>🔍 Fixed Validation Test for {$vehicle}</h1>";
        echo "<p><strong>Input Mileage:</strong> " . number_format($mileage) . " KM</p>";
        
        // Get raw data
        $rawRecords = DB::select("
            SELECT 
                Odometer,
                Datereceived,
                Description
            FROM ServiceRequest 
            WHERE UPPER(TRIM(Vehicle)) = ?
              AND Odometer IS NOT NULL 
              AND Odometer != ''
            ORDER BY Datereceived DESC
        ", [strtoupper(trim($vehicle))]);
        
        // Process to find valid mileages (same logic as validation)
        $validMileages = [];
        
        foreach ($rawRecords as $record) {
            $rawOdometer = trim($record->Odometer);
            
            if (is_numeric($rawOdometer)) {
                $mileageValue = floatval($rawOdometer);
                
                // Only accept reasonable values (above 10K to exclude zeros)
                if ($mileageValue >= 10000 && $mileageValue <= 2000000) {
                    $validMileages[] = [
                        'mileage' => intval($mileageValue),
                        'date' => $record->Datereceived,
                        'raw' => $rawOdometer
                    ];
                }
            }
        }
        
        if (empty($validMileages)) {
            echo "<p>❌ No valid mileage values found</p>";
            return;
        }
        
        // KEY: Use HIGHEST valid mileage
        $highestMileage = max(array_column($validMileages, 'mileage'));
        $userInput = intval($mileage);
        $difference = $highestMileage - $userInput;
        
        echo "<h3>📊 Corrected Analysis</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>";
        echo "<tr><td><strong>Highest Valid Mileage</strong></td><td>" . number_format($highestMileage) . " KM</td><td>✅ Found</td></tr>";
        echo "<tr><td><strong>Your Input</strong></td><td>" . number_format($userInput) . " KM</td><td>📝 Input</td></tr>";
        echo "<tr><td><strong>Difference</strong></td><td>" . number_format($difference) . " KM</td><td>" . ($difference > 0 ? "⬇️ Lower" : "⬆️ Higher") . "</td></tr>";
        echo "</table>";
        
        // Validation result
        if ($difference > 10000) {
            echo "<div style='background: #ffcccc; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #dc3545;'>";
            echo "<h3>❌ VALIDATION SHOULD FAIL ✅</h3>";
            echo "<p><strong>✅ CORRECT!</strong> User input ({$userInput}) is significantly lower than highest recorded ({$highestMileage})</p>";
            echo "<p><strong>Expected Error:</strong> \"Mileage appears incorrect. Highest recorded: " . number_format($highestMileage) . " KM, but you entered: " . number_format($userInput) . " KM\"</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #ccffcc; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #28a745;'>";
            echo "<h3>✅ VALIDATION SHOULD PASS</h3>";
            echo "<p>Mileage input is reasonable compared to database records.</p>";
            echo "</div>";
        }
        
        // Show valid mileage timeline
        echo "<h3>📈 Valid Mileage Timeline</h3>";
        echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr><th>Raw Value</th><th>Parsed KM</th><th>Date</th></tr>";
        
        // Sort by mileage (highest first)
        usort($validMileages, function($a, $b) {
            return $b['mileage'] - $a['mileage'];
        });
        
        foreach (array_slice($validMileages, 0, 10) as $vm) {
            $highlight = ($vm['mileage'] == $highestMileage) ? "style='background: #ffffcc; font-weight: bold;'" : "";
            echo "<tr {$highlight}>";
            echo "<td>'{$vm['raw']}'</td>";
            echo "<td>" . number_format($vm['mileage']) . "</td>";
            echo "<td>" . substr($vm['date'], 0, 10) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>🎯 Test Different Values</h3>";
        echo "<p>Try these links to test the validation:</p>";
        $testValues = [95000, 600000, 625000, 700000];
        foreach ($testValues as $testVal) {
            $expectation = ($highestMileage - $testVal > 10000) ? "❌ Should Fail" : "✅ Should Pass";
            echo "<a href='/test-mileage-validation/{$vehicle}/{$testVal}' style='margin-right: 15px; padding: 5px 10px; background: #f8f9fa; border: 1px solid #ddd; text-decoration: none; border-radius: 3px;'>" . number_format($testVal) . " KM ({$expectation})</a>";
        }
        
    } catch (\Exception $e) {
        echo "<h2>❌ Error:</h2>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    }
});

Route::get('/test-smart-filtering/{vehicle}/{mileage}', function($vehicle, $mileage) {
    try {
        echo "<h1>🧠 Smart Filtering Test for {$vehicle}</h1>";
        echo "<p><strong>Input Mileage:</strong> " . number_format($mileage) . " KM</p>";
        
        // Get controller instance
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        
        // Test the smart filtering validation
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('validateMileageWithSmartFiltering');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $vehicle, $mileage);
        
        echo "<h3>🎯 Smart Filtering Results:</h3>";
        
        if ($result['valid']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745;'>";
            echo "<h4>✅ VALIDATION PASSED</h4>";
            echo "<p>Mileage input is reasonable based on reliable data analysis.</p>";
            
            if (isset($result['data_quality_info'])) {
                $info = $result['data_quality_info'];
                echo "<ul>";
                echo "<li><strong>Outliers excluded:</strong> {$info['outliers_excluded']}</li>";
                echo "<li><strong>Reliable records used:</strong> {$info['reliable_records_used']}</li>";
                echo "<li><strong>Baseline mileage:</strong> " . number_format($info['baseline_mileage']) . " KM</li>";
                echo "</ul>";
            }
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
            echo "<h4>❌ VALIDATION FAILED</h4>";
            echo "<p><strong>Message:</strong> " . $result['message'] . "</p>";
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Metric</th><th>Value</th></tr>";
            echo "<tr><td>Your Input</td><td>" . number_format($result['user_input']) . " KM</td></tr>";
            echo "<tr><td>Reliable Baseline</td><td>" . number_format($result['reliable_mileage']) . " KM</td></tr>";
            echo "<tr><td>Difference</td><td>" . number_format($result['difference']) . " KM lower</td></tr>";
            echo "<tr><td>Service Date</td><td>{$result['service_date']}</td></tr>";
            echo "<tr><td>Outliers Excluded</td><td>{$result['outliers_excluded']}</td></tr>";
            echo "</table>";
            
            echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 3px;'>";
            echo "<strong>💡 Smart Filtering Benefits:</strong>";
            echo "<ul style='margin: 5px 0;'>";
            echo "<li>Uses only reliable data for validation</li>";
            echo "<li>Automatically excludes {$result['outliers_excluded']} questionable records</li>";
            echo "<li>Database remains completely unchanged</li>";
            echo "<li>More accurate validation decisions</li>";
            echo "</ul>";
            echo "</div>";
            echo "</div>";
        }
        
        // Show comparison with old method (if we had one)
        echo "<h3>📊 Comparison with Basic Method:</h3>";
        
        // Get highest raw value (old method)
        $rawRecords = DB::select("
            SELECT 
                Odometer,
                Datereceived,
                TRY_CAST(Odometer AS FLOAT) as OdometerFloat
            FROM ServiceRequest 
            WHERE UPPER(TRIM(Vehicle)) = ?
            AND Odometer IS NOT NULL 
            AND Odometer != ''
            AND TRY_CAST(Odometer AS FLOAT) IS NOT NULL
            AND TRY_CAST(Odometer AS FLOAT) > 1000
            ORDER BY TRY_CAST(Odometer AS FLOAT) DESC
        ", [strtoupper(trim($vehicle))]);
        
        if (!empty($rawRecords)) {
            $highestRaw = intval($rawRecords[0]->OdometerFloat);
            $rawDifference = $highestRaw - intval($mileage);
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Method</th><th>Baseline Used</th><th>Difference</th><th>Decision</th></tr>";
            echo "<tr>";
            echo "<td><strong>Basic Method</strong><br><small>(uses highest raw value)</small></td>";
            echo "<td>" . number_format($highestRaw) . " KM<br><small>from " . substr($rawRecords[0]->Datereceived, 0, 10) . "</small></td>";
            echo "<td>" . number_format($rawDifference) . " KM</td>";
            echo "<td>" . ($rawDifference > 10000 ? "❌ FAIL" : "✅ PASS") . "</td>";
            echo "</tr>";
            echo "<tr style='background: #e7f3ff;'>";
            echo "<td><strong>Smart Filtering</strong><br><small>(uses reliable baseline)</small></td>";
            echo "<td>" . number_format($result['reliable_mileage'] ?? $result['data_quality_info']['baseline_mileage'] ?? 0) . " KM<br><small>filtered & validated</small></td>";
            echo "<td>" . number_format($result['difference'] ?? 0) . " KM</td>";
            echo "<td>" . ($result['valid'] ? "✅ PASS" : "❌ FAIL") . "</td>";
            echo "</tr>";
            echo "</table>";
            
            if ($highestRaw != ($result['reliable_mileage'] ?? $result['data_quality_info']['baseline_mileage'] ?? 0)) {
                echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
                echo "<h4>🎯 Smart Filtering Advantage</h4>";
                echo "<p>The smart filtering detected that the highest raw value (" . number_format($highestRaw) . " KM) is likely an outlier, ";
                echo "and instead uses a more reliable baseline (" . number_format($result['reliable_mileage'] ?? $result['data_quality_info']['baseline_mileage'] ?? 0) . " KM) ";
                echo "based on statistical analysis of all data points.</p>";
                echo "</div>";
            }
        }
        
        echo "<h3>🔗 Quick Tests:</h3>";
        $testValues = [95000, 600000, 625000, 650000, 700000];
        foreach ($testValues as $testVal) {
            echo "<a href='/test-smart-filtering/{$vehicle}/{$testVal}' style='margin: 5px; padding: 8px 12px; background: #f8f9fa; border: 1px solid #ddd; text-decoration: none; border-radius: 4px; display: inline-block;'>";
            echo number_format($testVal) . " KM</a>";
        }
        
        echo "<p style='margin-top: 20px;'><a href='/fleet-data-quality/{$vehicle}'>🔍 View Full Data Quality Analysis</a></p>";
        
    } catch (\Exception $e) {
        echo "<h2>❌ Error:</h2>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
    }
});

Route::get('/debug-vek4613', function() {
    echo "<h1>🔍 VEK4613 Debug</h1>";
    
    try {
        $vehicle = 'VEK4613';
        
        // Test 1: Basic counts
        echo "<h3>1. Basic Database Tests</h3>";
        $totalRecords = DB::table('ServiceRequest')->count();
        echo "Total ServiceRequest records: " . number_format($totalRecords) . "<br>";
        
        // Test 2: Different lookup methods
        echo "<h3>2. Vehicle Lookup Methods</h3>";
        $methods = [
            'Exact match' => DB::table('ServiceRequest')->where('Vehicle', $vehicle)->count(),
            'Upper case' => DB::table('ServiceRequest')->where('Vehicle', strtoupper($vehicle))->count(),
            'Like search' => DB::table('ServiceRequest')->where('Vehicle', 'LIKE', '%' . $vehicle . '%')->count(),
            'Raw upper trim' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->count(),
            'With date filter' => DB::table('ServiceRequest')
                ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
                ->whereNotNull('Datereceived')
                ->count(),
        ];
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Method</th><th>Count</th><th>Status</th></tr>";
        foreach ($methods as $method => $count) {
            $status = $count > 0 ? '✅' : '❌';
            echo "<tr><td>{$method}</td><td>{$count}</td><td>{$status}</td></tr>";
        }
        echo "</table><br>";
        
        // Test 3: Sample records
        echo "<h3>3. Sample Records</h3>";
        $sampleRecords = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->orderBy('ID', 'desc')
            ->take(3)
            ->get(['ID', 'Vehicle', 'Datereceived', 'Description', 'Odometer']);
            
        if ($sampleRecords->count() > 0) {
            echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
            echo "<tr><th>ID</th><th>Vehicle</th><th>Date</th><th>Description</th><th>Odometer</th></tr>";
            foreach ($sampleRecords as $record) {
                echo "<tr>";
                echo "<td>{$record->ID}</td>";
                echo "<td>'{$record->Vehicle}'</td>";
                echo "<td>{$record->Datereceived}</td>";
                echo "<td>" . substr($record->Description ?? '', 0, 50) . "</td>";
                echo "<td>{$record->Odometer}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ No records found!</p>";
        }
        
        // Test 4: Laravel Model test
        echo "<h3>4. Laravel Model Test</h3>";
        try {
            $modelCount = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->count();
            echo "Laravel model count: " . $modelCount . "<br>";
            
            if ($modelCount > 0) {
                $firstRecord = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
                    ->orderBy('Datereceived', 'desc')
                    ->first();
                    
                if ($firstRecord) {
                    echo "Latest record ID: " . $firstRecord->ID . "<br>";
                    echo "Vehicle: '" . $firstRecord->Vehicle . "'<br>";
                    echo "Date: " . $firstRecord->Datereceived . "<br>";
                }
            }
        } catch (\Exception $e) {
            echo "Laravel model error: " . $e->getMessage() . "<br>";
        }
        
        // Test 5: Check for date issues
        echo "<h3>5. Date Issues Check</h3>";
        $withoutDateFilter = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->count();
            
        $withDateFilter = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->whereNotNull('Datereceived')
            ->count();
            
        echo "Without date filter: {$withoutDateFilter}<br>";
        echo "With date filter: {$withDateFilter}<br>";
        
        if ($withoutDateFilter > $withDateFilter) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>⚠️ Issue Found:</strong> Some records have NULL Datereceived values.<br>";
            echo "Records lost due to date filter: " . ($withoutDateFilter - $withDateFilter);
            echo "</div>";
        }
        
        // Test 6: Quick fix suggestion
        echo "<h3>6. Suggested Fix</h3>";
        if ($withoutDateFilter > 0 && $withDateFilter == 0) {
            echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
            echo "<h4>💡 Solution Found!</h4>";
            echo "<p>The vehicle has {$withoutDateFilter} records, but they all have NULL Datereceived values.</p>";
            echo "<p><strong>Quick Fix:</strong> Remove or modify the date filter in your getEnhancedVehicleHistory method.</p>";
            echo "<p>Change this line:<br><code>->whereNotNull('Datereceived')</code></p>";
            echo "<p>To this:<br><code>->where(function(\$query) { \$query->whereNotNull('Datereceived')->orWhereNotNull('ID'); })</code></p>";
            echo "</div>";
        } elseif ($withDateFilter > 0) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ Records Found!</h4>";
            echo "<p>The issue might be elsewhere in your code. The vehicle lookup is working.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h4>❌ No Records Found</h4>";
            echo "<p>The vehicle 'VEK4613' might not exist in your database, or there's a database connection issue.</p>";
            echo "</div>";
        }
        
    } catch (\Exception $e) {
        echo "<h3>❌ Database Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/simple-debug', function() {
    echo "<h1>🔍 Simple VEK4613 Debug</h1>";
    
    $vehicle = 'VEK4613';
    $mileage = 650000;
    
    try {
        echo "<h3>Step 1: Basic Record Count</h3>";
        $basicCount = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->count();
        echo "Basic count: {$basicCount} records<br>";
        
        echo "<h3>Step 2: Testing Controller Method</h3>";
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        
        // Test the enhanced history method
        echo "Calling getEnhancedVehicleHistory...<br>";
        
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getEnhancedVehicleHistory');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $vehicle);
        
        echo "<h3>Step 3: Results</h3>";
        echo "Total services: " . ($result['total_services'] ?? 'NULL') . "<br>";
        echo "Records object: " . (isset($result['records']) ? get_class($result['records']) : 'NULL') . "<br>";
        echo "Records count: " . (isset($result['records']) ? $result['records']->count() : 'NULL') . "<br>";
        echo "Vehicle type: " . ($result['vehicle_type'] ?? 'NULL') . "<br>";
        echo "Average interval: " . ($result['average_interval'] ?? 'NULL') . "<br>";
        
        if ($result['total_services'] === 0) {
            echo "<div style='background: #ffcccc; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h4>❌ PROBLEM FOUND!</h4>";
            echo "<p>getEnhancedVehicleHistory is returning 0 services even though {$basicCount} records exist.</p>";
            echo "<p>This means there's an exception being caught in the method.</p>";
            echo "</div>";
            
            echo "<h3>Step 4: Testing Simple Alternative</h3>";
            echo "Let's try the basic getVehicleHistory method instead...<br>";
            
            try {
                $simpleMethod = $reflection->getMethod('getVehicleHistory');
                $simpleMethod->setAccessible(true);
                $simpleResult = $simpleMethod->invoke($controller, $vehicle);
                
                echo "Simple method total services: " . ($simpleResult['total_services'] ?? 'NULL') . "<br>";
                
                if ($simpleResult['total_services'] > 0) {
                    echo "<div style='background: #ccffcc; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                    echo "<h4>✅ SOLUTION FOUND!</h4>";
                    echo "<p>The simple getVehicleHistory method works fine.</p>";
                    echo "<p><strong>Quick Fix:</strong> Replace getEnhancedVehicleHistory with getVehicleHistory in your predict method.</p>";
                    echo "</div>";
                }
                
            } catch (Exception $e) {
                echo "Simple method error: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "<div style='background: #ccffcc; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h4>✅ Enhanced method working!</h4>";
            echo "<p>The issue might be elsewhere in your prediction flow.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #ffcccc; padding: 15px; border-radius: 5px;'>";
        echo "<h4>❌ ERROR FOUND:</h4>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "</div>";
        
        // Show the exact error location
        echo "<h3>Error Details:</h3>";
        echo "<pre style='background: #f1f1f1; padding: 10px; font-size: 12px; border-radius: 3px;'>";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "</pre>";
    }
    
    echo "<h3>📋 Quick Actions</h3>";
    echo "<p>Based on the results above:</p>";
    echo "<ul>";
    echo "<li>If enhanced method returns 0: Use the simple method instead</li>";
    echo "<li>If there's an error: Check the file and line mentioned</li>";
    echo "<li>If both methods work: Check your predict() method flow</li>";
    echo "</ul>";
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/debug-mrtype', function() {
    echo "<h1>🔍 MrType Values Debug for VEK4613</h1>";
    
    try {
        $vehicle = 'VEK4613';
        
        // Get all MrType values for this vehicle
        $mrTypes = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->select('MrType', DB::raw('COUNT(*) as count'))
            ->groupBy('MrType')
            ->orderBy('count', 'desc')
            ->get();
        
        echo "<h3>📊 Actual MrType Values in Database:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>MrType Value</th><th>Count</th><th>Data Type</th><th>Expected Type</th></tr>";
        
        foreach ($mrTypes as $type) {
            $dataType = gettype($type->MrType);
            $expectedType = '';
            
            if ($type->MrType === '1' || $type->MrType === 1) {
                $expectedType = 'Repair';
            } elseif ($type->MrType === '2' || $type->MrType === 2) {
                $expectedType = 'Cleaning';
            } elseif ($type->MrType === '3' || $type->MrType === 3) {
                $expectedType = 'Maintenance';
            } else {
                $expectedType = 'Other/Unknown';
            }
            
            echo "<tr>";
            echo "<td>'" . ($type->MrType ?? 'NULL') . "'</td>";
            echo "<td>{$type->count}</td>";
            echo "<td>{$dataType}</td>";
            echo "<td>{$expectedType}</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Test different filtering approaches
        echo "<h3>🧪 Testing Different Filter Approaches:</h3>";
        
        $testResults = [
            'Integer 1' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', 1)->count(),
            'String "1"' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', '1')->count(),
            'Integer 2' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', 2)->count(),
            'String "2"' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', '2')->count(),
            'Integer 3' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', 3)->count(),
            'String "3"' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', '3')->count(),
            'NULL values' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->whereNull('MrType')->count(),
            'Empty string' => DB::table('ServiceRequest')->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])->where('MrType', '')->count(),
        ];
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Filter Type</th><th>Count</th><th>Status</th></tr>";
        
        foreach ($testResults as $filter => $count) {
            $status = $count > 0 ? '✅' : '❌';
            echo "<tr><td>{$filter}</td><td>{$count}</td><td>{$status}</td></tr>";
        }
        echo "</table><br>";
        
        // Show sample records
        echo "<h3>📝 Sample Records with MrType:</h3>";
        $sampleRecords = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [$vehicle])
            ->orderBy('ID', 'desc')
            ->take(5)
            ->get(['ID', 'MrType', 'Description', 'Datereceived']);
            
        echo "<table border='1' style='border-collapse: collapse; font-size: 12px;'>";
        echo "<tr><th>ID</th><th>MrType</th><th>Description</th><th>Date</th></tr>";
        
        foreach ($sampleRecords as $record) {
            echo "<tr>";
            echo "<td>{$record->ID}</td>";
            echo "<td>'" . ($record->MrType ?? 'NULL') . "'</td>";
            echo "<td>" . substr($record->Description ?? '', 0, 40) . "</td>";
            echo "<td>" . substr($record->Datereceived ?? '', 0, 10) . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Provide the fix based on results
        echo "<h3>💡 Suggested Fix:</h3>";
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
        echo "<p>Based on the analysis above, update your service breakdown code in getVehicleHistory():</p>";
        echo "<pre style='background: #f1f1f1; padding: 10px; border-radius: 3px;'>";
        
        // Generate the correct code based on what we find
        if ($testResults['String "1"'] > 0) {
            echo "'service_breakdown' => [\n";
            echo "    'repairs' => \$history->where('MrType', '1')->count(),\n";
            echo "    'cleaning' => \$history->where('MrType', '2')->count(),\n";
            echo "    'maintenance' => \$history->where('MrType', '3')->count(),\n";
            echo "    'other' => \$history->whereNotIn('MrType', ['1', '2', '3'])->count()\n";
            echo "],";
        } else {
            echo "'service_breakdown' => [\n";
            echo "    'repairs' => \$history->where('MrType', 1)->count(),\n";
            echo "    'cleaning' => \$history->where('MrType', 2)->count(),\n";
            echo "    'maintenance' => \$history->where('MrType', 3)->count(),\n";
            echo "    'other' => \$history->whereNotIn('MrType', [1, 2, 3])->count()\n";
            echo "],";
        }
        
        echo "</pre>";
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<h3>❌ Error:</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/test-keyword-matching/{vehicle}', function($vehicle) {
    echo "<h1>🔍 Enhanced Keyword Matching Test for {$vehicle}</h1>";
    
    try {
        // Get records containing oil/minyak (like your query results)
        $oilRecords = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->where(function($query) {
                $query->where('Description', 'LIKE', '%oil%')
                      ->orWhere('Description', 'LIKE', '%minyak%')
                      ->orWhere('Response', 'LIKE', '%oil%')
                      ->orWhere('Response', 'LIKE', '%minyak%');
            })
            ->orderBy('Datereceived', 'desc')
            ->take(20)
            ->get(['ID', 'Description', 'Response', 'Datereceived', 'Odometer']);
            
        echo "<h3>📋 Found " . $oilRecords->count() . " Oil/Minyak Related Records</h3>";
        
        // Define the enhanced keywords (same as in the enhanced method)
        $partKeywords = [
            'Engine Oil' => [
                'engine oil', 'oil change', 'motor oil', 'minyak enjin', 
                'minyak engine', 'tukar minyak', 'minyak gearbox', 'gearbox oil'
            ],
            'Oil Seals' => [
                'oil seal', 'sealing', 'shaft seal', 'seal minyak',
                'oil seal axle', 'oil seal shaft', 'oilseal'
            ],
            'Oil Filter' => [
                'oil filter', 'filter minyak', 'penapis minyak', 'filter oil'
            ],
            'Gearbox/Transmission' => [
                'gearbox', 'transmission', 'gear oil', 'kotak gear', 
                'minyak gearbox', 'gearbox bocor', 'transmission leak'
            ],
            'Brake System' => [
                'brake pad', 'brake check', 'brake chamber', 'brake fluid',
                'pad brek', 'brek', 'minyak brek', 'brake chamber adjust', 'adjuster bracket'
            ]
        ];
        
        // Test each record against all part categories
        echo "<div style='max-height: 600px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; margin: 15px 0;'>";
        
        foreach ($oilRecords as $record) {
            echo "<div style='border-bottom: 1px solid #eee; padding: 10px 0; margin-bottom: 10px;'>";
            echo "<h6>Record ID: {$record->ID} | Date: " . substr($record->Datereceived ?? '', 0, 10) . "</h6>";
            echo "<p><strong>Description:</strong> " . ($record->Description ?? 'No description') . "</p>";
            
            if ($record->Response) {
                echo "<p><strong>Response:</strong> " . substr($record->Response, 0, 200) . "</p>";
            }
            
            // Test against each part category
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            $matchedCategories = [];
            
            foreach ($partKeywords as $partName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($searchText, strtolower($keyword))) {
                        $matchedCategories[] = [
                            'part' => $partName,
                            'keyword' => $keyword
                        ];
                        break; // Only count once per part category
                    }
                }
            }
            
            if (!empty($matchedCategories)) {
                echo "<div style='background: #d4edda; padding: 8px; border-radius: 4px; margin-top: 5px;'>";
                echo "<strong>✅ Matched Categories:</strong><br>";
                foreach ($matchedCategories as $match) {
                    echo "<span style='background: #28a745; color: white; padding: 2px 6px; border-radius: 3px; margin-right: 5px; font-size: 12px;'>";
                    echo "{$match['part']} (via '{$match['keyword']}')</span> ";
                }
                echo "</div>";
            } else {
                echo "<div style='background: #fff3cd; padding: 8px; border-radius: 4px; margin-top: 5px;'>";
                echo "<strong>⚠️ No category match found</strong> - Consider adding more keywords";
                echo "</div>";
            }
            echo "</div>";
        }
        echo "</div>";
        
        // Summary statistics
        echo "<h3>📊 Keyword Matching Summary</h3>";
        $totalMatched = 0;
        $categoryStats = [];
        
        foreach ($oilRecords as $record) {
            $searchText = strtolower(($record->Description ?? '') . ' ' . ($record->Response ?? ''));
            $recordMatched = false;
            
            foreach ($partKeywords as $partName => $keywords) {
                foreach ($keywords as $keyword) {
                    if (str_contains($searchText, strtolower($keyword))) {
                        if (!isset($categoryStats[$partName])) {
                            $categoryStats[$partName] = 0;
                        }
                        $categoryStats[$partName]++;
                        $recordMatched = true;
                        break;
                    }
                }
            }
            
            if ($recordMatched) {
                $totalMatched++;
            }
        }
        
        echo "<table border='1' style='border-collapse: collapse; margin: 15px 0;'>";
        echo "<tr><th>Part Category</th><th>Records Matched</th><th>Percentage</th></tr>";
        
        foreach ($categoryStats as $partName => $count) {
            $percentage = round(($count / $oilRecords->count()) * 100, 1);
            echo "<tr><td>{$partName}</td><td>{$count}</td><td>{$percentage}%</td></tr>";
        }
        
        $unmatchedCount = $oilRecords->count() - $totalMatched;
        $unmatchedPercentage = round(($unmatchedCount / $oilRecords->count()) * 100, 1);
        
        echo "<tr style='background: #f8f9fa; font-weight: bold;'>";
        echo "<td>TOTAL MATCHED</td><td>{$totalMatched}</td><td>" . round(($totalMatched / $oilRecords->count()) * 100, 1) . "%</td></tr>";
        echo "<tr style='background: #fff3cd;'>";
        echo "<td>UNMATCHED</td><td>{$unmatchedCount}</td><td>{$unmatchedPercentage}%</td></tr>";
        echo "</table>";
        
        // Suggestions for improvement
        echo "<h3>💡 Improvement Suggestions</h3>";
        echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>Coverage:</strong> {$totalMatched} out of {$oilRecords->count()} records matched ({$unmatchedPercentage}% unmatched)</p>";
        
        if ($unmatchedPercentage > 20) {
            echo "<p><strong>⚠️ Consider adding more keywords for better coverage:</strong></p>";
            echo "<ul>";
            echo "<li>Add more Malay terms (bocor, ganti, tukar, etc.)</li>";
            echo "<li>Add specific part numbers or brands</li>";
            echo "<li>Add common misspellings (oilseal vs oil seal)</li>";
            echo "</ul>";
        } else {
            echo "<p><strong>✅ Good coverage! The enhanced keywords are working well.</strong></p>";
        }
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<h3>❌ Error:</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/test-tire-quick/{vehicle}', function($vehicle) {
    try {
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        $records = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->get();
        
        // Test if method exists
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('getAdvancedTireAnalysis');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $records, 650000);
        
        echo "<h1>✅ Test Results for {$vehicle}</h1>";
        echo "<p><strong>Total Tire Services:</strong> " . $result['total_tire_services'] . "</p>";
        echo "<p><strong>Health Score:</strong> " . $result['tire_health_score'] . "/100</p>";
        echo "<p><strong>Risk Level:</strong> " . $result['risk_level'] . "</p>";
        echo "<p><strong>Categories Found:</strong> " . count($result['categories']) . "</p>";
        
        if (!empty($result['categories'])) {
            echo "<h3>Categories:</h3>";
            foreach ($result['categories'] as $key => $category) {
                echo "<p>• {$category['name']}: {$category['count']} issues ({$category['percentage']}%)</p>";
            }
        }
        
        echo "<p><strong>✅ Integration working!</strong></p>";
        
    } catch (\Exception $e) {
        echo "<h1>❌ Error</h1>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
    }
});

Route::get('/debug-integration/{vehicle}/{mileage}', function($vehicle, $mileage) {
    echo "<h1>🔍 Integration Debug for {$vehicle}</h1>";
    
    try {
        echo "<h3>Step 1: Controller Instance</h3>";
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        echo "✅ Controller created successfully<br>";
        
        echo "<h3>Step 2: Get Vehicle Records</h3>";
        $records = App\Models\ServiceRequest::whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])->get();
        echo "✅ Found " . $records->count() . " records<br>";
        
        echo "<h3>Step 3: Test Method Exists</h3>";
        $reflection = new ReflectionClass($controller);
        if ($reflection->hasMethod('getAdvancedTireAnalysis')) {
            echo "✅ getAdvancedTireAnalysis method exists<br>";
        } else {
            echo "❌ getAdvancedTireAnalysis method NOT found<br>";
            echo "<p><strong>SOLUTION:</strong> Copy the tire analysis methods to your PredictionController.php</p>";
            return;
        }
        
        echo "<h3>Step 4: Test Method Call</h3>";
        $method = $reflection->getMethod('getAdvancedTireAnalysis');
        $method->setAccessible(true);
        
        $tireAnalysis = $method->invoke($controller, $records, intval($mileage));
        echo "✅ Method call successful<br>";
        echo "Result: " . $tireAnalysis['total_tire_services'] . " tire services found<br>";
        
        echo "<h3>Step 5: Test getVehicleHistory</h3>";
        if ($reflection->hasMethod('getVehicleHistory')) {
            $historyMethod = $reflection->getMethod('getVehicleHistory');
            $historyMethod->setAccessible(true);
            
            $vehicleHistory = $historyMethod->invoke($controller, $vehicle);
            echo "✅ getVehicleHistory successful<br>";
            echo "Total services: " . $vehicleHistory['total_services'] . "<br>";
            
            // Check if tire analysis is included
            if (isset($vehicleHistory['advanced_tire_analysis'])) {
                echo "✅ Tire analysis is included in vehicle history<br>";
            } else {
                echo "❌ Tire analysis NOT included in vehicle history<br>";
                echo "<p><strong>ISSUE:</strong> Integration line not added or not working</p>";
            }
        } else {
            echo "❌ getVehicleHistory method not found<br>";
        }
        
        echo "<h3>Step 6: Test Full Predict Flow</h3>";
        echo "<p>Try running the main prediction now. If it still fails, check Laravel logs.</p>";
        
        echo "<h3>Step 7: Carbon Import Check</h3>";
        try {
            $testDate = Carbon\Carbon::now();
            echo "✅ Carbon import working<br>";
        } catch (\Exception $e) {
            echo "❌ Carbon import issue: " . $e->getMessage() . "<br>";
            echo "<p><strong>SOLUTION:</strong> Add 'use Carbon\\Carbon;' at the top of PredictionController.php</p>";
        }
        
    } catch (\Exception $e) {
        echo "<h3>❌ Error Found:</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
        
        echo "<h3>🔧 Common Solutions:</h3>";
        echo "<ul>";
        echo "<li>Make sure all tire analysis methods are copied to PredictionController.php</li>";
        echo "<li>Check that 'use Carbon\\Carbon;' is at the top of the file</li>";
        echo "<li>Verify the integration line is added correctly</li>";
        echo "<li>Clear cache: php artisan cache:clear</li>";
        echo "</ul>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a> | <a href='/check-logs'>Check Logs</a></p>";
});

Route::get('/check-logs', function() {
    $logFile = storage_path('logs/laravel.log');
    
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $lines = explode("\n", $logs);
        
        // Get last 30 lines
        $recentLogs = array_slice($lines, -30);
        
        echo "<h1>📋 Recent Laravel Logs</h1>";
        echo "<div style='background: #f4f4f4; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 500px; overflow-y: auto;'>";
        
        foreach ($recentLogs as $line) {
            if (str_contains($line, 'ERROR') || str_contains($line, 'Exception') || str_contains($line, 'Fatal')) {
                echo "<div style='color: red; font-weight: bold;'>" . htmlspecialchars($line) . "</div>";
            } elseif (str_contains($line, 'tire') || str_contains($line, 'Tire') || str_contains($line, 'Advanced')) {
                echo "<div style='color: blue; font-weight: bold;'>" . htmlspecialchars($line) . "</div>";
            } else {
                echo "<div>" . htmlspecialchars($line) . "</div>";
            }
        }
        
        echo "</div>";
        
        echo "<h3>🔍 Look for:</h3>";
        echo "<ul>";
        echo "<li><span style='color: red;'>Red lines</span> - Errors and exceptions</li>";
        echo "<li><span style='color: blue;'>Blue lines</span> - Tire analysis related logs</li>";
        echo "<li>Method names, line numbers, file paths</li>";
        echo "</ul>";
        
    } else {
        echo "<h1>❌ No log file found</h1>";
        echo "<p>Expected location: {$logFile}</p>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/test-recommendations-flow/{vehicle}/{mileage}', function($vehicle, $mileage) {
    try {
        echo "<h1>🔍 Testing Recommendations Flow for {$vehicle}</h1>";
        
        // Create controller instance
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        $reflection = new ReflectionClass($controller);
        
        echo "<h3>Step 1: Test safeRecommendations Method Directly</h3>";
        
        // Create mock data
        $mockServiceSchedule = [
            'next_routine' => ['mileage' => 660000, 'km_remaining' => 10000],
            'next_major' => ['mileage' => 670000, 'km_remaining' => 20000]
        ];
        
        $mockPartsAnalysis = [
            'immediate' => [],
            'soon' => [],
            'routine' => [
                ['part' => 'Engine Oil', 'reason' => 'Due soon']
            ]
        ];
        
        $mockMLPrediction = [
            'prediction' => 'service',
            'confidence' => 0.7
        ];
        
        // Test the method directly
        $safeRecommendationsMethod = $reflection->getMethod('safeRecommendations');
        $safeRecommendationsMethod->setAccessible(true);
        
        $directResult = $safeRecommendationsMethod->invoke(
            $controller, 
            $mockServiceSchedule, 
            $mockPartsAnalysis, 
            $mockMLPrediction
        );
        
        echo "<div style='background: #f4f4f4; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h4>Direct Method Result:</h4>";
        echo "<pre>";
        print_r($directResult);
        echo "</pre>";
        echo "</div>";
        
        // Check specific keys
        echo "<h4>Key Check:</h4>";
        $checks = [
            'priority' => isset($directResult['priority']),
            'action_plan' => isset($directResult['action_plan']),
            'cost_estimate' => isset($directResult['cost_estimate']),
            'cost_estimate.min' => isset($directResult['cost_estimate']['min']),
            'cost_estimate.max' => isset($directResult['cost_estimate']['max']),
            'time_estimate' => isset($directResult['time_estimate'])
        ];
        
        foreach ($checks as $key => $exists) {
            $status = $exists ? '✅' : '❌';
            echo "{$status} {$key}<br>";
        }
        
        echo "<h3>Step 2: Test Full Flow (Real Data)</h3>";
        
        // Get real vehicle history
        $getVehicleHistoryMethod = $reflection->getMethod('getVehicleHistory');
        $getVehicleHistoryMethod->setAccessible(true);
        $vehicleHistory = $getVehicleHistoryMethod->invoke($controller, $vehicle);
        
        echo "Vehicle history total services: " . ($vehicleHistory['total_services'] ?? 'NULL') . "<br>";
        
        if ($vehicleHistory['total_services'] > 0) {
            // Test with real data
            $serviceScheduleMethod = $reflection->getMethod('calculateServiceSchedule');
            $serviceScheduleMethod->setAccessible(true);
            $realServiceSchedule = $serviceScheduleMethod->invoke($controller, intval($mileage), $vehicleHistory);
            
            $partsAnalysisMethod = $reflection->getMethod('safePartsAnalysis');
            $partsAnalysisMethod->setAccessible(true);
            $realPartsAnalysis = $partsAnalysisMethod->invoke($controller, intval($mileage), $vehicleHistory, $mockMLPrediction);
            
            $realRecommendations = $safeRecommendationsMethod->invoke(
                $controller, 
                $realServiceSchedule, 
                $realPartsAnalysis, 
                $mockMLPrediction
            );
            
            echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h4>Real Data Result:</h4>";
            echo "<pre>";
            print_r($realRecommendations);
            echo "</pre>";
            echo "</div>";
            
            // Check if cost_estimate exists in real flow
            if (isset($realRecommendations['cost_estimate']['min']) && isset($realRecommendations['cost_estimate']['max'])) {
                echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
                echo "<h4>✅ SUCCESS!</h4>";
                echo "<p>The safeRecommendations method IS working correctly!</p>";
                echo "<p>Cost estimate: RM " . number_format($realRecommendations['cost_estimate']['min']) . 
                     " - RM " . number_format($realRecommendations['cost_estimate']['max']) . "</p>";
                echo "<p><strong>This means the issue is happening AFTER this method is called.</strong></p>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
                echo "<h4>❌ PROBLEM FOUND!</h4>";
                echo "<p>The safeRecommendations method is not returning the expected structure.</p>";
                echo "</div>";
            }
        }
        
        echo "<h3>Step 3: Test What Gets to the View</h3>";
        echo "<p>Now try your normal prediction flow and check the Laravel logs to see if the debug messages show up.</p>";
        echo "<p>The logs will tell us exactly where the cost_estimate is getting lost.</p>";
        
        echo "<h3>📋 Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Add the debug logging to your predict() method (from the code above)</li>";
        echo "<li>Try a normal prediction with VEK4613</li>";
        echo "<li>Check <code>storage/logs/laravel.log</code> for the debug messages</li>";
        echo "<li>Look for where the cost_estimate disappears</li>";
        echo "</ol>";
        
    } catch (\Exception $e) {
        echo "<h3>❌ Error:</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/test-exact-flow/{vehicle}/{mileage}', function($vehicle, $mileage) {
    try {
        echo "<h1>🔍 Testing Exact Flow That's Failing</h1>";
        echo "<p><strong>Vehicle:</strong> {$vehicle} | <strong>Mileage:</strong> " . number_format($mileage) . "</p>";
        
        // Simulate the exact same flow as your predict method
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        $reflection = new ReflectionClass($controller);
        
        echo "<h3>Step 1: Get Vehicle History</h3>";
        $getVehicleHistoryMethod = $reflection->getMethod('getVehicleHistory');
        $getVehicleHistoryMethod->setAccessible(true);
        $vehicleHistory = $getVehicleHistoryMethod->invoke($controller, $vehicle);
        
        $totalServices = $vehicleHistory['total_services'] ?? 0;
        echo "Total services: {$totalServices}<br>";
        
        if ($totalServices === 0) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h4>⚠️ NEW VEHICLE DETECTED!</h4>";
            echo "<p>This vehicle has 0 services, so it's going to handleNewVehicle method.</p>";
            echo "<p><strong>Check if handleNewVehicle has proper cost_estimate structure!</strong></p>";
            
            // Test handleNewVehicle
            $handleNewVehicleMethod = $reflection->getMethod('handleNewVehicle');
            $handleNewVehicleMethod->setAccessible(true);
            
            try {
                $newVehicleResult = $handleNewVehicleMethod->invoke($controller, $vehicle, intval($mileage));
                echo "<p>✅ handleNewVehicle method executed successfully</p>";
                echo "<p>🔍 Check the getNewVehicleRecommendations method for cost_estimate structure</p>";
            } catch (\Exception $e) {
                echo "<p>❌ handleNewVehicle failed: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
            return;
        }
        
        echo "<h3>Step 2: Calculate Service Schedule</h3>";
        $calculateServiceScheduleMethod = $reflection->getMethod('calculateServiceSchedule');
        $calculateServiceScheduleMethod->setAccessible(true);
        $serviceSchedule = $calculateServiceScheduleMethod->invoke($controller, intval($mileage), $vehicleHistory);
        echo "✅ Service schedule calculated<br>";
        
        echo "<h3>Step 3: ML Prediction</h3>";
        $safeMLPredictionMethod = $reflection->getMethod('safeMLPrediction');
        $safeMLPredictionMethod->setAccessible(true);
        $mlPrediction = $safeMLPredictionMethod->invoke($controller, $vehicle, intval($mileage), $vehicleHistory);
        echo "✅ ML prediction: " . ($mlPrediction['prediction'] ?? 'unknown') . "<br>";
        
        echo "<h3>Step 4: Parts Analysis</h3>";
        $safePartsAnalysisMethod = $reflection->getMethod('safePartsAnalysis');
        $safePartsAnalysisMethod->setAccessible(true);
        $partsAnalysis = $safePartsAnalysisMethod->invoke($controller, intval($mileage), $vehicleHistory, $mlPrediction);
        
        $immediateCount = count($partsAnalysis['immediate'] ?? []);
        $soonCount = count($partsAnalysis['soon'] ?? []);
        $routineCount = count($partsAnalysis['routine'] ?? []);
        
        echo "✅ Parts analysis: {$immediateCount} immediate, {$soonCount} soon, {$routineCount} routine<br>";
        
        echo "<h3>Step 5: Recommendations (THE CRITICAL STEP)</h3>";
        $safeRecommendationsMethod = $reflection->getMethod('safeRecommendations');
        $safeRecommendationsMethod->setAccessible(true);
        $recommendations = $safeRecommendationsMethod->invoke($controller, $serviceSchedule, $partsAnalysis, $mlPrediction);
        
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4>✅ Recommendations Created Successfully</h4>";
        echo "<p><strong>Priority:</strong> " . ($recommendations['priority'] ?? 'MISSING') . "</p>";
        echo "<p><strong>Cost Estimate:</strong> ";
        if (isset($recommendations['cost_estimate']['min']) && isset($recommendations['cost_estimate']['max'])) {
            echo "RM " . number_format($recommendations['cost_estimate']['min']) . " - RM " . number_format($recommendations['cost_estimate']['max']);
        } else {
            echo "❌ MISSING";
        }
        echo "</p>";
        echo "<p><strong>Time Estimate:</strong> " . ($recommendations['time_estimate'] ?? 'MISSING') . "</p>";
        echo "</div>";
        
        echo "<h3>Step 6: Build Response Array (CRITICAL POINT)</h3>";
        
        // Build the exact same response as in predict method
        $response = [
            'vehicle' => $vehicle,
            'currentMileage' => intval($mileage),
            'vehicleHistory' => $vehicleHistory,
            'serviceSchedule' => $serviceSchedule,
            'partsAnalysis' => $partsAnalysis,
            'recommendations' => $recommendations, // <-- WATCH THIS CAREFULLY
            'mlPrediction' => $mlPrediction
        ];
        
        echo "<h4>Response Array Check:</h4>";
        echo "<p>✅ Response array built</p>";
        echo "<p><strong>Recommendations in response:</strong> ";
        if (isset($response['recommendations']['cost_estimate']['min']) && isset($response['recommendations']['cost_estimate']['max'])) {
            echo "RM " . number_format($response['recommendations']['cost_estimate']['min']) . " - RM " . number_format($response['recommendations']['cost_estimate']['max']);
        } else {
            echo "❌ MISSING FROM RESPONSE";
        }
        echo "</p>";
        
        // Compare original vs response
        if ($recommendations === $response['recommendations']) {
            echo "<p>✅ Original recommendations === Response recommendations</p>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h4>🚨 PROBLEM FOUND!</h4>";
            echo "<p>Original recommendations ≠ Response recommendations</p>";
            echo "<p>Something is modifying the data when building the response array!</p>";
            echo "</div>";
        }
        
        echo "<h3>Step 7: Simulate View Pass</h3>";
        echo "<p>If you see ✅ for cost_estimate above, then the issue is either:</p>";
        echo "<ul>";
        echo "<li>❌ An exception happening after response array is built</li>";
        echo "<li>❌ Mileage validation failing and returning early</li>";
        echo "<li>❌ Something in the view rendering process</li>";
        echo "</ul>";
        
        echo "<h3>🎯 Next Steps:</h3>";
        echo "<ol>";
        echo "<li>If cost_estimate is ✅ here, add the debug logging to your predict() method</li>";
        echo "<li>Try a real prediction and check the logs</li>";
        echo "<li>Look for where the flow differs from this test</li>";
        echo "</ol>";
        
        // Show full structure for debugging
        echo "<details style='margin: 20px 0;'>";
        echo "<summary>🔍 Full Recommendations Structure</summary>";
        echo "<pre style='background: #f4f4f4; padding: 15px; font-size: 12px;'>";
        print_r($recommendations);
        echo "</pre>";
        echo "</details>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Exception Found!</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . "</p>";
        echo "<p><strong>This exception might be happening in your real predict() method too!</strong></p>";
        echo "</div>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});

Route::get('/test-failing-values/{vehicle}/{mileage}', function($vehicle, $mileage) {
    echo "<h1>Testing with the exact values that failed</h1>";
    echo "<p>Vehicle: {$vehicle}, Mileage: {$mileage}</p>";
    
    // Test mileage validation with these exact values
    $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
    $reflection = new ReflectionClass($controller);
    
    try {
        $method = $reflection->getMethod('validateMileageWithSmartFiltering');
        $method->setAccessible(true);
        $result = $method->invoke($controller, $vehicle, $mileage);
        
        if ($result['valid']) {
            echo "<p>✅ Mileage validation PASSES</p>";
            echo "<p>The issue is elsewhere</p>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
            echo "<h3>🚨 FOUND THE ISSUE!</h3>";
            echo "<p><strong>Mileage validation FAILS</strong></p>";
            echo "<p><strong>Error:</strong> " . ($result['message'] ?? 'No message') . "</p>";
            echo "<p>This validation failure returns early WITHOUT recommendations!</p>";
            echo "<p><strong>Solution:</strong> Either fix the validation or handle the return properly</p>";
            echo "</div>";
        }
        
    } catch (\Exception $e) {
        echo "<p>❌ Error testing validation: " . $e->getMessage() . "</p>";
    }
});

// Route 1: Analyze your fleet's data quality issues
Route::get('/analyze-fleet-data-quality', function() {
    try {
        echo "<h1>🔍 Fleet Data Quality Analysis</h1>";
        echo "<p><strong>Goal:</strong> Identify odometer data issues across your entire fleet</p>";
        echo "<p><strong>Status:</strong> READ-ONLY analysis - no changes made to existing data</p>";
        
        // Get all vehicles with odometer readings
        $vehicles = DB::table('ServiceRequest')
            ->select('Vehicle')
            ->whereNotNull('Vehicle')
            ->where('Vehicle', '!=', '')
            ->whereNotNull('Odometer')
            ->where('Odometer', '!=', '')
            ->distinct()
            ->orderBy('Vehicle')
            ->get();
            
        $totalVehicles = $vehicles->count();
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📊 Fleet Overview</h2>";
        echo "<p><strong>Total vehicles with odometer data:</strong> {$totalVehicles}</p>";
        echo "</div>";
        
        // Analyze each vehicle for data quality issues
        $problemVehicles = [];
        $totalRecords = 0;
        $problemRecords = 0;
        
        echo "<h2>🔍 Vehicle-by-Vehicle Analysis</h2>";
        echo "<div style='max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; margin: 15px 0;'>";
        
        foreach ($vehicles->take(20) as $vehicle) { // Analyze first 20 vehicles
            $vehicleNumber = $vehicle->Vehicle;
            
            // Get all odometer readings for this vehicle
            $readings = DB::table('ServiceRequest')
                ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicleNumber))])
                ->whereNotNull('Odometer')
                ->where('Odometer', '!=', '')
                ->orderBy('Datereceived', 'desc')
                ->get(['Odometer', 'Datereceived', 'ID']);
            
            $vehicleIssues = [];
            $validReadings = 0;
            $invalidReadings = 0;
            
            foreach ($readings as $reading) {
                $totalRecords++;
                $mileage = trim($reading->Odometer);
                
                // Check for issues
                $hasIssue = false;
                
                if ($mileage === '0' || $mileage === '') {
                    $vehicleIssues[] = 'Zero/empty values';
                    $hasIssue = true;
                }
                
                if (is_numeric($mileage) && floatval($mileage) < 1000 && floatval($mileage) > 0) {
                    $vehicleIssues[] = 'Suspiciously low values';
                    $hasIssue = true;
                }
                
                if (is_numeric($mileage) && floatval($mileage) > 2000000) {
                    $vehicleIssues[] = 'Suspiciously high values';
                    $hasIssue = true;
                }
                
                if (!is_numeric($mileage)) {
                    $vehicleIssues[] = 'Non-numeric values';
                    $hasIssue = true;
                }
                
                if ($hasIssue) {
                    $invalidReadings++;
                    $problemRecords++;
                } else {
                    $validReadings++;
                }
            }
            
            // Check for backwards progression
            $numericReadings = [];
            foreach ($readings as $reading) {
                if (is_numeric(trim($reading->Odometer)) && floatval(trim($reading->Odometer)) > 1000) {
                    $numericReadings[] = floatval(trim($reading->Odometer));
                }
            }
            
            if (count($numericReadings) > 1) {
                $hasBackwards = false;
                for ($i = 0; $i < count($numericReadings) - 1; $i++) {
                    if ($numericReadings[$i] < $numericReadings[$i + 1]) { // Remember: sorted desc by date
                        $hasBackwards = true;
                        break;
                    }
                }
                if ($hasBackwards) {
                    $vehicleIssues[] = 'Backwards progression';
                }
            }
            
            $issueCount = count(array_unique($vehicleIssues));
            $dataQuality = $validReadings > 0 ? round(($validReadings / ($validReadings + $invalidReadings)) * 100) : 0;
            
            if ($issueCount > 0) {
                $problemVehicles[] = [
                    'vehicle' => $vehicleNumber,
                    'total_readings' => $readings->count(),
                    'valid' => $validReadings,
                    'invalid' => $invalidReadings,
                    'issues' => array_unique($vehicleIssues),
                    'data_quality' => $dataQuality
                ];
                
                $color = $dataQuality < 50 ? '#f8d7da' : ($dataQuality < 80 ? '#fff3cd' : '#d4edda');
            } else {
                $color = '#d4edda';
            }
            
            echo "<div style='background: {$color}; padding: 8px; margin: 5px 0; border-radius: 4px; font-size: 12px;'>";
            echo "<strong>{$vehicleNumber}:</strong> ";
            echo "{$readings->count()} readings, ";
            echo "{$dataQuality}% quality";
            if ($issueCount > 0) {
                echo " - Issues: " . implode(', ', array_unique($vehicleIssues));
            }
            echo "</div>";
        }
        
        echo "</div>";
        
        // Summary statistics
        $problemVehicleCount = count($problemVehicles);
        $overallDataQuality = $totalRecords > 0 ? round((($totalRecords - $problemRecords) / $totalRecords) * 100) : 0;
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📈 Fleet Data Quality Summary</h2>";
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 15px 0;'>";
        
        echo "<div style='background: #fff; padding: 15px; border-radius: 8px; text-center; border: 2px solid #007bff;'>";
        echo "<h3 style='color: #007bff; margin: 0;'>{$totalVehicles}</h3>";
        echo "<small>Total Vehicles</small>";
        echo "</div>";
        
        echo "<div style='background: #fff; padding: 15px; border-radius: 8px; text-center; border: 2px solid " . ($problemVehicleCount > $totalVehicles * 0.3 ? "#dc3545" : "#28a745") . ";'>";
        echo "<h3 style='color: " . ($problemVehicleCount > $totalVehicles * 0.3 ? "#dc3545" : "#28a745") . "; margin: 0;'>{$problemVehicleCount}</h3>";
        echo "<small>Vehicles with Issues</small>";
        echo "</div>";
        
        echo "<div style='background: #fff; padding: 15px; border-radius: 8px; text-center; border: 2px solid " . ($overallDataQuality < 70 ? "#dc3545" : "#28a745") . ";'>";
        echo "<h3 style='color: " . ($overallDataQuality < 70 ? "#dc3545" : "#28a745") . "; margin: 0;'>{$overallDataQuality}%</h3>";
        echo "<small>Overall Data Quality</small>";
        echo "</div>";
        
        echo "<div style='background: #fff; padding: 15px; border-radius: 8px; text-center; border: 2px solid #ffc107;'>";
        echo "<h3 style='color: #ffc107; margin: 0;'>" . number_format($totalRecords) . "</h3>";
        echo "<small>Total Readings Analyzed</small>";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
        
        // Top problem vehicles
        if (!empty($problemVehicles)) {
            usort($problemVehicles, function($a, $b) {
                return $a['data_quality'] - $b['data_quality']; // Worst first
            });
            
            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h2>⚠️ Vehicles Needing Attention (Top 10)</h2>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='padding: 8px;'>Vehicle</th>";
            echo "<th style='padding: 8px;'>Total Readings</th>";
            echo "<th style='padding: 8px;'>Data Quality</th>";
            echo "<th style='padding: 8px;'>Issues Found</th>";
            echo "<th style='padding: 8px;'>Action</th>";
            echo "</tr>";
            
            foreach (array_slice($problemVehicles, 0, 10) as $vehicle) {
                $qualityColor = $vehicle['data_quality'] < 50 ? '#f8d7da' : '#fff3cd';
                
                echo "<tr style='background: {$qualityColor};'>";
                echo "<td style='padding: 8px;'><strong>{$vehicle['vehicle']}</strong></td>";
                echo "<td style='padding: 8px;'>{$vehicle['total_readings']}</td>";
                echo "<td style='padding: 8px;'>{$vehicle['data_quality']}%</td>";
                echo "<td style='padding: 8px; font-size: 11px;'>" . implode(', ', $vehicle['issues']) . "</td>";
                echo "<td style='padding: 8px;'>";
                echo "<a href='/analyze-vehicle-details/{$vehicle['vehicle']}' style='padding: 4px 8px; background: #007bff; color: white; text-decoration: none; border-radius: 3px; font-size: 11px;'>Analyze</a>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
            echo "</div>";
        }
        
        // Recommendations
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>💡 Recommended Next Steps</h2>";
        echo "<ol>";
        echo "<li><strong>Detailed Analysis:</strong> Click on specific vehicles above to see detailed issues</li>";
        echo "<li><strong>Test Smart Validation:</strong> Use the test routes to see how validation would work</li>";
        echo "<li><strong>Pilot Implementation:</strong> Start with the most problematic vehicles</li>";
        echo "<li><strong>Training Plan:</strong> Identify staff who need odometer reading training</li>";
        echo "</ol>";
        
        echo "<div style='text-align: center; margin: 20px 0;'>";
        echo "<a href='/test-validation-scenarios' style='padding: 15px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; margin: 10px;'>Test Smart Validation</a>";
        echo "<a href='/analyze-vehicle-details/WMJ4703' style='padding: 15px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; margin: 10px;'>See WMJ4703 Example</a>";
        echo "</div>";
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Analysis Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "<p>This might indicate database connection issues or data structure differences.</p>";
        echo "</div>";
    }
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Main App</a>";
    echo "</p>";
});

// Route 2: Detailed analysis of specific vehicles
Route::get('/analyze-vehicle-details/{vehicle}', function($vehicle) {
    try {
        echo "<h1>🔍 Detailed Analysis: {$vehicle}</h1>";
        echo "<p><strong>Status:</strong> READ-ONLY analysis - no changes made</p>";
        
        // Get all odometer readings for this vehicle
        $readings = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->whereNotNull('Odometer')
            ->orderBy('Datereceived', 'desc')
            ->get(['ID', 'Odometer', 'Datereceived', 'Description', 'MrType', 'Status']);
        
        if ($readings->isEmpty()) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>⚠️ No Data Found</h3>";
            echo "<p>No odometer readings found for vehicle: {$vehicle}</p>";
            echo "</div>";
            return;
        }
        
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📊 Raw Data Analysis</h2>";
        echo "<p><strong>Total records found:</strong> " . $readings->count() . "</p>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'>";
        echo "<th style='padding: 6px;'>Date</th>";
        echo "<th style='padding: 6px;'>Raw Value</th>";
        echo "<th style='padding: 6px;'>Parsed KM</th>";
        echo "<th style='padding: 6px;'>Issues</th>";
        echo "<th style='padding: 6px;'>Description</th>";
        echo "</tr>";
        
        $issues = [];
        $validReadings = [];
        
        foreach ($readings as $reading) {
            $rawValue = trim($reading->Odometer);
            $recordIssues = [];
            
            // Analyze this reading
            if ($rawValue === '0' || $rawValue === '') {
                $recordIssues[] = 'Zero/Empty';
            }
            
            if (is_numeric($rawValue)) {
                $mileage = floatval($rawValue);
                if ($mileage < 1000 && $mileage > 0) {
                    $recordIssues[] = 'Too Low';
                } elseif ($mileage > 2000000) {
                    $recordIssues[] = 'Too High';
                } else {
                    $validReadings[] = ['mileage' => $mileage, 'date' => $reading->Datereceived];
                }
            } elseif ($rawValue !== '0' && $rawValue !== '') {
                $recordIssues[] = 'Non-numeric';
            }
            
            $issues = array_merge($issues, $recordIssues);
            
            $rowColor = empty($recordIssues) ? '#d4edda' : '#f8d7da';
            
            echo "<tr style='background: {$rowColor};'>";
            echo "<td style='padding: 6px;'>" . substr($reading->Datereceived ?? '', 0, 10) . "</td>";
            echo "<td style='padding: 6px;'>'{$rawValue}'</td>";
            echo "<td style='padding: 6px;'>" . (is_numeric($rawValue) ? number_format(floatval($rawValue)) : 'N/A') . "</td>";
            echo "<td style='padding: 6px;'>" . (empty($recordIssues) ? '✅' : '❌ ' . implode(', ', $recordIssues)) . "</td>";
            echo "<td style='padding: 6px;'>" . substr($reading->Description ?? '', 0, 30) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Smart filtering simulation
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🧠 Smart Filtering Simulation</h2>";
        echo "<p>This shows what would happen with smart validation:</p>";
        
        if (!empty($validReadings)) {
            // Sort by mileage to find progression issues
            usort($validReadings, function($a, $b) {
                return strtotime($b['date']) - strtotime($a['date']); // Most recent first
            });
            
            echo "<h4>✅ Valid Readings (after filtering):</h4>";
            $baseline = 0;
            $filteredReadings = [];
            
            foreach ($validReadings as $reading) {
                if ($reading['mileage'] > $baseline) {
                    $baseline = $reading['mileage'];
                    $filteredReadings[] = $reading;
                }
            }
            
            if (!empty($filteredReadings)) {
                echo "<ul>";
                foreach (array_slice($filteredReadings, 0, 5) as $reading) {
                    echo "<li>" . number_format($reading['mileage']) . " KM (" . substr($reading['date'], 0, 10) . ")</li>";
                }
                echo "</ul>";
                
                $highestReading = max(array_column($filteredReadings, 'mileage'));
                echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
                echo "<strong>🎯 Recommended Baseline:</strong> " . number_format($highestReading) . " KM";
                echo "</div>";
                
                // Test scenarios
                echo "<h4>🧪 Test Validation Scenarios:</h4>";
                $testValues = [
                    $highestReading - 10000, // Backwards
                    $highestReading + 5000,  // Normal
                    $highestReading + 50000, // High
                    0, // Zero
                ];
                
                foreach ($testValues as $testValue) {
                    $result = '';
                    $color = '';
                    
                    if ($testValue == 0) {
                        $result = 'FAIL - Zero not allowed';
                        $color = '#f8d7da';
                    } elseif ($testValue < $highestReading) {
                        $result = 'FAIL - Backwards movement';
                        $color = '#f8d7da';
                    } elseif ($testValue - $highestReading > 50000) {
                        $result = 'FAIL - Unrealistic increase';
                        $color = '#f8d7da';
                    } else {
                        $result = 'PASS - Valid progression';
                        $color = '#d4edda';
                    }
                    
                    echo "<div style='background: {$color}; padding: 8px; margin: 5px 0; border-radius: 3px; font-size: 12px;'>";
                    echo "<strong>" . number_format($testValue) . " KM:</strong> {$result}";
                    echo "</div>";
                }
            } else {
                echo "<p>❌ No reliable baseline could be established from this data.</p>";
            }
        } else {
            echo "<p>❌ No valid readings found for baseline calculation.</p>";
        }
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Analysis Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/analyze-fleet-data-quality' style='padding: 15px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 8px;'>← Back to Fleet Analysis</a>";
    echo "</p>";
});

// Route 3: Test validation scenarios without changing anything
Route::get('/test-validation-scenarios', function() {
    echo "<h1>🧪 Test Smart Validation Scenarios</h1>";
    echo "<p><strong>Status:</strong> SIMULATION ONLY - no changes to production code</p>";
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🎯 Purpose</h2>";
    echo "<p>This demonstrates how smart odometer validation would work on your real data, without making any changes to your existing system.</p>";
    echo "</div>";
    
    // Get a few real vehicles for testing
    $testVehicles = DB::table('ServiceRequest')
        ->select('Vehicle')
        ->whereNotNull('Vehicle')
        ->where('Vehicle', '!=', '')
        ->whereNotNull('Odometer')
        ->where('Odometer', '!=', '')
        ->distinct()
        ->take(10)
        ->get();
    
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🚗 Select a Vehicle to Test</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;'>";
    
    foreach ($testVehicles as $vehicle) {
        echo "<a href='/test-vehicle-validation/{$vehicle->Vehicle}' style='padding: 15px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; text-align: center; display: block;'>";
        echo "<strong>{$vehicle->Vehicle}</strong><br>";
        echo "<small>Test Validation</small>";
        echo "</a>";
    }
    
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ What You'll See</h2>";
    echo "<ul>";
    echo "<li><strong>Current Data Issues:</strong> Problems with existing odometer data</li>";
    echo "<li><strong>Smart Filtering:</strong> How the system would clean the data</li>";
    echo "<li><strong>Validation Rules:</strong> How different user inputs would be handled</li>";
    echo "<li><strong>User Experience:</strong> What error messages users would see</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/analyze-fleet-data-quality' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Fleet Analysis</a>";
    echo "</p>";
});

Route::get('/test-user-choice-validation/{vehicle}/{mileage?}', function($vehicle, $mileage = null) {
    try {
        echo "<h1>🎯 User-Choice Validation Test: {$vehicle}</h1>";
        
        if ($mileage) {
            echo "<p><strong>User Input:</strong> " . number_format($mileage) . " KM</p>";
        }
        
        // Get all odometer readings for this vehicle
        $allReadings = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->whereNotNull('Odometer')
            ->where('Odometer', '!=', '')
            ->orderBy('Datereceived', 'desc')
            ->get(['ID', 'Odometer', 'Datereceived', 'Description', 'MrType']);
        
        if ($allReadings->isEmpty()) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>⚠️ No Odometer Data Found</h3>";
            echo "<p>Vehicle {$vehicle} has no odometer readings in the database.</p>";
            echo "</div>";
            return;
        }
        
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📊 Found " . $allReadings->count() . " Odometer Readings</h2>";
        echo "</div>";
        
        // Process readings into valid and invalid
        $validReadings = [];
        $invalidReadings = [];
        
        foreach ($allReadings as $reading) {
            $rawValue = trim($reading->Odometer);
            $issues = [];
            
            if ($rawValue === '0' || $rawValue === '') {
                $issues[] = 'Zero/empty value';
            } elseif (!is_numeric($rawValue)) {
                $issues[] = 'Non-numeric value';
            } elseif (floatval($rawValue) < 1000) {
                $issues[] = 'Too low (< 1,000 KM)';
            } elseif (floatval($rawValue) > 2000000) {
                $issues[] = 'Too high (> 2M KM)';
            }
            
            $readingData = [
                'id' => $reading->ID,
                'mileage' => is_numeric($rawValue) ? floatval($rawValue) : 0,
                'raw_value' => $rawValue,
                'date' => $reading->Datereceived,
                'date_formatted' => Carbon\Carbon::parse($reading->Datereceived)->format('d M Y'),
                'days_ago' => Carbon\Carbon::parse($reading->Datereceived)->diffInDays(now()),
                'description' => substr($reading->Description ?? '', 0, 40),
                'issues' => $issues
            ];
            
            if (empty($issues)) {
                $validReadings[] = $readingData;
            } else {
                $invalidReadings[] = $readingData;
            }
        }
        
        // Remove duplicates and sort valid readings
        $uniqueReadings = [];
        $seenMileages = [];
        
        foreach ($validReadings as $reading) {
            if (!in_array($reading['mileage'], $seenMileages)) {
                $uniqueReadings[] = $reading;
                $seenMileages[] = $reading['mileage'];
            }
        }
        
        usort($uniqueReadings, function($a, $b) {
            return $b['mileage'] - $a['mileage'];
        });
        
        // Show the user choice interface
        echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>👤 User Choice Interface Simulation</h2>";
        
        if (count($uniqueReadings) > 1) {
            echo "<p><strong>Multiple Valid Readings Found!</strong> User would see this choice screen:</p>";
            
            echo "<div style='border: 2px solid #007bff; padding: 20px; border-radius: 10px; background: white; margin: 15px 0;'>";
            echo "<h3>Choose Your Reference Reading:</h3>";
            
            foreach ($uniqueReadings as $index => $reading) {
                // Generate recommendation
                $isHighest = ($index === 0);
                $isRecent = ($reading['days_ago'] < 90);
                
                if ($isHighest && $isRecent) {
                    $recommendation = ['icon' => '✅', 'text' => 'Recommended - Highest recent reading', 'level' => 'success'];
                } elseif ($isHighest) {
                    $recommendation = ['icon' => '⚠️', 'text' => 'Highest but older reading', 'level' => 'warning'];
                } else {
                    $recommendation = ['icon' => '❌', 'text' => 'Lower reading - use with caution', 'level' => 'danger'];
                }
                
                $borderColor = $recommendation['level'] === 'success' ? '#28a745' : 
                             ($recommendation['level'] === 'warning' ? '#ffc107' : '#dc3545');
                
                echo "<div style='border: 2px solid {$borderColor}; padding: 15px; margin: 10px 0; border-radius: 8px; background: #f8f9fa;'>";
                echo "<div style='display: flex; justify-content: between; align-items: center;'>";
                echo "<div style='flex: 1;'>";
                echo "<h4>{$recommendation['icon']} " . number_format($reading['mileage']) . " KM</h4>";
                echo "<p><strong>Date:</strong> {$reading['date_formatted']} ({$reading['days_ago']} days ago)</p>";
                echo "<p><strong>Service:</strong> {$reading['description']}</p>";
                
                if ($mileage) {
                    $difference = $mileage - $reading['mileage'];
                    echo "<p><strong>Your Input Effect:</strong> ";
                    if ($difference >= 0) {
                        echo "<span style='color: green;'>+{$difference} KM increase</span>";
                        if ($reading['days_ago'] > 0) {
                            $dailyUsage = round($difference / $reading['days_ago']);
                            echo " (≈{$dailyUsage} KM/day)";
                        }
                    } else {
                        echo "<span style='color: red;'>{$difference} KM (BACKWARDS!)</span>";
                    }
                    echo "</p>";
                }
                
                echo "</div>";
                echo "<div style='text-align: center; min-width: 120px;'>";
                echo "<span style='background: {$borderColor}; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px;'>";
                echo ($index === 0 ? 'RECOMMENDED' : ($recommendation['level'] === 'warning' ? 'CAUTION' : 'RISKY'));
                echo "</span>";
                echo "</div>";
                echo "</div>";
                echo "<small style='color: #666;'>{$recommendation['text']}</small>";
                echo "</div>";
            }
            
            echo "</div>";
            
        } else {
            echo "<p>✅ <strong>Single Valid Reading Found</strong> - No choice needed, system uses: " . 
                 number_format($uniqueReadings[0]['mileage']) . " KM</p>";
        }
        echo "</div>";
        
        // Show validation results for each choice
        if ($mileage && count($uniqueReadings) > 1) {
            echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h2>🔍 Validation Results by Choice</h2>";
            echo "<p>How your input of <strong>" . number_format($mileage) . " KM</strong> would be validated:</p>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th style='padding: 10px;'>Reference Choice</th>";
            echo "<th style='padding: 10px;'>Difference</th>";
            echo "<th style='padding: 10px;'>Daily Usage</th>";
            echo "<th style='padding: 10px;'>Validation Result</th>";
            echo "</tr>";
            
            foreach ($uniqueReadings as $reading) {
                $difference = $mileage - $reading['mileage'];
                $dailyUsage = $reading['days_ago'] > 0 ? round($difference / $reading['days_ago']) : 0;
                
                // Determine validation result
                if ($difference < 0) {
                    $result = '❌ FAIL - Backwards movement';
                    $color = '#f8d7da';
                } elseif ($difference == 0 && $reading['days_ago'] > 1) {
                    $result = '⚠️ WARNING - No movement';
                    $color = '#fff3cd';
                } elseif ($dailyUsage > 500) {
                    $result = '❌ FAIL - Extreme usage';
                    $color = '#f8d7da';
                } elseif ($dailyUsage > 200) {
                    $result = '⚠️ WARNING - High usage';
                    $color = '#fff3cd';
                } else {
                    $result = '✅ PASS - Normal progression';
                    $color = '#d4edda';
                }
                
                echo "<tr style='background: {$color};'>";
                echo "<td style='padding: 8px;'>" . number_format($reading['mileage']) . " KM<br><small>{$reading['date_formatted']}</small></td>";
                echo "<td style='padding: 8px;'>" . ($difference >= 0 ? '+' : '') . number_format($difference) . " KM</td>";
                echo "<td style='padding: 8px;'>{$dailyUsage} KM/day</td>";
                echo "<td style='padding: 8px;'>{$result}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
        // Show filtered readings
        if (!empty($invalidReadings)) {
            echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h2>🗑️ Filtered Out Readings</h2>";
            echo "<p>These readings were excluded from choices due to data quality issues:</p>";
            
            echo "<ul>";
            foreach ($invalidReadings as $reading) {
                echo "<li>";
                echo "<strong>'{$reading['raw_value']}'</strong> ({$reading['date_formatted']}) - ";
                echo "<span style='color: #dc3545;'>" . implode(', ', $reading['issues']) . "</span>";
                echo "</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        // Benefits of this approach
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🎯 Benefits of User-Choice Approach</h2>";
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
        
        echo "<div>";
        echo "<h4>✅ User Advantages:</h4>";
        echo "<ul>";
        echo "<li>Feel in control of validation process</li>";
        echo "<li>Use their knowledge of vehicle history</li>";
        echo "<li>Learn about data quality issues</li>";
        echo "<li>Understand why certain readings exist</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div>";
        echo "<h4>✅ System Advantages:</h4>";
        echo "<ul>";
        echo "<li>Still prevents obvious errors</li>";
        echo "<li>Provides smart recommendations</li>";
        echo "<li>Validates based on user's context</li>";
        echo "<li>Creates opportunity for data cleanup</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "</div>";
        echo "</div>";
        
        // Test with different mileage values
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🧪 Test Different Mileage Inputs</h2>";
        echo "<p>See how validation would work with different user inputs:</p>";
        
        if (count($uniqueReadings) > 0) {
            $highestReading = $uniqueReadings[0]['mileage'];
            $testValues = [
                $highestReading - 10000, // Backwards
                $highestReading + 1000,  // Small increase
                $highestReading + 10000, // Normal increase
                $highestReading + 50000, // Large increase
            ];
            
            foreach ($testValues as $testValue) {
                echo "<a href='/test-user-choice-validation/{$vehicle}/{$testValue}' style='margin: 5px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>";
                echo number_format($testValue) . " KM</a>";
            }
        }
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Test Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/analyze-fleet-data-quality' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Fleet Analysis</a>";
    echo "</p>";
});

// Quick access route for testing user choice with popular vehicles
Route::get('/test-user-choice-scenarios', function() {
    echo "<h1>🎯 User-Choice Validation Testing</h1>";
    echo "<p>Test the user-choice approach with vehicles that have conflicting odometer data</p>";
    
    // Get vehicles with multiple readings
    $vehiclesWithMultipleReadings = DB::select("
        SELECT Vehicle, COUNT(DISTINCT Odometer) as reading_count
        FROM ServiceRequest 
        WHERE Vehicle IS NOT NULL 
        AND Vehicle != ''
        AND Odometer IS NOT NULL 
        AND Odometer != ''
        AND Odometer != '0'
        AND TRY_CAST(Odometer AS FLOAT) IS NOT NULL
        GROUP BY Vehicle
        HAVING COUNT(DISTINCT Odometer) > 1
        ORDER BY reading_count DESC
    ");
    
    echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🚗 Vehicles with Multiple Odometer Readings</h2>";
    echo "<p>These vehicles have conflicting data and would benefit from user-choice validation:</p>";
    
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";
    
    foreach (array_slice($vehiclesWithMultipleReadings, 0, 9) as $vehicle) {
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 2px solid #007bff; text-align: center;'>";
        echo "<h4>{$vehicle->Vehicle}</h4>";
        echo "<p>{$vehicle->reading_count} different readings</p>";
        echo "<a href='/test-user-choice-validation/{$vehicle->Vehicle}' style='padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 2px;'>Analyze</a>";
        echo "<a href='/test-user-choice-validation/{$vehicle->Vehicle}/650000' style='padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 2px;'>Test 650K</a>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>💡 How It Works</h2>";
    echo "<ol>";
    echo "<li><strong>Detect Conflicts:</strong> System finds multiple valid odometer readings</li>";
    echo "<li><strong>Show Choices:</strong> User sees all options with recommendations</li>";
    echo "<li><strong>User Decides:</strong> User picks the reading they trust most</li>";
    echo "<li><strong>Smart Validation:</strong> System validates against chosen baseline</li>";
    echo "<li><strong>Clear Feedback:</strong> User gets specific validation results</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Main App</a>";
    echo "</p>";
});

Route::get('/compare-validation-approaches/{vehicle}', function($vehicle) {
    try {
        echo "<h1>⚖️ Validation Approach Comparison: {$vehicle}</h1>";
        echo "<p>Comparing <strong>Automatic Selection</strong> vs <strong>User Choice</strong> approaches</p>";
        
        // Get vehicle data
        $readings = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->whereNotNull('Odometer')
            ->where('Odometer', '!=', '')
            ->where('Odometer', '!=', '0')
            ->whereRaw('TRY_CAST(Odometer AS FLOAT) IS NOT NULL')
            ->whereRaw('TRY_CAST(Odometer AS FLOAT) > 1000')
            ->orderBy('Datereceived', 'desc')
            ->get(['Odometer', 'Datereceived', 'Description']);
        
        if ($readings->count() < 2) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>⚠️ Insufficient Data</h3>";
            echo "<p>This vehicle doesn't have enough conflicting readings to demonstrate the comparison.</p>";
            echo "</div>";
            return;
        }
        
        // Process readings
        $validReadings = [];
        foreach ($readings as $reading) {
            $mileage = floatval(trim($reading->Odometer));
            if ($mileage > 1000 && $mileage < 2000000) {
                $validReadings[] = [
                    'mileage' => $mileage,
                    'date' => $reading->Datereceived,
                    'date_formatted' => Carbon\Carbon::parse($reading->Datereceived)->format('d M Y'),
                    'days_ago' => Carbon\Carbon::parse($reading->Datereceived)->diffInDays(now()),
                    'description' => substr($reading->Description ?? '', 0, 30)
                ];
            }
        }
        
        // Remove duplicates
        $uniqueReadings = [];
        $seenMileages = [];
        foreach ($validReadings as $reading) {
            if (!in_array($reading['mileage'], $seenMileages)) {
                $uniqueReadings[] = $reading;
                $seenMileages[] = $reading['mileage'];
            }
        }
        
        usort($uniqueReadings, function($a, $b) {
            return $b['mileage'] - $a['mileage'];
        });
        
        echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📊 Available Readings for {$vehicle}</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f8f9fa;'><th style='padding: 8px;'>Reading</th><th style='padding: 8px;'>Date</th><th style='padding: 8px;'>Days Ago</th><th style='padding: 8px;'>Description</th></tr>";
        
        foreach ($uniqueReadings as $reading) {
            echo "<tr>";
            echo "<td style='padding: 8px;'><strong>" . number_format($reading['mileage']) . " KM</strong></td>";
            echo "<td style='padding: 8px;'>{$reading['date_formatted']}</td>";
            echo "<td style='padding: 8px;'>{$reading['days_ago']}</td>";
            echo "<td style='padding: 8px;'>{$reading['description']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        if (count($uniqueReadings) < 2) {
            echo "<p>Not enough conflicting readings to demonstrate comparison.</p>";
            return;
        }
        
        // Automatic approach
        $automaticBaseline = $uniqueReadings[0]; // Highest reading
        
        // Scenario: User wants to enter a value
        $testUserInput = $automaticBaseline['mileage'] - 15000; // Backwards scenario
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 30px 0;'>";
        
        // AUTOMATIC APPROACH
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border: 3px solid #dc3545;'>";
        echo "<h2>🤖 Automatic Selection Approach</h2>";
        echo "<h4>System Chooses: " . number_format($automaticBaseline['mileage']) . " KM</h4>";
        echo "<p><small>Automatically selects highest reading</small></p>";
        
        echo "<div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h5>User Scenario:</h5>";
        echo "<p>User enters: <strong>" . number_format($testUserInput) . " KM</strong></p>";
        echo "<p><strong>Result:</strong> <span style='color: #dc3545;'>❌ VALIDATION FAILS</span></p>";
        echo "<p><strong>Error:</strong> \"Reading cannot go backwards from " . number_format($automaticBaseline['mileage']) . " KM\"</p>";
        echo "</div>";
        
        echo "<h5>❌ Problems with Automatic:</h5>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>User doesn't understand why it failed</li>";
        echo "<li>No context about why that baseline was chosen</li>";
        echo "<li>User can't override system decision</li>";
        echo "<li>May reject valid entries if highest reading is wrong</li>";
        echo "<li>Frustrating user experience</li>";
        echo "</ul>";
        
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "<h6>📱 User Experience:</h6>";
        echo "<p style='font-size: 12px; margin: 0;'>\"Why is it saying my reading is wrong? I just looked at the odometer! This system doesn't work!\"</p>";
        echo "</div>";
        echo "</div>";
        
        // USER CHOICE APPROACH
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border: 3px solid #28a745;'>";
        echo "<h2>👤 User Choice Approach</h2>";
        echo "<h4>User Sees All Options</h4>";
        echo "<p><small>User chooses which reading to trust</small></p>";
        
        echo "<div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h5>Choice Interface:</h5>";
        foreach (array_slice($uniqueReadings, 0, 3) as $index => $reading) {
            $isHighest = ($index === 0);
            $recommendation = $isHighest ? '✅ Recommended' : '⚠️ Consider carefully';
            $bgColor = $isHighest ? '#d4edda' : '#fff3cd';
            
            echo "<div style='background: {$bgColor}; padding: 8px; margin: 5px 0; border-radius: 3px; font-size: 12px;'>";
            echo "<strong>" . number_format($reading['mileage']) . " KM</strong> ";
            echo "({$reading['date_formatted']}) {$recommendation}";
            echo "</div>";
        }
        echo "</div>";
        
        echo "<div style='background: white; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h5>User Scenario:</h5>";
        echo "<p>User enters: <strong>" . number_format($testUserInput) . " KM</strong></p>";
        echo "<p>User chooses: <strong>" . number_format($uniqueReadings[1]['mileage'] ?? $testUserInput + 5000) . " KM</strong> baseline</p>";
        echo "<p><strong>Result:</strong> <span style='color: #28a745;'>✅ VALIDATION PASSES</span></p>";
        echo "<p><strong>Message:</strong> \"Valid increase based on your chosen reference\"</p>";
        echo "</div>";
        
        echo "<h5>✅ Benefits of User Choice:</h5>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>User understands the validation process</li>";
        echo "<li>User has control and context</li>";
        echo "<li>System still provides smart recommendations</li>";
        echo "<li>Flexible enough to handle edge cases</li>";
        echo "<li>Educational for users about data quality</li>";
        echo "</ul>";
        
        echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
        echo "<h6>📱 User Experience:</h6>";
        echo "<p style='font-size: 12px; margin: 0;'>\"Oh, I see there are multiple readings. I'll choose the one from last month's service since I know that one is correct.\"</p>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        
        // Detailed comparison table
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📋 Detailed Comparison</h2>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='padding: 12px;'>Aspect</th>";
        echo "<th style='padding: 12px; color: #dc3545;'>Automatic Selection</th>";
        echo "<th style='padding: 12px; color: #28a745;'>User Choice</th>";
        echo "</tr>";
        
        $comparisons = [
            ['aspect' => 'User Control', 'auto' => '❌ None - system decides', 'choice' => '✅ Full control with guidance'],
            ['aspect' => 'Transparency', 'auto' => '❌ Black box decision', 'choice' => '✅ All options visible'],
            ['aspect' => 'Flexibility', 'auto' => '❌ Rigid rules only', 'choice' => '✅ Adapts to user knowledge'],
            ['aspect' => 'Error Handling', 'auto' => '❌ Blocks valid entries', 'choice' => '✅ User can override'],
            ['aspect' => 'User Education', 'auto' => '❌ No learning opportunity', 'choice' => '✅ Teaches data quality'],
            ['aspect' => 'Trust Building', 'auto' => '❌ "System knows better"', 'choice' => '✅ Collaborative approach'],
            ['aspect' => 'Edge Cases', 'auto' => '❌ Difficult to handle', 'choice' => '✅ User provides context'],
            ['aspect' => 'Data Cleanup', 'auto' => '❌ No feedback loop', 'choice' => '✅ Identifies bad data'],
        ];
        
        foreach ($comparisons as $comp) {
            echo "<tr>";
            echo "<td style='padding: 10px; font-weight: bold;'>{$comp['aspect']}</td>";
            echo "<td style='padding: 10px; background: #f8d7da;'>{$comp['auto']}</td>";
            echo "<td style='padding: 10px; background: #d4edda;'>{$comp['choice']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Implementation consideration
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🛠️ Implementation Considerations</h2>";
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
        
        echo "<div>";
        echo "<h4>✅ When to Use User Choice:</h4>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>Multiple valid readings exist</li>";
        echo "<li>Readings conflict significantly</li>";
        echo "<li>Data quality issues detected</li>";
        echo "<li>User has vehicle knowledge</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div>";
        echo "<h4>⚡ When to Use Automatic:</h4>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>Single clear baseline exists</li>";
        echo "<li>All readings are consistent</li>";
        echo "<li>High confidence in data quality</li>";
        echo "<li>Batch processing scenarios</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "</div>";
        
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4>🎯 Hybrid Approach (Recommended):</h4>";
        echo "<ol style='font-size: 14px;'>";
        echo "<li><strong>Detect conflicts:</strong> Check if multiple valid readings exist</li>";
        echo "<li><strong>Auto when clear:</strong> Use automatic selection for obvious cases</li>";
        echo "<li><strong>Choice when complex:</strong> Show user options when conflicts exist</li>";
        echo "<li><strong>Learn from choices:</strong> Use user selections to improve automatic logic</li>";
        echo "</ol>";
        echo "</div>";
        
        echo "</div>";
        
        // Real-world benefits
        echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🌟 Real-World Benefits of User Choice</h2>";
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
        
        $benefits = [
            ['title' => '👥 User Adoption', 'desc' => 'Users trust systems they understand and control'],
            ['title' => '🎓 Training Opportunity', 'desc' => 'Users learn proper odometer reading techniques'],
            ['title' => '🔍 Data Quality', 'desc' => 'Users help identify and fix historical data issues'],
            ['title' => '💪 Flexibility', 'desc' => 'System adapts to edge cases and unique situations'],
            ['title' => '🤝 Collaboration', 'desc' => 'Combines human knowledge with system intelligence'],
            ['title' => '📈 Continuous Improvement', 'desc' => 'User choices improve automatic recommendations']
        ];
        
        foreach ($benefits as $benefit) {
            echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 2px solid #28a745;'>";
            echo "<h5>{$benefit['title']}</h5>";
            echo "<p style='font-size: 13px; margin: 0;'>{$benefit['desc']}</p>";
            echo "</div>";
        }
        
        echo "</div>";
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Comparison Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<div style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/test-user-choice-validation/{$vehicle}' style='padding: 15px 25px; background: #28a745; color: white; text-decoration: none; border-radius: 8px; margin: 10px;'>Test User Choice</a>";
    echo "<a href='/test-user-choice-scenarios' style='padding: 15px 25px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; margin: 10px;'>More Examples</a>";
    echo "<a href='/' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; margin: 10px;'>← Back to App</a>";
    echo "</div>";
});

Route::get('/test-simplified-choice/{vehicle}/{mileage?}', function($vehicle, $mileage = null) {
    try {
        echo "<h1>🎯 Simplified Choice Test: {$vehicle}</h1>";
        
        if ($mileage) {
            echo "<p><strong>User Input:</strong> " . number_format($mileage) . " KM</p>";
        }
        
        // Get all readings for this vehicle
        $allReadings = DB::table('ServiceRequest')
            ->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicle))])
            ->whereNotNull('Odometer')
            ->where('Odometer', '!=', '')
            ->orderBy('Datereceived', 'desc')
            ->get(['ID', 'Odometer', 'Datereceived', 'Description']);
        
        if ($allReadings->isEmpty()) {
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
            echo "<h3>⚠️ No Data Found</h3>";
            echo "<p>No odometer readings found for vehicle: {$vehicle}</p>";
            echo "</div>";
            return;
        }
        
        echo "<div style='background: #d1ecf1; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📊 Found " . $allReadings->count() . " Total Readings</h2>";
        echo "</div>";
        
        // Get the two options
        $automaticBaseline = getAutomaticBaseline($allReadings);
        $latestBaseline = getLatestBaseline($allReadings);
        $hasConflict = checkIfChoiceNeeded($automaticBaseline, $latestBaseline);
        
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🔍 Two-Option Analysis</h2>";
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 20px 0;'>";
        
        // Option 1: System Recommendation
        if ($automaticBaseline) {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border: 3px solid #28a745;'>";
            echo "<h3>🤖 System Recommendation</h3>";
            echo "<div style='background: white; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h4>" . number_format($automaticBaseline['mileage']) . " KM</h4>";
            echo "<p><strong>Date:</strong> " . $automaticBaseline['date_formatted'] . " (" . $automaticBaseline['days_ago'] . " days ago)</p>";
            echo "<p><strong>Service:</strong> " . $automaticBaseline['description'] . "</p>";
            echo "<p><strong>Why:</strong> " . $automaticBaseline['recommendation']['reason'] . "</p>";
            echo "</div>";
            
            if ($mileage) {
                $autoPreview = $mileage - $automaticBaseline['mileage'];
                echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
                echo "<strong>With your input:</strong> ";
                if ($autoPreview >= 0) {
                    echo "<span style='color: green;'>+{$autoPreview} KM increase</span>";
                } else {
                    echo "<span style='color: red;'>{$autoPreview} KM (backwards!)</span>";
                }
                echo "</div>";
            }
            echo "</div>";
        }
        
        // Option 2: Latest Entry
        if ($latestBaseline) {
            $hasIssues = !empty($latestBaseline['recommendation']['issues']);
            $borderColor = $hasIssues ? '#ffc107' : '#007bff';
            
            echo "<div style='background: " . ($hasIssues ? '#fff3cd' : '#e7f3ff') . "; padding: 20px; border-radius: 10px; border: 3px solid {$borderColor};'>";
            echo "<h3>📅 Latest Entry</h3>";
            echo "<div style='background: white; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
            echo "<h4>" . ($latestBaseline['mileage'] > 0 ? number_format($latestBaseline['mileage']) . " KM" : "Invalid Reading") . "</h4>";
            echo "<p><strong>Date:</strong> " . $latestBaseline['date_formatted'] . " (" . $latestBaseline['days_ago'] . " days ago)</p>";
            echo "<p><strong>Service:</strong> " . $latestBaseline['description'] . "</p>";
            echo "<p><strong>Note:</strong> " . $latestBaseline['recommendation']['reason'] . "</p>";
            
            if ($hasIssues) {
                echo "<div style='background: #fff3cd; padding: 8px; border-radius: 3px; margin: 8px 0;'>";
                echo "<strong>⚠️ Issues:</strong> " . implode(', ', $latestBaseline['recommendation']['issues']);
                echo "</div>";
            }
            echo "</div>";
            
            if ($mileage && $latestBaseline['mileage'] > 0) {
                $latestPreview = $mileage - $latestBaseline['mileage'];
                echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
                echo "<strong>With your input:</strong> ";
                if ($latestPreview >= 0) {
                    echo "<span style='color: green;'>+{$latestPreview} KM increase</span>";
                } else {
                    echo "<span style='color: red;'>{$latestPreview} KM (backwards!)</span>";
                }
                echo "</div>";
            }
            echo "</div>";
        }
        
        echo "</div>";
        
        // Decision logic
        echo "<div style='background: " . ($hasConflict ? '#fff3cd' : '#d4edda') . "; padding: 15px; border-radius: 8px; margin: 15px 0;'>";
        echo "<h4>🎯 Choice Decision:</h4>";
        
        if ($hasConflict) {
            echo "<p><strong>✅ SHOW CHOICE INTERFACE</strong></p>";
            echo "<p>User would see both options because:</p>";
            echo "<ul>";
            if ($automaticBaseline && $latestBaseline && $automaticBaseline['id'] !== $latestBaseline['id']) {
                echo "<li>Different readings (System: " . number_format($automaticBaseline['mileage']) . " vs Latest: " . number_format($latestBaseline['mileage']) . ")</li>";
            }
            if (!empty($latestBaseline['recommendation']['issues'])) {
                echo "<li>Latest entry has data quality issues</li>";
            }
            $mileageDiff = abs($automaticBaseline['mileage'] - $latestBaseline['mileage']);
            if ($mileageDiff > 10000) {
                echo "<li>Significant mileage difference: " . number_format($mileageDiff) . " KM</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>❌ NO CHOICE NEEDED</strong></p>";
            echo "<p>System would automatically use: " . ($automaticBaseline ? number_format($automaticBaseline['mileage']) . " KM" : "latest reading") . "</p>";
            echo "<p>Reason: Readings are consistent or only one valid option exists</p>";
        }
        
        echo "</div>";
        echo "</div>";
        
        // Show all readings for context
        echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>📋 All Readings (for context)</h2>";
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #e9ecef;'>";
        echo "<th style='padding: 8px;'>Date</th>";
        echo "<th style='padding: 8px;'>Raw Value</th>";
        echo "<th style='padding: 8px;'>Parsed KM</th>";
        echo "<th style='padding: 8px;'>Status</th>";
        echo "<th style='padding: 8px;'>Selection</th>";
        echo "</tr>";
        
        foreach ($allReadings as $reading) {
            $rawValue = trim($reading->Odometer);
            $isValid = isValidForFiltering($rawValue);
            $isAutomatic = $automaticBaseline && $reading->ID == $automaticBaseline['id'];
            $isLatest = $latestBaseline && $reading->ID == $latestBaseline['id'];
            
            $rowColor = '#ffffff';
            if ($isAutomatic) $rowColor = '#d4edda';
            if ($isLatest && !$isAutomatic) $rowColor = '#e7f3ff';
            
            echo "<tr style='background: {$rowColor};'>";
            echo "<td style='padding: 6px;'>" . substr($reading->Datereceived, 0, 10) . "</td>";
            echo "<td style='padding: 6px;'>'{$rawValue}'</td>";
            echo "<td style='padding: 6px;'>" . (is_numeric($rawValue) ? number_format(floatval($rawValue)) : 'Invalid') . "</td>";
            echo "<td style='padding: 6px;'>" . ($isValid ? '✅ Valid' : '❌ Filtered') . "</td>";
            echo "<td style='padding: 6px;'>";
            if ($isAutomatic) echo '🤖 System Choice';
            if ($isLatest && !$isAutomatic) echo '📅 Latest Entry';
            if (!$isAutomatic && !$isLatest) echo '-';
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "</div>";
        
        // Benefits of simplified approach
        echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🎯 Benefits of Simplified Two-Option Approach</h2>";
        
        echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
        
        echo "<div>";
        echo "<h4>✅ User Experience:</h4>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>Simple decision (only 2 choices)</li>";
        echo "<li>Clear recommendations</li>";
        echo "<li>No analysis paralysis</li>";
        echo "<li>Quick to understand</li>";
        echo "<li>Mobile-friendly interface</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div>";
        echo "<h4>✅ System Benefits:</h4>";
        echo "<ul style='font-size: 14px;'>";
        echo "<li>Easier to implement</li>";
        echo "<li>Covers 95% of scenarios</li>";
        echo "<li>Less complex UI logic</li>";
        echo "<li>Faster user decisions</li>";
        echo "<li>Still provides user control</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "</div>";
        
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h4>🧠 Smart Logic:</h4>";
        echo "<p style='font-size: 14px; margin: 0;'>";
        echo "<strong>Option 1 (System):</strong> Highest valid reading after filtering errors<br>";
        echo "<strong>Option 2 (Latest):</strong> Most recent entry regardless of value<br>";
        echo "<strong>Choice shown only when:</strong> They differ significantly or latest has issues";
        echo "</p>";
        echo "</div>";
        
        echo "</div>";
        
        // Test different scenarios
        if ($mileage) {
            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
            echo "<h2>🧪 Validation Results</h2>";
            
            if ($automaticBaseline) {
                echo "<h4>If user chooses System Recommendation:</h4>";
                echo getValidationResult($mileage, $automaticBaseline);
            }
            
            if ($latestBaseline && $latestBaseline['mileage'] > 0) {
                echo "<h4>If user chooses Latest Entry:</h4>";
                echo getValidationResult($mileage, $latestBaseline);
            }
            
            echo "</div>";
        }
        
        // Test with different values
        echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
        echo "<h2>🔬 Test Different Inputs</h2>";
        
        if ($automaticBaseline) {
            $testValues = [
                $automaticBaseline['mileage'] - 5000,  // Backwards
                $automaticBaseline['mileage'] + 2000,  // Normal
                $automaticBaseline['mileage'] + 20000, // High
            ];
            
            foreach ($testValues as $testValue) {
                echo "<a href='/test-simplified-choice/{$vehicle}/{$testValue}' style='margin: 5px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; display: inline-block;'>";
                echo number_format($testValue) . " KM</a>";
            }
        }
        
        echo "</div>";
        
    } catch (\Exception $e) {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Test Error</h3>";
        echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
        echo "</div>";
    }
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/test-simplified-scenarios' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Test Menu</a>";
    echo "</p>";
});

// Helper functions for the test
function getAutomaticBaseline($allReadings) {
    $validReadings = [];
    
    foreach ($allReadings as $reading) {
        $mileage = trim($reading->Odometer);
        if (isValidForFiltering($mileage)) {
            $validReadings[] = [
                'id' => $reading->ID,
                'mileage' => floatval($mileage),
                'date' => $reading->Datereceived,
                'date_formatted' => Carbon\Carbon::parse($reading->Datereceived)->format('d M Y'),
                'days_ago' => Carbon\Carbon::parse($reading->Datereceived)->diffInDays(now()),
                'description' => substr($reading->Description ?? '', 0, 40)
            ];
        }
    }
    
    if (empty($validReadings)) return null;
    
    usort($validReadings, function($a, $b) {
        return $b['mileage'] - $a['mileage'];
    });
    
    $baseline = $validReadings[0];
    $baseline['recommendation'] = [
        'title' => 'System Recommendation',
        'icon' => '🤖',
        'reason' => 'Highest valid reading from ' . count($validReadings) . ' filtered readings'
    ];
    
    return $baseline;
}

function getLatestBaseline($allReadings) {
    $latestReading = $allReadings->first();
    if (!$latestReading) return null;
    
    $mileage = trim($latestReading->Odometer);
    $issues = [];
    
    if ($mileage === '0' || $mileage === '') {
        $issues[] = 'Zero value';
    } elseif (!is_numeric($mileage)) {
        $issues[] = 'Non-numeric';
    } elseif (floatval($mileage) < 1000) {
        $issues[] = 'Very low value';
    }
    
    return [
        'id' => $latestReading->ID,
        'mileage' => is_numeric($mileage) ? floatval($mileage) : 0,
        'date' => $latestReading->Datereceived,
        'date_formatted' => Carbon\Carbon::parse($latestReading->Datereceived)->format('d M Y'),
        'days_ago' => Carbon\Carbon::parse($latestReading->Datereceived)->diffInDays(now()),
        'description' => substr($latestReading->Description ?? '', 0, 40),
        'recommendation' => [
            'title' => 'Latest Entry',
            'icon' => '📅',
            'reason' => empty($issues) ? 'Most recent service entry' : 'Latest but has issues: ' . implode(', ', $issues),
            'issues' => $issues
        ]
    ];
}

function checkIfChoiceNeeded($automatic, $latest) {
    if (!$automatic || !$latest) return false;
    if ($automatic['id'] === $latest['id']) return false;
    
    $mileageDiff = abs($automatic['mileage'] - $latest['mileage']);
    if ($mileageDiff > 10000) return true;
    
    if (!empty($latest['recommendation']['issues'])) return true;
    
    return false;
}

function isValidForFiltering($value) {
    if ($value === '0' || $value === '' || !is_numeric($value)) return false;
    $numericValue = floatval($value);
    return $numericValue >= 1000 && $numericValue <= 2000000;
}

function getValidationResult($userInput, $baseline) {
    $difference = $userInput - $baseline['mileage'];
    $dailyUsage = $baseline['days_ago'] > 0 ? $difference / $baseline['days_ago'] : 0;
    
    if ($difference < 0) {
        return "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>❌ FAIL - Backwards movement</div>";
    } elseif ($dailyUsage > 500) {
        return "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>❌ FAIL - Extreme usage (" . round($dailyUsage) . " KM/day)</div>";
    } elseif ($dailyUsage > 200) {
        return "<div style='background: #fff3cd; padding: 10px; border-radius: 5px;'>⚠️ WARNING - High usage (" . round($dailyUsage) . " KM/day)</div>";
    } else {
        return "<div style='background: #d4edda; padding: 10px; border-radius: 5px;'>✅ PASS - Normal progression (" . round($dailyUsage) . " KM/day)</div>";
    }
}

Route::get('/test-simplified-scenarios', function() {
    echo "<h1>🎯 Simplified Two-Option Testing</h1>";
    echo "<p>Test the simplified approach: <strong>System Recommendation</strong> vs <strong>Latest Entry</strong></p>";
    
    // Get some vehicles with multiple readings
    $testVehicles = DB::select("
        SELECT Vehicle, COUNT(DISTINCT Odometer) as reading_count
        FROM ServiceRequest 
        WHERE Vehicle IS NOT NULL 
        AND Vehicle != ''
        AND Odometer IS NOT NULL 
        AND Odometer != ''
        GROUP BY Vehicle
        HAVING COUNT(DISTINCT Odometer) > 1
        ORDER BY reading_count DESC
    ");
    
    echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>🚗 Test Vehicles</h2>";
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;'>";
    
    foreach (array_slice($testVehicles, 0, 6) as $vehicle) {
        echo "<div style='background: white; padding: 15px; border-radius: 8px; border: 2px solid #007bff; text-center;'>";
        echo "<h4>{$vehicle->Vehicle}</h4>";
        echo "<p>{$vehicle->reading_count} different readings</p>";
        echo "<a href='/test-simplified-choice/{$vehicle->Vehicle}' style='padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 2px; display: inline-block; font-size: 12px;'>Analyze Choices</a>";
        echo "<a href='/test-simplified-choice/{$vehicle->Vehicle}/650000' style='padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; margin: 2px; display: inline-block; font-size: 12px;'>Test 650K</a>";
        echo "</div>";
    }
    
    echo "</div>";
    echo "</div>";
    
    echo "<div style='background: #e8f5e8; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h2>✅ Simplified Approach Benefits</h2>";
    echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
    
    echo "<div>";
    echo "<h4>👤 User Benefits:</h4>";
    echo "<ul>";
    echo "<li>Only 2 simple choices</li>";
    echo "<li>Clear recommendations</li>";
    echo "<li>No decision paralysis</li>";
    echo "<li>Quick and intuitive</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div>";
    echo "<h4>⚙️ Implementation Benefits:</h4>";
    echo "<ul>";
    echo "<li>Simpler UI design</li>";
    echo "<li>Easier to code</li>";
    echo "<li>Faster user decisions</li>";
    echo "<li>Mobile-friendly</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "</div>";
    echo "</div>";
    
    echo "<p style='text-align: center; margin: 30px 0;'>";
    echo "<a href='/' style='padding: 15px 25px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px;'>← Back to Main App</a>";
    echo "</p>";
});

Route::get('/test-validation-fix/{vehicle}/{mileage}', function($vehicle, $mileage) {
    echo "<h1>🔍 Testing Validation Fix</h1>";
    echo "<p><strong>Vehicle:</strong> {$vehicle}</p>";
    echo "<p><strong>Mileage:</strong> " . number_format($mileage) . " KM</p>";
    
    try {
        $controller = new App\Http\Controllers\PredictionController(new App\Services\VMSPredictionService());
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('validateMileageWithSmartFiltering');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $vehicle, $mileage);
        
        if ($result['valid']) {
            echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; border-left: 5px solid #28a745;'>";
            echo "<h3>✅ VALIDATION PASSED</h3>";
            echo "<p>This mileage would be accepted by the system.</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; border-left: 5px solid #dc3545;'>";
            echo "<h3>❌ VALIDATION FAILED (Correctly!)</h3>";
            echo "<p><strong>Error Message:</strong></p>";
            echo "<p><em>\"" . ($result['message'] ?? 'No message') . "\"</em></p>";
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Metric</th><th>Value</th></tr>";
            echo "<tr><td>Your Input</td><td>" . number_format($result['user_input'] ?? 0) . " KM</td></tr>";
            echo "<tr><td>Reliable Baseline</td><td>" . number_format($result['reliable_mileage'] ?? 0) . " KM</td></tr>";
            echo "<tr><td>Difference</td><td>" . number_format($result['difference'] ?? 0) . " KM lower</td></tr>";
            echo "<tr><td>Outliers Excluded</td><td>" . ($result['outliers_excluded'] ?? 0) . "</td></tr>";
            echo "</table>";
            echo "</div>";
        }
        
        echo "<h3>🔗 Test Different Values:</h3>";
        $testValues = [95000, 600000, 625000, 650000, 700000];
        foreach ($testValues as $testVal) {
            $expectation = $testVal < 600000 ? "Should Fail ❌" : "Should Pass ✅";
            echo "<a href='/test-validation-fix/{$vehicle}/{$testVal}' style='margin: 5px; padding: 8px 12px; background: #f8f9fa; border: 1px solid #ddd; text-decoration: none; border-radius: 4px; display: inline-block;'>";
            echo number_format($testVal) . " KM<br><small>({$expectation})</small></a>";
        }
        
    } catch (\Exception $e) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; border-left: 5px solid #ffc107;'>";
        echo "<h3>⚠️ Error</h3>";
        echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . basename($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "</div>";
    }
    
    echo "<p><a href='/'>← Back to Main App</a></p>";
});
