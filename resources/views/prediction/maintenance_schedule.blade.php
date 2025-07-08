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
                        <h5>Fleet Maintenance Analysis</h5>
                        <small>Generated: {{ now()->format('d M Y, g:i A') }}</small>
                        @if(isset($mlPrediction['source']))
                        <br><small class="badge bg-info">{{ $mlPrediction['source'] }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- 🎯 PRIORITY ALERT - MOVED TO TOP -->
        @if($recommendations['priority'] == 'immediate' || $recommendations['priority'] == 'critical_safety')
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
        @endif

        <!-- 💰 COST & TIME ESTIMATE - MOVED UP -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-dollar-sign"></i> 💰 Estimated Cost</h5>
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
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-clock"></i> ⏰ Estimated Time</h5>
                    </div>
                    <div class="card-body text-center">
                        @php
                            $timeEstimate = $recommendations['time_estimate'] ?? '2-4 hours';
                        @endphp
                        
                        <h3 class="text-warning">{{ $timeEstimate }}</h3>
                        
                        <p class="text-muted">Fleet maintenance time required</p>
                        
                        @if(isset($recommendations['safety_priority']) && $recommendations['safety_priority'])
                        <span class="badge bg-danger">Safety Priority</span>
                        @endif
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
                        @foreach($partsAnalysis['immediate'] as $part)
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
                            
                            <!-- ENHANCED LAST SERVICE DETAILS -->
                            @if(isset($part['last_service_details']) && $part['last_service_details'])
                            <div class="card service-details-card mb-3">
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
                                            <p class="mb-1"><strong>Location:</strong> {{ $part['last_service_details']['building'] }} - {{ $part['last_service_details']['department'] }}</p>
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
                            @else
                            <div class="alert alert-warning py-2">
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

            @if(!empty($partsAnalysis['soon']))
            <div class="col-lg-6 mb-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5><i class="fas fa-clock"></i> ⚠️ Soon ({{ count($partsAnalysis['soon']) }})</h5>
                        <small>Service within 2-4 weeks</small>
                    </div>
                    <div class="card-body">
                        @foreach($partsAnalysis['soon'] as $part)
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
                            
                            <!-- ENHANCED LAST SERVICE DETAILS FOR SOON PARTS -->
                            @if(isset($part['last_service_details']) && $part['last_service_details'])
                            <div class="card service-details-card mb-3">
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
                                            <p class="mb-1"><strong>Location:</strong> {{ $part['last_service_details']['building'] }}</p>
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
                            @else
                            <div class="alert alert-info py-2">
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
                <small>Components in good condition based on fleet maintenance intervals</small>
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
                        @foreach($priority1Parts as $part)
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
                                    
                                    <!-- DETAILED SERVICE HISTORY FOR PRIORITY 1 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="service-summary p-2 rounded mb-2">
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
                                                @if($part['last_service_details']['contractor'] !== 'No contractor')
                                                <p class="mb-1"><small><strong>Contractor:</strong> {{ $part['last_service_details']['contractor'] }}</small></p>
                                                @endif
                                                @if($part['last_service_details']['description'] !== 'No description')
                                                <p class="mb-0"><small><strong>Work:</strong> {{ Str::limit($part['last_service_details']['description'], 80) }}</small></p>
                                                @endif
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
                                        <small class="text-info">Fleet history: {{ $part['service_count'] }} services</small>
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
                        @foreach($priority2Parts as $part)
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
                                    
                                    <!-- DETAILED SERVICE HISTORY FOR PRIORITY 2 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="service-summary p-2 rounded mb-2">
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
                                            </p>
                                            @if($part['last_service_details']['description'] !== 'No description')
                                            <p class="mb-0"><small>{{ Str::limit($part['last_service_details']['description'], 60) }}</small></p>
                                            @endif
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
                                        <small class="text-secondary">{{ $part['service_count'] }} fleet services</small>
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
                        @foreach($priority3Parts as $part)
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
                                    
                                    <!-- SERVICE HISTORY FOR PRIORITY 3 PARTS -->
                                    @if(isset($part['last_service_details']) && $part['last_service_details'])
                                    <div class="service-summary p-2 rounded mb-2">
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

        <!-- 📋 ACTION PLAN - MOVED AFTER PARTS -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-list-ol"></i> 📋 Recommended Action Plan</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        @php
                            $actionPlan = $recommendations['action_plan'] ?? ['📅 Schedule regular fleet maintenance'];
                            $priority = $recommendations['priority'] ?? 'routine';
                        @endphp
                        
                        @if(!empty($actionPlan))
                            <ol class="list-group list-group-numbered">
                                @foreach($actionPlan as $action)
                                <li class="list-group-item">{!! $action !!}</li>
                                @endforeach
                                <li class="list-group-item">📞 Contact fleet manager to schedule maintenance</li>
                                <li class="list-group-item">🔧 Follow recommended maintenance procedures</li>
                                <li class="list-group-item">📅 Schedule next check at {{ number_format($serviceSchedule['next_routine']['mileage'] ?? 0) }} KM</li>
                            </ol>
                        @else
                            <p class="text-muted">No specific action plan available. Continue with regular fleet operations.</p>
                        @endif
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-light">
                            <h6><i class="fas fa-info-circle"></i> Fleet Analytics</h6>
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

        <!-- Quick Action Buttons - MOVED UP -->
        <div class="text-center mb-4">
            <button class="btn btn-success btn-lg me-3" onclick="scheduleService()">
                <i class="fas fa-wrench"></i> Schedule Fleet Maintenance
            </button>
            <a href="{{ route('prediction.index') }}" class="btn btn-primary btn-lg me-3">
                <i class="fas fa-plus"></i> Analyze Another Vehicle
            </a>
            <button onclick="window.print()" class="btn btn-secondary btn-lg me-3">
                <i class="fas fa-print"></i> Print Fleet Report
            </button>
        </div>

        <!-- Service History Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h3 class="text-success">{{ $vehicleHistory['total_services'] }}</h3>
                        <p class="mb-0"><strong>Total Services</strong></p>
                        <small class="text-muted">Complete maintenance history</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h3 class="text-info">{{ $vehicleHistory['service_patterns']['services_per_month'] ?? 'N/A' }}</h3>
                        <p class="mb-0"><strong>Services/Month</strong></p>
                        <small class="text-muted">Average frequency</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h3 class="text-warning">{{ number_format($vehicleHistory['average_interval']) }} KM</h3>
                        <p class="mb-0"><strong>Avg Interval</strong></p>
                        <small class="text-muted">Between services</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-secondary">
                    <div class="card-body text-center">
                        <h3 class="text-secondary">{{ $vehicleHistory['service_patterns']['data_quality'] ?? 'N/A' }}%</h3>
                        <p class="mb-0"><strong>Data Quality</strong></p>
                        <small class="text-muted">Mileage records</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Schedule Prediction -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card border-primary">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-calendar-alt"></i> Next Routine Service</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-primary">{{ number_format($serviceSchedule['next_routine']['mileage']) }} KM</h3>
                        <p class="mb-2">
                            <strong>Distance:</strong> {{ number_format($serviceSchedule['next_routine']['km_remaining']) }} KM to go
                        </p>
                        <p class="mb-2">
                            <strong>Estimated:</strong> {{ $serviceSchedule['days_estimate'] }}
                        </p>
                        <p class="text-muted">{{ $serviceSchedule['next_routine']['description'] }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-tools"></i> Next Major Service</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="text-success">{{ number_format($serviceSchedule['next_major']['mileage']) }} KM</h3>
                        <p class="mb-2">
                            <strong>Distance:</strong> {{ number_format($serviceSchedule['next_major']['km_remaining']) }} KM to go
                        </p>
                        <p class="mb-2">
                            <strong>Type:</strong> {{ $serviceSchedule['next_major']['type'] }}
                        </p>
                        <p class="text-muted">{{ $serviceSchedule['next_major']['description'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ENHANCED FEATURES SECTION -->
        @if(isset($systemEnhancement))
        <div class="alert alert-success mb-4">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5><i class="fas fa-rocket"></i> Enhanced System Active - Version {{ $systemEnhancement['version'] }}</h5>
                    <p class="mb-0">
                        <strong>Active Enhancements:</strong> 
                        @foreach($systemEnhancement['enhancements_active'] as $enhancement)
                            <span class="badge bg-success me-1">{{ ucwords(str_replace('_', ' ', $enhancement)) }}</span>
                        @endforeach
                    </p>
                </div>
                <div class="col-md-4 text-md-end">
                    <small class="text-muted">Processing Time: {{ $systemEnhancement['processing_time'] }}</small>
                </div>
            </div>
        </div>
        @endif

        <!-- Recent Maintenance History - MOVED OUT OF COLLAPSIBLE -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5><i class="fas fa-history"></i> 📋 Recent Maintenance History</h5>
            </div>
            <div class="card-body">
                @if(isset($vehicleHistory['processed_history']) && $vehicleHistory['processed_history'] && $vehicleHistory['processed_history']->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Service Type</th>
                                <th>Description</th>
                                <th>Mileage</th>
                                <th>Days Ago</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($vehicleHistory['processed_history']->take(10) as $service)
                            <tr>
                                <td>
                                    <strong>{{ $service['date'] ?? 'Unknown date' }}</strong><br>
                                    <small class="text-muted">{{ $service['sr_number'] ?? 'No SR' }}</small>
                                </td>
                                <td>
                                    <span class="badge 
                                        @if(($service['service_type'] ?? '') == 'Repair') bg-danger
                                        @elseif(($service['service_type'] ?? '') == 'Cleaning') bg-info  
                                        @elseif(($service['service_type'] ?? '') == 'Maintenance') bg-success
                                        @else bg-secondary @endif">
                                        {{ $service['service_type'] ?? 'Unknown' }}
                                    </span>
                                </td>
                                <td>
                                    {{ $service['description'] ?? 'No description' }}
                                    @if(($service['category'] ?? '') && $service['category'] != 'General')
                                    <br><small class="text-muted">{{ $service['category'] }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $service['odometer'] ?? 'Not recorded' }}</strong>
                                </td>
                                <td>{{ $service['days_ago'] ?? 'Unknown' }} days</td>
                                <td>
                                    <span class="badge 
                                        @if(($service['status'] ?? '') == 'Completed') bg-success
                                        @elseif(($service['status'] ?? '') == 'Pending') bg-warning
                                        @else bg-secondary @endif">
                                        {{ $service['status'] ?? 'Unknown' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($vehicleHistory['processed_history']->count() > 10)
                <div class="text-center mt-3">
                    <button class="btn btn-outline-secondary" onclick="toggleFullHistory()">
                        <i class="fas fa-plus"></i> Show All {{ $vehicleHistory['total_services'] }} Services
                    </button>
                </div>
                @endif
                @else
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> No Maintenance History</h6>
                    <p class="mb-0">No previous maintenance records found for this vehicle. This appears to be a new vehicle or the first service request.</p>
                    <p>Showing {{ $vehicleHistory['total_services'] ?? 0 }} total service records for this vehicle.</p>
                </div>
                @endif
            </div>
        </div>

        <!-- COLLAPSIBLE DETAILED SECTIONS -->
        <div class="accordion mb-4" id="detailedAccordion">
            
            {{-- ENHANCED VALIDATION RESULTS --}}
            @if(isset($advancedValidation) && isset($advancedValidation['layers_passed']))
            <div class="accordion-item">
                <h2 class="accordion-header" id="validationHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#validationCollapse">
                        <i class="fas fa-shield-check me-2"></i> Advanced Validation Results
                    </button>
                </h2>
                <div id="validationCollapse" class="accordion-collapse collapse" data-bs-parent="#detailedAccordion">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Validation Layers Passed:</h6>
                                <div class="list-group list-group-flush">
                                    @foreach($advancedValidation['layers_passed'] as $layer)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        {{ ucwords(str_replace('_', ' ', $layer)) }}
                                        <span class="badge bg-success rounded-pill">✓</span>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center">
                                    <div class="circular-progress-validation bg-success" style="--progress: {{ $advancedValidation['validation_score'] ?? 100 }};">
                                        <span class="h4 text-success">{{ $advancedValidation['validation_score'] ?? 100 }}</span>
                                    </div>
                                    <div class="mt-2">
                                        <strong>Validation Score</strong><br>
                                        <small class="text-muted">{{ $advancedValidation['message'] }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- SAFETY ANALYSIS --}}
            @if(isset($safetyAnalysis))
            <div class="accordion-item">
                <h2 class="accordion-header" id="safetyHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#safetyCollapse">
                        <i class="fas fa-shield-alt me-2"></i> Safety-Critical Systems Analysis
                    </button>
                </h2>
                <div id="safetyCollapse" class="accordion-collapse collapse" data-bs-parent="#detailedAccordion">
                    <div class="accordion-body">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <h6>Safety Score: {{ $safetyAnalysis['overall_safety_score'] }}/100</h6>
                                <span class="badge bg-{{ $safetyAnalysis['breakdown_risk'] === 'critical' ? 'danger' : ($safetyAnalysis['breakdown_risk'] === 'high' ? 'warning' : 'success') }}">
                                    {{ ucfirst($safetyAnalysis['breakdown_risk']) }} Risk
                                </span>
                            </div>
                        </div>
                        
                        @if(!empty($safetyAnalysis['safety_systems']))
                        <div class="row">
                            @foreach($safetyAnalysis['safety_systems'] as $systemName => $systemData)
                            <div class="col-md-6 col-lg-3 mb-3">
                                <div class="card h-100 border-{{ $systemData['risk_level'] === 'critical' ? 'danger' : ($systemData['risk_level'] === 'high' ? 'warning' : ($systemData['risk_level'] === 'medium' ? 'info' : 'success')) }}">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">{{ ucwords(str_replace('_', ' ', $systemName)) }}</h6>
                                        <div class="progress mb-2" style="height: 20px;">
                                            <div class="progress-bar bg-{{ $systemData['safety_score'] >= 80 ? 'success' : ($systemData['safety_score'] >= 60 ? 'warning' : 'danger') }}" 
                                                 style="width: {{ $systemData['safety_score'] }}%">
                                                {{ $systemData['safety_score'] }}%
                                            </div>
                                        </div>
                                        <span class="badge bg-{{ $systemData['risk_level'] === 'critical' ? 'danger' : ($systemData['risk_level'] === 'high' ? 'warning' : ($systemData['risk_level'] === 'medium' ? 'info' : 'success')) }}">
                                            {{ ucfirst($systemData['risk_level']) }} Risk
                                        </span>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- COST ANALYSIS --}}
            @if(isset($costAnalysis))
            <div class="accordion-item">
                <h2 class="accordion-header" id="costHeading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#costCollapse">
                        <i class="fas fa-calculator me-2"></i> Predictive Cost Analytics
                    </button>
                </h2>
                <div id="costCollapse" class="accordion-collapse collapse" data-bs-parent="#detailedAccordion">
                    <div class="accordion-body">
                        @if(!empty($costAnalysis['cost_breakdown']))
                        <div class="table-responsive mb-4">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Urgency</th>
                                        <th>Cost Range</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($costAnalysis['cost_breakdown'] as $item)
                                    <tr>
                                        <td><strong>{{ $item['item'] }}</strong></td>
                                        <td>
                                            <span class="badge bg-{{ $item['urgency'] === 'immediate' ? 'danger' : ($item['urgency'] === 'soon' ? 'warning' : 'info') }}">
                                                {{ ucfirst($item['urgency']) }}
                                            </span>
                                        </td>
                                        <td>RM {{ number_format($item['cost_range']['min']) }} - {{ number_format($item['cost_range']['max']) }}</td>
                                        <td><small>{{ $item['reason'] }}</small></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
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
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: bold;
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
</style>

@endsection