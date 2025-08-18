@extends('layouts.app')

@section('title', 'Fleet Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-chart-line text-primary"></i> 
                        Fleet Analytics Dashboard
                    </h1>
                    <p class="text-muted mb-0">Comprehensive fleet analysis and insights</p>
                </div>
                <div>
                    <a href="{{ route('prediction.index') }}" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
            
            @if(isset($processing_message))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-check-circle"></i> {{ $processing_message }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(isset($error))
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>

    <!-- Fleet Overview Cards -->
    @if(isset($fleet_overview))
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-truck fa-3x mb-3"></i>
                    <h3>{{ number_format($fleet_overview['total_vehicles']) }}</h3>
                    <p class="mb-0">Total Vehicles</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <i class="fas fa-tools fa-3x mb-3"></i>
                    <h3>{{ number_format($fleet_overview['total_services_all_time']) }}</h3>
                    <p class="mb-0">Total Services (All Time)</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <i class="fas fa-heart fa-3x mb-3"></i>
                    <h3>{{ $fleet_overview['fleet_health_score'] }}%</h3>
                    <p class="mb-0">Fleet Health Score</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body text-center">
                    <i class="fas fa-percentage fa-3x mb-3"></i>
                    <h3>{{ $fleet_overview['service_efficiency'] }}%</h3>
                    <p class="mb-0">Service Efficiency</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <!-- Service Statistics -->
        @if(isset($services_data))
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-bar"></i> Service Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="border-end">
                                <h4 class="text-primary">{{ number_format($services_data['total_count']) }}</h4>
                                <p class="text-muted mb-0">Total Services (All Time)</p>
                                <small>Complete Database Analysis</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <h4 class="text-info">{{ number_format(($services_data['by_mr_type'][1] ?? 0)) }}</h4>
                            <p class="text-muted mb-0">Maintenance Services</p>
                            <small>Type 1 (Repairs & Maintenance)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Service Type Breakdown -->
        @if(isset($service_type_breakdown) && !empty($service_type_breakdown))
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-pie-chart"></i> Service Type Breakdown</h5>
                </div>
                <div class="card-body">
                    @foreach($service_type_breakdown as $type)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <strong>{{ $type['type_name'] }}</strong>
                            <br>
                            <small class="text-muted">{{ number_format($type['count']) }} services</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary fs-6">{{ $type['percentage'] }}%</span>
                        </div>
                    </div>
                    @if(!$loop->last)
                    <hr>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Maintenance Trends -->
    @if(isset($maintenance_trends) && !empty($maintenance_trends['monthly_trends']))
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-chart-line"></i> Maintenance Trends</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-primary">{{ $maintenance_trends['average_monthly_services'] }}</h4>
                                <p class="text-muted mb-0">Average Monthly Services</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-info">
                                    @if($maintenance_trends['trend_direction'] == 'increasing')
                                        <i class="fas fa-arrow-up text-success"></i> Increasing
                                    @elseif($maintenance_trends['trend_direction'] == 'decreasing')
                                        <i class="fas fa-arrow-down text-danger"></i> Decreasing
                                    @else
                                        <i class="fas fa-minus text-secondary"></i> Stable
                                    @endif
                                </h4>
                                <p class="text-muted mb-0">Trend Direction</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center">
                                <h4 class="text-success">{{ $maintenance_trends['total_months_analyzed'] ?? count($maintenance_trends['monthly_trends']) }}</h4>
                                <p class="text-muted mb-0">Total Months Analyzed</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Total Services</th>
                                    <th>Maintenance</th>
                                    <th>Cleaning</th>
                                    <th>Tires</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(array_reverse($maintenance_trends['monthly_trends']) as $trend)
                                <tr>
                                    <td><strong>{{ $trend['month_name'] ?? $trend['month'] . '/' . $trend['year'] }}</strong></td>
                                    <td>{{ number_format($trend['total_services']) }}</td>
                                    <td>{{ number_format($trend['maintenance_services'] ?? 0) }}</td>
                                    <td>{{ number_format($trend['cleaning_services'] ?? 0) }}</td>
                                    <td>{{ number_format($trend['tire_services'] ?? 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Footer Info -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <p class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        <strong>Analysis Scope:</strong> Complete Database ({{ $date_range['start'] ?? 'Unknown' }} to {{ $date_range['end'] ?? 'Unknown' }})
                        |
                        <strong>Generated:</strong> {{ now()->format('d M Y, H:i:s') }}
                        |
                        <strong>Total Records Analyzed:</strong> {{ number_format($services_data['total_count'] ?? 0) }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection