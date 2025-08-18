<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class VehicleProfile extends Model
{
    use HasFactory;

    protected $table = 'Vehicle_profile';
    protected $primaryKey = 'vh_regno';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'vh_regno', 'trailer_no', 'dept_kod', 'vh_rtax_no', 'vh_rtax_expdte',
        'depot_kod', 'staff_nama1', 'staff_kod1', 'staff_nama2', 'staff_kod2',
        'insurance_no', 'insurance_exp', 'permit_expdte', 'jpj_inspdte',
        'minor_svcdte', 'major_svcdte', 'wt_noload', 'wt_load', 'income_exp',
        'trip_exp', 'wt_exp', 'cc_desc', 'cc_code', 'bizarea_code', 'gl_kod',
        'Status', 'jarak_operasi', 'ModelID', 'UpdateBy', 'DateUpdated',
        'TimeUpdated', 'UnderMaintenance', 'permit_no', 'CreatedBy',
        'CreatedDate', 'Remark'
    ];

    protected $casts = [
        'vh_rtax_expdte' => 'date',
        'insurance_exp' => 'date',
        'permit_expdte' => 'date',
        'jpj_inspdte' => 'date',
        'minor_svcdte' => 'date',
        'major_svcdte' => 'date',
        'DateUpdated' => 'date',
        'CreatedDate' => 'date',
        'wt_noload' => 'float',
        'wt_load' => 'float',
        'income_exp' => 'float',
        'trip_exp' => 'float',
        'wt_exp' => 'float',
        'jarak_operasi' => 'float',
        'Status' => 'integer',
        'UnderMaintenance' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get all service requests for this vehicle
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno');
    }

    /**
     * Get only maintenance service requests (exclude cleaning)
     */
    public function maintenanceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno')
                    ->maintenanceOnly();
    }

    /**
     * Get only cleaning service requests
     */
    public function cleaningRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno')
                    ->cleaningOnly();
    }

    /**
     * Get tire service requests
     */
    public function tireServices()
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno')
                    ->tireServices();
    }

    /**
     * Get recent service requests
     */
    public function recentServices($days = 30)
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno')
                    ->recent($days)
                    ->orderByRecent();
    }

    /**
     * Get the depot information
     */
    public function depot()
    {
        return $this->belongsTo(Depot::class, 'depot_kod', 'depot_kod');
    }

    /**
     * Get primary staff member
     */
    public function primaryStaff()
    {
        return $this->belongsTo(User::class, 'staff_kod1', 'UID');
    }

    /**
     * Get secondary staff member
     */
    public function secondaryStaff()
    {
        return $this->belongsTo(User::class, 'staff_kod2', 'UID');
    }

    // ========================================
    // ACCESSORS (Computed Properties)
    // ========================================

    /**
     * Get formatted vehicle registration
     */
    public function getFormattedRegistrationAttribute()
    {
        return strtoupper(trim($this->vh_regno));
    }

    /**
     * Get vehicle status text
     */
    public function getStatusTextAttribute()
    {
        $statuses = [
            1 => 'Active',
            2 => 'Inactive',
            3 => 'Under Maintenance',
            9 => 'Decommissioned'
        ];
        
        return $statuses[$this->Status] ?? 'Unknown';
    }

    /**
     * Check if vehicle is active
     */
    public function getIsActiveAttribute()
    {
        return $this->Status === 1;
    }

    /**
     * Check if vehicle is under maintenance
     */
    public function getIsUnderMaintenanceAttribute()
    {
        return $this->UnderMaintenance === true || $this->Status === 3;
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
                'address' => $this->depot->address,
                'short_name' => $this->depot->SF ?? $this->depot->depot_kod
            ];
        }
        
        return [
            'code' => $this->depot_kod ?? 'Unknown',
            'name' => 'Unknown Depot',
            'address' => 'Address not available',
            'short_name' => $this->depot_kod ?? 'UNK'
        ];
    }

    /**
     * Get full staff information
     */
    public function getStaffInfoAttribute()
    {
        $primary = $this->primaryStaff;
        $secondary = $this->secondaryStaff;
        
        return [
            'primary' => [
                'name' => $this->staff_nama1 ?? ($primary->FirstName ?? 'Unknown') . ' ' . ($primary->LastName ?? ''),
                'code' => $this->staff_kod1,
                'user' => $primary
            ],
            'secondary' => [
                'name' => $this->staff_nama2 ?? ($secondary->FirstName ?? '') . ' ' . ($secondary->LastName ?? ''),
                'code' => $this->staff_kod2,
                'user' => $secondary
            ]
        ];
    }

    /**
     * Get vehicle capacity information
     */
    public function getCapacityInfoAttribute()
    {
        return [
            'weight_no_load' => $this->wt_noload ? number_format($this->wt_noload, 2) . ' tons' : 'Not specified',
            'weight_with_load' => $this->wt_load ? number_format($this->wt_load, 2) . ' tons' : 'Not specified',
            'cargo_capacity' => $this->wt_load && $this->wt_noload ? 
                number_format($this->wt_load - $this->wt_noload, 2) . ' tons' : 'Not calculated'
        ];
    }

    /**
     * Get operational distance
     */
    public function getFormattedOperationalDistanceAttribute()
    {
        if (!$this->jarak_operasi) return 'Not specified';
        
        return number_format($this->jarak_operasi) . ' KM';
    }

    /**
     * Get vehicle model information
     */
    public function getModelInfoAttribute()
    {
        return $this->ModelID ?? 'Model not specified';
    }

    /**
     * Get cost center description
     */
    public function getCostCenterInfoAttribute()
    {
        return [
            'code' => $this->cc_code,
            'description' => $this->cc_desc ?? 'Not specified'
        ];
    }

    // ========================================
    // SCOPES (Query Helpers)
    // ========================================

    /**
     * Get only active vehicles
     */
    public function scopeActive($query)
    {
        return $query->where('Status', 1);
    }

    /**
     * Get vehicles under maintenance
     */
    public function scopeUnderMaintenance($query)
    {
        return $query->where(function($q) {
            $q->where('UnderMaintenance', true)
              ->orWhere('Status', 3);
        });
    }

    /**
     * Get vehicles by depot
     */
    public function scopeByDepot($query, $depotCode)
    {
        return $query->where('depot_kod', $depotCode);
    }

    /**
     * Get vehicles by model
     */
    public function scopeByModel($query, $modelId)
    {
        return $query->where('ModelID', $modelId);
    }

    /**
     * Search vehicles by registration
     */
    public function scopeSearchRegistration($query, $search)
    {
        return $query->whereRaw('UPPER(vh_regno) LIKE ?', ['%' . strtoupper($search) . '%']);
    }

    // ========================================
    // BUSINESS LOGIC METHODS
    // ========================================

    /**
     * Get maintenance statistics for this vehicle
     */
    public function getMaintenanceStats()
    {
        $allServices = $this->serviceRequests()->withValidDates()->count();
        $maintenanceServices = $this->maintenanceRequests()->withValidDates()->count();
        $cleaningServices = $this->cleaningRequests()->withValidDates()->count();
        $tireServices = $this->tireServices()->withValidDates()->count();
        
        $lastService = $this->serviceRequests()
                           ->withValidDates()
                           ->orderByRecent()
                           ->first();
        
        $lastMaintenance = $this->maintenanceRequests()
                               ->withValidDates()
                               ->orderByRecent()
                               ->first();
        
        return [
            'total_services' => $allServices,
            'maintenance_count' => $maintenanceServices,
            'cleaning_count' => $cleaningServices,
            'tire_services_count' => $tireServices,
            'last_service' => $lastService,
            'last_maintenance' => $lastMaintenance,
            'days_since_last_service' => $lastService ? 
                Carbon::parse($lastService->Datereceived)->diffInDays(now()) : null,
            'days_since_last_maintenance' => $lastMaintenance ? 
                Carbon::parse($lastMaintenance->Datereceived)->diffInDays(now()) : null,
        ];
    }

    /**
     * Calculate average maintenance interval
     */
    public function getAverageMaintenanceInterval()
    {
        $maintenanceRecords = $this->maintenanceRequests()
                                  ->withValidDates()
                                  ->withOdometer()
                                  ->orderBy('Datereceived')
                                  ->get();
        
        if ($maintenanceRecords->count() < 2) return null;
        
        $intervals = [];
        for ($i = 1; $i < $maintenanceRecords->count(); $i++) {
            $prevOdometer = floatval($maintenanceRecords[$i-1]->Odometer);
            $currentOdometer = floatval($maintenanceRecords[$i]->Odometer);
            
            if ($currentOdometer > $prevOdometer) {
                $intervals[] = $currentOdometer - $prevOdometer;
            }
        }
        
        return count($intervals) > 0 ? array_sum($intervals) / count($intervals) : null;
    }

    /**
     * Get service frequency (services per month)
     */
    public function getServiceFrequency()
    {
        $services = $this->maintenanceRequests()->withValidDates()->get();
        
        if ($services->isEmpty()) return 0;
        
        $oldest = $services->min('Datereceived');
        $newest = $services->max('Datereceived');
        
        if (!$oldest || !$newest) return 0;
        
        $monthsSpan = max(1, Carbon::parse($oldest)->diffInMonths(Carbon::parse($newest)));
        
        return round($services->count() / $monthsSpan, 2);
    }

    /**
     * Check if vehicle needs maintenance based on patterns
     */
    public function needsMaintenance($currentMileage = null)
    {
        $stats = $this->getMaintenanceStats();
        
        // No service history
        if ($stats['total_services'] === 0) {
            return ['needs' => false, 'reason' => 'No service history available'];
        }
        
        // Very frequent services (possible issue)
        if ($this->getServiceFrequency() > 10) {
            return ['needs' => true, 'reason' => 'High maintenance frequency detected'];
        }
        
        // Long time since last maintenance
        if ($stats['days_since_last_maintenance'] && $stats['days_since_last_maintenance'] > 90) {
            return ['needs' => true, 'reason' => 'Over 90 days since last maintenance'];
        }
        
        // Mileage-based check
        if ($currentMileage && $stats['last_maintenance']) {
            $lastOdometer = floatval($stats['last_maintenance']->Odometer ?? 0);
            $kmSinceService = $currentMileage - $lastOdometer;
            
            if ($kmSinceService > 15000) {
                return ['needs' => true, 'reason' => 'Over 15,000 KM since last maintenance'];
            }
        }
        
        return ['needs' => false, 'reason' => 'Vehicle maintenance is up to date'];
    }

    /**
     * Get vehicle usage pattern
     */
    public function getUsagePattern()
    {
        $frequency = $this->getServiceFrequency();
        
        if ($frequency > 10) return 'Heavy Commercial';
        if ($frequency > 5) return 'Commercial';
        if ($frequency > 2) return 'Regular';
        return 'Light';
    }

    /**
     * Check if documents are expiring soon
     */
    public function getExpiringDocuments($withinDays = 30)
    {
        $expiring = [];
        $checkDate = now()->addDays($withinDays);
        
        if ($this->vh_rtax_expdte && Carbon::parse($this->vh_rtax_expdte)->lte($checkDate)) {
            $expiring[] = [
                'document' => 'Road Tax',
                'expiry_date' => $this->vh_rtax_expdte,
                'days_to_expiry' => Carbon::parse($this->vh_rtax_expdte)->diffInDays(now(), false)
            ];
        }
        
        if ($this->insurance_exp && Carbon::parse($this->insurance_exp)->lte($checkDate)) {
            $expiring[] = [
                'document' => 'Insurance',
                'expiry_date' => $this->insurance_exp,
                'days_to_expiry' => Carbon::parse($this->insurance_exp)->diffInDays(now(), false)
            ];
        }
        
        if ($this->permit_expdte && Carbon::parse($this->permit_expdte)->lte($checkDate)) {
            $expiring[] = [
                'document' => 'Permit',
                'expiry_date' => $this->permit_expdte,
                'days_to_expiry' => Carbon::parse($this->permit_expdte)->diffInDays(now(), false)
            ];
        }
        
        return $expiring;
    }
}