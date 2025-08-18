<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ServiceRequest extends Model
{
    use HasFactory;

    protected $table = 'ServiceRequest';
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'ComplaintNo', 'SR', 'Datereceived', 'timereceived', 'Requestor',
        'Building', 'department', 'location', 'CMType', 'Description',
        'responseDate', 'ResponseTime', 'responsedBy', 'Response', 'Inspection',
        'InspectBy', 'image1', 'image2', 'Report', 'COID', 'Contractor',
        'Insp_position', 'Status', 'DateClose', 'TimeClose', 'AggreeBy',
        'Agg_Position', 'QRef', 'QDesc', 'QFile', 'Staff', 'Priority',
        'Odometer', 'Vehicle', 'DateModify', 'TimeModify', 'ModifyBy',
        'TrailerNo', 'Driver', 'Jarak_Operasi', 'MrType', 'ForTrailer'
    ];

    protected $casts = [
        'ID' => 'integer',
        'Datereceived' => 'datetime',
        'responseDate' => 'datetime',
        'DateClose' => 'datetime',
        'DateModify' => 'datetime',
        'TimeModify' => 'datetime',
        'Jarak_Operasi' => 'float',
        'ForTrailer' => 'boolean',
        'Priority' => 'integer',
        'Status' => 'integer',
        'MrType' => 'integer',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get the vehicle profile for this service request
     */
    public function vehicleProfile()
    {
        return $this->belongsTo(VehicleProfile::class, 'Vehicle', 'vh_regno');
    }

    /**
     * Get the depot information
     */
    public function depot()
    {
        return $this->belongsTo(Depot::class, 'Building', 'depot_kod');
    }

    /**
     * Get the requesting user
     */
    public function requestor()
    {
        return $this->belongsTo(User::class, 'Requestor', 'UID');
    }

    /**
     * Get the responding user
     */
    public function responder()
    {
        return $this->belongsTo(User::class, 'responsedBy', 'UID');
    }

    /**
     * Get the inspector
     */
    public function inspector()
    {
        return $this->belongsTo(User::class, 'InspectBy', 'UID');
    }

    // ========================================
    // ACCESSORS (Computed Properties)
    // ========================================

    /**
     * Get formatted date received
     */
    public function getFormattedDateReceivedAttribute()
    {
        if (!$this->Datereceived) return 'Unknown date';
        
        try {
            $date = Carbon::parse($this->Datereceived)->format('d M Y');
            $time = $this->timereceived ? ' at ' . $this->timereceived : '';
            return $date . $time;
        } catch (\Exception $e) {
            return 'Invalid date';
        }
    }

    /**
     * Get priority text (using correct reference data)
     */
    public function getPriorityTextAttribute()
    {
        $priorities = [
            1 => 'High',
            2 => 'Medium',
            3 => 'Low'
        ];
        
        return $priorities[$this->Priority] ?? 'Unknown';
    }

    /**
     * Get status text (using correct reference data)
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            0 => 'New',
            1 => 'Approved by KB',
            2 => 'MO Created',
            3 => 'Closed'
        ];
        
        return $statuses[$this->Status] ?? 'Unknown';
    }

    /**
     * Get MR Type text (using correct reference data)
     */
    public function getMrTypeTextAttribute()
    {
        $mrTypes = [
            1 => 'Senggaraan',      // Maintenance
            2 => 'Cuci',            // Cleaning/Washing
            3 => 'Tayar',           // Tires
            4 => 'Rental',          // Rental
            5 => 'Operation'        // Operation
        ];
        
        return $mrTypes[$this->MrType] ?? 'Other';
    }

    /**
     * Get English MR Type text for UI
     */
    public function getMrTypeEnglishAttribute()
    {
        $mrTypesEn = [
            1 => 'Maintenance',
            2 => 'Cleaning/Washing',
            3 => 'Tires',
            4 => 'Rental',
            5 => 'Operation'
        ];
        
        return $mrTypesEn[$this->MrType] ?? 'Other';
    }

    /**
     * Get formatted description combining description and response
     */
    public function getFormattedDescriptionAttribute()
    {
        $desc = trim($this->Description ?? '');
        $resp = trim($this->Response ?? '');
        
        if (empty($desc) && empty($resp)) {
            return 'No description available';
        }
        
        if (empty($resp)) {
            return $desc;
        }
        
        if (empty($desc)) {
            return 'Response: ' . $resp;
        }
        
        return $desc . ' | Response: ' . (strlen($resp) > 100 ? substr($resp, 0, 100) . '...' : $resp);
    }

    /**
     * Get days ago from service date
     */
    public function getDaysAgoAttribute()
    {
        if (!$this->Datereceived) return 'Unknown';
        
        try {
            return Carbon::parse($this->Datereceived)->diffInDays(now()) . ' days ago';
        } catch (\Exception $e) {
            return 'Unknown';
        }
    }

    /**
     * Get formatted odometer reading
     */
    public function getFormattedOdometerAttribute()
    {
        if (!$this->Odometer || !is_numeric($this->Odometer)) {
            return 'Not recorded';
        }
        
        return number_format(floatval($this->Odometer)) . ' KM';
    }

    /**
     * Check if this is a maintenance record (not cleaning)
     */
    public function getIsMaintenanceAttribute()
    {
        return $this->MrType !== 2; // Not cleaning
    }

    /**
     * Check if this is a tire-related service
     */
    public function getIsTireServiceAttribute()
    {
        return $this->MrType === 3; // Tire service
    }

    /**
     * Get service category for analysis
     */
    public function getServiceCategoryAttribute()
    {
        switch ($this->MrType) {
            case 1: return 'maintenance';
            case 2: return 'cleaning';
            case 3: return 'tires';
            case 4: return 'rental';
            case 5: return 'operation';
            default: return 'other';
        }
    }

    /**
     * Get depot information
     */
    public function getDepotInfoAttribute()
    {
        if ($this->depot) {
            return [
                'code' => $this->depot->depot_kod,
                'name' => $this->depot->depot_nama,
                'address' => $this->depot->address
            ];
        }
        
        return [
            'code' => $this->Building ?? 'Unknown',
            'name' => 'Unknown Depot',
            'address' => 'Address not available'
        ];
    }

    // ========================================
    // SCOPES (Query Helpers)
    // ========================================

    /**
     * Filter by vehicle
     */
    public function scopeForVehicle($query, $vehicleNumber)
    {
        return $query->whereRaw('UPPER(TRIM(Vehicle)) = ?', [strtoupper(trim($vehicleNumber))]);
    }

    /**
     * Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('Status', $status);
    }

    /**
     * Filter by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('Priority', $priority);
    }

    /**
     * Filter by MR Type
     */
    public function scopeByMrType($query, $mrType)
    {
        return $query->where('MrType', $mrType);
    }

    /**
     * Get only maintenance records (exclude cleaning)
     */
    public function scopeMaintenanceOnly($query)
    {
        return $query->where('MrType', '!=', 2);
    }

    /**
     * Get only cleaning records
     */
    public function scopeCleaningOnly($query)
    {
        return $query->where('MrType', 2);
    }

    /**
     * Get only tire services
     */
    public function scopeTireServices($query)
    {
        return $query->where('MrType', 3);
    }

    /**
     * Get recent requests
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('Datereceived', '>=', now()->subDays($days));
    }

    /**
     * Get records with valid dates
     */
    public function scopeWithValidDates($query)
    {
        return $query->whereNotNull('Datereceived');
    }

    /**
     * Get records with odometer readings
     */
    public function scopeWithOdometer($query)
    {
        return $query->whereNotNull('Odometer')
                    ->where('Odometer', '>', 0);
    }

    /**
     * Order by most recent first
     */
    public function scopeOrderByRecent($query)
    {
        return $query->orderByRaw('COALESCE(Datereceived, DateModify, responseDate, getdate()) DESC');
    }

    // ========================================
    // BUSINESS LOGIC METHODS
    // ========================================

    /**
     * Check if service is overdue for response
     */
    public function isOverdueForResponse()
    {
        if ($this->Status >= 2) return false; // Already responded
        
        if (!$this->Datereceived) return false;
        
        $daysSinceRequest = Carbon::parse($this->Datereceived)->diffInDays(now());
        
        // High priority: 1 day, Medium: 2 days, Low: 3 days
        $maxDays = match($this->Priority) {
            1 => 1,
            2 => 2,
            3 => 3,
            default => 2
        };
        
        return $daysSinceRequest > $maxDays;
    }

    /**
     * Check if service is completed
     */
    public function isCompleted()
    {
        return $this->Status === 3;
    }

    /**
     * Get service duration in days
     */
    public function getServiceDurationDays()
    {
        if (!$this->Datereceived || !$this->DateClose) return null;
        
        try {
            return Carbon::parse($this->Datereceived)->diffInDays(Carbon::parse($this->DateClose));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if this service matches keywords (for parts analysis)
     */
    public function matchesKeywords(array $keywords)
    {
        $searchText = strtolower(($this->Description ?? '') . ' ' . ($this->Response ?? ''));
        
        foreach ($keywords as $keyword) {
            if (str_contains($searchText, strtolower($keyword))) {
                return true;
            }
        }
        
        return false;
    }
}