<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class ServiceTrendsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vehicle' => 'required|string|exists:Vehicle_profile,vh_regno',
            'period' => 'nullable|string|in:daily,weekly,monthly,yearly',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from|before_or_equal:today',
            'include_cleaning' => 'nullable|boolean',
            'mr_types' => 'nullable|array',
            'mr_types.*' => 'integer|in:1,2,3,4,5'
        ];
    }

    public function messages(): array
    {
        return [
            'vehicle.required' => 'Vehicle number is required.',
            'vehicle.exists' => 'Vehicle not found in the system.',
            'period.in' => 'Period must be one of: daily, weekly, monthly, yearly.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'mr_types.*.in' => 'Invalid maintenance type in the list.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'vehicle' => strtoupper(trim($this->vehicle ?? '')),
            'period' => $this->period ?? 'monthly',
            'include_cleaning' => $this->boolean('include_cleaning', true)
        ]);
    }
}