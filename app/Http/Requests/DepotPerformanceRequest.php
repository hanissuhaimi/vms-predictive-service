<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class DepotPerformanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'depot_code' => 'required|string|exists:Depot,depot_kod',
            'date_from' => 'nullable|date|before_or_equal:date_to',
            'date_to' => 'nullable|date|after_or_equal:date_from|before_or_equal:today',
            'include_metrics' => 'nullable|array',
            'include_metrics.*' => 'string|in:response_time,completion_rate,service_volume,trends',
            'format' => 'nullable|string|in:json,report,summary'
        ];
    }

    public function messages(): array
    {
        return [
            'depot_code.required' => 'Depot code is required.',
            'depot_code.exists' => 'The selected depot does not exist.',
            'date_from.before_or_equal' => 'Start date must be before or equal to end date.',
            'date_to.after_or_equal' => 'End date must be after or equal to start date.',
            'include_metrics.*.in' => 'Invalid metric type.',
            'format.in' => 'Format must be one of: json, report, summary.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'depot_code' => strtoupper(trim($this->depot_code ?? '')),
            'format' => $this->format ?? 'json'
        ]);

        // Default metrics if not specified
        if (!$this->has('include_metrics')) {
            $this->merge([
                'include_metrics' => ['response_time', 'completion_rate', 'service_volume']
            ]);
        }
    }
}