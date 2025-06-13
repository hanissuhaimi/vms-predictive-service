<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    use HasFactory;

    // Specify the table name (matches your existing table)
    protected $table = 'ServiceRequest';
    
    // Primary key configuration
    protected $primaryKey = 'ID';
    public $incrementing = true;
    protected $keyType = 'int';
    
    // Disable Laravel's automatic timestamp management since you have custom fields
    public $timestamps = false;

    // Map to your existing columns
    protected $fillable = [
        'ComplaintNo',       // Complaint/Reference number
        'SR',                // Service Request number
        'Datereceived',      // Date request was received
        'timereceived',      // Time request was received
        'Requestor',         // Person making the request
        'Building',          // Building/location code
        'department',        // Department
        'location',          // Specific location
        'CMType',            // Complaint/Maintenance type
        'Description',       // Issue description
        'responseDate',      // Response date
        'ResponseTime',      // Response time
        'responsedBy',       // Who responded
        'Response',          // Response details
        'Inspection',        // Inspection notes
        'InspectBy',         // Inspector name
        'image1',            // Image path 1
        'image2',            // Image path 2
        'Report',            // Report file path
        'COID',              // Contractor ID
        'Contractor',        // Contractor name
        'Insp_position',     // Inspector position
        'Status',            // Request status
        'DateClose',         // Date closed
        'TimeClose',         // Time closed
        'AggreeBy',          // Agreed by
        'Agg_Position',      // Agreeing person's position
        'QRef',              // Quality reference
        'QDesc',             // Quality description
        'QFile',             // Quality file
        'Staff',             // Staff assigned
        'Priority',          // Priority level
        'Odometer',          // Vehicle odometer reading
        'Vehicle',           // Vehicle number/plate
        'DateModify',        // Last modified date
        'TimeModify',        // Last modified time
        'ModifyBy',          // Modified by
        'TrailerNo',         // Trailer number
        'Driver',            // Driver name/ID
        'Jarak_Operasi',     // Operation distance
        'MrType',            // Maintenance request type
        'ForTrailer',        // Is for trailer (boolean)
    ];

    // Define data type casting
    protected $casts = [
        'ID' => 'integer',
        'Datereceived' => 'datetime',
        'responseDate' => 'datetime', 
        'DateClose' => 'datetime',
        'DateModify' => 'datetime',
        'TimeModify' => 'datetime',
        'Jarak_Operasi' => 'float',
        'ForTrailer' => 'boolean',
    ];

    // Accessor for getting formatted date received
    public function getFormattedDateReceivedAttribute()
    {
        return $this->Datereceived ? $this->Datereceived->format('Y-m-d H:i:s') : null;
    }

    // Accessor for getting priority text
    public function getPriorityTextAttribute()
    {
        $priorities = [
            '1' => 'Critical',
            '2' => 'High', 
            '3' => 'Normal',
            '4' => 'Low'
        ];
        
        return $priorities[$this->Priority] ?? 'Unknown';
    }

    // Accessor for getting status text
    public function getStatusTextAttribute()
    {
        $statuses = [
            '1' => 'Pending',
            '2' => 'In Progress',
            '3' => 'Completed',
            '4' => 'Cancelled'
        ];
        
        return $statuses[$this->Status] ?? 'Unknown';
    }

    // Accessor for MrType text
    public function getMrTypeTextAttribute()
    {
        $mrTypes = [
            '1' => 'Repair',
            '2' => 'Cleaning/Washing', 
            '3' => 'Maintenance',
            '4' => 'Inspection'
        ];
        
        return $mrTypes[trim($this->MrType)] ?? 'Other';
    }

    // Scope for filtering by vehicle
    public function scopeForVehicle($query, $vehicle)
    {
        return $query->where('Vehicle', $vehicle);
    }

    // Scope for filtering by status
    public function scopeByStatus($query, $status)
    {
        return $query->where('Status', $status);
    }

    // Scope for filtering by priority
    public function scopeByPriority($query, $priority)
    {
        return $query->where('Priority', $priority);
    }

    // Scope for recent requests
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('Datereceived', '>=', now()->subDays($days));
    }
}