@extends('layouts.app')

@section('title', 'Fleet Analysis Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="fas fa-truck-moving"></i> Fleet Analysis Dashboard</h3>
                            <p class="mb-0">Comprehensive analysis of all vehicles in your fleet</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('prediction.index') }}" class="btn btn-light">
                                <i class="fas fa-arrow-left"></i> Back to Vehicle Prediction
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breadcrumb -->
    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('prediction.index') }}">
                            <i class="fas fa-home"></i> Fleet Prediction
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Fleet Analysis
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Coming Soon Section -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-tools fa-5x text-muted"></i>
                    </div>
                    
                    <h2 class="card-title mb-3">Fleet Analysis Dashboard</h2>
                    <p class="card-text text-muted mb-4">
                        This comprehensive fleet analysis dashboard is currently under development. 
                        Soon you'll be able to analyze all vehicles in your fleet with features including:
                    </p>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Fleet Overview Statistics</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Vehicle Performance Rankings</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Maintenance Cost Analysis</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Service Frequency Patterns</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled text-start">
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Fleet Health Score</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Predictive Maintenance Alerts</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Vehicle Comparison Tools</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Export & Reporting Features</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('fleet.analysis.all') }}" class="btn btn-success btn-lg me-3">
                            <i class="fas fa-chart-bar"></i> Preview Fleet Analysis
                        </a>
                        <a href="{{ route('prediction.index') }}" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Individual Vehicle Analysis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats Preview (Optional) -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Quick Fleet Information</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-primary">{{ \App\Models\FleetAnalysis::getTotalVehicles() }}</h4>
                                <small class="text-muted">Total Vehicles</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-info">{{ number_format(\App\Models\FleetAnalysis::getTotalServices()) }}</h4>
                                <small class="text-muted">Total Services</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success">{{ \App\Models\FleetAnalysis::getAverageServicesPerVehicle() }}</h4>
                                <small class="text-muted">Avg Services/Vehicle</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-warning">{{ \App\Models\FleetAnalysis::calculateFleetHealthScore() }}%</h4>
                            <small class="text-muted">Fleet Health Score</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection