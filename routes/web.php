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