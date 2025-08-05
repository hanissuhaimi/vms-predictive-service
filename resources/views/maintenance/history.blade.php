@extends('layouts.app')

@section('title', 'Maintenance History - ' . $vehicle)

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h3><i class="fas fa-history"></i> Maintenance History - {{ $vehicle }}</h3>
                            <p class="mb-0">Complete service record analysis</p>
                            @if($currentMileage > 0)
                            <small>Current Analysis Mileage: {{ number_format($currentMileage) }} KM</small>
                            @endif
                        </div>
                        <div class="col-md-6 text-md-end">
                            @if($currentMileage > 0)
                                <a href="{{ route('prediction.show', ['vehicle' => $vehicle, 'mileage' => $currentMileage]) }}" class="btn btn-light me-2">
                                    <i class="fas fa-arrow-left"></i> Back to Prediction Results
                                </a>
                            @else
                                <a href="{{ route('prediction.index') }}" class="btn btn-light me-2">
                                    <i class="fas fa-arrow-left"></i> Back to Prediction Form
                                </a>
                            @endif
                            <button onclick="window.print()" class="btn btn-outline-light">
                                <i class="fas fa-print"></i> Print History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('prediction.index') }}">
                            <i class="fas fa-home"></i> Fleet Prediction
                        </a>
                    </li>
                    @if($currentMileage > 0)
                    <li class="breadcrumb-item">
                        <a href="{{ route('prediction.show', ['vehicle' => $vehicle, 'mileage' => $currentMileage]) }}">
                            <i class="fas fa-chart-line"></i> {{ $vehicle }} Analysis
                        </a>
                    </li>
                    @endif
                    <li class="breadcrumb-item active" aria-current="page">
                        <i class="fas fa-history"></i> Maintenance History
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Statistics Overview -->
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div class="flex-fill" style="min-width: 18%;">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <h2 class="text-primary">{{ number_format($totalRecords) }}</h2>
                    <h6 class="card-title">Total Records</h6>
                    <p class="text-muted small">All service records</p>
                </div>
            </div>
        </div>
        <div class="flex-fill" style="min-width: 18%;">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <h2 class="text-success">{{ number_format($vehicleHistory['total_services'] ?? 0) }}</h2>
                    <h6 class="card-title">Maintenance Services</h6>
                    <p class="text-muted small">Excludes cleaning/washing</p>
                </div>
            </div>
        </div>
        <div class="flex-fill" style="min-width: 18%;">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <h2 class="text-info">{{ $vehicleHistory['service_patterns']['services_per_month'] ?? 'N/A' }}</h2>
                    <h6 class="card-title">Services/Month</h6>
                    <p class="text-muted small">Average frequency</p>
                </div>
            </div>
        </div>
        <div class="flex-fill" style="min-width: 18%;">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <h2 class="text-warning">{{ number_format($vehicleHistory['average_interval']) }} KM</h2>
                    <h6 class="card-title">Avg Interval</h6>
                    <p class="text-muted small">Between services</p>
                </div>
            </div>
        </div>
        <div class="flex-fill" style="min-width: 18%;">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <h2 class="text-success">{{ $vehicleHistory['service_patterns']['data_quality'] ?? 'N/A' }}%</h2>
                    <h6 class="card-title">Data Quality</h6>
                    <p class="text-muted small">Mileage records</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Analysis Charts -->
    <div class="row mb-4">
        <!-- Service Types -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-2">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-chart-pie me-2"></i>
                        <span>Service Types</span>
                        <span class="badge bg-white text-primary ms-auto">{{ isset($serviceStats['by_type']) ? $serviceStats['by_type']->count() : 0 }}</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(isset($serviceStats['by_type']) && $serviceStats['by_type']->isNotEmpty())
                        @foreach($serviceStats['by_type'] as $type)
                        <div class="service-item d-flex align-items-center justify-content-between py-2 mb-2 rounded">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="service-indicator me-3
                                    @if($type['name'] == 'Repair') bg-danger
                                    @elseif($type['name'] == 'Maintenance') bg-success
                                    @elseif($type['name'] == 'Cleaning/Washing') bg-info
                                    @elseif($type['name'] == 'Inspection') bg-warning
                                    @else bg-secondary @endif">
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">{{ $type['name'] }}</div>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar 
                                            @if($type['name'] == 'Repair') bg-danger
                                            @elseif($type['name'] == 'Maintenance') bg-success
                                            @elseif($type['name'] == 'Cleaning/Washing') bg-info
                                            @elseif($type['name'] == 'Inspection') bg-warning
                                            @else bg-secondary @endif" 
                                            style="width: {{ $type['percentage'] }}%">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <div class="fw-bold text-primary">{{ $type['count'] }}</div>
                                <small class="text-muted">{{ $type['percentage'] }}%</small>
                            </div>
                        </div>
                        @endforeach
                        
                        <!-- Summary -->
                        <div class="bg-light rounded p-2 mt-3 text-center">
                            <div class="row">
                                <div class="col-6">
                                    <div class="fw-bold text-success">{{ $vehicleHistory['total_services'] ?? 0 }}</div>
                                    <small class="text-muted">Maintenance</small>
                                </div>
                                <div class="col-6">
                                    <div class="fw-bold text-info">{{ $vehicleHistory['service_patterns']['maintenance_vs_total']['cleaning_count'] ?? 0 }}</div>
                                    <small class="text-muted">Cleaning</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-chart-pie fa-2x mb-2"></i>
                            <div>No service data available</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Parts & Systems Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-info text-white py-2">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-cog me-2"></i>
                        <span>Parts & Systems</span>
                        <span class="badge bg-white text-info ms-auto">{{ isset($partsAnalysis) ? count($partsAnalysis) : 0 }}</span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(isset($partsAnalysis) && !empty($partsAnalysis))
                        @foreach(array_slice($partsAnalysis, 0, 6) as $partName => $data)
                        <div class="parts-item d-flex align-items-center justify-content-between py-2 mb-2 rounded">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="parts-indicator bg-info me-3"></div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold text-dark">{{ $partName }}</div>
                                    <div class="progress mt-1" style="height: 3px;">
                                        <div class="progress-bar bg-info" style="width: {{ min(100, $data['percentage'] * 2) }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end ms-3">
                                <span class="badge bg-primary">{{ $data['count'] }}</span>
                                <div><small class="text-muted">{{ $data['percentage'] }}%</small></div>
                            </div>
                        </div>
                        @endforeach
                        
                        <!-- Parts Summary -->
                        <div class="bg-light rounded p-2 mt-3 text-center">
                            <div class="fw-bold text-info">{{ count($partsAnalysis) }}</div>
                            <small class="text-muted">Components Serviced</small>
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-cog fa-2x mb-2"></i>
                            <div>No parts data available</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Yearly Costs Card -->
        <div class="col-md-4 mb-3">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header bg-success text-white py-2">
                    <h6 class="mb-0 d-flex align-items-center">
                        <i class="fas fa-chart-line me-2"></i>
                        <span>Yearly Costs</span>
                        <span class="badge bg-white text-success ms-auto">
                            {{ isset($yearlyCosts) ? count($yearlyCosts) : 0 }}
                        </span>
                    </h6>
                </div>
                <div class="card-body p-3">
                    @if(isset($yearlyCosts) && !empty($yearlyCosts))
                        @foreach(array_slice($yearlyCosts, 0, 4, true) as $year => $data)
                        <div class="year-item bg-light rounded p-3 mb-2">
                            <!-- Year Header -->
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="fw-bold text-success">{{ $year }}</div>
                                <div class="text-end">
                                    <div class="text-success fw-bold small">({{ $data['total_cost']['formatted_min'] }} - {{ $data['total_cost']['formatted_max'] }})</div>
                                </div>
                            </div>
                            
                            <!-- Metrics -->
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="fw-bold text-primary">{{ $data['service_counts']['maintenance'] }}</div>
                                    <small class="text-muted">Services</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-warning">{{ $data['service_counts']['repairs'] }}</div>
                                    <small class="text-muted">Repairs</small>
                                </div>
                                <div class="col-4">
                                    <div class="fw-bold text-info">{{ $data['service_counts']['cleaning'] }}</div>
                                    <small class="text-muted">Cleaning</small>
                                </div>
                            </div>
                            
                            <!-- Average Cost -->
                            @if($data['service_counts']['maintenance'] > 0)
                            <div class="text-center mt-2">
                                <div class="bg-white rounded px-2 py-1">
                                    <small class="text-muted">
                                        Average: <span class="fw-bold">RM {{ number_format($data['average_cost_per_service']['min']) }} - {{ number_format($data['average_cost_per_service']['max']) }}</span>
                                    </small>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                        
                        <!-- Total Summary -->
                        @php
                        $totalCostMin = array_sum(array_column(array_column($yearlyCosts, 'total_cost'), 'min'));
                        $totalCostMax = array_sum(array_column(array_column($yearlyCosts, 'total_cost'), 'max'));
                        $totalServices = array_sum(array_column(array_column($yearlyCosts, 'service_counts'), 'maintenance'));
                        @endphp
                        
                        @if($totalServices > 0)
                        <div class="bg-success text-white rounded p-3 text-center">
                            <div class="fw-bold">RM {{ number_format($totalCostMin) }} - {{ number_format($totalCostMax) }}</div>
                            <small>Total Historical Cost ({{ $totalServices }} services)</small>
                        </div>
                        @endif
                        
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                            <div>No yearly data available</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Service Analytics Summary -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-info">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1"><i class="fas fa-chart-line"></i> Maintenance Analytics</h6>
                        <p class="mb-0">
                            Showing <strong>{{ number_format($totalRecords) }}</strong> total records 
                            ({{ number_format($vehicleHistory['total_services'] ?? 0) }} maintenance services, 
                            {{ number_format(($vehicleHistory['service_patterns']['maintenance_vs_total']['cleaning_count'] ?? 0)) }} cleaning services)
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <span class="badge bg-primary">Avg Interval: {{ number_format($vehicleHistory['average_interval'] ?? 0) }} KM</span>
                        <br><small class="text-muted">Calculated from maintenance services only</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Search Records</label>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search description, SR number...">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Service Type</label>
                            <select class="form-select" id="typeFilter">
                                <option value="">All Types</option>
                                <option value="Maintenance">🔧 Maintenance</option>
                                <option value="Repair">⚙️ Repair</option>
                                <option value="Cleaning">🧽 Cleaning/Washing</option>
                                <option value="Inspection">🔍 Inspection</option>
                                <option value="Other">📋 Other</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Priority</label>
                            <select class="form-select" id="priorityFilter">
                                <option value="">All Priorities</option>
                                <option value="Critical">Critical</option>
                                <option value="High">High</option>
                                <option value="Normal">Normal</option>
                                <option value="Low">Low</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select class="form-select" id="yearFilter">
                                <option value="">All Years</option>
                                @if(isset($serviceStats['by_year']))
                                    @foreach($serviceStats['by_year']->keys() as $year)
                                        <option value="{{ $year }}">{{ $year }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button class="btn btn-secondary d-block w-100" onclick="clearFilters()">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maintenance History Table -->
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-list"></i> Complete Maintenance Records</h5>
                <div>
                    <span class="badge bg-primary" id="recordCount">{{ number_format($totalRecords) }} records</span>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="historyTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>SR Number</th>
                            <th>Service Type</th>
                            <th>Description</th>
                            <th>Mileage</th>
                            <th>Priority</th>
                            <!--<th>Status</th>-->
                            <th>Technician</th>
                            <th>Depot</th>
                            <th>Days Ago</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allRecords as $record)
                        <tr class="history-row" 
                            data-type="{{ $record->mr_type_text ?? '' }}"
                            data-priority="{{ $record->priority_text ?? '' }}"
                            data-year="{{ $record->Datereceived ? \Carbon\Carbon::parse($record->Datereceived)->format('Y') : '' }}"
                            data-search="{{ strtolower(($record->Description ?? '') . ' ' . ($record->SR ?? '')) }}">
                            
                            <td>
                                <strong>{{ $record->Datereceived ? \Carbon\Carbon::parse($record->Datereceived)->format('d M Y') : 'Unknown' }}</strong>
                                @if($record->timereceived)
                                    <br><small class="text-muted">{{ $record->timereceived }}</small>
                                @endif
                            </td>
                            
                            <td>
                                <span class="font-monospace">{{ $record->SR ?? 'No SR' }}</span>
                            </td>
                            
                            <td>
                                <span class="badge 
                                    @if(($record->mr_type_text ?? '') == 'Repair') bg-danger
                                    @elseif(($record->mr_type_text ?? '') == 'Cleaning/Washing') bg-info  
                                    @elseif(($record->mr_type_text ?? '') == 'Maintenance') bg-success
                                    @else bg-secondary @endif">
                                    {{ $record->mr_type_text ?? 'Unknown' }}
                                </span>
                            </td>
                            
                            <td>
                                <div class="description-cell">
                                    {{ Str::limit($record->Description ?? 'No description', 80) }}
                                    @if(strlen($record->Description ?? '') > 80)
                                        <button class="btn btn-link btn-sm p-0 ms-1" onclick="showFullDescription('{{ $record->ID }}')">
                                            <i class="fas fa-expand-alt"></i>
                                        </button>
                                    @endif
                                </div>
                                
                                <!-- Hidden full description -->
                                <div class="full-description d-none" id="fullDesc{{ $record->ID }}">
                                    {{ $record->Description ?? 'No description' }}
                                    @if($record->Response && $record->Response !== 'No response')
                                        <hr class="my-2">
                                        <strong>Response:</strong> {{ $record->Response }}
                                    @endif
                                </div>
                            </td>
                            
                            <td>
                                @if($record->Odometer && is_numeric($record->Odometer) && floatval($record->Odometer) > 1000)
                                    <strong>{{ number_format(floatval($record->Odometer)) }} KM</strong>
                                @else
                                    <span class="text-muted">Not recorded</span>
                                @endif
                            </td>
                            
                            <td>
                                <span class="badge priority-badge-{{ strtolower(str_replace(' ', '', $record->priority_text ?? 'unknown')) }}">
                                    {{ $record->priority_text ?? 'Unknown' }}
                                </span>
                            </td>
                            
                            {{-- <td>
                                <span class="badge 
                                    @if(($record->status_text ?? '') == 'Completed') bg-success
                                    @elseif(($record->status_text ?? '') == 'Pending') bg-warning text-dark
                                    @elseif(($record->status_text ?? '') == 'In Progress') bg-info
                                    @else bg-secondary @endif">
                                    {{ $record->status_text ?? 'Unknown' }}
                                </span>
                            </td> --}}
                            
                            <td>
                                <small>
                                    @if($record->responsedBy && $record->responsedBy !== 'Unknown')
                                        {{ $record->responsedBy }}
                                    @elseif($record->InspectBy && $record->InspectBy !== 'Unknown')
                                        {{ $record->InspectBy }}
                                    @else
                                        <span class="text-muted">Not specified</span>
                                    @endif
                                </small>
                            </td>
                            
                            <td>
                                <small>
                                    @if(isset($record->depot_info) && is_array($record->depot_info))
                                        {{ $record->depot_info['id'] }}
                                    @else
                                        {{ $record->Building ?? 'Unknown' }}
                                    @endif
                                </small>
                            </td>
                            
                            <td>
                                <small class="text-muted">
                                    {{ $record->days_ago ?? 'Unknown' }}
                                </small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Full Description Modal -->
<div class="modal fade" id="descriptionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Service Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Search and filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const priorityFilter = document.getElementById('priorityFilter');
    const yearFilter = document.getElementById('yearFilter');
    const rows = document.querySelectorAll('.history-row');
    const recordCount = document.getElementById('recordCount');

    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase();
        const typeValue = typeFilter.value;
        const priorityValue = priorityFilter.value;
        const yearValue = yearFilter.value;
        
        let visibleCount = 0;

        rows.forEach(row => {
            const searchData = row.getAttribute('data-search') || '';
            const typeData = row.getAttribute('data-type') || '';
            const priorityData = row.getAttribute('data-priority') || '';
            const yearData = row.getAttribute('data-year') || '';

            const matchesSearch = searchData.includes(searchTerm);
            const matchesType = !typeValue || typeData === typeValue;
            const matchesPriority = !priorityValue || priorityData === priorityValue;
            const matchesYear = !yearValue || yearData === yearValue;

            if (matchesSearch && matchesType && matchesPriority && matchesYear) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        recordCount.textContent = `${visibleCount.toLocaleString()} records`;
    }

    // Add event listeners
    searchInput.addEventListener('input', filterTable);
    typeFilter.addEventListener('change', filterTable);
    priorityFilter.addEventListener('change', filterTable);
    yearFilter.addEventListener('change', filterTable);
});

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('typeFilter').value = '';
    document.getElementById('priorityFilter').value = '';
    document.getElementById('yearFilter').value = '';
    
    // Trigger filter update
    const event = new Event('input');
    document.getElementById('searchInput').dispatchEvent(event);
}

function showFullDescription(recordId) {
    const fullDesc = document.getElementById('fullDesc' + recordId);
    const modal = new bootstrap.Modal(document.getElementById('descriptionModal'));
    const modalContent = document.getElementById('modalContent');
    
    modalContent.innerHTML = fullDesc.innerHTML;
    modal.show();
}
</script>

<style>
/* Priority badge styling */
.priority-badge-critical {
    background: linear-gradient(45deg, #dc3545, #c82333) !important;
    color: white !important;
}

.priority-badge-high {
    background: linear-gradient(45deg, #ffc107, #e0a800) !important;
    color: #212529 !important;
}

.priority-badge-normal, .priority-badge-low {
    background: linear-gradient(45deg, #6c757d, #545b62) !important;
    color: white !important;
}

.priority-badge-unknown {
    background: linear-gradient(45deg, #6c757d, #495057) !important;
    color: white !important;
}

/* Table styling */
.table th {
    font-weight: 600;
    font-size: 0.9rem;
}

.table td {
    vertical-align: middle;
    font-size: 0.85rem;
}

.description-cell {
    max-width: 300px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.8rem;
    }
    
    .description-cell {
        max-width: 200px;
    }
}

@media print {
    .btn, .card-header, .modal {
        display: none !important;
    }
    
    .table {
        font-size: 0.7rem;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}

/* Card Base */
.card {
    transition: all 0.2s ease;
    border-radius: 8px !important;
}

.card:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
    border-bottom: none;
}

/* Service Items */
.service-item {
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.service-item:hover {
    background-color: rgba(0,123,255,0.05);
    border-color: rgba(0,123,255,0.1);
}

/* Service Indicator (colored circles) */
.service-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Parts Items */
.parts-item {
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.parts-item:hover {
    background-color: rgba(23,162,184,0.05);
    border-color: rgba(23,162,184,0.1);
}

/* Parts Indicator */
.parts-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Year Items */
.year-item {
    transition: all 0.2s ease;
    border: 1px solid rgba(0,0,0,0.1);
}

.year-item:hover {
    border-color: #28a745;
    box-shadow: 0 2px 8px rgba(40,167,69,0.1);
}

/* Typography */
.fw-semibold {
    font-weight: 600;
}

/* Progress Bars */
.progress {
    background-color: rgba(0,0,0,0.08);
    border-radius: 2px;
}

.progress-bar {
    border-radius: 2px;
}

/* Badges */
.badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
}

/* Empty State Icons */
.fa-2x {
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .card-body {
        padding: 1rem !important;
    }
    
    .service-item,
    .parts-item {
        padding: 0.75rem !important;
    }
    
    .year-item {
        padding: 1rem !important;
        margin-bottom: 0.75rem !important;
    }
    
    .service-indicator,
    .parts-indicator {
        width: 10px;
        height: 10px;
    }
}

/* Text Utilities */
.text-muted {
    color: #6c757d !important;
}

/* Background Utilities */
.bg-light {
    background-color: #f8f9fa !important;
}

/* Hover Effects */
.service-item:hover .service-indicator,
.parts-item:hover .parts-indicator {
    transform: scale(1.2);
    transition: transform 0.2s ease;
}

/* Card Content Max Height for Scrolling */
.card-body {
    max-height: 400px;
    overflow-y: auto;
}

/* Custom Scrollbar */
.card-body::-webkit-scrollbar {
    width: 4px;
}

.card-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.card-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.card-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Clean Spacing */
.mb-2 {
    margin-bottom: 0.5rem !important;
}

.mb-3 {
    margin-bottom: 1rem !important;
}

.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.px-2 {
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
}
</style>

@endsection