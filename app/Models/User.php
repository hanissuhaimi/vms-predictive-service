<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ========================================
 * USER MODEL
 * ========================================
 */

class User extends Model
{
    use HasFactory;

    protected $table = 'Users';
    protected $primaryKey = 'UID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'UID', 'FirstName', 'LastName', 'NRIC', 'Address1', 'Address2',
        'Postcode', 'City', 'State', 'Country', 'Telephone', 'Extension',
        'Fax', 'MobilePhone', 'Designation', 'HourRate', 'DateCreated',
        'COID', 'Company', 'StaffNo', 'Department', 'Qualification',
        'DateJoined', 'HOD', 'PrevCompany', 'Status', 'DateResign',
        'Active', 'Email1', 'Email2', 'StatusEIS', 'rolecode'
    ];

    protected $casts = [
        'DateCreated' => 'date',
        'DateJoined' => 'date',
        'DateResign' => 'date',
        'HourRate' => 'float',
        'Active' => 'boolean',
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get vehicles assigned as primary staff
     */
    public function primaryVehicles()
    {
        return $this->hasMany(VehicleProfile::class, 'staff_kod1', 'UID');
    }

    /**
     * Get vehicles assigned as secondary staff
     */
    public function secondaryVehicles()
    {
        return $this->hasMany(VehicleProfile::class, 'staff_kod2', 'UID');
    }

    /**
     * Get all vehicles assigned to this user
     */
    public function allVehicles()
    {
        return VehicleProfile::where('staff_kod1', $this->UID)
                            ->orWhere('staff_kod2', $this->UID);
    }

    /**
     * Get service requests created by this user
     */
    public function requestedServices()
    {
        return $this->hasMany(ServiceRequest::class, 'Requestor', 'UID');
    }

    /**
     * Get service requests responded by this user
     */
    public function respondedServices()
    {
        return $this->hasMany(ServiceRequest::class, 'responsedBy', 'UID');
    }

    /**
     * Get service requests inspected by this user
     */
    public function inspectedServices()
    {
        return $this->hasMany(ServiceRequest::class, 'InspectBy', 'UID');
    }

    /**
     * Get depot information
     */
    public function depot()
    {
        return $this->belongsTo(Depot::class, 'COID', 'depot_kod');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get full name
     */
    public function getFullNameAttribute()
    {
        return trim(($this->FirstName ?? '') . ' ' . ($this->LastName ?? ''));
    }

    /**
     * Get formatted contact information
     */
    public function getContactInfoAttribute()
    {
        return [
            'phone' => $this->Telephone,
            'mobile' => $this->MobilePhone,
            'email' => $this->Email1 ?? $this->Email2,
            'fax' => $this->Fax
        ];
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddressAttribute()
    {
        $address = [];
        
        if ($this->Address1) $address[] = $this->Address1;
        if ($this->Address2) $address[] = $this->Address2;
        if ($this->Postcode) $address[] = $this->Postcode;
        if ($this->City) $address[] = $this->City;
        if ($this->State) $address[] = $this->State;
        
        return implode(', ', $address);
    }

    /**
     * Check if user is active
     */
    public function getIsActiveAttribute()
    {
        return $this->Active === true && $this->Status !== 'RESIGNED';
    }

    /**
     * Get user role description
     */
    public function getRoleDescriptionAttribute()
    {
        $roles = [
            1 => 'Administrator',
            2 => 'Manager',
            3 => 'Supervisor',
            4 => 'Technician',
            5 => 'Driver',
            6 => 'Clerk'
        ];
        
        return $roles[$this->rolecode] ?? $this->Designation ?? 'Staff';
    }

    /**
     * Get depot information
     */
    public function getDepotInfoAttribute()
    {
        if ($this->depot) {
            return $this->depot->formatted_info;
        }
        
        return [
            'code' => $this->COID ?? 'Unknown',
            'name' => 'Unknown Depot',
            'short_name' => $this->COID ?? 'UNK'
        ];
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Get active users only
     */
    public function scopeActive($query)
    {
        return $query->where('Active', true)
                    ->where('Status', '!=', 'RESIGNED');
    }

    /**
     * Search users by name
     */
    public function scopeSearchName($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->whereRaw('LOWER(FirstName) LIKE ?', ['%' . strtolower($search) . '%'])
              ->orWhereRaw('LOWER(LastName) LIKE ?', ['%' . strtolower($search) . '%'])
              ->orWhereRaw('LOWER(CONCAT(FirstName, " ", LastName)) LIKE ?', ['%' . strtolower($search) . '%']);
        });
    }

    /**
     * Filter by depot
     */
    public function scopeByDepot($query, $depotCode)
    {
        return $query->where('COID', $depotCode);
    }

    /**
     * Filter by role
     */
    public function scopeByRole($query, $roleCode)
    {
        return $query->where('rolecode', $roleCode);
    }

    /**
     * Filter by designation
     */
    public function scopeByDesignation($query, $designation)
    {
        return $query->whereRaw('LOWER(Designation) LIKE ?', ['%' . strtolower($designation) . '%']);
    }

    // ========================================
    // BUSINESS LOGIC
    // ========================================

    /**
     * Get user performance statistics
     */
    public function getPerformanceStats()
    {
        return [
            'vehicles_assigned' => $this->allVehicles()->count(),
            'services_requested' => $this->requestedServices()->count(),
            'services_responded' => $this->respondedServices()->count(),
            'services_inspected' => $this->inspectedServices()->count(),
            'recent_activity' => $this->respondedServices()->recent(30)->count()
        ];
    }

    /**
     * Check if user has permission for action
     */
    public function hasPermission($action)
    {
        // Basic permission check based on role
        $permissions = [
            1 => ['create', 'read', 'update', 'delete', 'approve'], // Admin
            2 => ['create', 'read', 'update', 'approve'],           // Manager
            3 => ['create', 'read', 'update'],                     // Supervisor
            4 => ['read', 'update'],                               // Technician
            5 => ['read'],                                         // Driver
            6 => ['create', 'read']                                // Clerk
        ];
        
        $userPermissions = $permissions[$this->rolecode] ?? ['read'];
        
        return in_array($action, $userPermissions);
    }

    /**
     * Get user workload (vehicles assigned)
     */
    public function getWorkloadInfo()
    {
        $primaryVehicles = $this->primaryVehicles()->active()->count();
        $secondaryVehicles = $this->secondaryVehicles()->active()->count();
        
        return [
            'primary_vehicles' => $primaryVehicles,
            'secondary_vehicles' => $secondaryVehicles,
            'total_vehicles' => $primaryVehicles + $secondaryVehicles,
            'workload_level' => $this->calculateWorkloadLevel($primaryVehicles + $secondaryVehicles)
        ];
    }

    /**
     * Calculate workload level
     */
    private function calculateWorkloadLevel($vehicleCount)
    {
        if ($vehicleCount >= 20) return 'Heavy';
        if ($vehicleCount >= 10) return 'Moderate';
        if ($vehicleCount >= 5) return 'Light';
        return 'Minimal';
    }
}