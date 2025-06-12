@extends('layouts.app')

@section('title', 'Vehicle Maintenance Prediction')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-tools"></i> New Maintenance Request</h3>
                <p class="mb-0">Just fill in what you know - we'll estimate the rest!</p>
            </div>
            
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                @endif

                <form action="{{ route('prediction.predict') }}" method="POST">
                    @csrf
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5><i class="fas fa-edit"></i> What's the problem?</h5>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Describe the issue:</label>
                                <textarea class="form-control" id="description" name="description" rows="4" 
                                    placeholder="Example: brake noise, flat tire, engine won't start, routine service, oil change..." 
                                    required>{{ old('description') }}</textarea>
                                <div class="form-text">Describe in your own words - English or Bahasa Malaysia</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="odometer" class="form-label"><i class="fas fa-tachometer-alt"></i> Current Mileage (KM)</label>
                                <input type="number" class="form-control" id="odometer" name="odometer" 
                                    min="1" value="{{ old('odometer', 200000) }}" required>
                                <div class="form-text">What's showing on your odometer?</div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5><i class="fas fa-car"></i> Vehicle Info</h5>
                            
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="auto_detect_priority" checked>
                                    <label class="form-check-label" for="auto_detect_priority">
                                        <i class="fas fa-robot"></i> Auto-detect urgency
                                    </label>
                                </div>
                                <div class="form-text">Let AI determine urgency from your description</div>
                            </div>
                            
                            <div id="priority_select" class="mb-3" style="display: none;">
                                <label for="priority" class="form-label">How urgent?</label>
                                <select class="form-control" id="priority" name="priority">
                                    <option value="1" {{ old('priority', 1) == 1 ? 'selected' : '' }}>🔴 Critical - Emergency</option>
                                    <option value="2" {{ old('priority') == 2 ? 'selected' : '' }}>🟠 High - Important</option>
                                    <option value="3" {{ old('priority') == 3 ? 'selected' : '' }}>🟡 Normal - Standard</option>
                                    <option value="0" {{ old('priority') == 0 ? 'selected' : '' }}>⚪ Low - Routine</option>
                                </select>
                            </div>
                            
                            <div id="priority_display" class="alert alert-info mb-3">
                                Auto-detected: 🔴 Critical
                            </div>
                            
                            <div class="mb-3">
                                <label for="number_plate" class="form-label"><i class="fas fa-id-card"></i> Vehicle Number Plate</label>
                                <input type="text" class="form-control" id="number_plate" name="number_plate" 
                                    value="{{ old('number_plate', 'WGW1349') }}" required>
                                <div class="form-text">Enter your vehicle's number plate (e.g., WGW1349)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Advanced Options -->
                    <div class="accordion mt-3" id="advancedOptions">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingAdvanced">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseAdvanced" aria-expanded="false">
                                    <i class="fas fa-cogs"></i> &nbsp; More Details (Optional)
                                </button>
                            </h2>
                            <div id="collapseAdvanced" class="accordion-collapse collapse" data-bs-parent="#advancedOptions">
                                <div class="accordion-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="service_count" class="form-label"><i class="fas fa-wrench"></i> How many times serviced before?</label>
                                                <input type="number" class="form-control" id="service_count" name="service_count" 
                                                    min="2" max="2704" value="{{ old('service_count', 200) }}" required>
                                                <div class="form-text">We estimated based on your mileage</div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label for="building_encoded" class="form-label"><i class="fas fa-map-marker-alt"></i> Where will you take it?</label>
                                                <select class="form-control" id="building_encoded" name="building_encoded" required>
                                                    <option value="2" {{ old('building_encoded', 2) == 2 ? 'selected' : '' }}>Main Workshop</option>
                                                    <option value="3" {{ old('building_encoded') == 3 ? 'selected' : '' }}>Branch Workshop</option>
                                                    <option value="1" {{ old('building_encoded') == 1 ? 'selected' : '' }}>Service Center</option>
                                                    <option value="6" {{ old('building_encoded') == 6 ? 'selected' : '' }}>Repair Shop</option>
                                                    <option value="7" {{ old('building_encoded') == 7 ? 'selected' : '' }}>Maintenance Bay</option>
                                                    <option value="0" {{ old('building_encoded') == 0 ? 'selected' : '' }}>Other Location</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="alert alert-info">
                                                <h6><i class="fas fa-info-circle"></i> Additional Info</h6>
                                                <p class="mb-1">✅ Request type and status are automatically determined from your problem description.</p>
                                                <p class="mb-0">💡 This helps our AI give you the most accurate diagnosis.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Timing Section -->
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="request_date" class="form-label"><i class="fas fa-calendar"></i> Request Date</label>
                                <input type="date" class="form-control" id="request_date" name="request_date" 
                                    value="{{ old('request_date', date('Y-m-d')) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="response_days" class="form-label"><i class="fas fa-clock"></i> How urgent? (days)</label>
                                <input type="number" class="form-control" id="response_days" name="response_days" 
                                    min="0" value="{{ old('response_days', 1) }}" required>
                                <div class="form-text">0 = Today, 1 = Tomorrow, etc.</div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-magic"></i> Analyze My Vehicle Problem
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection