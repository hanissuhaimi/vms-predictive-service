<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'dept_kod' => 'integer',
        'depot_kod' => 'integer',
        'Status' => 'integer',
        'UnderMaintenance' => 'boolean',
    ];

    // Relationship with ServiceRequest
    public function serviceRequests()
    {
        return $this->hasMany(ServiceRequest::class, 'Vehicle', 'vh_regno');
    }

    // Scope for active vehicles
    public function scopeActive($query)
    {
        return $query->where('Status', 1);
    }

    // Scope for vehicles not under maintenance
    public function scopeNotUnderMaintenance($query)
    {
        return $query->where(function($q) {
            $q->where('UnderMaintenance', '!=', 1)
              ->orWhereNull('UnderMaintenance');
        });
    }

    // Get status text
    public function getStatusTextAttribute()
    {
        return $this->Status == 1 ? 'Active' : 'Inactive';
    }

    // Get depot description
    public function getDepotDescriptionAttribute()
    {
        $depots = [
            40100 => 'Shah Alam',
            40200 => 'Port Klang',
            40300 => 'Setia Alam',
            40400 => 'Trolak',
            40500 => 'Port Gudang',
            40600 => 'Kuantan',
            40910 => 'Sahabat',
        ];
        
        return $depots[$this->depot_kod] ?? 'Unknown Depot';
    }
}