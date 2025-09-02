@extends('layouts.app')

@section('title', 'Fleet Analytics Dashboard')

@section('content')
<div class="container-fluid px-4">
    <!-- Compact Page Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-chart-line text-primary"></i> 
                        Fleet Analytics Dashboard
                    </h2>
                    <p class="text-muted mb-0 small">Comprehensive fleet analysis and insights</p>
                </div>
                <div>
                    <a href="{{ route('prediction.index') }}" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            
            @if(isset($processing_message))
                <div class="alert alert-success alert-dismissible fade show mt-2 py-2" role="alert">
                    <small><i class="fas fa-check-circle"></i> {{ $processing_message }}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(isset($error))
                <div class="alert alert-danger alert-dismissible fade show mt-2 py-2" role="alert">
                    <small><i class="fas fa-exclamation-triangle"></i> {{ $error }}</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
        </div>
    </div>

    <!-- Compact Fleet Overview Cards -->
    @if(isset($fleet_overview))
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-truck fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ number_format($fleet_overview['total_vehicles']) }}</h4>
                    <small>Total Vehicles</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-info text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-tools fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ number_format($fleet_overview['total_services_all_time']) }}</h4>
                    <small>Total Services</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-heart fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ \App\Models\FleetAnalysis::calculateFleetHealthScore() }}%</h4>
                    <small>Fleet Health Score</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-warning text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-percentage fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ $fleet_overview['service_efficiency'] }}%</h4>
                    <small>Service Efficiency</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-dark text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-file-alt fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ number_format(\App\Models\FleetAnalysis::getTotalMaintenanceOrder()) }}</h4>
                    <small>Maintenance Orders</small>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body text-center py-3">
                    <i class="fas fa-car fa-2x mb-2"></i>
                    <h4 class="mb-1">{{ number_format(\App\Models\FleetAnalysis::getTotalVehiclesWithMaintenanceOrder()) }}</h4>
                    <small>Vehicles with Maintenance Order</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Metrics Row -->
    <div class="row mb-3">
        <div class="col-md-6 mb-2">
            <div class="card bg-danger text-white">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ number_format(\App\Models\FleetAnalysis::getVehiclesSkippedMajorService()) }}</h5>
                            <small>Vehicles with No Major Services in 12+ months</small>
                        </div>
                        <i class="fas fa-exclamation-triangle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-2">
            <div class="card bg-warning text-white">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-1">{{ number_format(\App\Models\FleetAnalysis::getVehiclesSkippedMinorService()) }}</h5>
                            <small>Vehicles with No Minor Services in 6+ months</small>
                        </div>
                        <i class="fas fa-exclamation-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Frequent Service Breakdown - Compact Version -->
    @if(!empty($frequent_breakdown_service))
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="fas fa-wrench"></i> Service Types for Frequent Breakdown Vehicles</h6>
                </div>
                <div class="card-body py-3">
                    <div class="row justify-content-center">
                        @foreach($frequent_breakdown_service as $serviceType => $data)
                            <div class="col-md-2 col-sm-4 col-6 mb-2">
                                <div class="card border h-100">
                                    <div class="card-body text-center py-3">
                                        @switch($serviceType)
                                            @case('Maintenance')
                                                <i class="fas fa-tools fa-2x text-danger mb-2"></i>
                                                @break
                                            @case('Cleaning/Washing')
                                                <i class="fas fa-soap fa-2x text-info mb-2"></i>
                                                @break
                                            @case('Tires')
                                                <i class="fas fa-circle fa-2x text-warning mb-2"></i>
                                                @break
                                            @default
                                                <i class="fas fa-wrench fa-2x text-secondary mb-2"></i>
                                        @endswitch
                                        <h5 class="mb-1">{{ $data['percentage'] }}%</h5>
                                        <small class="text-muted d-block">{{ $serviceType }}</small>
                                        <small class="text-muted">{{ $data['count'] }} services</small>
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

    <div class="row mb-3">
        <!-- Compact Service Statistics -->
        @if(isset($fleet_overview))
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Service Statistics</h6>
                </div>
                <div class="card-body py-3">
                    <!-- Maintenance Services -->
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                        <div>
                            <h5 class="text-primary mb-0">{{ number_format($fleet_overview['maintenance_services_all_time'] ?? 0) }}</h5>
                            <small class="text-muted">Type 1 - Maintenance Services</small>
                        </div>
                        <i class="fas fa-wrench fa-lg text-primary"></i>
                    </div>
                    
                    <!-- Cleaning Services -->
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded mb-2">
                        <div>
                            <h5 class="text-info mb-0">{{ number_format($fleet_overview['cleaning_services_all_time'] ?? 0) }}</h5>
                            <small class="text-muted">Type 2 - Cleaning & Washing</small>
                        </div>
                        <i class="fas fa-soap fa-lg text-info"></i>
                    </div>
                    
                    <!-- Tire Services -->
                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                        <div>
                            <h5 class="text-success mb-0">{{ number_format($fleet_overview['tire_services_all_time'] ?? 0) }}</h5>
                            <small class="text-muted">Type 3 - Tire Services</small>
                        </div>
                        <i class="fas fa-circle fa-lg text-success"></i>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Service Type Breakdown -->
        @if(isset($service_type_breakdown) && !empty($service_type_breakdown))
        <div class="col-lg-6 mb-3">
            <div class="card h-100">
                <div class="card-header py-2">
                    <h6 class="mb-0"><i class="fas fa-pie-chart"></i> Service Type Breakdown</h6>
                </div>
                <div class="card-body py-3">
                    @foreach($service_type_breakdown as $type)
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong class="small">{{ $type['type_name'] }}</strong>
                            <br>
                            <small class="text-muted">{{ number_format($type['count']) }} services</small>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary">{{ $type['percentage'] }}%</span>
                        </div>
                    </div>
                    @if(!$loop->last)
                    <hr class="my-2">
                    @endif
                    @endforeach
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Maintenance Trends - Expandable Table -->
    @if(isset($maintenance_trends) && !empty($maintenance_trends['monthly_trends']))
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-chart-line"></i> Maintenance Trends</h6>
                        <div class="d-flex gap-3 align-items-center">
                            <small class="text-muted">
                                <strong>Avg Monthly:</strong> {{ $maintenance_trends['average_monthly_services'] }}
                            </small>
                            <small class="text-muted">
                                <strong>Trend:</strong> 
                                @if($maintenance_trends['trend_direction'] == 'increasing')
                                    <span class="text-success"><i class="fas fa-arrow-up"></i> Increasing</span>
                                @elseif($maintenance_trends['trend_direction'] == 'decreasing')
                                    <span class="text-danger"><i class="fas fa-arrow-down"></i> Decreasing</span>
                                @else
                                    <span class="text-secondary"><i class="fas fa-minus"></i> Stable</span>
                                @endif
                            </small>
                            <small class="text-muted">
                                <strong>Total Months:</strong> {{ $maintenance_trends['total_months_analyzed'] ?? count($maintenance_trends['monthly_trends']) }}
                            </small>
                        </div>
                    </div>
                </div>
                <div class="card-body py-2">
                    <!-- View Controls -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="btn-group btn-group-sm" role="group">
                            <input type="radio" class="btn-check" name="trendsView" id="recent12" autocomplete="off" checked>
                            <label class="btn btn-outline-primary btn-sm" for="recent12">Recent 12 Months</label>

                            <input type="radio" class="btn-check" name="trendsView" id="recent24" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="recent24">Recent 24 Months</label>

                            <input type="radio" class="btn-check" name="trendsView" id="viewAll" autocomplete="off">
                            <label class="btn btn-outline-primary btn-sm" for="viewAll">All History</label>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-secondary" onclick="exportTrendsData()">
                                <i class="fas fa-download"></i> Export
                            </button>
                        </div>
                    </div>

                    <!-- Table Container -->
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;" id="trendsTableContainer">
                        <table class="table table-sm table-striped mb-0" id="trendsTable">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th class="small">Month</th>
                                    <th class="small">Total</th>
                                    <th class="small">Maintenance</th>
                                    <th class="small">Cleaning</th>
                                    <th class="small">Tires</th>
                                </tr>
                            </thead>
                            <tbody id="trendsTableBody">
                                @foreach(array_reverse($maintenance_trends['monthly_trends']) as $index => $trend)
                                <tr class="trend-row" data-index="{{ $index }}" style="{{ $index >= 12 ? 'display: none;' : '' }}">
                                    <td class="small"><strong>{{ $trend['month_name'] ?? $trend['month'] . '/' . $trend['year'] }}</strong></td>
                                    <td class="small">{{ number_format($trend['total_services']) }}</td>
                                    <td class="small">{{ number_format($trend['maintenance_services'] ?? 0) }}</td>
                                    <td class="small">{{ number_format($trend['cleaning_services'] ?? 0) }}</td>
                                    <td class="small">{{ number_format($trend['tire_services'] ?? 0) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary Info -->
                    <div class="mt-2 d-flex justify-content-between align-items-center">
                        <small class="text-muted" id="trendsViewInfo">
                            <i class="fas fa-info-circle"></i> 
                            <span id="viewingText">Viewing recent 12 months</span> of {{ count($maintenance_trends['monthly_trends']) }} total months
                        </small>
                        <small class="text-muted" id="trendsStats">
                            <!-- Dynamic stats will be inserted here -->
                        </small>
                    </div>

                    <!-- Simple Chart Container (Initially Hidden) -->
                    <div id="trendsChartContainer" style="display: none;" class="mt-3">
                        <canvas id="trendsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Trends view control
        document.addEventListener('DOMContentLoaded', function() {
            const recent12 = document.getElementById('recent12');
            const recent24 = document.getElementById('recent24');
            const viewAll = document.getElementById('viewAll');
            const rows = document.querySelectorAll('.trend-row');
            const viewingText = document.getElementById('viewingText');
            const trendsStats = document.getElementById('trendsStats');
            
            function updateTrendsView() {
                let visibleCount = 0;
                let totalServices = 0;
                let maxRows = 0;
                
                if (recent12.checked) {
                    maxRows = 12;
                    viewingText.textContent = 'Viewing recent 12 months';
                } else if (recent24.checked) {
                    maxRows = 24;
                    viewingText.textContent = 'Viewing recent 24 months';
                } else {
                    maxRows = rows.length;
                    viewingText.textContent = 'Viewing all historical data';
                }
                
                rows.forEach((row, index) => {
                    if (index < maxRows) {
                        row.style.display = '';
                        visibleCount++;
                        // Sum up total services for visible rows
                        const totalCell = row.cells[1].textContent.replace(/,/g, '');
                        totalServices += parseInt(totalCell) || 0;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update stats
                const avgServices = visibleCount > 0 ? Math.round(totalServices / visibleCount) : 0;
                trendsStats.innerHTML = `<strong>Visible:</strong> ${visibleCount} months | <strong>Avg:</strong> ${avgServices.toLocaleString()} services/month`;
            }
            
            recent12.addEventListener('change', updateTrendsView);
            recent24.addEventListener('change', updateTrendsView);
            viewAll.addEventListener('change', updateTrendsView);
            
            // Initialize
            updateTrendsView();
        });
        
        // Export functionality
        function exportTrendsData() {
            const table = document.getElementById('trendsTable');
            const rows = table.querySelectorAll('tr:not([style*="display: none"])');
            let csv = '';
            
            rows.forEach((row, index) => {
                const cells = row.querySelectorAll('th, td');
                const rowData = Array.from(cells).map(cell => {
                    return '"' + cell.textContent.trim() + '"';
                }).join(',');
                csv += rowData + '\n';
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'maintenance_trends_' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
    @endif

    <!-- Compact Footer Info -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body py-2">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-database"></i>
                                <strong>Scope:</strong> {{ $date_range['start'] ?? 'Unknown' }} to {{ $date_range['end'] ?? 'Unknown' }}
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i>
                                <strong>Generated:</strong> {{ now()->format('d M Y, H:i') }}
                            </small>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">
                                <i class="fas fa-chart-bar"></i>
                                <strong>Records:</strong> {{ number_format($services_data['total_count'] ?? 0) }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.1);
}

.card-header {
    background-color: rgba(0, 0, 0, 0.03);
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.table-sm th,
.table-sm td {
    padding: 0.3rem 0.5rem;
}

@media (max-width: 768px) {
    .container-fluid {
        padding-left: 15px;
        padding-right: 15px;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 1rem 0.75rem;
    }
}
</style>
@endsection