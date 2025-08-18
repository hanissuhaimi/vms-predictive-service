@extends('layouts.app')

@section('title', 'Processing Fleet Analysis')

@push('styles')
<style>
.processing-container {
    min-height: 80vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-spinner {
    width: 80px;
    height: 80px;
    border: 8px solid #f3f3f3;
    border-top: 8px solid #28a745;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 30px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.progress-text {
    font-size: 18px;
    margin: 20px 0;
}

.progress-details {
    color: #666;
    font-size: 14px;
}

.progress-bar-custom {
    width: 100%;
    height: 20px;
    background-color: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin: 20px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    width: 0%;
    transition: width 2s ease-in-out;
    border-radius: 10px;
}

.step-indicator {
    display: flex;
    justify-content: space-between;
    margin: 30px 0;
    font-size: 12px;
}

.step {
    text-align: center;
    flex: 1;
    opacity: 0.5;
    transition: opacity 0.5s;
}

.step.active {
    opacity: 1;
    color: #28a745;
    font-weight: bold;
}

.step.completed {
    opacity: 1;
    color: #28a745;
}
</style>
@endpush

@section('content')
<div class="processing-container">
    <div class="text-center">
        <div class="card" style="min-width: 500px;">
            <div class="card-header bg-success text-white">
                <h4><i class="fas fa-chart-line"></i> Fleet Analysis in Progress</h4>
            </div>
            <div class="card-body">
                <div class="progress-spinner"></div>
                
                <h5 id="currentStep">Initializing comprehensive fleet analysis...</h5>
                
                <div class="progress-bar-custom">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                
                <div class="step-indicator">
                    <div class="step" id="step1">
                        <div>📊</div>
                        <div>Fleet Overview</div>
                    </div>
                    <div class="step" id="step2">
                        <div>🔧</div>
                        <div>Service Data</div>
                    </div>
                    <div class="step" id="step3">
                        <div>📈</div>
                        <div>Performance</div>
                    </div>
                    <div class="step" id="step4">
                        <div>📅</div>
                        <div>Trends</div>
                    </div>
                    <div class="step" id="step5">
                        <div>🏢</div>
                        <div>Depot Analysis</div>
                    </div>
                    <div class="step" id="step6">
                        <div>✅</div>
                        <div>Finalizing</div>
                    </div>
                </div>
                
                <div class="progress-text">
                    <p><strong>Processing {{ $total_records }} maintenance records</strong></p>
                    <div class="progress-details">
                        <div>📊 Analyzing vehicle performance patterns</div>
                        <div>🔍 Calculating maintenance trends</div>
                        <div>📈 Generating fleet health metrics</div>
                        <div>🏢 Processing depot performance data</div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="fas fa-info-circle"></i>
                    <strong>Estimated time:</strong> {{ $estimated_time }}<br>
                    <small>Large dataset processing requires patience. Please do not refresh this page.</small>
                </div>
                
                <div class="mt-3">
                    <small class="text-muted">
                        Started: <span id="startTime"></span> | 
                        Elapsed: <span id="elapsedTime">0:00</span>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startTime = new Date();
    document.getElementById('startTime').textContent = startTime.toLocaleTimeString();
    
    const steps = [
        { id: 'step1', message: 'Loading fleet overview statistics...', progress: 15 },
        { id: 'step2', message: 'Processing 88,000+ service records...', progress: 30 },
        { id: 'step3', message: 'Analyzing vehicle performance data...', progress: 45 },
        { id: 'step4', message: 'Calculating maintenance trends...', progress: 60 },
        { id: 'step5', message: 'Processing depot performance metrics...', progress: 80 },
        { id: 'step6', message: 'Finalizing analysis dashboard...', progress: 95 }
    ];
    
    let currentStepIndex = 0;
    let progressInterval;
    
    // Update elapsed time every second
    setInterval(function() {
        const elapsed = new Date() - startTime;
        const minutes = Math.floor(elapsed / 60000);
        const seconds = Math.floor((elapsed % 60000) / 1000);
        document.getElementById('elapsedTime').textContent = 
            minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }, 1000);
    
    // Simulate progress through steps
    function updateProgress() {
        if (currentStepIndex < steps.length) {
            const step = steps[currentStepIndex];
            
            // Update current step message
            document.getElementById('currentStep').textContent = step.message;
            
            // Update progress bar
            document.getElementById('progressFill').style.width = step.progress + '%';
            
            // Mark step as active
            document.getElementById(step.id).classList.add('active');
            
            // Mark previous steps as completed
            for (let i = 0; i < currentStepIndex; i++) {
                document.getElementById(steps[i].id).classList.remove('active');
                document.getElementById(steps[i].id).classList.add('completed');
            }
            
            currentStepIndex++;
        }
    }
    
    // Start progress simulation
    updateProgress(); // First step immediately
    progressInterval = setInterval(updateProgress, 20000); // Every 20 seconds
    
    // Start the actual analytics processing
    setTimeout(function() {
        fetch('{{ $data_url }}', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (response.ok) {
                return response.text();
            }
            throw new Error('Analytics processing failed');
        })
        .then(html => {
            // Replace current page with results
            document.open();
            document.write(html);
            document.close();
        })
        .catch(error => {
            console.error('Analytics error:', error);
            clearInterval(progressInterval);
            
            document.getElementById('currentStep').textContent = '❌ Processing encountered an error';
            document.getElementById('currentStep').style.color = '#dc3545';
            
            // Show retry option
            const retryHtml = `
                <div class="alert alert-danger mt-3">
                    <strong>Processing Failed</strong><br>
                    The server encountered an issue while processing your fleet analysis.
                    <br><br>
                    <a href="{{ route('analytics.dashboard') }}" class="btn btn-danger">Try Again</a>
                    <a href="{{ route('prediction.index') }}" class="btn btn-secondary ml-2">Back to Dashboard</a>
                </div>
            `;
            document.querySelector('.card-body').innerHTML += retryHtml;
        });
    }, 2000); // Start processing after 2 seconds
    
    // Clean up interval after 5 minutes (failsafe)
    setTimeout(function() {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
    }, 300000);
});
</script>
@endpush