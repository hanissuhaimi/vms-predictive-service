<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from|before_or_equal:today',
            'depot' => 'nullable|string|exists:Depot,depot_kod',
            'mr_type' => 'nullable|integer|in:1,2,3,4,5',
            'period' => 'nullable|string|in:daily,weekly,monthly,yearly',
            'vehicle' => 'nullable|string|exists:Vehicle_profile,vh_regno',
            'format' => 'nullable|string|in:json,chart,table'
        ];
    }

    public function messages(): array
    {
        return [
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'date_to.before_or_equal' => 'End date cannot be in the future.',
            'depot.exists' => 'The selected depot does not exist.',
            'mr_type.in' => 'Invalid maintenance type.',
            'period.in' => 'Period must be one of: daily, weekly, monthly, yearly.',
            'vehicle.exists' => 'The selected vehicle does not exist.',
            'format.in' => 'Format must be one of: json, chart, table.'
        ];
    }

    protected function prepareForValidation()
    {
        // Set default date range if not provided (last 6 months)
        if (!$this->has('date_from') && !$this->has('date_to')) {
            $this->merge([
                'date_from' => now()->subMonths(6)->startOfMonth()->toDateString(),
                'date_to' => now()->endOfMonth()->toDateString()
            ]);
        }

        // Clean vehicle number
        if ($this->has('vehicle')) {
            $this->merge([
                'vehicle' => strtoupper(trim($this->vehicle))
            ]);
        }
    }

    /**
     * Get validated date range
     */
    public function getDateRange(): array
    {
        return [
            'start' => $this->validated('date_from') ?? now()->subMonths(6)->startOfMonth()->toDateString(),
            'end' => $this->validated('date_to') ?? now()->endOfMonth()->toDateString()
        ];
    }
}