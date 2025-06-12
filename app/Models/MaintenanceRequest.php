<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'issue_type',
        'odometer',
        'description',
        'preferred_date',
        'confidence',
        'estimated_cost',
        'time_needed',
    ];
}
