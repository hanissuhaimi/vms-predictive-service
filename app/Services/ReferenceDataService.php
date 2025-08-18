<?php

namespace App\Services;

use App\Models\VehicleProfile;
use App\Models\ServiceRequest;
use App\Models\Depot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReferenceDataService
{
    private static $mrTypes = [
        1 => ['malay' => 'Senggaraan', 'english' => 'Maintenance'],
        2 => ['malay' => 'Cuci', 'english' => 'Cleaning/Washing'],
        3 => ['malay' => 'Tayar', 'english' => 'Tires'],
        4 => ['malay' => 'Rental', 'english' => 'Rental'],
        5 => ['malay' => 'Operation', 'english' => 'Operation']
    ];
    
    private static $statusCodes = [
        0 => 'New',
        1 => 'Approved by KB',
        2 => 'MO Created',
        3 => 'Closed'
    ];
    
    private static $priorityLevels = [
        1 => 'High',
        2 => 'Medium',
        3 => 'Low'
    ];
    
    /**
     * Get MR Type information
     */
    public function getMRTypeInfo(int $mrTypeId, string $language = 'english'): array
    {
        if (!isset(self::$mrTypes[$mrTypeId])) {
            return ['id' => $mrTypeId, 'name' => 'Unknown', 'display_name' => 'Unknown'];
        }
        
        $mrType = self::$mrTypes[$mrTypeId];
        
        return [
            'id' => $mrTypeId,
            'name' => $mrType[$language] ?? $mrType['english'],
            'malay_name' => $mrType['malay'],
            'english_name' => $mrType['english'],
            'display_name' => $mrType['english']
        ];
    }
    
    /**
     * Get all MR Types
     */
    public function getAllMRTypes(string $language = 'english'): array
    {
        $result = [];
        
        foreach (self::$mrTypes as $id => $names) {
            $result[$id] = [
                'id' => $id,
                'name' => $names[$language] ?? $names['english'],
                'malay_name' => $names['malay'],
                'english_name' => $names['english']
            ];
        }
        
        return $result;
    }
    
    /**
     * Get status information
     */
    public function getStatusInfo(int $statusCode): array
    {
        return [
            'code' => $statusCode,
            'name' => self::$statusCodes[$statusCode] ?? 'Unknown',
            'is_active' => $statusCode < 3,
            'is_closed' => $statusCode === 3
        ];
    }
    
    /**
     * Get priority information
     */
    public function getPriorityInfo(int $priorityLevel): array
    {
        $colors = [1 => 'danger', 2 => 'warning', 3 => 'success'];
        
        return [
            'level' => $priorityLevel,
            'name' => self::$priorityLevels[$priorityLevel] ?? 'Unknown',
            'color' => $colors[$priorityLevel] ?? 'secondary',
            'urgency_days' => match($priorityLevel) {
                1 => 1,
                2 => 2,
                3 => 3,
                default => 2
            }
        ];
    }
    
    /**
     * Get depot information by code
     */
    public function getDepotInfo(string $depotCode): array
    {
        $depot = Depot::where('depot_kod', $depotCode)->first();
        
        if ($depot) {
            return $depot->formatted_info;
        }
        
        return [
            'code' => $depotCode,
            'name' => "Unknown Depot ({$depotCode})",
            'short_name' => $depotCode,
            'address' => 'Address not available'
        ];
    }
    
    /**
     * Get user information by ID
     */
    public function getUserInfo(string $userId): array
    {
        $user = User::where('UID', $userId)->first();
        
        if ($user) {
            return [
                'id' => $user->UID,
                'name' => $user->full_name,
                'designation' => $user->Designation,
                'department' => $user->Department,
                'depot_info' => $user->depot_info,
                'is_active' => $user->is_active
            ];
        }
        
        return [
            'id' => $userId,
            'name' => 'Unknown User',
            'designation' => 'Unknown',
            'is_active' => false
        ];
    }
}