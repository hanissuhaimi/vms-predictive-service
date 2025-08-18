<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class PredictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request
     */
    public function authorize(): bool
    {
        return true; // Adjust based on your authentication needs
    }

    /**
     * Get the validation rules
     */
    public function rules(): array
    {
        return [
            'vehicle_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9]+$/', // Only alphanumeric
                function ($attribute, $value, $fail) {
                    $vehicle = VehicleProfile::where('vh_regno', strtoupper(trim($value)))->first();
                    
                    if (!$vehicle) {
                        $fail("Vehicle {$value} not found in the system.");
                        return;
                    }
                    
                    if (!$vehicle->is_active) {
                        $fail("Vehicle {$value} is not active or is under maintenance.");
                        return;
                    }
                }
            ],
            'current_mileage' => [
                'required',
                'integer',
                'min:0',
                'max:10000000',
                function ($attribute, $value, $fail) {
                    if (!$this->has('vehicle_number')) return;
                    
                    $vehicleNumber = strtoupper(trim($this->input('vehicle_number')));
                    
                    // Check against last service mileage
                    $lastService = ServiceRequest::forVehicle($vehicleNumber)
                                                ->withOdometer()
                                                ->orderByRecent()
                                                ->first();
                    
                    if ($lastService) {
                        $lastMileage = floatval($lastService->Odometer);
                        
                        if ($value < $lastMileage) {
                            $fail("Current mileage ({$value} KM) cannot be less than last service mileage (" . number_format($lastMileage) . " KM).");
                            return;
                        }
                        
                        // Check for unrealistic mileage increase
                        $daysSinceService = now()->diffInDays($lastService->Datereceived);
                        $mileageDiff = $value - $lastMileage;
                        
                        if ($daysSinceService > 0 && ($mileageDiff / $daysSinceService) > 1000) {
                            $fail("Mileage increase seems unrealistic (+" . number_format($mileageDiff) . " KM in {$daysSinceService} days). Please verify the odometer reading.");
                        }
                    }
                }
            ]
        ];
    }

    /**
     * Get custom messages for validator errors
     */
    public function messages(): array
    {
        return [
            'vehicle_number.required' => 'Vehicle number is required.',
            'vehicle_number.regex' => 'Vehicle number must contain only letters and numbers.',
            'current_mileage.required' => 'Current mileage is required.',
            'current_mileage.integer' => 'Mileage must be a whole number.',
            'current_mileage.min' => 'Mileage cannot be negative.',
            'current_mileage.max' => 'Mileage cannot exceed 10,000,000 KM.',
        ];
    }

    /**
     * Get custom attributes for validator errors
     */
    public function attributes(): array
    {
        return [
            'vehicle_number' => 'vehicle registration number',
            'current_mileage' => 'current vehicle mileage'
        ];
    }

    /**
     * Configure the validator instance
     */
    public function withValidator($validator)
    {
        $validator->sometimes('current_mileage', 'required|integer|min:0', function ($input) {
            return !empty($input->vehicle_number);
        });
    }

    /**
     * Prepare the data for validation
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'vehicle_number' => strtoupper(trim($this->vehicle_number ?? '')),
            'current_mileage' => is_numeric($this->current_mileage) ? (int) $this->current_mileage : null
        ]);
    }
}