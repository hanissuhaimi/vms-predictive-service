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
                            <h3><i class="fas fa-truck-moving"></i> All Vehicles Analysis</h3>
                            <p class="mb-0">Comprehensive fleet analysis and insights</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="{{ route('fleet.analysis.index') }}" class="btn btn-light me-2">
                                <i class="fas fa-arrow-left"></i> Back to Fleet Dashboard
                            </a>
                            <button onclick="window.print()" class="btn btn-outline-light">
                                <i class="fas fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fleet Statistics Overview -->
    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['total_vehicles'] }}</h3>
                    <p class="mb-0">Total Vehicles</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-2x mb-2"></i>
                    <h3>{{ number_format($fleetStats['total_services']) }}</h3>
                    <p class="mb-0">Total Services</p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-wrench fa-2x mb-2"></i>
                    <h3>{{ number_format($fleetStats['maintenance_services']) }}</h3>
                    <p class="mb-0">Maintenance<br><small>(Repair/Service/Inspect)</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-spray-can fa-2x mb-2"></i>
                    <h3>{{ number_format($fleetStats['cleaning_services']) }}</h3>
                    <p class="mb-0">Cleaning<br><small>Services</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-secondary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['active_vehicles'] }}</h3>
                    <p class="mb-0">Active Vehicles<br><small>(Last 6 months)</small></p>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card bg-dark text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tachometer-alt fa-2x mb-2"></i>
                    <h3>{{ $fleetStats['vehicles_with_odometer'] }}</h3>
                    <p class="mb-0">Vehicles with<br><small>Odometer Data</small></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Vehicle List -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list"></i> Vehicle Service Overview</h5>
                </div>
                <div class="card-body">
                    @if($vehicles->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Vehicle Number</th>
                                        <th>Total Services</th>
                                        <th>Maintenance</th>
                                        <th>Cleaning</th>
                                        <th>Last Service</th>
                                        <th>Highest Mileage</th>
                                        <th>Odometer Records</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vehicles as $vehicle)
                                        <tr>
                                            <td>
                                                <strong>{{ $vehicle->Vehicle }}</strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">{{ $vehicle->total_services }}</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">{{ $vehicle->maintenance_services ?? 0 }}</span>
                                                <small class="text-muted d-block">R/M/I</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $vehicle->cleaning_services ?? 0 }}</span>
                                                <small class="text-muted d-block">Wash</small>
                                            </td>
                                            <td>
                                                @if($vehicle->last_service)
                                                    {{ \Carbon\Carbon::parse($vehicle->last_service)->format('d/m/Y') }}
                                                    <small class="text-muted d-block">
                                                        {{ \Carbon\Carbon::parse($vehicle->last_service)->diffForHumans() }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">No date recorded</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($vehicle->highest_mileage && $vehicle->highest_mileage > 0)
                                                    <strong>{{ number_format($vehicle->highest_mileage) }}</strong> KM
                                                    @if($vehicle->avg_mileage && $vehicle->avg_mileage != $vehicle->highest_mileage)
                                                        <small class="text-muted d-block">
                                                            Avg: {{ number_format($vehicle->avg_mileage) }} KM
                                                        </small>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No mileage data</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($vehicle->odometer_records > 0)
                                                    <span class="badge bg-secondary">{{ $vehicle->odometer_records }}</span>
                                                    <small class="text-muted d-block">records</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $daysSinceService = $vehicle->last_service ? 
                                                        \Carbon\Carbon::parse($vehicle->last_service)->diffInDays(now()) : 999;
                                                    $maintenanceRatio = $vehicle->total_services > 0 ? 
                                                        ($vehicle->maintenance_services / $vehicle->total_services) : 0;
                                                @endphp
                                                
                                                @if($daysSinceService < 30)
                                                    <span class="badge bg-success">Recent Service</span>
                                                @elseif($daysSinceService < 90)
                                                    <span class="badge bg-warning">Check Soon</span>
                                                @else
                                                    <span class="badge bg-danger">Overdue Check</span>
                                                @endif
                                                
                                                @if($maintenanceRatio > 0.8)
                                                    <br><small class="text-success">High Maintenance</small>
                                                @elseif($maintenanceRatio < 0.2)
                                                    <br><small class="text-info">Mostly Cleaning</small>
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
                            <p class="text-muted">No vehicle data is available in the system.</p>
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
                    @if(!empty($performanceAnalysis['service_type_distribution']))
                        <h6>Service Type Distribution</h6>
                        <div class="row mb-3">
                            @foreach($performanceAnalysis['service_type_distribution'] as $type => $count)
                                <div class="col-6 mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $type }}:</span>
                                        <strong>{{ number_format($count) }}</strong>
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
                                <li><i class="fas fa-info-circle text-info"></i> {{ $note }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-exclamation-triangle"></i> High Maintenance Vehicles</h5>
                </div>
                <div class="card-body">
                    @if($performanceAnalysis['high_maintenance_vehicles']->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Vehicle</th>
                                        <th>Services</th>
                                        <th>Max Odometer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($performanceAnalysis['high_maintenance_vehicles']->take(8) as $vehicle)
                                        <tr>
                                            <td><strong>{{ $vehicle->Vehicle }}</strong></td>
                                            <td><span class="badge bg-warning">{{ $vehicle->maintenance_count }}</span></td>
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
                    @else
                        <p class="text-muted">No high maintenance vehicles identified.</p>
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
                    <h5><i class="fas fa-chart-line"></i> Fleet Maintenance Trends</h5>
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
                                                <th>Maintenance</th>
                                                <th>Cleaning</th>
                                                <th>Repairs</th>
                                                <th>Inspections</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($maintenanceTrends['monthly_trends']->take(6) as $trend)
                                                <tr>
                                                    <td><strong>{{ $trend->year }}-{{ str_pad($trend->month, 2, '0', STR_PAD_LEFT) }}</strong></td>
                                                    <td><span class="badge bg-primary">{{ $trend->total_services }}</span></td>
                                                    <td><span class="badge bg-success">{{ $trend->maintenance_services }}</span></td>
                                                    <td><span class="badge bg-info">{{ $trend->cleaning_services }}</span></td>
                                                    <td><span class="badge bg-warning">{{ $trend->repairs }}</span></td>
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
                                    @if(isset($maintenanceTrends['trend_summary']['vehicles_needing_attention']))
                                        <p><strong>Vehicles Needing Attention:</strong> 
                                            <span class="badge bg-danger">{{ $maintenanceTrends['trend_summary']['vehicles_needing_attention'] }}</span>
                                        </p>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    @if(!empty($maintenanceTrends['priority_distribution']))
                        <div class="row">
                            <div class="col-md-6">
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
                            </div>
                            <div class="col-md-6">
                                @if(!empty($maintenanceTrends['building_trends']) && $maintenanceTrends['building_trends']->count() > 0)
                                    <h6>Top Service Locations</h6>
                                    @foreach($maintenanceTrends['building_trends']->take(5) as $building)
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>{{ $building->Building ?: 'Unknown' }}:</span>
                                            <span><strong>{{ $building->service_count }}</strong> services ({{ $building->vehicle_count }} vehicles)</span>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection