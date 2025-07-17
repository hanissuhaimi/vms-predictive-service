@extends('layouts.app')

@section('title', 'Vehicle Maintenance Prediction')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white text-center">
                <h3><i class="fas fa-car-side"></i> Vehicle Maintenance Prediction</h3>
                <p class="mb-0">Enter vehicle details to predict upcoming maintenance needs</p>
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
                    
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            
                            <!-- Vehicle Number Input -->
                            <div class="mb-4">
                                <label for="vehicle_number" class="form-label">
                                    <i class="fas fa-truck"></i> Vehicle Number Plate
                                </label>
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="vehicle_number" 
                                       name="vehicle_number" 
                                       value="{{ old('vehicle_number') }}" 
                                       placeholder=""
                                       required>
                                <div class="form-text">Enter the vehicle's registration number</div>
                            </div>

                            <!-- Current Mileage Input -->
                            <div class="mb-4">
                                <label for="current_mileage" class="form-label">
                                    <i class="fas fa-tachometer-alt"></i> Current Mileage (KM)
                                </label>
                                <input type="number" 
                                    class="form-control form-control-lg @error('current_mileage') is-invalid @enderror" 
                                    id="current_mileage" 
                                    name="current_mileage" 
                                    value="{{ old('current_mileage') }}" 
                                    min="1" 
                                    max="2000000"
                                    placeholder="cd .."
                                    required>
                                
                                @error('current_mileage')
                                    <div class="invalid-feedback">
                                        <strong>{{ $message }}</strong>
                                    </div>
                                @enderror
                                
                                <div class="form-text">
                                    💡 Enter the current odometer reading from your vehicle dashboard
                                </div>
                                
                                <!-- Helpful mileage guide -->
                                {{-- <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Vehicle Ranges:</strong> 
                                        New (0-100K) | Active (100K-500K) | High-Usage (500K+)
                                    </small>
                                </div> --}}
                            </div>

                            <!-- Submit Button -->
                            <div class="text-center mb-4">
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-magic"></i> Analyze Vehicle
                                </button>
                            </div>

                            @if ($errors->has('vehicle_number'))
                                <div class="alert alert-danger">
                                    <h5><i class="fas fa-exclamation-triangle"></i> Vehicle Not Found</h5>
                                    <p>{{ $errors->first('vehicle_number') }}</p>
                                    <p><strong>Suggestions:</strong></p>
                                    <ul>
                                        <li>Double-check the vehicle number spelling</li>
                                        <li>Ensure the vehicle is registered in the vehicle management system</li>
                                        <li>Contact the manager if vehicle should be in the system</li>
                                    </ul>
                                </div>
                            @endif

                            @if (session('error'))
                                <div class="alert alert-warning">
                                    <h5><i class="fas fa-info-circle"></i> Notice</h5>
                                    <p>{{ session('error') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </form>

                <!-- Sample Vehicles -->
                {{-- <div class="card mt-4">
                    <div class="card-header">
                        <h6><i class="fas fa-list"></i>Test Vehicles</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-primary btn-sm w-100" 
                                        onclick="fillSample('VEK4613', 95000)">
                                    VEK4613 - 95,000 KM<br>
                                    <small>733 service records</small>
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-secondary btn-sm w-100" 
                                        onclick="fillSample('WSG7937', 950000)">
                                    WSG7937 - 950,000 KM<br>
                                    <small>1,337 service records</small>
                                </button>
                            </div>
                            <div class="col-md-4 mb-2">
                                <button class="btn btn-outline-success btn-sm w-100" 
                                        onclick="fillSample('FLEET999', 50000)">
                                    FLEET999 - 50,000 KM<br>
                                    <small>Test unknown vehicle</small>
                                </button>
                            </div>
                        </div>
                    </div>
                </div> --}}
            </div>
        </div>
    </div>
</div>

<script>
// Essential JavaScript functions
function fillSample(vehicle, mileage) {
    document.getElementById('vehicle_number').value = vehicle;
    document.getElementById('current_mileage').value = mileage;
}

// Add real-time validation feedback
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vehicle prediction form loaded successfully');
    
    const mileageInput = document.getElementById('current_mileage');
    if (mileageInput) {
        mileageInput.addEventListener('input', function() {
            const value = parseInt(this.value);
            
            if (value && value < 1000) {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            } else if (value && value > 0) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-invalid', 'is-valid');
            }
        });
    }
});
</script>

<style>
.is-valid {
    border-color: #28a745 !important;
}

.is-invalid {
    border-color: #dc3545 !important;
}

.form-control-lg {
    font-size: 1.125rem;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .btn-group .btn {
        margin-right: 0;
    }
}
</style>
@endsection