<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class MaintenanceHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle_number' => [
                'required',
                'string',
                'exists:Vehicle_profile,vh_regno'
            ],
            'mr_type' => 'nullable|integer|in:1,2,3,4,5',
            'status' => 'nullable|integer|in:0,1,2,3',
            'priority' => 'nullable|integer|in:1,2,3',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from|before_or_equal:today',
            'limit' => 'nullable|integer|min:10|max:1000',
            'export_format' => 'nullable|string|in:json,excel,pdf'
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle_number.exists' => 'Vehicle not found in the system.',
            'mr_type.in' => 'Invalid maintenance type.',
            'status.in' => 'Invalid status code.',
            'priority.in' => 'Invalid priority level.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'date_to.before_or_equal' => 'End date cannot be in the future.',
            'limit.max' => 'Maximum 1000 records can be retrieved at once.',
            'export_format.in' => 'Export format must be json, excel, or pdf.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'vehicle_number' => strtoupper(trim($this->vehicle_number ?? '')),
            'limit' => is_numeric($this->limit) ? min(1000, max(10, (int) $this->limit)) : 100
        ]);
    }
}