@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h2 class="mb-4">🛠 Maintenance Request</h2>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>⚠ Whoops!</strong> Please fix the following errors:<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('maintenance.store') }}">
        @csrf

        <div class="mb-3">
            <label for="issue_type" class="form-label">Issue Type (Predicted)</label>
            <input type="text" name="issue_type" class="form-control" id="issue_type" 
                   value="{{ old('issue_type', $prediction ?? 'General Maintenance') }}" required>
        </div>

        <div class="mb-3">
            <label for="odometer" class="form-label">Odometer Reading (km)</label>
            <input type="number" name="odometer" class="form-control" id="odometer" 
                   value="{{ old('odometer', $odometer ?? 150000) }}" required min="0">
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Issue Description</label>
            <textarea name="description" id="description" class="form-control" rows="4" 
                      placeholder="Describe the vehicle issue or maintenance needed...">{{ old('description', $description ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="preferred_date" class="form-label">Preferred Service Date</label>
            <input type="date" name="preferred_date" class="form-control" id="preferred_date" 
                   value="{{ old('preferred_date', now()->addDay()->format('Y-m-d')) }}" required>
        </div>

        <div class="mb-3">
            <label for="number_plate" class="form-label">Vehicle Number Plate</label>
            <input type="text" name="number_plate" class="form-control" id="number_plate" 
                   value="{{ old('number_plate', $number_plate ?? '') }}" 
                   placeholder="e.g., WGW1349">
        </div>

        <div class="mb-3">
            <label for="priority" class="form-label">Priority Level</label>
            <select name="priority" class="form-control" id="priority">
                <option value="1" {{ old('priority', $priority ?? 1) == 1 ? 'selected' : '' }}>🔴 Critical - Emergency</option>
                <option value="2" {{ old('priority', $priority ?? 1) == 2 ? 'selected' : '' }}>🟠 High - Important</option>
                <option value="3" {{ old('priority', $priority ?? 1) == 3 ? 'selected' : '' }}>🟡 Normal - Standard</option>
                <option value="4" {{ old('priority', $priority ?? 1) == 4 ? 'selected' : '' }}>⚪ Low - Routine</option>
            </select>
        </div>

        {{-- Hidden fields for ML prediction data (if available) --}}
        @if(isset($confidence))
            <input type="hidden" name="confidence" value="{{ $confidence }}">
        @endif

        @if(isset($cost_estimate))
            <input type="hidden" name="estimated_cost" value="{{ $cost_estimate }}">
        @endif

        @if(isset($time_needed))
            <input type="hidden" name="time_needed" value="{{ $time_needed }}">
        @endif

        @if(isset($prediction_confidence))
            <input type="hidden" name="prediction_confidence" value="{{ $prediction_confidence }}">
        @endif

        @if(isset($prediction_category))
            <input type="hidden" name="prediction_category" value="{{ $prediction_category }}">
        @endif

        @if(isset($ml_source))
            <input type="hidden" name="ml_source" value="{{ $ml_source }}">
        @endif

        {{-- Display prediction results if available --}}
        @if(isset($confidence) || isset($cost_estimate) || isset($time_needed))
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-robot"></i> ML Prediction Results</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if(isset($confidence))
                            <div class="col-md-4">
                                <strong>🎯 Confidence:</strong><br>
                                <span class="badge bg-success">{{ $confidence }}</span>
                            </div>
                        @endif
                        @if(isset($cost_estimate))
                            <div class="col-md-4">
                                <strong>💰 Estimated Cost:</strong><br>
                                <span class="text-primary">{{ $cost_estimate }}</span>
                            </div>
                        @endif
                        @if(isset($time_needed))
                            <div class="col-md-4">
                                <strong>⏱️ Time Needed:</strong><br>
                                <span class="text-warning">{{ $time_needed }}</span>
                            </div>
                        @endif
                    </div>
                    @if(isset($prediction_confidence))
                        <div class="mt-2">
                            <small class="text-muted">
                                ML Confidence Score: {{ number_format($prediction_confidence * 100, 1) }}%
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Submit Request
            </button>
            <a href="{{ route('prediction.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Prediction
            </a>
            @if(isset($result) && isset($analysis))
                <a href="javascript:history.back()" class="btn btn-outline-info">
                    <i class="fas fa-chart-line"></i> Back to Results
                </a>
            @endif
        </div>
    </form>
</div>

<script>
// Auto-set minimum date to today
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('preferred_date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
});
</script>
@endsection