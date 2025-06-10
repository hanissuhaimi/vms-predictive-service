@extends('layouts.app')

@section('title', 'Prediction Result')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <!-- Main Result Card -->
        <div class="result-card mb-4">
            <h2 class="text-primary mb-2">{{ $analysis['solution'] ?? 'Vehicle Analysis Complete' }}</h2>
            <h4 class="text-secondary">{{ $analysis['action'] ?? 'Recommendation provided' }}</h4>
        </div>

        <!-- Key Information Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="info-card time-card">
                    <h5 class="text-warning"><i class="fas fa-clock"></i> Time Needed</h5>
                    <h3 class="text-dark">{{ $analysis['time_needed'] ?? '1 day' }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card cost-card">
                    <h5 class="text-success"><i class="fas fa-dollar-sign"></i> Estimated Cost</h5>
                    <h3 class="text-dark">{{ $analysis['cost_estimate'] ?? 'RM 200 - RM 500' }}</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card service-card">
                    <h5 class="text-info"><i class="fas fa-wrench"></i> Next Service</h5>
                    @php
                        $nextServiceKm = (floor($requestData['odometer'] / 10000) + 1) * 10000;
                        $kmUntilService = $nextServiceKm - $requestData['odometer'];
                    @endphp
                    <h3 class="text-dark">{{ number_format($kmUntilService) }} KM</h3>
                    <small>At {{ number_format($nextServiceKm) }} KM</small>
                </div>
            </div>
        </div>

        <!-- Urgency Alert -->
        <div class="mb-4">
            @if(in_array($result['prediction'], ['brake_system', 'engine']) || str_contains(strtolower($requestData['description']), 'emergency'))
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> URGENT</h5>
                    <p class="mb-0">This is a safety issue. Get it fixed immediately before driving!</p>
                </div>
            @elseif(in_array($result['prediction'], ['tire', 'mechanical']))
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation"></i> IMPORTANT</h5>
                    <p class="mb-0">Schedule repair soon to avoid further damage.</p>
                </div>
            @else
                <div class="alert alert-info">
                    <h5><i class="fas fa-check"></i> ROUTINE</h5>
                    <p class="mb-0">Can be scheduled at your convenience.</p>
                </div>
            @endif
        </div>

        <!-- Next Steps -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-list"></i> Next Steps</h5>
            </div>
            <div class="card-body">
                @if($result['prediction'] == 'cleaning')
                    <ol>
                        <li>🚿 Go to vehicle wash bay</li>
                        <li>🧽 Request interior and exterior cleaning</li>
                        <li>✅ Inspect vehicle after cleaning</li>
                    </ol>
                @elseif(in_array($result['prediction'], ['brake_system', 'engine']))
                    <ol>
                        <li>🚨 Stop driving if unsafe</li>
                        <li>📞 Call workshop immediately</li>
                        <li>🔧 Schedule emergency repair</li>
                    </ol>
                @elseif($result['prediction'] == 'tire')
                    <ol>
                        <li>🛞 Check tire condition and pressure</li>
                        <li>🔧 Replace if damaged or worn</li>
                        <li>⚖️ Check wheel alignment</li>
                    </ol>
                @else
                    <ol>
                        <li>📞 Call workshop to book appointment</li>
                        <li>🚗 Bring vehicle for inspection</li>
                        <li>✅ Follow mechanic's recommendations</li>
                    </ol>
                @endif
            </div>
        </div>



        <!-- Action Buttons -->
        <div class="text-center">
            <a href="{{ route('prediction.index') }}" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-plus"></i> New Prediction
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-lg">
                <i class="fas fa-print"></i> Print Result
            </button>
        </div>
    </div>
</div>
@endsection