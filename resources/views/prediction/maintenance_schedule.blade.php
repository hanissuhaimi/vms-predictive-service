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
                        @if(isset($vehicleHistory['service_patterns']))
                        <small>{{ $vehicleHistory['service_patterns']['usage_pattern'] }}</small>
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
                        
                        <p class="text-muted">Based on {{ $vehicleHistory['total_services'] ?? 0 }} service records</p>
                        
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
                            <p class="mb-1"><strong>Total Services:</strong> {{ $vehicleHistory['total_services'] ?? 0 }}</p>
                            @if(isset($vehicleHistory['last_service']) && $vehicleHistory['last_service'])
                            <p class="mb-1"><strong>Last Service:</strong> {{ Carbon\Carbon::parse($vehicleHistory['last_service']->Datereceived)->format('d M Y') }}</p>
                            <p class="mb-1"><strong>Days Ago:</strong> {{ intval($vehicleHistory['days_since_last'] ?? 0) }} days</p>
                            @endif
                            <p class="mb-1"><strong>Avg Interval:</strong> {{ number_format($vehicleHistory['average_interval'] ?? 1000) }} KM</p>
                            <p class="mb-0"><strong>Vehicle Type:</strong> {{ $vehicleHistory['vehicle_type'] ?? 'Unknown' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🔧 PARTS REQUIRING ATTENTION - ENHANCED WITH DETAILED SERVICE INFO -->
        @if(!empty($partsAnalysis['immediate']) || !empty($partsAnalysis['soon']))
        <div class="row mb-4">
            @if(!empty($partsAnalysis['immediate']))
            <div class="col-lg-6 mb-4">
                <div class="card border-danger attention-card">
                    <div class="card-header bg-danger text-white">
                        <h5><i class="fas fa-exclamation-circle"></i> 🚨 Immediate ({{ count($partsAnalysis['immediate']) }})</h5>
                        <small>Action required now</small>
                    </div>
                    <div class="card-body">
                        @foreach($partsAnalysis['immediate'] as $index => $part)
                        <div class="border-bottom mb-3 pb-3">
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
                            
                            <!-- COLLAPSIBLE SERVICE DETAILS -->
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
                            
                            <div class="row text-sm">
                                <div class="col-6">
                                    <strong>Next Due:</strong><br>
                                    <small class="text-primary">{{ $part['next_due_date'] }}</small>
                                    <br><small class="text-muted">{{ $part['next_due_km'] }} KM</small>
                                </div>
                                <div class="col-6">
                                    <strong>Cost Estimate:</strong><br>
                                    <small class="text-success cost-highlight">RM {{ number_format($part['cost_range']['min']) }} - {{ number_format($part['cost_range']['max']) }}</small>
                                    <br><small class="text-muted">{{ $part['industry_note'] }}</small>
                                </div>
                            </div>
                            
                            @if($part['status'] === 'overdue')
                                <div class="mt-2">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-clock"></i> OVERDUE by {{ $part['km_remaining'] }} KM
                                    </span>
                                </div>
                            @else
                                <div class="mt-2">
                                    <span class="badge bg-info">
                                        <i class="fas fa-road"></i> {{ $part['km_remaining'] }} KM remaining
                                    </span>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- SOON PARTS - Updated with Collapsible Service Details -->
            @if(!empty($partsAnalysis['soon']))
            <div class="col-lg-6 mb-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-clock"></i> ⚠️ Soon ({{ count($partsAnalysis['soon']) }})</h5>
                        <small>Service within 2-4 weeks</small>
                    </div>
                    <div class="card-body">
                        @foreach($partsAnalysis['soon'] as $index => $part)
                        <div class="border-bottom mb-3 pb-3">
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
                            
                            <!-- COLLAPSIBLE SERVICE DETAILS FOR SOON PARTS -->
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
                            
                            <div class="row text-sm">
                                <div class="col-6">
                                    <strong>Next Due:</strong><br>
                                    <small class="text-warning">{{ $part['next_due_date'] }}</small>
                                    <br><small class="text-muted">{{ $part['next_due_km'] }} KM</small>
                                </div>
                                <div class="col-6">
                                    <strong>Estimated Cost:</strong><br>
                                    <small class="text-success">RM {{ number_format($part['cost_range']['min']) }} - {{ number_format($part['cost_range']['max']) }}</small>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <div class="progress" style="height: 8px;">
                                    @php
                                        $intervalKm = str_replace(',', '', $part['interval_km']);
                                        $remainingKm = str_replace(',', '', $part['km_remaining']);
                                        $progressPercent = $intervalKm > 0 ? max(10, (($intervalKm - $remainingKm) / $intervalKm) * 100) : 50;
                                    @endphp
                                    <div class="progress-bar bg-warning" style="width: {{ $progressPercent }}%"></div>
                                </div>
                                <small class="text-muted">
                                    {{ $part['km_remaining'] }} KM remaining ({{ intval($part['days_remaining'] ?? 'Unknown') }} days)
                                </small>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
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
                                    
                                    <div class="row text-sm">
                                        <div class="col-6">
                                            <strong>Next Due:</strong><br>
                                            <small class="text-success">{{ $part['next_due_km'] }} KM</small>
                                        </div>
                                        <div class="col-6">
                                            <strong>Cost Range:</strong><br>
                                            <small class="text-muted">RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-road"></i> {{ $part['km_remaining'] }} KM until service
                                            @if(isset($part['days_remaining']) && intval($part['days_remaining']) > 0)
                                                (≈{{ intval($part['days_remaining']) }} days)
                                            @endif
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
                                    
                                    <div class="row text-sm">
                                        <div class="col-6">
                                            <strong>Next Due:</strong><br>
                                            <small class="text-info">{{ $part['next_due_km'] }} KM</small>
                                        </div>
                                        <div class="col-6">
                                            <strong>Budget:</strong><br>
                                            <small class="text-muted">RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> {{ $part['km_remaining'] }} KM remaining
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
                                    
                                    <div class="row text-sm">
                                        <div class="col-6">
                                            <strong>Schedule:</strong><br>
                                            <small class="text-muted">{{ $part['interval_km'] }} KM</small>
                                        </div>
                                        <div class="col-6">
                                            <strong>Remaining:</strong><br>
                                            <small class="text-secondary">{{ $part['km_remaining'] }} KM</small>
                                        </div>
                                    </div>
                                    
                                    @if(isset($part['cost_range']))
                                    <div class="mt-1">
                                        <small class="text-muted">Est. Cost: RM {{ number_format($part['cost_range']['min']) }}-{{ number_format($part['cost_range']['max']) }}</small>
                                    </div>
                                    @endif
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
            <button class="btn btn-success btn-lg me-3" onclick="scheduleService()">
                <i class="fas fa-wrench"></i> Schedule Vehicle Maintenance
            </button>
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
</style>

@endsection