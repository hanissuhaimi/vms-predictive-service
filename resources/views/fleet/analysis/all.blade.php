@extends('layouts.app')

@section('title', 'All Vehicles Analysis')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3><i class="fas fa-truck-moving"></i> All Vehicles Analysis (MO Created)</h3>
                            <p class="mb-0">Comprehensive fleet analysis and insights for vehicles with active maintenance orders</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('fleet.analysis.index') }}" class="btn btn-light me-2">
                                <i class="fas fa-arrow-left"></i> Back to Fleet Dashboard
                            </a>
                            <div class="btn-group">
                                <button onclick="window.print()" class="btn btn-outline-light">
                                    <i class="fas fa-print"></i> Print
                                </button>
                                <a href="{{ route('fleet.analysis.export') }}?format=csv" class="btn btn-outline-light">
                                    <i class="fas fa-download"></i> Export
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Analysis Results (4 Requirements) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-chart-line"></i> Key Fleet Analysis Results</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-truck fa-2x text-primary mb-2"></i>
                                <h3 class="text-primary">{{ $specificAnalysis['total_vehicles_in_workshop'] ?? 0 }}</h3>
                                <strong>Total Vehicles</strong>
                                <small class="d-block text-muted">Active MO (Status = 2)</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-exclamation-triangle fa-2x text-danger mb-2"></i>
                                <h3 class="text-danger">{{ $specificAnalysis['vehicles_skipped_major_service'] ?? 0 }}</h3>
                                <strong>Skipped Major Service</strong>
                                <small class="d-block text-muted">No service in 6+ months</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-tools fa-2x text-warning mb-2"></i>
                                <h3 class="text-warning">{{ $specificAnalysis['vehicles_skipped_minor_service'] ?? 0 }}</h3>
                                <strong>Skipped Minor Service</strong>
                                <small class="d-block text-muted">No service in 3+ months</small>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="p-3 border rounded">
                                <i class="fas fa-chart-pie fa-2x text-info mb-2"></i>
                                <h3 class="text-info">
                                    @if(!empty($specificAnalysis['breakdown_service_types']))
                                        {{ count($specificAnalysis['breakdown_service_types']) }}
                                    @else
                                        0
                                    @endif
                                </h3>
                                <strong>Service Types Identified</strong>
                                <small class="d-block text-muted">For breakdown vehicles</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Service Types Detail -->
    @if(!empty($specificAnalysis['breakdown_service_types']))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-wrench"></i> Service Types for Frequent Breakdown Vehicles (Percentages)</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($specificAnalysis['breakdown_service_types'] as $serviceType => $data)
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card h-100 border-left-primary">
                                    <div class="card-body text-center">
                                        @switch($serviceType)
                                            @case('Repair')
                                                <i class="fas fa-tools fa-3x text-danger mb-3"></i>
                                                @break
                                            @case('Maintenance')
                                                <i class="fas fa-cogs fa-3x text-warning mb-3"></i>
                                                @break
                                            @case('Cleaning/Washing')
                                                <i class="fas fa-spray-can fa-3x text-info mb-3"></i>
                                                @break
                                            @case('Inspection')
                                                <i class="fas fa-search fa-3x text-success mb-3"></i>
                                                @break
                                            @default
                                                <i class="fas fa-wrench fa-3x text-secondary mb-3"></i>
                                        @endswitch
                                        <h4>{{ $data['percentage'] }}%</h4>
                                        <h6>{{ $serviceType }}</h6>
                                        <small class="text-muted">{{ $data['count'] }} services</small>
                                        <div class="progress mt-2">
                                            <div class="progress-bar" style="width: {{ $data['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Fleet Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['total_vehicles_in_workshop'] ?? \App\Models\FleetAnalysis::getTotalVehiclesInWorkshop() }}</h3>
                    <p class="mb-0">Workshop Vehicles</p>
                    <small>(MO Created)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['total_services'] ?? \App\Models\FleetAnalysis::getTotalServices() }}</h3>
                    <p class="mb-0">Total Services</p>
                    <small>(MO Created)</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-wrench fa-2x mb-2"></i>
                    <h3>{{ number_format($fleetStats['maintenance_services']) }}</h3>
                    <p class="mb-0">Maintenance</p>
                    <small>Repair/Service/Inspect</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-spray-can fa-2x mb-2"></i>
                    <h3>{{ number_format($fleetStats['cleaning_services']) }}</h3>
                    <p class="mb-0">Cleaning</p>
                    <small>Services</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['active_vehicles'] ?? 0 }}</h3>
                    <p class="mb-0">Active Vehicles</p>
                    <small>Last 6 months</small>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tachometer-alt fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['vehicles_with_odometer'] ?? 0 }}</h3>
                    <p class="mb-0">With Odometer</p>
                    <small>Data Available</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Gap Analysis -->
    @if(isset($fleetStats['vehicles_needing_major_service']) || isset($fleetStats['vehicles_needing_minor_service']))
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h5><i class="fas fa-exclamation-triangle"></i> Major Service Gaps</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2 class="text-danger">{{ $specificAnalysis['vehicles_skipped_major_service'] ?? 0 }}</h2>
                        <p>Vehicles skipped major service (12+ months)</p>
                    </div>
                    @if(($specificAnalysis['vehicles_skipped_major_service'] ?? 0) > 0)
                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Major services include repairs and maintenance. 
                                These vehicles haven't had major service in the last 12 months.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <small>
                                <i class="fas fa-check-circle"></i>
                                All vehicles have had major services within 12 months!
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5><i class="fas fa-tools"></i> Minor Service Gaps</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2 class="text-warning">{{ $specificAnalysis['vehicles_skipped_minor_service'] ?? 0 }}</h2>
                        <p>Vehicles skipped minor service (6+ months)</p>
                    </div>
                    @if(($specificAnalysis['vehicles_skipped_minor_service'] ?? 0) > 0)
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Minor services include cleaning and inspections. 
                                These vehicles haven't had minor service in the last 6 months.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <small>
                                <i class="fas fa-check-circle"></i>
                                All vehicles have had minor services within 6 months!
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-warning">
                <div class="card-header bg-warning text-white">
                    <h5><i class="fas fa-tools"></i> Minor Service Gaps</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2 class="text-warning">{{ $fleetStats['vehicles_needing_minor_service'] ?? 0 }}</h2>
                        <p>Vehicles need minor service (6+ months overdue)</p>
                    </div>
                    @if(($fleetStats['vehicles_needing_minor_service'] ?? 0) > 0)
                        <div class="alert alert-info">
                            <small>
                                <i class="fas fa-info-circle"></i>
                                Minor services include cleaning and inspections. 
                                Regular minor services help prevent major breakdowns.
                            </small>
                        </div>
                    @else
                        <div class="alert alert-success">
                            <small>
                                <i class="fas fa-check-circle"></i>
                                All vehicles are up to date with minor services!
                            </small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Vehicle List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Vehicle Service Overview (MO Created Only)</h5>
                </div>
                <div class="card-body">
                    @if($vehicles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Vehicle Number</th>
                                        <th>Total Services</th>
                                        <th>Repairs</th>
                                        <th>Maintenance</th>
                                        <th>Cleaning/Washing</th>
                                        <th>Inspections</th>
                                        <th>Last Service</th>
                                        <th>Highest Mileage</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vehicles as $vehicle)
                                        <tr>
                                            <td><strong>{{ $vehicle->Vehicle }}</strong></td>
                                            <td><span class="badge bg-primary">{{ $vehicle->total_services }}</span></td>
                                            <td><span class="badge bg-danger">{{ $vehicle->repairs ?? 0 }}</span></td>
                                            <td><span class="badge bg-success">{{ $vehicle->maintenance ?? 0 }}</span></td>
                                            <td><span class="badge bg-info">{{ $vehicle->cleaning_services ?? 0 }}</span></td>
                                            <td><span class="badge bg-secondary">{{ $vehicle->inspections ?? 0 }}</span></td>
                                            <td>
                                                @if($vehicle->last_service)
                                                    {{ \Carbon\Carbon::parse($vehicle->last_service)->format('d/m/Y') }}
                                                    <small class="d-block text-muted">
                                                        {{ \Carbon\Carbon::parse($vehicle->last_service)->diffForHumans() }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">No date recorded</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($vehicle->highest_mileage && $vehicle->highest_mileage > 0)
                                                    <strong>{{ number_format($vehicle->highest_mileage) }}</strong> KM
                                                @else
                                                    <span class="text-muted">No mileage data</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $daysSinceService = $vehicle->last_service ? 
                                                        \Carbon\Carbon::parse($vehicle->last_service)->diffInDays(now()) : 999;
                                                    $majorServices = ($vehicle->repairs ?? 0) + ($vehicle->maintenance ?? 0);
                                                    $minorServices = ($vehicle->cleaning_services ?? 0) + ($vehicle->inspections ?? 0);
                                                    
                                                    // Get the average services per vehicle for comparison
                                                    $avgServicesPerVehicle = \App\Models\FleetAnalysis::getAverageServicesPerVehicle();
                                                    $isFrequentBreakdown = $vehicle->total_services > $avgServicesPerVehicle;
                                                @endphp
                                                
                                                @if($daysSinceService < 30)
                                                    <span class="badge bg-success">Recent Service</span>
                                                @elseif($daysSinceService < 90)
                                                    <span class="badge bg-warning">Check Soon</span>
                                                @else
                                                    <span class="badge bg-danger">Service Overdue</span>
                                                @endif
                                                
                                                <br>
                                                
                                                @if($isFrequentBreakdown)
                                                    <small class="text-danger">Frequent Breakdown</small>
                                                @elseif($majorServices > $minorServices)
                                                    <small class="text-warning">High Maintenance</small>
                                                @elseif($minorServices > $majorServices * 2)
                                                    <small class="text-info">Mostly Preventive</small>
                                                @else
                                                    <small class="text-success">Balanced Service</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($vehicle->highest_mileage && $vehicle->highest_mileage > 0)
                                                    <a href="{{ route('prediction.show', ['vehicle' => $vehicle->Vehicle, 'mileage' => $vehicle->highest_mileage]) }}" 
                                                    class="btn btn-sm btn-primary" title="Analyze Vehicle">
                                                        <i class="fas fa-search"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('maintenance.history', ['vehicle' => $vehicle->Vehicle]) }}" 
                                                class="btn btn-sm btn-info" title="View History">
                                                    <i class="fas fa-history"></i>
                                                </a>
                                                
                                                @if($isFrequentBreakdown)
                                                    <span class="badge bg-warning ms-1" title="Frequent Breakdown Vehicle">
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $vehicles->links() }}
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
                            <h5>No Vehicles Found</h5>
                            <p class="text-muted">No vehicle data with Maintenance Order status is available in the system.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Fleet Performance Analysis -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Fleet Performance Analysis</h5>
                </div>
                <div class="card-body">
                    @if(!empty($performanceAnalysis['breakdown_service_distribution']))
                        <h6>Breakdown Service Distribution</h6>
                        <div class="row mb-3">
                            @foreach($performanceAnalysis['breakdown_service_distribution'] as $type => $details)
                                <div class="col-12 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>{{ $type }}:</span>
                                        <div>
                                            <strong>{{ $details['percentage'] }}%</strong>
                                            <small class="text-muted">({{ $details['count'] }})</small>
                                        </div>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar" style="width: {{ $details['percentage'] }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    @if($performanceAnalysis['average_odometer_reading'] > 0)
                        <h6>Fleet Statistics</h6>
                        <p><strong>Average Odometer Reading:</strong> {{ number_format($performanceAnalysis['average_odometer_reading']) }} KM</p>
                    @endif
                    
                    @if(!empty($performanceAnalysis['fleet_efficiency_notes']))
                        <h6>Efficiency Notes</h6>
                        <ul class="list-unstyled">
                            @foreach($performanceAnalysis['fleet_efficiency_notes'] as $note)
                                <li class="mb-1">
                                    <i class="fas fa-info-circle text-info"></i> 
                                    <small>{{ $note }}</small>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> Frequent Breakdown Vehicles</h5>
                </div>
                <div class="card-body">
                    @if($performanceAnalysis['high_maintenance_vehicles']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Total Services</th>
                                        <th>Repairs</th>
                                        <th>Maintenance</th>
                                        <th>Max Odometer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($performanceAnalysis['high_maintenance_vehicles']->take(8) as $vehicle)
                                        <tr>
                                            <td><strong>{{ $vehicle->Vehicle }}</strong></td>
                                            <td><span class="badge bg-warning">{{ $vehicle->maintenance_count }}</span></td>
                                            <td><span class="badge bg-danger">{{ $vehicle->repairs ?? 0 }}</span></td>
                                            <td><span class="badge bg-success">{{ $vehicle->maintenance ?? 0 }}</span></td>
                                            <td>
                                                @if($vehicle->max_odometer > 0)
                                                    {{ number_format($vehicle->max_odometer) }} KM
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        @if($performanceAnalysis['high_maintenance_vehicles']->count() > 8)
                            <small class="text-muted">
                                Showing top 8 of {{ $performanceAnalysis['high_maintenance_vehicles']->count() }} high maintenance vehicles.
                            </small>
                        @endif
                    @else
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            No vehicles identified as having frequent breakdowns. This indicates good fleet maintenance!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance Trends -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Fleet Maintenance Trends (Maintenance Order)</h5>
                </div>
                <div class="card-body">
                    @if(!empty($maintenanceTrends['monthly_trends']) && $maintenanceTrends['monthly_trends']->count() > 0)
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h6>Monthly Service Activity (Last 12 Months)</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <th>Total</th>
                                                <th>Repairs</th>
                                                <th>Maintenance</th>
                                                <th>Cleaning/Washing</th>
                                                <th>Inspections</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($maintenanceTrends['monthly_trends']->take(6) as $trend)
                                                <tr>
                                                    <td><strong>{{ $trend->year }}-{{ str_pad($trend->month, 2, '0', STR_PAD_LEFT) }}</strong></td>
                                                    <td><span class="badge bg-primary">{{ $trend->total_services }}</span></td>
                                                    <td><span class="badge bg-danger">{{ $trend->repairs }}</span></td>
                                                    <td><span class="badge bg-success">{{ $trend->maintenance }}</span></td>
                                                    <td><span class="badge bg-info">{{ $trend->cleaning_services }}</span></td>
                                                    <td><span class="badge bg-secondary">{{ $trend->inspections }}</span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-4">
                                @if(!empty($maintenanceTrends['trend_summary']))
                                    <h6>Trend Summary</h6>
                                    @if(isset($maintenanceTrends['trend_summary']['avg_monthly_services']))
                                        <p><strong>Avg Monthly Services:</strong> {{ $maintenanceTrends['trend_summary']['avg_monthly_services'] }}</p>
                                    @endif
                                    @if(isset($maintenanceTrends['trend_summary']['recent_trend']))
                                        <p><strong>Recent Trend:</strong> 
                                            <span class="badge bg-{{ $maintenanceTrends['trend_summary']['recent_trend'] === 'increasing' ? 'warning' : 'success' }}">
                                                {{ ucfirst($maintenanceTrends['trend_summary']['recent_trend']) }}
                                            </span>
                                        </p>
                                    @endif
                                    
                                    <!-- New specific analysis data -->
                                    @if(isset($maintenanceTrends['trend_summary']['major_service_gaps']))
                                        <p><strong>Major Service Gaps:</strong> 
                                            <span class="badge bg-danger">{{ $maintenanceTrends['trend_summary']['major_service_gaps'] }}</span>
                                        </p>
                                    @endif
                                    @if(isset($maintenanceTrends['trend_summary']['minor_service_gaps']))
                                        <p><strong>Minor Service Gaps:</strong> 
                                            <span class="badge bg-warning">{{ $maintenanceTrends['trend_summary']['minor_service_gaps'] }}</span>
                                        </p>
                                    @endif
                                    @if(isset($maintenanceTrends['trend_summary']['total_workshop_vehicles']))
                                        <p><strong>Workshop Vehicles:</strong> 
                                            <span class="badge bg-primary">{{ $maintenanceTrends['trend_summary']['total_workshop_vehicles'] }}</span>
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            @if(!empty($maintenanceTrends['priority_distribution']))
                                <h6>Service Priority Distribution</h6>
                                <div class="row">
                                    @foreach($maintenanceTrends['priority_distribution'] as $priority => $count)
                                        <div class="col-6 mb-2">
                                            <div class="d-flex justify-content-between">
                                                <span>{{ $priority }}:</span>
                                                <strong>{{ number_format($count) }}</strong>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="col-md-6">
                            @if(!empty($maintenanceTrends['building_trends']) && $maintenanceTrends['building_trends']->count() > 0)
                                <h6>Top Service Locations</h6>
                                @foreach($maintenanceTrends['building_trends']->take(5) as $building)
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>{{ $building->Building ?: 'Unknown' }}:</span>
                                        <span>
                                            <strong>{{ $building->service_count }}</strong> services 
                                            <small class="text-muted">({{ $building->vehicle_count }} vehicles)</small>
                                        </span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Summary -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-clipboard-check"></i> Analysis Summary & Recommendations</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Key Findings:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-truck text-primary"></i> 
                                    <strong>{{ $specificAnalysis['total_vehicles_in_workshop'] ?? 0 }}</strong> vehicles currently have active maintenance orders
                                </li>
                                <li><i class="fas fa-exclamation-triangle text-danger"></i> 
                                    <strong>{{ $specificAnalysis['vehicles_skipped_major_service'] ?? 0 }}</strong> vehicles are overdue for major service
                                </li>
                                <li><i class="fas fa-tools text-warning"></i> 
                                    <strong>{{ $specificAnalysis['vehicles_skipped_minor_service'] ?? 0 }}</strong> vehicles are overdue for minor service
                                </li>
                                <li><i class="fas fa-chart-pie text-info"></i> 
                                    Service breakdown analysis identifies key maintenance patterns
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Recommendations:</h6>
                            <ul class="list-unstyled">
                                @if(($specificAnalysis['vehicles_skipped_major_service'] ?? 0) > 0)
                                    <li><i class="fas fa-arrow-right text-danger"></i> 
                                        Prioritize major service scheduling for overdue vehicles
                                    </li>
                                @endif
                                @if(($specificAnalysis['vehicles_skipped_minor_service'] ?? 0) > 0)
                                    <li><i class="fas fa-arrow-right text-warning"></i> 
                                        Schedule minor services to prevent major breakdowns
                                    </li>
                                @endif
                                @if($performanceAnalysis['high_maintenance_vehicles']->count() > 0)
                                    <li><i class="fas fa-arrow-right text-info"></i> 
                                        Monitor frequent breakdown vehicles for patterns
                                    </li>
                                @endif
                                <li><i class="fas fa-arrow-right text-success"></i> 
                                    Maintain regular service intervals to optimize fleet health
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Information -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6><i class="fas fa-info-circle"></i> Analysis Criteria (Updated Logic):</h6>
                <div class="row">
                    <div class="col-md-4">
                        <ul class="mb-0">
                            <li><strong>Data Filter:</strong> Vehicle_profile.Status = 1 AND ServiceRequest.Status = 2</li>
                            <li><strong>Major Services:</strong> MrType 1 (Repair) + 3 (Maintenance)</li>
                            <li><strong>Minor Services:</strong> MrType 2 (Cleaning/Washing) + 4 (Inspection)</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="mb-0">
                            <li><strong>Major Service Gap:</strong> No service in last 12 months</li>
                            <li><strong>Minor Service Gap:</strong> No service in last 6 months</li>
                            <li><strong>Frequent Breakdown:</strong> Above fleet average service frequency</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <ul class="mb-0">
                            <li><strong>Service Types:</strong> 1=Repair, 2=Cleaning/Washing, 3=Maintenance, 4=Inspection</li>
                            <li><strong>Fleet Health Score:</strong> Based on maintenance compliance & activity</li>
                            <li><strong>Analysis Date:</strong> {{ date('d/m/Y H:i') }}</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
// Add any additional JavaScript for enhanced functionality
$(document).ready(function() {
    // Initialize tooltips
    $('[title]').tooltip();
    
    // Add export functionality
    $('.export-btn').click(function() {
        // Add export logic if needed
    });
});
</script>
@endsection

@endsection