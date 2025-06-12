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
            <input type="text" name="issue_type" class="form-control" id="issue_type" value="{{ old('issue_type', $prediction ?? '') }}" readonly>
        </div>

        <div class="mb-3">
            <label for="odometer" class="form-label">Odometer Reading (km)</label>
            <input type="number" name="odometer" class="form-control" id="odometer" value="{{ old('odometer', $odometer ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label for="description" class="form-label">Issue Description</label>
            <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $description ?? '') }}</textarea>
        </div>

        <div class="mb-3">
            <label for="preferred_date" class="form-label">Preferred Service Date</label>
            <input type="date" name="preferred_date" class="form-control" id="preferred_date" value="{{ old('preferred_date') }}" required>
        </div>

        <!-- Optional fields: hidden or shown based on your design -->

        @if (!empty($confidence))
        <div class="mb-3">
            <label for="confidence" class="form-label">Prediction Confidence</label>
            <input type="text" name="confidence" class="form-control" id="confidence" value="{{ $confidence }}" readonly>
        </div>
        @endif

        @if (!empty($cost_estimate))
        <div class="mb-3">
            <label for="estimated_cost" class="form-label">Estimated Cost (RM)</label>
            <input type="text" name="estimated_cost" class="form-control" id="estimated_cost" value="{{ $cost_estimate }}" readonly>
        </div>
        @endif

        @if (!empty($time_needed))
        <div class="mb-3">
            <label for="time_needed" class="form-label">Estimated Time Required</label>
            <input type="text" name="time_needed" class="form-control" id="time_needed" value="{{ $time_needed }}" readonly>
        </div>
        @endif

        <button type="submit" class="btn btn-primary">Submit Request</button>
        <a href="{{ route('prediction.index') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
