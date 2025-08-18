<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// ========================================
// DEPOT MODEL
// ========================================

class Depot extends Model
{
    use HasFactory;

    protected $table = 'Depot';
    protected $primaryKey = 'depot_kod';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'depot_kod', 'depot_nama', 'address', 'tel', 'fax', 'SF',
        'fas_siteID', 'plantCode', 'plantCodeDesc', 'purGrp', 'purGrpDesc'
    ];

    // ========================================
    // RELATIONSHIPS
    // ========================================

    /**
     * Get all vehicles assigned to this depot
     */
    public function vehicles()
    {
        return $this->hasMany(VehicleProfile::class, 'depot_kod', 'depot_kod');
    }

    /**
     * Get active vehicles only
     */
    public function activeVehicles()
    {
        return $this->hasMany(VehicleProfile::class, 'depot_kod', 'depot_kod')
                    ->active();
    }

    /**
     * Get service requests from this depot
     */
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'Building', 'depot_kod');
    }

    /**
     * Get users assigned to this depot
     */
    public function users()
    {
        return $this->hasMany(User::class, 'COID', 'depot_kod');
    }

    // ========================================
    // ACCESSORS
    // ========================================

    /**
     * Get formatted depot information
     */
    public function getFormattedInfoAttribute()
    {
        return [
            'code' => $this->depot_kod,
            'name' => $this->depot_nama,
            'short_name' => $this->SF ?? $this->depot_kod,
            'address' => $this->address ?? 'Address not available',
            'contact' => [
                'phone' => $this->tel,
                'fax' => $this->fax
            ]
        ];
    }

    /**
     * Get depot location type
     */
    public function getLocationTypeAttribute()
    {
        $name = strtolower($this->depot_nama ?? '');
        
        if (str_contains($name, 'hq') || str_contains($name, 'headquarters')) {
            return 'Headquarters';
        } elseif (str_contains($name, 'kurier') || str_contains($name, 'courier')) {
            return 'Courier Station';
        } elseif (str_contains($name, 'depot')) {
            return 'Depot';
        } elseif (str_contains($name, 'pejabat') || str_contains($name, 'office')) {
            return 'Office';
        }
        
        return 'Branch';
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Search by depot name or code
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->whereRaw('LOWER(depot_nama) LIKE ?', ['%' . strtolower($search) . '%'])
              ->orWhereRaw('LOWER(depot_kod) LIKE ?', ['%' . strtolower($search) . '%'])
              ->orWhereRaw('LOWER(SF) LIKE ?', ['%' . strtolower($search) . '%']);
        });
    }

    /**
     * Get depots by region/state
     */
    public function scopeByRegion($query, $region)
    {
        return $query->whereRaw('LOWER(depot_nama) LIKE ?', ['%' . strtolower($region) . '%']);
    }

    // ========================================
    // BUSINESS LOGIC
    // ========================================

    /**
     * Get depot statistics
     */
    public function getDepotStats()
    {
        return [
            'total_vehicles' => $this->vehicles()->count(),
            'active_vehicles' => $this->activeVehicles()->count(),
            'vehicles_under_maintenance' => $this->vehicles()->underMaintenance()->count(),
            'total_service_requests' => $this->serviceRequests()->count(),
            'recent_service_requests' => $this->serviceRequests()->recent(30)->count(),
            'staff_count' => $this->users()->count()
        ];
    }
}