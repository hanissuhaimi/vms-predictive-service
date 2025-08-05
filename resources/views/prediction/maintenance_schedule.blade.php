@extends('layouts.app')

@section('title', 'Maintenance Schedule - ' . $vehicle)

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-12">
        
        <!-- Vehicle Summary Header -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h4><i class="fas fa-truck"></i> {{ $vehicle }}</h4>
                        <p class="mb-0">Current Mileage: {{ number_format($currentMileage) }} KM</p>
                        <small>{{ $vehicleHistory['total_services'] }} maintenance services | {{ $vehicleHistory['total_all_records'] }} total records</small>
                        @if(isset($vehicleHistory['service_patterns']))
                        <br><small>{{ $vehicleHistory['service_patterns']['usage_pattern'] }}</small>
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5>Vehicle Maintenance Analysis</h5>
                        <small>Generated: {{ now()->format('d M Y, g:i A') }}</small>
                        @if(isset($mlPrediction['source']))
                        <br><small class="badge bg-info">{{ $mlPrediction['source'] }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 🎯 PRIORITY ALERT - MOVED TO TOP -->
        {{-- @if($recommendations['priority'] == 'immediate' || $recommendations['priority'] == 'critical_safety')
            <div class="alert alert-danger alert-lg mb-4 position-relative">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5><i class="fas fa-exclamation-triangle"></i> 🚨 IMMEDIATE ATTENTION REQUIRED</h5>
                        <p class="mb-0">Based on {{ $vehicleHistory['total_services'] }} service records, this vehicle needs urgent maintenance.</p>
                        @if(isset($safetyAnalysis['critical_alerts']) && !empty($safetyAnalysis['critical_alerts']))
                            <div class="mt-2">
                                @foreach($safetyAnalysis['critical_alerts'] as $alert)
                                <div class="mb-1"><strong>{{ strtoupper(str_replace('_', ' ', $alert['system'])) }}:</strong> {{ $alert['message'] }}</div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="col-md-4 text-center">
                        <button class="btn btn-light btn-lg pulse-animation" onclick="alert('Emergency Contact: Call fleet manager immediately!')">
                            <i class="fas fa-phone"></i> EMERGENCY CONTACT
                        </button>
                    </div>
                </div>
            </div>
        @elseif($recommendations['priority'] == 'high' || $recommendations['priority'] == 'high_safety')
            <div class="alert alert-warning alert-lg mb-4">
                <h5><i class="fas fa-clock"></i> ⚠️ SERVICE NEEDED SOON</h5>
                <p class="mb-0">Fleet analysis of {{ $vehicleHistory['total_services'] }} services indicates maintenance needed within 2 weeks.</p>
            </div>
        @else
            <div class="alert alert-info alert-lg mb-4">
                <h5><i class="fas fa-check-circle"></i> ✅ ROUTINE MAINTENANCE</h5>
                <p class="mb-0">Vehicle is following good maintenance patterns based on {{ $vehicleHistory['total_services'] }} service records.</p>
            </div>
        @endif --}}

        <!-- Service Schedule Prediction -->
        <div class="row mb-4">
            <!-- Next Routine Service -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Next Routine Service</h5>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="text-primary">{{ number_format($serviceSchedule['next_routine']['mileage']) }} KM</h3>
                        <p class="mb-2">
                            <strong>Distance:</strong> {{ number_format($serviceSchedule['next_routine']['km_remaining']) }} KM to go
                        </p>
                        <p class="mb-2">
                            <strong>Estimated:</strong> {{ $serviceSchedule['days_estimate'] }}
                        </p>
                        <p class="text-muted small">{{ $serviceSchedule['next_routine']['description'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Next Major Service -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tools"></i> Next Major Service</h5>
                    </div>
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ number_format($serviceSchedule['next_major']['mileage']) }} KM</h3>
                        <p class="mb-2">
                            <strong>Distance:</strong> {{ number_format($serviceSchedule['next_major']['km_remaining']) }} KM to go
                        </p>
                        <p class="mb-2">
                            <strong>Type:</strong> {{ $serviceSchedule['next_major']['type'] }}
                        </p>
                        <p class="text-muted small">{{ $serviceSchedule['next_major']['description'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Estimated Cost -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> 💰 Estimated Cost</h5>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $costMin = $recommendations['cost_estimate']['min'] ?? 300;
                            $costMax = $recommendations['cost_estimate']['max'] ?? 600;
                        @endphp
                        
                        <h3 class="text-success">
                            RM {{ number_format($costMin) }} - RM {{ number_format($costMax) }}
                        </h3>
                        
                        <p>Based on {{ $vehicleHistory['total_services'] ?? 0 }} maintenance services ({{ $vehicleHistory['total_all_records'] ?? 0 }} total records)</p>
                        
                        @if(isset($costAnalysis['cost_confidence']))
                        <span class="badge bg-{{ $costAnalysis['cost_confidence'] === 'high' ? 'success' : ($costAnalysis['cost_confidence'] === 'medium' ? 'warning' : 'secondary') }}">
                            {{ ucfirst($costAnalysis['cost_confidence']) }} Confidence
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Estimated Time -->
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-clock"></i> ⏰ Estimated Time</h5>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $timeEstimate = $recommendations['time_estimate'] ?? '2-4 hours';
                        @endphp
                        
                        <h3 class="text-warning">{{ $timeEstimate }}</h3>
                        
                        <p class="text-muted">Maintenance time required</p>
                        
                        @if(isset($recommendations['safety_priority']) && $recommendations['safety_priority'])
                        <span class="badge bg-danger">Safety Priority</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 📋 ACTION PLAN - MOVED AFTER PARTS -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-list-ol"></i> 📋 Recommended Action Plan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        @php
                            $actionPlan = $recommendations['action_plan'] ?? ['📅 Schedule regular vehicle maintenance'];
                            $priority = $recommendations['priority'] ?? 'routine';
                        @endphp
                        
                        @if(!empty($actionPlan))
                            <ol class="list-group list-group-numbered">
                                @foreach($actionPlan as $action)
                                <li class="list-group-item">{!! $action !!}</li>
                                @endforeach
                                <li class="list-group-item">📞 Contact the manager to schedule maintenance</li>
                                <li class="list-group-item">🔧 Follow recommended maintenance procedures</li>
                                <li class="list-group-item">📅 Schedule next check at {{ number_format($serviceSchedule['next_routine']['mileage'] ?? 0) }} KM</li>
                            </ol>
                        @else
                            <p class="text-muted">No specific action plan available. Continue with regular vehicle operations.</p>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-light">
                            <h6><i class="fas fa-info-circle"></i> Vehicle Analytics</h6>
                            <p class="mb-1"><strong>Maintenance Services:</strong> {{ $vehicleHistory['total_services'] ?? 0 }}</p>
                            @if(isset($vehicleHistory['service_patterns']['maintenance_vs_total']))
                            <p class="mb-1"><strong>Cleaning Services:</strong> {{ $vehicleHistory['service_patterns']['maintenance_vs_total']['cleaning_count'] ?? 0 }}</p>
                            @endif
                            <p class="mb-1"><strong>Total Records:</strong> {{ $vehicleHistory['total_all_records'] ?? 0 }}</p>
                            @if(isset($vehicleHistory['last_service']) && $vehicleHistory['last_service'])
                            <p class="mb-1"><strong>Last Maintenance:</strong> {{ Carbon\Carbon::parse($vehicleHistory['last_service']->Datereceived)->format('d M Y') }}</p>
                            <p class="mb-1"><strong>Days Ago:</strong> {{ intval($vehicleHistory['days_since_last'] ?? 0) }} days</p>
                            @endif
                            <p class="mb-1"><strong>Avg Interval:</strong> {{ number_format($vehicleHistory['average_interval'] ?? 1000) }} KM</p>
                            <p class="mb-0"><strong>Vehicle Type:</strong> {{ $vehicleHistory['vehicle_type'] ?? 'Unknown' }}</p>
                        </div>
                        @if(isset($vehicleHistory['service_patterns']['service_breakdown']))
                        <div class="alert alert-info mt-2">
                            <h6><i class="fas fa-chart-pie"></i> Service Breakdown</h6>
                            <div class="row text-sm">
                                <div class="col-6">
                                    <p class="mb-1">🔧 <strong>Maintenance:</strong> {{ $vehicleHistory['service_patterns']['service_breakdown']['maintenance'] ?? 0 }}</p>
                                    <p class="mb-1">⚙️ <strong>Repairs:</strong> {{ $vehicleHistory['service_patterns']['service_breakdown']['repairs'] ?? 0 }}</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-1">🧽 <strong>Cleaning:</strong> {{ $vehicleHistory['service_patterns']['service_breakdown']['cleaning'] ?? 0 }}</p>
                                    <p class="mb-1">🔍 <strong>Inspections:</strong> {{ $vehicleHistory['service_patterns']['service_breakdown']['inspections'] ?? 0 }}</p>
                                </div>
                            </div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Maintenance interval calculated from {{ $vehicleHistory['total_services'] }} maintenance services only
                            </small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @php
        // Calculate total costs for immediate parts
        $immediateMinTotal = 0;
        $immediateMaxTotal = 0;
        if (!empty($partsAnalysis['immediate'])) {
            foreach ($partsAnalysis['immediate'] as $part) {
                $immediateMinTotal += $part['cost_range']['min'] ?? 0;
                $immediateMaxTotal += $part['cost_range']['max'] ?? 0;
            }
        }

        // Calculate total costs for soon parts  
        $soonMinTotal = 0;
        $soonMaxTotal = 0;
        if (!empty($partsAnalysis['soon'])) {
            foreach ($partsAnalysis['soon'] as $part) {
                $soonMinTotal += $part['cost_range']['min'] ?? 0;
                $soonMaxTotal += $part['cost_range']['max'] ?? 0;
            }
        }

        // Combined totals
        $combinedMinTotal = $immediateMinTotal + $soonMinTotal;
        $combinedMaxTotal = $immediateMaxTotal + $soonMaxTotal;
        @endphp

        <!-- 🔧 PARTS REQUIRING ATTENTION - WITH COMPLETE SERVICE DETAILS -->
        @if(!empty($partsAnalysis['immediate']) || !empty($partsAnalysis['soon']))
        <div class="row parts-attention-row mb-4">
            @if(!empty($partsAnalysis['immediate']))
            <div class="col-lg-6 parts-attention-col mb-4">
                <div class="card border-danger attention-card parts-attention-card">
                    <div class="card-header bg-danger text-white">
                        <div class="card-header-with-total w-100">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> 🚨 Immediate ({{ count($partsAnalysis['immediate']) }})</h5>
                                <small>Action required now</small>
                            </div>
                            @if($immediateMinTotal > 0)
                            <div class="header-cost-preview">
                                <i class="fas fa-calculator"></i> Est: RM {{ number_format($immediateMinTotal) }} - RM {{ number_format($immediateMaxTotal) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="parts-scrollable-content">
                            @foreach($partsAnalysis['immediate'] as $index => $part)
                            <div class="part-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 text-danger">
                                            {{ $part['part'] }}
                                            @if($part['is_critical'] ?? false)
                                                <i class="fas fa-exclamation-triangle text-warning" title="Critical Component"></i>
                                            @endif
                                        </h6>
                                        <small class="text-muted">{{ $part['reason'] }}</small>
                                    </div>
                                    <span class="badge bg-danger">{{ $part['status'] === 'overdue' ? 'OVERDUE' : 'DUE' }}</span>
                                </div>
                                
                                <!-- COMPLETE COLLAPSIBLE SERVICE DETAILS -->
                                @if(isset($part['last_service_details']) && $part['last_service_details'])
                                <div class="mb-3">
                                    <!-- Toggle Button -->
                                    <button class="btn btn-outline-primary btn-sm" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#serviceDetails{{ $index }}" 
                                            aria-expanded="false" 
                                            aria-controls="serviceDetails{{ $index }}">
                                        <i class="fas fa-eye me-1"></i> View Last Service Details
                                        <i class="fas fa-chevron-down ms-1 toggle-icon"></i>
                                    </button>
                                    
                                    <!-- Collapsible Content -->
                                    <div class="collapse mt-2" id="serviceDetails{{ $index }}">
                                        <div class="card service-details-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-primary mb-2">
                                                    <i class="fas fa-wrench"></i> Last Service Details
                                                </h6>
                                                
                                                <div class="row text-sm">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Date:</strong> {{ $part['last_service'] }}</p>
                                                        <p class="mb-1"><strong>SR Number:</strong> {{ $part['last_service_details']['sr_number'] }}</p>
                                                        <p class="mb-1"><strong>Mileage:</strong> {{ $part['last_service_km'] }}</p>
                                                        <p class="mb-1"><strong>Service Type:</strong> 
                                                            <span class="badge bg-info">{{ $part['last_service_details']['service_type'] }}</span>
                                                        </p>
                                                        <p class="mb-1"><strong>Priority:</strong> 
                                                            @php
                                                                $priorityClass = 'priority-badge-' . strtolower(str_replace(' ', '', $part['last_service_details']['priority']));
                                                            @endphp
                                                            <span class="badge service-badge {{ $priorityClass }}">
                                                                {{ $part['last_service_details']['priority'] }}
                                                            </span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Requested by:</strong> {{ $part['last_service_details']['requested_by'] }}</p>
                                                        <p class="mb-1"><strong>Serviced by:</strong> {{ $part['last_service_details']['serviced_by'] }}</p>
                                                        @if($part['last_service_details']['contractor'] !== 'No contractor')
                                                        <p class="mb-1"><strong>Contractor:</strong> {{ $part['last_service_details']['contractor'] }}</p>
                                                        @endif
                                                        <p class="mb-1"><strong>Depot:</strong> 
                                                            @if(isset($part['last_service_details']['depot_info']) && is_array($part['last_service_details']['depot_info']))
                                                                {{ $part['last_service_details']['depot_info']['name'] }} ({{ $part['last_service_details']['depot_info']['id'] }})
                                                            @else
                                                                {{ $part['last_service_details']['building'] ?? 'Unknown' }}
                                                            @endif
                                                        </p>
                                                        <p class="mb-1"><strong>Status:</strong> 
                                                            <span class="badge bg-success">{{ $part['last_service_details']['status'] }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                @if($part['last_service_details']['description'] !== 'No description')
                                                <div class="mt-2">
                                                    <strong>Description:</strong>
                                                    <p class="text-muted small mb-1">{{ $part['last_service_details']['description'] }}</p>
                                                </div>
                                                @endif
                                                
                                                @if($part['last_service_details']['response'] !== 'No response')
                                                <div class="mt-2">
                                                    <strong>Work Done:</strong>
                                                    <p class="text-success small mb-1">{{ Str::limit($part['last_service_details']['response'], 150) }}</p>
                                                </div>
                                                @endif
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> {{ $part['last_service_details']['days_ago'] }}
                                                        @if($part['last_service_details']['date_closed'] !== 'Not closed')
                                                            | Closed: {{ $part['last_service_details']['date_closed'] }}
                                                        @endif
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="alert alert-warning py-2 mb-3">
                                    <small><i class="fas fa-info-circle"></i> No detailed service history found for this component</small>
                                </div>
                                @endif
                                
                                <!-- SIMPLIFIED COST DISPLAY (No KM info) -->
                                <div class="text-sm">
                                    <strong>Cost Estimate:</strong><br>
                                    <small class="text-success cost-highlight">RM {{ number_format($part['cost_range']['min']) }} - {{ number_format($part['cost_range']['max']) }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- TOTAL COST SUMMARY FOR IMMEDIATE -->
                        @if($immediateMinTotal > 0)
                        <div class="total-cost-summary total-cost-immediate">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-danger">
                                    <i class="fas fa-calculator"></i> Immediate Service Total Cost
                                </h6>
                                <span class="cost-highlight-total">RM {{ number_format($immediateMinTotal) }} - RM {{ number_format($immediateMaxTotal) }}</span>
                            </div>
                            
                            @foreach($partsAnalysis['immediate'] as $part)
                            <div class="cost-breakdown-item">
                                <span>{{ $part['part'] }}</span>
                                <span class="text-success">RM {{ number_format($part['cost_range']['min']) }} - RM {{ number_format($part['cost_range']['max']) }}</span>
                            </div>
                            @endforeach
                            
                            <div class="total-divider immediate-divider">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-danger">TOTAL ESTIMATED IMMEDIATE SERVICE:</strong>
                                    <strong class="cost-highlight-total">RM {{ number_format($immediateMinTotal) }} - RM {{ number_format($immediateMaxTotal) }}</strong>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-exclamation-triangle"></i> Urgent attention required for all items
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- SOON PARTS - WITH COMPLETE SERVICE DETAILS -->
            @if(!empty($partsAnalysis['soon']))
            <div class="col-lg-6 parts-attention-col mb-4">
                <div class="card border-warning parts-attention-card">
                    <div class="card-header bg-warning text-dark">
                        <div class="card-header-with-total w-100">
                            <div>
                                <h5 class="mb-0"><i class="fas fa-clock"></i> ⚠️ Next Service ({{ count($partsAnalysis['soon']) }})</h5>
                                <small>Service within 2-4 weeks</small>
                            </div>
                            @if($soonMinTotal > 0)
                            <div class="header-cost-preview">
                                <i class="fas fa-calculator"></i> Est: RM {{ number_format($soonMinTotal) }} - RM {{ number_format($soonMaxTotal) }}
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="parts-scrollable-content">
                            @foreach($partsAnalysis['soon'] as $index => $part)
                            <div class="part-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1">
                                            {{ $part['part'] }}
                                            @if($part['is_critical'] ?? false)
                                                <i class="fas fa-star text-warning" title="Critical Component"></i>
                                            @endif
                                        </h6>
                                        <small class="text-muted">{{ $part['reason'] }}</small>
                                    </div>
                                    <span class="badge bg-warning text-dark">Soon</span>
                                </div>
                                
                                <!-- COMPLETE COLLAPSIBLE SERVICE DETAILS FOR SOON PARTS -->
                                @if(isset($part['last_service_details']) && $part['last_service_details'])
                                <div class="mb-3">
                                    <!-- Toggle Button -->
                                    <button class="btn btn-outline-warning btn-sm" type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#soonServiceDetails{{ $index }}" 
                                            aria-expanded="false" 
                                            aria-controls="soonServiceDetails{{ $index }}">
                                        <i class="fas fa-eye me-1"></i> View Service History
                                        <i class="fas fa-chevron-down ms-1 toggle-icon"></i>
                                    </button>
                                    
                                    <!-- Collapsible Content -->
                                    <div class="collapse mt-2" id="soonServiceDetails{{ $index }}">
                                        <div class="card service-details-card">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-warning mb-2">
                                                    <i class="fas fa-tools"></i> Last Service History
                                                </h6>
                                                
                                                <div class="row text-sm">
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Date:</strong> {{ $part['last_service'] }}</p>
                                                        <p class="mb-1"><strong>SR:</strong> {{ $part['last_service_details']['sr_number'] }}</p>
                                                        <p class="mb-1"><strong>Mileage:</strong> {{ $part['last_service_km'] }}</p>
                                                        <p class="mb-1"><strong>Type:</strong> 
                                                            <span class="badge bg-secondary">{{ $part['last_service_details']['service_type'] }}</span>
                                                        </p>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <p class="mb-1"><strong>Technician:</strong> {{ $part['last_service_details']['serviced_by'] }}</p>
                                                        <p class="mb-1"><strong>Depot:</strong> 
                                                            @if(isset($part['last_service_details']['depot_info']))
                                                                {{ $part['last_service_details']['depot_info']['name'] }} ({{ $part['last_service_details']['depot_info']['id'] }})
                                                            @else
                                                                {{ $part['last_service_details']['building'] ?? 'Unknown' }}
                                                            @endif
                                                        </p>
                                                        @if($part['last_service_details']['contractor'] !== 'No contractor')
                                                        <p class="mb-1"><strong>Contractor:</strong> {{ $part['last_service_details']['contractor'] }}</p>
                                                        @endif
                                                        <p class="mb-1"><strong>Status:</strong> 
                                                            <span class="badge bg-success">{{ $part['last_service_details']['status'] }}</span>
                                                        </p>
                                                    </div>
                                                </div>
                                                
                                                @if($part['last_service_details']['description'] !== 'No description')
                                                <div class="mt-2">
                                                    <strong>Work Description:</strong>
                                                    <p class="text-muted small">{{ Str::limit($part['last_service_details']['description'], 120) }}</p>
                                                </div>
                                                @endif
                                                
                                                <div class="mt-2">
                                                    <small class="text-muted">
                                                        <i class="fas fa-history"></i> {{ $part['last_service_details']['days_ago'] }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="alert alert-info py-2 mb-3">
                                    <small><i class="fas fa-search"></i> No recent service history found for this component</small>
                                </div>
                                @endif
                                <div class="text-sm">
                                    <strong>Estimated Cost:</strong><br>
                                    <small class="text-success">RM {{ number_format($part['cost_range']['min']) }} - {{ number_format($part['cost_range']['max']) }}</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <!-- TOTAL COST SUMMARY FOR SOON -->
                        @if($soonMinTotal > 0)
                        <div class="total-cost-summary total-cost-soon">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-calculator"></i> Next Service Total Cost
                                </h6>
                                <span class="cost-highlight-total">RM {{ number_format($soonMinTotal) }} - RM {{ number_format($soonMaxTotal) }}</span>
                            </div>
                            
                            @foreach($partsAnalysis['soon'] as $part)
                            <div class="cost-breakdown-item">
                                <span>{{ $part['part'] }}</span>
                                <span class="text-success">RM {{ number_format($part['cost_range']['min']) }} - RM {{ number_format($part['cost_range']['max']) }}</span>
                            </div>
                            @endforeach
                            
                            <div class="total-divider soon-divider">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-warning">TOTAL ESTIMATED NEXT SERVICE:</strong>
                                    <strong class="cost-highlight-total">RM {{ number_format($soonMinTotal) }} - RM {{ number_format($soonMaxTotal) }}</strong>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-calendar-alt"></i> Schedule within 2-4 weeks
                                </small>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- COMBINED TOTAL SUMMARY -->
        @if($combinedMinTotal > 0)
        <div class="alert alert-info mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="mb-2">
                        <i class="fas fa-money-bill-wave"></i> Total Maintenance Budget Required
                    </h5>
                    <p class="mb-0">Combined estimate for all immediate and upcoming maintenance items</p>
                    <small class="text-muted">
                        • Immediate: RM {{ number_format($immediateMinTotal) }} - RM {{ number_format($immediateMaxTotal) }}
                        @if($soonMinTotal > 0)
                        <br>• Upcoming: RM {{ number_format($soonMinTotal) }} - RM {{ number_format($soonMaxTotal) }}
                        @endif
                    </small>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="cost-highlight-total" style="font-size: 1.3rem;">
                        RM {{ number_format($combinedMinTotal) }} - RM {{ number_format($combinedMaxTotal) }}
                    </div>
                    <small class="text-muted d-block mt-1">
                        @if($immediateMinTotal > 0 && $soonMinTotal > 0)
                            Immediate + Upcoming
                        @elseif($immediateMinTotal > 0)
                            Immediate Only
                        @else
                            Upcoming Only
                        @endif
                    </small>
                </div>
            </div>
        </div>
        @endif

        <!-- ROUTINE PARTS STATUS - ENHANCED WITH SERVICE DETAILS -->
        @if(!empty($partsAnalysis['routine']))
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>📊 Routine Parts Status ({{ count($partsAnalysis['routine']) }})</h5>
                <small>Components in good condition based on vehicle maintenance intervals</small>
            </div>
            <div class="card-body">
                @php
                    $priority1Parts = collect($partsAnalysis['routine'])->where('priority', 1);
                    $priority2Parts = collect($partsAnalysis['routine'])->where('priority', 2);
                    $priority3Parts = collect($partsAnalysis['routine'])->where('priority', 3);
                @endphp

                @if($priority1Parts->isNotEmpty())
                <div class="mb-4">
                    <h6 class="text-danger"><i class="fas fa-shield-alt me-1"></i>Priority 1 - Critical Safety Components</h6>
                    <div class="row">
                        @foreach($priority1Parts as $index => $part)
                        <div class="col-md-6 mb-3">
                            <div class="card border-success h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-danger me-1">P1</span>
                                                {{ $part['part'] }}
                                            </h6>
                                            <small class="text-muted">{{ $part['reason'] }}</small>
                                        </div>
                                        <span class="badge bg-success">OK</span>
                                    </div>
                                    
                                    <!-- COLLAPSIBLE SERVICE HISTORY FOR PRIORITY 1 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="mb-2">
                                        <!-- Toggle Button -->
                                        <button class="btn btn-outline-info btn-sm service-details-toggle" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#routineP1Details{{ $index }}" 
                                                aria-expanded="false" 
                                                aria-controls="routineP1Details{{ $index }}">
                                            <i class="fas fa-eye me-1"></i> View Service History
                                            <i class="fas fa-chevron-down ms-1 toggle-icon"></i>
                                        </button>
                                        
                                        <!-- Collapsible Content -->
                                        <div class="collapse mt-2" id="routineP1Details{{ $index }}">
                                            <div class="service-summary p-2 rounded">
                                                <h6 class="text-success mb-1" style="font-size: 0.85rem;">
                                                    <i class="fas fa-check-circle"></i> Recent Service
                                                </h6>
                                                <div class="row text-sm">
                                                    <div class="col-12">
                                                        <p class="mb-1"><strong>{{ $part['last_service'] }}</strong> ({{ $part['last_service_details']['days_ago'] }})</p>
                                                        <p class="mb-1">SR: {{ $part['last_service_details']['sr_number'] }} | {{ $part['last_service_km'] }}</p>
                                                        <p class="mb-1">
                                                            <span class="badge bg-info">{{ $part['last_service_details']['service_type'] }}</span>
                                                            @php
                                                                $priorityClass = 'priority-badge-' . strtolower(str_replace(' ', '', $part['last_service_details']['priority']));
                                                            @endphp
                                                            <span class="badge service-badge {{ $priorityClass }}">{{ $part['last_service_details']['priority'] }}</span>
                                                        </p>
                                                        @if($part['last_service_details']['serviced_by'] !== 'Unknown')
                                                        <p class="mb-1"><small><strong>Tech:</strong> {{ $part['last_service_details']['serviced_by'] }}</small></p>
                                                        @endif
                                                        @if(isset($part['last_service_details']['depot_info']))
                                                        <p class="mb-1"><small><strong>Depot:</strong> {{ $part['last_service_details']['depot_info']['name'] }}</small></p>
                                                        @endif
                                                        @if($part['last_service_details']['contractor'] !== 'No contractor')
                                                        <p class="mb-1"><small><strong>Contractor:</strong> {{ $part['last_service_details']['contractor'] }}</small></p>
                                                        @endif
                                                        @if($part['last_service_details']['description'] !== 'No description')
                                                        <p class="mb-0"><small><strong>Work:</strong> {{ Str::limit($part['last_service_details']['description'], 80) }}</small></p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="text-sm">
                                        <strong>Estimated Cost:</strong><br>
                                        <small class="text-muted">RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                    </div>

                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Regular maintenance schedule
                                        </small>
                                    </div>

                                    @if(isset($part['service_count']) && $part['service_count'] > 0)
                                    <div class="mt-1">
                                        <small class="text-info">Vehicle history: {{ $part['service_count'] }} services</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($priority2Parts->isNotEmpty())
                <div class="mb-4">
                    <h6 class="text-warning"><i class="fas fa-cog me-1"></i>Priority 2 - Important Maintenance Components</h6>
                    <div class="row">
                        @foreach($priority2Parts as $index => $part)
                        <div class="col-md-6 mb-3">
                            <div class="card border-info h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-warning text-dark me-1">P2</span>
                                                {{ $part['part'] }}
                                            </h6>
                                            <small class="text-muted">{{ $part['reason'] }}</small>
                                        </div>
                                        <span class="badge bg-info">Good</span>
                                    </div>
                                    
                                    <!-- COLLAPSIBLE SERVICE HISTORY FOR PRIORITY 2 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="mb-2">
                                        <!-- Toggle Button -->
                                        <button class="btn btn-outline-info btn-sm service-details-toggle" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#routineP2Details{{ $index }}" 
                                                aria-expanded="false" 
                                                aria-controls="routineP2Details{{ $index }}">
                                            <i class="fas fa-eye me-1"></i> View Maintenance
                                            <i class="fas fa-chevron-down ms-1 toggle-icon"></i>
                                        </button>
                                        
                                        <!-- Collapsible Content -->
                                        <div class="collapse mt-2" id="routineP2Details{{ $index }}">
                                            <div class="service-summary p-2 rounded">
                                                <h6 class="text-info mb-1" style="font-size: 0.85rem;">
                                                    <i class="fas fa-wrench"></i> Last Maintenance
                                                </h6>
                                                <div class="text-sm">
                                                    <p class="mb-1">{{ $part['last_service'] }} | {{ $part['last_service_km'] }}</p>
                                                    <p class="mb-1">SR: {{ $part['last_service_details']['sr_number'] }}</p>
                                                    <p class="mb-1">
                                                        <span class="badge bg-secondary">{{ $part['last_service_details']['service_type'] }}</span>
                                                        @if($part['last_service_details']['contractor'] !== 'No contractor')
                                                            <span class="badge bg-primary">{{ Str::limit($part['last_service_details']['contractor'], 15) }}</span>
                                                        @endif
                                                        @if(isset($part['last_service_details']['depot_info']))
                                                            <span class="badge bg-secondary">{{ $part['last_service_details']['depot_info']['id'] }}</span>
                                                        @endif
                                                    </p>
                                                    @if($part['last_service_details']['description'] !== 'No description')
                                                    <p class="mb-0"><small>{{ Str::limit($part['last_service_details']['description'], 60) }}</small></p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="text-sm">
                                        <strong>Estimated Cost:</strong><br>
                                        <small class="text-muted">RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Regular maintenance schedule
                                        </small>
                                    </div>
                                    @if(isset($part['service_count']) && $part['service_count'] > 0)
                                    <div class="mt-1">
                                        <small class="text-secondary">{{ $part['service_count'] }} vehicle services</small>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($priority3Parts->isNotEmpty())
                <div class="mb-3">
                    <h6 class="text-secondary"><i class="fas fa-wrench me-1"></i>Priority 3 - Routine Maintenance Components</h6>
                    <div class="row">
                        @foreach($priority3Parts as $index => $part)
                        <div class="col-md-6 mb-3">
                            <div class="card border-secondary h-100">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1">
                                                <span class="badge bg-secondary me-1">P3</span>
                                                {{ $part['part'] }}
                                            </h6>
                                            <small class="text-muted">{{ $part['reason'] }}</small>
                                        </div>
                                        <span class="badge bg-secondary">Routine</span>
                                    </div>
                                    
                                    <!-- COLLAPSIBLE SERVICE HISTORY FOR PRIORITY 3 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="mb-2">
                                        <!-- Toggle Button -->
                                        <button class="btn btn-outline-secondary btn-sm service-details-toggle" type="button" 
                                                data-bs-toggle="collapse" 
                                                data-bs-target="#routineP3Details{{ $index }}" 
                                                aria-expanded="false" 
                                                aria-controls="routineP3Details{{ $index }}">
                                            <i class="fas fa-eye me-1"></i> View History
                                            <i class="fas fa-chevron-down ms-1 toggle-icon"></i>
                                        </button>
                                        
                                        <!-- Collapsible Content -->
                                        <div class="collapse mt-2" id="routineP3Details{{ $index }}">
                                            <div class="service-summary p-2 rounded">
                                                <h6 class="text-secondary mb-1" style="font-size: 0.85rem;">
                                                    <i class="fas fa-tools"></i> Service History
                                                </h6>
                                                <div class="text-sm">
                                                    <p class="mb-1">{{ $part['last_service'] }}</p>
                                                    <p class="mb-1">{{ $part['last_service_km'] }} | {{ $part['last_service_details']['days_ago'] }}</p>
                                                    @if($part['last_service_details']['service_type'] !== 'Unknown')
                                                    <p class="mb-1"><span class="badge bg-secondary">{{ $part['last_service_details']['service_type'] }}</span></p>
                                                    @endif
                                                    @if($part['last_service_details']['description'] !== 'No description')
                                                    <p class="mb-0"><small>{{ Str::limit($part['last_service_details']['description'], 50) }}</small></p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <div class="text-sm">
                                        <strong>Estimated Cost:</strong><br>
                                        <small class="text-muted">RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> Regular maintenance schedule
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Quick Action Buttons - MOVED UP -->
        <div class="text-center mb-4">
            {{-- <button class="btn btn-success btn-lg me-3" onclick="scheduleService()">
                <i class="fas fa-wrench"></i> Schedule Vehicle Maintenance
            </button> --}}
            <a href="{{ route('maintenance.history', ['vehicle' => $vehicle, 'mileage' => $currentMileage]) }}" class="btn btn-info btn-lg me-3">
                <i class="fas fa-history"></i> View Full Maintenance History
            </a>
            <a href="{{ route('prediction.index') }}" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-plus"></i> Analyze Another Vehicle
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-lg me-3">
                <i class="fas fa-print"></i> Print Vehicle Report
            </button>
        </div>
    </div>
</div>

<script>
function scheduleService() {
    if (confirm('Schedule maintenance service for {{ $vehicle }}?\n\nEstimated Cost: RM {{ number_format($recommendations['cost_estimate']['min'] ?? 300) }} - {{ number_format($recommendations['cost_estimate']['max'] ?? 600) }}\nEstimated Time: {{ $recommendations['time_estimate'] ?? '2-4 hours' }}')) {
        alert('Service scheduled! You will receive a confirmation email shortly.');
    }
}

function toggleFullHistory() {
    alert('Full history view would expand here - showing all {{ $vehicleHistory["total_services"] ?? 0 }} service records');
}

// Enhanced collapsible functionality
document.addEventListener('DOMContentLoaded', function() {
    // Handle collapse events for better UX
    const collapseElements = document.querySelectorAll('[data-bs-toggle="collapse"]');
    
    collapseElements.forEach(function(element) {
        element.addEventListener('click', function() {
            const icon = this.querySelector('.toggle-icon');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // Update button text
            const textElement = this.childNodes[1]; // The text node
            if (isExpanded) {
                textElement.textContent = ' Hide Service Details';
                this.querySelector('.fa-eye').classList.remove('fa-eye');
                this.querySelector('.fa-eye').classList.add('fa-eye-slash');
            } else {
                textElement.textContent = ' View Service Details';
                this.querySelector('.fa-eye-slash')?.classList.remove('fa-eye-slash');
                this.querySelector('.fas').classList.add('fa-eye');
            }
        });
    });

    // Add event listeners for Bootstrap collapse events
    const collapseTargets = document.querySelectorAll('.collapse');
    
    collapseTargets.forEach(function(collapseTarget) {
        collapseTarget.addEventListener('show.bs.collapse', function() {
            const button = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (button) {
                button.classList.add('active');
                const icon = button.querySelector('.toggle-icon');
                if (icon) {
                    icon.style.transform = 'rotate(180deg)';
                }
                
                // Update text and icon
                const eyeIcon = button.querySelector('.fas');
                if (eyeIcon && eyeIcon.classList.contains('fa-eye')) {
                    eyeIcon.classList.remove('fa-eye');
                    eyeIcon.classList.add('fa-eye-slash');
                }
                
                // Update text content
                const textNode = Array.from(button.childNodes).find(node => 
                    node.nodeType === Node.TEXT_NODE && node.textContent.includes('View')
                );
                if (textNode) {
                    textNode.textContent = ' Hide Service Details';
                }
            }
        });

        collapseTarget.addEventListener('hide.bs.collapse', function() {
            const button = document.querySelector(`[data-bs-target="#${this.id}"]`);
            if (button) {
                button.classList.remove('active');
                const icon = button.querySelector('.toggle-icon');
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
                
                // Update text and icon
                const eyeIcon = button.querySelector('.fas');
                if (eyeIcon && eyeIcon.classList.contains('fa-eye-slash')) {
                    eyeIcon.classList.remove('fa-eye-slash');
                    eyeIcon.classList.add('fa-eye');
                }
                
                // Update text content
                const textNode = Array.from(button.childNodes).find(node => 
                    node.nodeType === Node.TEXT_NODE && node.textContent.includes('Hide')
                );
                if (textNode) {
                    textNode.textContent = ' View Service Details';
                }
            }
        });
    });

// Function to toggle all collapsibles in a section
function toggleAllInSection(sectionType, expand) {
    const selector = sectionType === 'immediate' ? '#serviceDetails' : '#soonServiceDetails';
    const collapses = document.querySelectorAll(`[id^="${selector.substring(1)}"]`);
    
    collapses.forEach(function(collapse) {
        const bsCollapse = new bootstrap.Collapse(collapse, { toggle: false });
        if (expand) {
            bsCollapse.show();
        } else {
            bsCollapse.hide();
        }
    });
}
</script>

<style>
.alert-lg {
    padding: 1.5rem;
    font-size: 1.1rem;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,123,255,.075);
}

.text-sm {
    font-size: 0.875rem;
}

.border-bottom:last-child {
    border-bottom: none !important;
}

.progress {
    background-color: #e9ecef;
}

.circular-progress-validation {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: conic-gradient(var(--bs-success) calc(var(--progress) * 1%), #e9ecef 0deg);
    position: relative;
    margin: 0 auto;
}

.circular-progress-validation::before {
    content: '';
    position: absolute;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: white;
}

.circular-progress-validation span {
    position: relative;
    z-index: 1;
}

.attention-card {
    animation: subtle-pulse 3s infinite;
}

@keyframes subtle-pulse {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.2); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
}

.pulse-animation {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* ENHANCED SERVICE DETAIL CARDS */
.service-details-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
}

.service-details-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.service-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.service-summary {
    background: rgba(255,255,255,0.8);
    border-radius: 6px;
    padding: 8px 12px;
    border-left: 3px solid #17a2b8;
}

/* PRIORITY BADGE STYLING */
.priority-badge-critical {
    background: linear-gradient(45deg, #dc3545, #c82333) !important;
    color: white !important;
    font-weight: bold;
    animation: subtle-pulse 2s infinite;
}

.priority-badge-high {
    background: linear-gradient(45deg, #ffc107, #e0a800) !important;
    color: #212529 !important;
    font-weight: bold;
}

.priority-badge-normal, .priority-badge-low {
    background: linear-gradient(45deg, #6c757d, #545b62) !important;
    color: white !important;
}

.priority-badge-unknown {
    background: linear-gradient(45deg, #6c757d, #495057) !important;
    color: white !important;
}

.cost-highlight {
    background: linear-gradient(45deg, #28a745, #1e7e34);
    color: white !important;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
    display: inline-block;
}

/* Responsive improvements for service details */
@media (max-width: 768px) {
    .service-details-card .row {
        flex-direction: column;
    }
    
    .service-details-card .col-md-6 {
        margin-bottom: 10px;
    }
    
    .service-badge {
        font-size: 0.7rem;
        margin-bottom: 2px;
    }
    
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        margin-right: 0;
    }
}

@media print {
    .btn, .alert, .accordion-button {
        display: none !important;
    }
    .card {
        border: 1px solid #ddd !important;
        page-break-inside: avoid;
    }
    .service-details-card {
        background: #f8f9fa !important;
        border: 1px solid #dee2e6 !important;
    }
}

.four-box-row .card {
    height: 100%;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.four-box-row .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
}

.four-box-row .card-body {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-height: 140px;
}

.four-box-row .card-body h3 {
    font-size: 1.8rem;
    font-weight: bold;
    margin: 0.5rem 0;
}

/* Responsive adjustments for four boxes */
@media (max-width: 1200px) {
    .four-box-row .card-body h3 {
        font-size: 1.6rem;
    }
    .four-box-row .card-body {
        min-height: 130px;
    }
}

@media (max-width: 992px) {
    .four-box-row .card-body h3 {
        font-size: 1.4rem;
    }
    .four-box-row .card-body {
        min-height: 120px;
    }
    .four-box-row .card-header h5 {
        font-size: 0.95rem;
    }
}

@media (max-width: 768px) {
    .four-box-row .card-body h3 {
        font-size: 1.3rem;
    }
    .four-box-row .card-body {
        min-height: 110px;
        padding: 1rem 0.75rem;
    }
    .four-box-row .card-header h5 {
        font-size: 0.9rem;
    }
    .four-box-row .card-body p {
        font-size: 0.85rem;
        margin-bottom: 0.5rem;
    }
}

@media (max-width: 576px) {
    .four-box-row .col-sm-6 {
        margin-bottom: 1rem;
    }
    .four-box-row .card-body h3 {
        font-size: 1.2rem;
    }
    .four-box-row .card-body {
        min-height: 100px;
        padding: 0.75rem;
    }
}

/* Equal height cards */
.four-box-row {
    display: flex;
    flex-wrap: wrap;
}

.four-box-row > [class*="col-"] {
    display: flex;
    flex-direction: column;
}

/* Collapsible Service Details Styles */
.toggle-icon {
    transition: transform 0.2s ease;
}

.collapse.show + .toggle-icon,
[aria-expanded="true"] .toggle-icon {
    transform: rotate(180deg);
}

.service-details-toggle {
    border: 1px solid #dee2e6;
    background: #f8f9fa;
    transition: all 0.2s ease;
}

.service-details-toggle:hover {
    background: #e9ecef;
    border-color: #ced4da;
}

.service-details-toggle:focus {
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Animation for collapse */
.collapse {
    transition: height 0.35s ease;
}

.collapsing {
    transition: height 0.35s ease;
}

/* Enhanced service details card when collapsed/expanded */
.service-details-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-left: 4px solid #007bff;
    transition: all 0.3s ease;
    margin-top: 0.5rem;
}

.service-details-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

/* Button styling for different urgency levels */
.btn-outline-primary.service-details-toggle {
    border-color: #007bff;
    color: #007bff;
}

.btn-outline-warning.service-details-toggle {
    border-color: #ffc107;
    color: #856404;
}

.btn-outline-info.service-details-toggle {
    border-color: #17a2b8;
    color: #17a2b8;
}

/* Smooth animation for the chevron icon */
.btn[data-bs-toggle="collapse"] .toggle-icon {
    transition: transform 0.3s ease;
}

.btn[data-bs-toggle="collapse"][aria-expanded="true"] .toggle-icon {
    transform: rotate(180deg);
}

/* Responsive adjustments for service detail buttons */
@media (max-width: 768px) {
    .btn-outline-primary.service-details-toggle,
    .btn-outline-warning.service-details-toggle,
    .btn-outline-info.service-details-toggle {
        font-size: 0.8rem;
        padding: 0.25rem 0.5rem;
    }
    
    .service-details-card .card-body {
        padding: 0.75rem;
    }
    
    .service-details-card .row .col-md-6 {
        margin-bottom: 0.5rem;
    }    
}

/* EQUAL HEIGHT PARTS CONTAINERS */
.parts-attention-row {
    display: flex;
    flex-wrap: wrap;
    align-items: stretch; /* Makes all flex items the same height */
}

.parts-attention-col {
    display: flex;
    flex-direction: column;
}

.parts-attention-card {
    display: flex;
    flex-direction: column;
    height: 100%; /* Makes card fill the column height */
    min-height: 400px; /* Minimum height for consistency */
}

.parts-attention-card .card-body {
    flex: 1; /* Makes card body grow to fill available space */
    display: flex;
    flex-direction: column;
}

.parts-scrollable-content {
    flex: 1;
    overflow-y: auto;
    max-height: 600px; /* Prevents cards from becoming too tall */
    padding-right: 8px; /* Space for scrollbar */
}

/* Custom scrollbar for parts content */
.parts-scrollable-content::-webkit-scrollbar {
    width: 6px;
}

.parts-scrollable-content::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.parts-scrollable-content::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.parts-scrollable-content::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Ensure equal spacing between parts */
.part-item {
    padding: 1rem;
    border-bottom: 1px solid rgba(0,0,0,0.1);
    margin-bottom: 0;
}

.part-item:last-child {
    border-bottom: none;
}

/* Enhanced visual consistency */
.parts-attention-card .card-header {
    flex-shrink: 0; /* Prevent header from shrinking */
    min-height: 80px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

/* Responsive adjustments */
@media (max-width: 992px) {
    .parts-attention-card {
        min-height: 350px;
    }
    .parts-scrollable-content {
        max-height: 500px;
    }
}

@media (max-width: 768px) {
    .parts-attention-row {
        display: block; /* Stack vertically on mobile */
    }
    .parts-attention-card {
        min-height: 300px;
        margin-bottom: 1rem;
    }
    .parts-scrollable-content {
        max-height: 400px;
    }
}

/* TOTAL COST SUMMARY STYLES */
.total-cost-summary {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-top: 2px solid rgba(0,0,0,0.1);
    padding: 1rem;
    margin-top: 0.5rem;
    border-radius: 0 0 8px 8px;
    flex-shrink: 0;
}

.total-cost-immediate {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    border-top: 2px solid #dc3545;
}

.total-cost-soon {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
    border-top: 2px solid #ffc107;
}

.cost-highlight-total {
    background: linear-gradient(45deg, #28a745, #1e7e34);
    color: white !important;
    padding: 4px 8px;
    border-radius: 6px;
    font-weight: bold;
    display: inline-block;
    font-size: 1.1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    animation: subtle-glow 3s infinite;
}

.cost-breakdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.25rem 0;
    font-size: 0.9rem;
}

.cost-breakdown-item:not(:last-child) {
    border-bottom: 1px dotted rgba(0,0,0,0.2);
}

.total-divider {
    border-top: 2px solid;
    margin: 0.5rem 0;
    padding-top: 0.5rem;
}

.immediate-divider {
    border-color: #dc3545;
}

.soon-divider {
    border-color: #ffc107;
}

.card-header-with-total {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.header-cost-preview {
    font-size: 0.9rem;
    opacity: 0.9;
    background: rgba(255,255,255,0.2);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    margin-top: 0.25rem;
}

@keyframes subtle-glow {
    0%, 100% { box-shadow: 0 2px 4px rgba(0,0,0,0.2); }
    50% { box-shadow: 0 2px 8px rgba(40, 167, 69, 0.4); }
}

@media (max-width: 768px) {
    .card-header-with-total {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .header-cost-preview {
        margin-top: 0.5rem;
        align-self: stretch;
        text-align: center;
    }
    
    .cost-highlight-total {
        font-size: 1rem;
    }
}
</style>

@endsection