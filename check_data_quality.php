<?php
// Run with: php check_data_quality.php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$activeVehicles = DB::table('Vehicle_profile')->where('Status', 1)->count();
$moCreatedRecords = DB::table('ServiceRequest')->where('Status', 2)->count();

$filteredRecords = DB::table('ServiceRequest as sr')
    ->join('Vehicle_profile as vp', 'sr.Vehicle', '=', 'vp.vh_regno')
    ->where('sr.Status', 2)
    ->where('vp.Status', 1)
    ->count();

echo "=== VMS Data Quality Report ===\n";
echo "Active vehicles: {$activeVehicles}\n";
echo "MO Created service records: {$moCreatedRecords}\n";
echo "Filtered records (what system uses): {$filteredRecords}\n";
echo "Data quality: " . round(($filteredRecords / max($moCreatedRecords, 1)) * 100, 1) . "%\n";