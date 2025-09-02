@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="card shadow-lg p-4">
        <div class="card-body text-center">
            <div class="mb-4">
                <h2 class="text-success"><i class="bi bi-check-circle-fill"></i> Service Request Submitted Successfully</h2>
                <p class="text-muted">Your service request has been saved to the system.</p>
                <p><strong>Service Request ID:</strong> {{ $request->ID }}</p>
                @if($request->SR)
                    <p><strong>Reference Number:</strong> {{ $request->SR }}</p>
                @endif
            </div>

            <div class="text-start mx-auto" style="max-width: 600px;">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item">
                        <strong>🚗 Vehicle:</strong> {{ $request->Vehicle ?? 'N/A' }}
                        @if($request->Odometer)
                            <span class="badge bg-info ms-2">{{ number_format($request->Odometer) }} KM</span>
                        @endif
                    </li>
                    <li class="list-group-item">
                        <strong>📝 Issue Description:</strong> 
                        <div class="mt-2">{{ $request->Description ?? 'N/A' }}</div>
                    </li>
                    <li class="list-group-item">
                        <strong>📅 Date Received:</strong> 
                        {{ $request->Datereceived ? $request->Datereceived->format('Y-m-d') : 'N/A' }}
                        @if($request->timereceived)
                            at {{ $request->timereceived }}
                        @endif
                    </li>
                    <li class="list-group-item">
                        <strong>👤 Requestor:</strong> {{ $request->Requestor ?? 'N/A' }}
                    </li>
                    <li class="list-group-item">
                        <strong>🎯 Priority:</strong> 
                        <span class="badge bg-{{ $request->Priority == '1' ? 'danger' : ($request->Priority == '2' ? 'warning' : 'info') }}">
                            {{ $request->priority_text }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <strong>📊 Status:</strong> 
                        <span class="badge bg-{{ $request->Status == '3' ? 'success' : ($request->Status == '2' ? 'warning' : 'secondary') }}">
                            {{ $request->status_text }}
                        </span>
                    </li>
                    <li class="list-group-item">
                        <strong>🔧 Request Type:</strong> 
                        <span class="badge bg-primary">{{ $request->mr_type_text }}</span>
                        @if($request->CMType)
                            <span class="badge bg-secondary ms-1">{{ $request->CMType }}</span>
                        @endif
                    </li>
                    @if($request->Building)
                        <li class="list-group-item">
                            <strong>🏢 Location:</strong> 
                            Building {{ $request->Building }}
                            @if($request->location)
                                ({{ $request->location }})
                            @endif
                        </li>
                    @endif
                    @if($request->Staff)
                        <li class="list-group-item">
                            <strong>👥 Assigned Staff:</strong> {{ $request->Staff }}
                        </li>
                    @endif
                    @if($request->Response)
                        <li class="list-group-item">
                            <strong>🤖 ML Prediction Details:</strong>
                            <div class="mt-2">
                                <pre class="bg-light p-2 rounded" style="font-size: 12px;">{{ $request->Response }}</pre>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>

            <div class="mt-4">
                <a href="{{ route('prediction.index') }}" class="btn btn-primary btn-lg">
                    🔁 Make Another Prediction
                </a>
                <a href="{{ route('maintenance.index') }}" class="btn btn-secondary btn-lg ms-2">
                    📋 View All Requests
                </a>
            </div>
        </div>
    </div>
</div>
@endsection