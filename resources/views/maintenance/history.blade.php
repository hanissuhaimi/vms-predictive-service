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
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <h2 class="text-primary">{{ number_format($totalRecords) }}</h2>
                    <h6 class="card-title">Total Services</h6>
                    <p class="text-muted small">Complete maintenance history</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-info h-100">
                <div class="card-body text-center">
                    <h2 class="text-info">{{ $vehicleHistory['service_patterns']['services_per_month'] ?? 'N/A' }}</h2>
                    <h6 class="card-title">Services/Month</h6>
                    <p class="text-muted small">Average frequency</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <h2 class="text-warning">{{ number_format($vehicleHistory['average_interval']) }} KM</h2>
                    <h6 class="card-title">Avg Interval</h6>
                    <p class="text-muted small">Between services</p>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
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
            <div class="card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-pie"></i> Service Types</h6>
                </div>
                <div class="card-body">
                    @if(isset($serviceStats['by_type']) && $serviceStats['by_type']->isNotEmpty())
                        @foreach($serviceStats['by_type'] as $type)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ $type['name'] }}</span>
                                <span><strong>{{ $type['count'] }}</strong> ({{ $type['percentage'] }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar 
                                    @if($type['name'] == 'Repair') bg-danger
                                    @elseif($type['name'] == 'Maintenance') bg-success
                                    @elseif($type['name'] == 'Cleaning/Washing') bg-info
                                    @else bg-secondary @endif" 
                                    style="width: {{ $type['percentage'] }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted">No service type data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Parts Analysis -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-cog"></i> Parts & Systems</h6>
                </div>
                <div class="card-body">
                    @if(isset($partsAnalysis) && !empty($partsAnalysis))
                        @foreach(array_slice($partsAnalysis, 0, 6) as $partName => $data)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="small">{{ $partName }}</span>
                                <span class="small"><strong>{{ $data['count'] }}</strong> ({{ $data['percentage'] }}%)</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: {{ min(100, $data['percentage'] * 2) }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted">No parts data available</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Yearly Trends -->
        <div class="col-md-4 mb-3">
            <div class="card h-100">
                <div class="card-header">
                    <h6><i class="fas fa-chart-line"></i> Yearly Activity</h6>
                </div>
                <div class="card-body">
                    @if(isset($serviceStats['by_year']) && $serviceStats['by_year']->isNotEmpty())
                        @foreach($serviceStats['by_year']->take(5) as $year => $count)
                        <div class="mb-2">
                            <div class="d-flex justify-content-between">
                                <span>{{ $year }}</span>
                                <span><strong>{{ $count }}</strong> services</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                @php
                                    $maxCount = $serviceStats['by_year']->max();
                                    $percentage = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                                @endphp
                                <div class="progress-bar bg-info" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <p class="text-muted">No yearly data available</p>
                    @endif
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
                                <option value="Repair">Repair</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Cleaning">Cleaning</option>
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
                            <th>Status</th>
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
                            
                            <td>
                                <span class="badge 
                                    @if(($record->status_text ?? '') == 'Completed') bg-success
                                    @elseif(($record->status_text ?? '') == 'Pending') bg-warning text-dark
                                    @elseif(($record->status_text ?? '') == 'In Progress') bg-info
                                    @else bg-secondary @endif">
                                    {{ $record->status_text ?? 'Unknown' }}
                                </span>
                            </td>
                            
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
</style>

@endsection