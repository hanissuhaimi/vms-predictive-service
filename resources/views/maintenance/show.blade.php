@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg p-4">
        <div class="card-body text-center">
            <div class="mb-4">
                <h2 class="text-success"><i class="bi bi-check-circle-fill"></i> Maintenance Request Submitted</h2>
                <p class="text-muted">Your request has been saved successfully.</p>
            </div>

            <div class="text-start mx-auto" style="max-width: 500px;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>🔧 Issue Type:</strong> {{ $request->issue_type }}
                    </li>
                    <li class="list-group-item">
                        <strong>📍 Odometer:</strong> {{ $request->odometer }}
                    </li>
                    <li class="list-group-item">
                        <strong>📝 Description:</strong> {{ $request->description ?? 'N/A' }}
                    </li>
                    <li class="list-group-item">
                        <strong>📅 Preferred Date:</strong> {{ $request->preferred_date }}
                    </li>
                    <li class="list-group-item">
                        <strong>🎯 Confidence:</strong> {{ $request->confidence ?? 'N/A' }}
                    </li>
                    <li class="list-group-item">
                        <strong>💰 Estimated Cost:</strong> {{ $request->estimated_cost ?? 'N/A' }}
                    </li>
                    <li class="list-group-item">
                        <strong>⏱️ Time Needed:</strong> {{ $request->time_needed ?? 'N/A' }}
                    </li>
                </ul>
            </div>

            <div class="mt-4">
                <a href="{{ route('prediction.index') }}" class="btn btn-primary btn-lg">
                    🔁 Make Another Prediction
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
