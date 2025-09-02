@extends('layouts.app')

@section('title', 'Vehicle Maintenance Prediction')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-car-side"></i> Vehicle Maintenance Prediction</h3>
                <p class="mb-0">Enter vehicle details to predict upcoming maintenance needs</p>
            </div>
            
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('prediction.predict') }}" method="POST">
                    @csrf
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            
                            <!-- Vehicle Number Input -->
                            <div class="mb-4">
                                <label for="vehicle_number" class="form-label">
                                    <i class="fas fa-truck"></i> Vehicle Number Plate
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="vehicle_number" 
                                       name="vehicle_number"
                                       value="{{ old('vehicle_number') }}"
                                       placeholder="Enter vehicle number (e.g., ABC1234)"
                                       required>
                                <div class="form-text">Enter the vehicle registration number</div>
                            </div>
                            
                            <!-- Current Mileage Input -->
                            <div class="mb-4">
                                <label for="current_mileage" class="form-label">
                                    <i class="fas fa-tachometer-alt"></i> Current Mileage (KM)
                                </label>
                                <input type="number" 
                                       class="form-control form-control-lg" 
                                       id="current_mileage" 
                                       name="current_mileage"
                                       value="{{ old('current_mileage') }}"
                                       placeholder="Enter current odometer reading"
                                       min="1"
                                       max="5000000"
                                       required>
                                <div class="form-text">Enter the current odometer reading in kilometers</div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid gap-2 mb-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-search"></i> Predict Maintenance Schedule
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Fleet Analysis Section -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-truck-moving"></i> Fleet Management Tools
                </h5>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="card-title">Analyze All Vehicles</h6>
                        <p class="card-text text-muted mb-2">
                            Get comprehensive insights across your entire fleet including performance trends, 
                            maintenance patterns, and cost analysis.
                        </p>
                        <small class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            View fleet statistics, vehicle rankings, and predictive maintenance alerts
                        </small>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="{{ route('analytics.dashboard') }}" class="btn btn-success btn-lg">
                            <i class="fas fa-chart-line"></i> Fleet Analysis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row justify-content-center mt-4">
    <div class="col-lg-8">
        <div class="card bg-light">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4">
                        <h4 class="text-primary">{{ \App\Models\FleetAnalysis::getTotalVehicles() }}</h4>
                        <small class="text-muted">Total Vehicles in Fleet</small>
                    </div>
                    <div class="col-md-4">
                        <h4 class="text-info">{{ number_format(\App\Models\FleetAnalysis::getTotalMaintenanceOrder()) }}</h4>
                        <small class="text-muted">Total Services Recorded</small>
                    </div>
                    <div class="col-md-4">
                        <h4 class="text-success">{{ \App\Models\FleetAnalysis::calculateFleetHealthScore() }}%</h4>
                        <small class="text-muted">Fleet Health Score</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection