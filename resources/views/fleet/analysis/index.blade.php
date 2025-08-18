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
                            <p class="mb-0">Comprehensive analysis of all vehicles with completed service requests</p>
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

    <!-- Key Analysis Metrics (4 Requirements) - UPDATED with corrected method names -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-3x mb-3"></i>
                    <h2>{{ \App\Models\FleetAnalysis::getTotalVehiclesWithServices() }}</h2>
                    <p class="mb-0"><strong>Total Vehicles with Services</strong></p>
                    <small>Vehicles with completed services</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                    <h2>{{ \App\Models\FleetAnalysis::getVehiclesSkippedMajorService() }}</h2>
                    <p class="mb-0"><strong>Skipped Major Service</strong></p>
                    <small>No major service in 12+ months</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-3x mb-3"></i>
                    <h2>{{ \App\Models\FleetAnalysis::getVehiclesSkippedMinorService() }}</h2>
                    <p class="mb-0"><strong>Skipped Minor Service</strong></p>
                    <small>No minor service in 6+ months</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-pie fa-3x mb-3"></i>
                    <h2>{{ \App\Models\FleetAnalysis::calculateFleetHealthScore() }}%</h2>
                    <p class="mb-0"><strong>Fleet Health Score</strong></p>
                    <small>Overall fleet condition</small>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATED: Service Types Analysis with corrected interpretation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-wrench"></i> Service Types for Frequent Service Vehicles</h5>
                    <small class="text-muted">Percentage breakdown of services performed on vehicles with above-average service frequency</small>
                </div>
                <div class="card-body">
                    @php
                        $breakdownServices = \App\Models\FleetAnalysis::getBreakdownServiceTypes();
                    @endphp
                    
                    @if(!empty($breakdownServices) && is_array($breakdownServices))
                        <div class="row">
                            @foreach($breakdownServices as $serviceType => $data)
                                <div class="col-md-3 mb-3">
                                    <div class="card border-left-primary">
                                        <div class="card-body">
                                            <div class="row no-gutters align-items-center">
                                                <div class="col mr-2">
                                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                        {{ $serviceType }}
                                                    </div>
                                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                        {{ is_array($data) && isset($data['percentage']) ? $data['percentage'] : '0' }}%
                                                    </div>
                                                    <small class="text-muted">
                                                        {{ is_array($data) && isset($data['count']) ? $data['count'] : '0' }} services
                                                    </small>
                                                </div>
                                                <div class="col-auto">
                                                    @switch($serviceType)
                                                        @case('Repair')
                                                            <i class="fas fa-tools fa-2x text-danger"></i>
                                                            @break
                                                        @case('Maintenance')
                                                            <i class="fas fa-cogs fa-2x text-warning"></i>
                                                            @break
                                                        @case('Cleaning/Washing')
                                                            <i class="fas fa-spray-can fa-2x text-info"></i>
                                                            @break
                                                        @case('Inspection')
                                                            <i class="fas fa-search fa-2x text-success"></i>
                                                            @break
                                                        @default
                                                            <i class="fas fa-wrench fa-2x text-secondary"></i>
                                                    @endswitch
                                                </div>
                                            </div>
                                            <!-- Progress bar -->
                                            <div class="progress mt-2" style="height: 5px;">
                                                <div class="progress-bar" role="progressbar" 
                                                    style="width: {{ is_array($data) && isset($data['percentage']) ? $data['percentage'] : 0 }}%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No frequent service data available. This could mean:
                            <ul class="mb-0 mt-2">
                                <li>No vehicles have frequent service requests</li>
                                <li>Insufficient data to determine frequent service patterns</li>
                                <li>All vehicles have similar service frequencies</li>
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATED: Fleet Overview Statistics with corrected method calls and terminology -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Fleet Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-secondary">{{ \App\Models\FleetAnalysis::getTotalActiveVehicles() }}</h4>
                                <small class="text-muted">Total Active Vehicles<br>(With Service Records)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-info">{{ number_format(\App\Models\FleetAnalysis::getTotalServices()) }}</h4>
                                <small class="text-muted">Total Services<br>(Completed)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success">{{ \App\Models\FleetAnalysis::getAverageServicesPerVehicle() }}</h4>
                                <small class="text-muted">Avg Services/Vehicle<br>(Maintenance Only)</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-primary">{{ number_format(\App\Models\FleetAnalysis::getMaintenanceServices()) }}</h4>
                            <small class="text-muted">Maintenance Services<br>(Excl. Cleaning)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-heartbeat"></i> Fleet Health Indicators</h5>
                </div>
                <div class="card-body">
                    @php
                        $healthScore = \App\Models\FleetAnalysis::calculateFleetHealthScore();
                        $majorSkipped = \App\Models\FleetAnalysis::getVehiclesSkippedMajorService();
                        $minorSkipped = \App\Models\FleetAnalysis::getVehiclesSkippedMinorService();
                        $totalActiveVehicles = \App\Models\FleetAnalysis::getTotalActiveVehicles();
                    @endphp
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Overall Health:</span>
                            <strong class="text-{{ $healthScore >= 70 ? 'success' : ($healthScore >= 50 ? 'warning' : 'danger') }}">
                                {{ $healthScore }}%
                            </strong>
                        </div>
                        <div class="progress mt-1">
                            <div class="progress-bar bg-{{ $healthScore >= 70 ? 'success' : ($healthScore >= 50 ? 'warning' : 'danger') }}" 
                                 style="width: {{ $healthScore }}%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Major Service Compliance:</span>
                            <strong>{{ $totalActiveVehicles > 0 ? round((($totalActiveVehicles - $majorSkipped) / $totalActiveVehicles) * 100, 1) : 0 }}%</strong>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <div class="d-flex justify-content-between">
                            <span>Minor Service Compliance:</span>
                            <strong>{{ $totalActiveVehicles > 0 ? round((($totalActiveVehicles - $minorSkipped) / $totalActiveVehicles) * 100, 1) : 0 }}%</strong>
                        </div>
                    </div>
                    
                    @if($majorSkipped > 0 || $minorSkipped > 0)
                        <div class="alert alert-warning mt-3 p-2">
                            <small>
                                <i class="fas fa-exclamation-triangle"></i>
                                {{ $majorSkipped + $minorSkipped }} vehicles need attention
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Additional Fleet Metrics -->
    <div class="row mt-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Service Summary</h5>
                </div>
                <div class="card-body">
                    @php
                        $serviceDistribution = \App\Models\FleetAnalysis::getServiceTypeDistribution();
                    @endphp
                    
                    @if($serviceDistribution && $serviceDistribution->isNotEmpty())
                        <div class="row">
                            @foreach($serviceDistribution as $serviceType => $data)
                                <div class="col-6 mb-3">
                                    <div class="text-center">
                                        <div class="h6 mb-1">{{ $serviceType }}</div>
                                        <div class="h4 text-primary">{{ $data['count'] }}</div>
                                        <small class="text-muted">{{ $data['percentage'] }}%</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> No service distribution data available.
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-calendar-check"></i> Service Intervals</h5>
                </div>
                <div class="card-body">
                    @php
                        $vehiclesNeedingMajor = \App\Models\FleetAnalysis::getVehiclesNeedingMajorService();
                        $vehiclesNeedingMinor = \App\Models\FleetAnalysis::getVehiclesNeedingMinorService();
                    @endphp
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="border-end">
                                <h3 class="text-danger">{{ $vehiclesNeedingMajor->count() }}</h3>
                                <small class="text-muted">Vehicles Needing<br>Major Service</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h3 class="text-warning">{{ $vehiclesNeedingMinor->count() }}</h3>
                            <small class="text-muted">Vehicles Needing<br>Minor Service</small>
                        </div>
                    </div>
                    
                    @if($vehiclesNeedingMajor->count() > 0 || $vehiclesNeedingMinor->count() > 0)
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                Schedule upcoming services to maintain fleet health
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-4">Fleet Analysis Tools</h5>
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('fleet.analysis.all') }}" class="btn btn-success btn-lg w-100">
                                <i class="fas fa-chart-bar"></i><br>
                                <small>Complete Fleet Analysis</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('fleet.analysis.detailed') }}" class="btn btn-info btn-lg w-100">
                                <i class="fas fa-list-alt"></i><br>
                                <small>Detailed Service Analysis</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('fleet.analysis.export') }}?format=csv" class="btn btn-warning btn-lg w-100">
                                <i class="fas fa-download"></i><br>
                                <small>Export to CSV</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="{{ route('prediction.index') }}" class="btn btn-outline-secondary btn-lg w-100">
                                <i class="fas fa-arrow-left"></i><br>
                                <small>Individual Analysis</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- UPDATED: Information Note with corrected terminology -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Analysis Information:</h6>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li><strong>Vehicles with Services:</strong> Only includes vehicles with Status = 2 (Maintenance Order Created)</li>
                            <li><strong>Active Vehicles:</strong> Vehicles with Status = 1 in Vehicle_profile table</li>
                            <li><strong>Major Services:</strong> Repairs (Type 1) & Maintenance (Type 3)</li>
                            <li><strong>Minor Services:</strong> Cleaning (Type 2) & Inspections (Type 4)</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="mb-0">
                            <li><strong>Major Service Interval:</strong> Every 12 months recommended</li>
                            <li><strong>Minor Service Interval:</strong> Every 6 months recommended</li>
                            <li><strong>Frequent Services:</strong> Vehicles with above-average service frequency</li>
                            <li><strong>Fleet Health:</strong> Calculated based on service compliance and activity</li>
                        </ul>
                    </div>
                </div>
                
                <!-- UPDATED: Add clarification about status interpretation -->
                <div class="mt-3 p-2 bg-light rounded">
                    <small class="text-muted">
                        <strong>Note:</strong> This analysis focuses on requested services (Status = 2) to provide accurate historical data about fleet maintenance patterns and identify vehicles that may need attention.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection