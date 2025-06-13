<?php
// Create this file as sql-diagnostic.php in your Laravel public folder
// Access via: http://localhost/vms-prediction/public/sql-diagnostic.php

echo "<h1>🔍 SQL Server Connection Diagnostic</h1><hr>";

// Test 1: PHP Extensions
echo "<h2>1. PHP Extensions Check</h2>";
$extensions = ['sqlsrv', 'pdo_sqlsrv', 'odbc', 'pdo_odbc'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo sprintf("%-15s: %s<br>", $ext, $loaded ? "✅ Loaded" : "❌ Not loaded");
}

// Test 2: Available PDO Drivers
echo "<h2>2. Available PDO Drivers</h2>";
$drivers = PDO::getAvailableDrivers();
echo "Available drivers: " . implode(', ', $drivers) . "<br>";
echo "SQL Server driver available: " . (in_array('sqlsrv', $drivers) ? "✅ Yes" : "❌ No") . "<br>";

// Test 3: Network Connectivity
echo "<h2>3. Network Connectivity Test</h2>";
$host = '127.0.0.1';
$port = 1433;
$timeout = 5;

$connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
if ($connection) {
    echo "✅ TCP connection to {$host}:{$port} successful<br>";
    fclose($connection);
} else {
    echo "❌ TCP connection to {$host}:{$port} failed<br>";
    echo "Error: {$errno} - {$errstr}<br>";
    echo "<strong>This suggests SQL Server is not running or not accepting connections on port 1433</strong><br>";
}

// Test 4: Direct SQL Server Connection (if extensions available)
echo "<h2>4. Direct SQL Server Connection Test</h2>";

if (extension_loaded('sqlsrv')) {
    $serverName = "127.0.0.1,1433";
    $connectionOptions = [
        "Database" => "vms",
        "Uid" => "laravel_user",
        "PWD" => "LaravelPassword123!",
        "Encrypt" => true,
        "TrustServerCertificate" => true
    ];
    
    echo "Attempting connection to: $serverName<br>";
    $conn = sqlsrv_connect($serverName, $connectionOptions);
    
    if ($conn) {
        echo "✅ SQL Server connection successful!<br>";
        
        // Test query
        $sql = "SELECT @@VERSION as version, DB_NAME() as current_db";
        $stmt = sqlsrv_query($conn, $sql);
        
        if ($stmt) {
            $row = sqlsrv_fetch_array($stmt);
            echo "SQL Server Version: " . substr($row['version'], 0, 100) . "...<br>";
            echo "Current Database: " . $row['current_db'] . "<br>";
        }
        
        sqlsrv_close($conn);
    } else {
        echo "❌ SQL Server connection failed<br>";
        $errors = sqlsrv_errors();
        if ($errors) {
            foreach ($errors as $error) {
                echo "Error: " . $error['message'] . "<br>";
            }
        }
    }
} else {
    echo "⚠️ SQL Server extension not available for direct test<br>";
}

// Test 5: PDO Connection Test
echo "<h2>5. PDO Connection Test</h2>";

if (in_array('sqlsrv', PDO::getAvailableDrivers())) {
    try {
        $dsn = "sqlsrv:Server=127.0.0.1,1433;Database=vms;Encrypt=yes;TrustServerCertificate=yes";
        $username = "laravel_user";
        $password = "LaravelPassword123!";
        
        echo "Attempting PDO connection...<br>";
        $pdo = new PDO($dsn, $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ PDO SQL Server connection successful!<br>";
        
        // Test query
        $stmt = $pdo->query("SELECT @@VERSION as version");
        $row = $stmt->fetch();
        echo "Connected to: " . substr($row['version'], 0, 50) . "...<br>";
        
    } catch (PDOException $e) {
        echo "❌ PDO connection failed<br>";
        echo "Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "⚠️ PDO SQL Server driver not available<br>";
}

// Test 6: Environment Configuration
echo "<h2>6. Environment Configuration</h2>";
if (file_exists('../.env')) {
    echo "✅ .env file found<br>";
    $env = file_get_contents('../.env');
    $dbLines = array_filter(explode("\n", $env), function($line) {
        return strpos($line, 'DB_') === 0;
    });
    
    echo "<strong>Database configuration:</strong><br>";
    foreach ($dbLines as $line) {
        // Hide password for security
        if (strpos($line, 'DB_PASSWORD') !== false) {
            echo "DB_PASSWORD=***hidden***<br>";
        } else {
            echo htmlspecialchars($line) . "<br>";
        }
    }
} else {
    echo "❌ .env file not found<br>";
}

// Recommendations
echo "<h2>🔧 Troubleshooting Recommendations</h2>";
echo "<ul>";

if (!$connection) {
    echo "<li><strong>SQL Server is not running or not accepting connections:</strong>";
    echo "<ul>";
    echo "<li>Check if SQL Server service is running (services.msc)</li>";
    echo "<li>Enable TCP/IP in SQL Server Configuration Manager</li>";
    echo "<li>Set TCP port to 1433</li>";
    echo "<li>Restart SQL Server service</li>";
    echo "<li>Check Windows Firewall settings</li>";
    echo "</ul></li>";
}

if (!extension_loaded('sqlsrv') && !extension_loaded('pdo_sqlsrv')) {
    echo "<li><strong>PHP SQL Server extensions not installed:</strong>";
    echo "<ul>";
    echo "<li>Download Microsoft Drivers for PHP for SQL Server</li>";
    echo "<li>Copy DLL files to php/ext directory</li>";
    echo "<li>Update php.ini to enable extensions</li>";
    echo "<li>Restart Apache</li>";
    echo "</ul></li>";
}

echo "<li><strong>If still having issues:</strong>";
echo "<ul>";
echo "<li>Try using Windows Authentication instead of SQL Server Authentication</li>";
echo "<li>Check SQL Server error logs</li>";
echo "<li>Try connecting with SQL Server Management Studio first</li>";
echo "<li>Consider using ODBC driver as alternative</li>";
echo "</ul></li>";

echo "</ul>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong> Fix the issues identified above, then test Laravel connection at: <a href='test-db-connection'>/test-db-connection</a></p>";
?>