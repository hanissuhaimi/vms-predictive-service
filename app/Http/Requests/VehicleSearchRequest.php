<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\VehicleProfile;
use App\Models\ServiceRequest;

class VehicleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:50',
            'depot' => 'nullable|string|exists:Depot,depot_kod',
            'status' => 'nullable|string|in:active,maintenance,inactive,all',
            'model' => 'nullable|string|max:50',
            'staff' => 'nullable|string|exists:Users,UID',
            'per_page' => 'nullable|integer|min:10|max:100'
        ];
    }

    public function messages(): array
    {
        return [
            'depot.exists' => 'The selected depot does not exist.',
            'status.in' => 'Invalid status. Must be one of: active, maintenance, inactive, all.',
            'staff.exists' => 'The selected staff member does not exist.',
            'per_page.between' => 'Items per page must be between 10 and 100.'
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'search' => trim($this->search ?? ''),
            'per_page' => is_numeric($this->per_page) ? min(100, max(10, (int) $this->per_page)) : 20
        ]);
    }
}