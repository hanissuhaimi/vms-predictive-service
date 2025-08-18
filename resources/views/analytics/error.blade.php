@extends('layouts.app')

@section('title', 'Fleet Analysis Error')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fas fa-exclamation-triangle"></i> Fleet Analysis Processing Error</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <h5>⚠️ Processing Issue Encountered</h5>
                        <p>{{ $error_message }}</p>
                        
                        @if(isset($technical_details) && $technical_details)
                        <details class="mt-3">
                            <summary>Technical Details (for debugging)</summary>
                            <pre class="mt-2 p-2 bg-light border rounded">{{ $technical_details }}</pre>
                        </details>
                        @endif
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>What happened?</h6>
                            <ul>
                                <li>Large dataset processing was interrupted</li>
                                <li>Server timeout or memory limit reached</li>
                                <li>Database connection issue during analysis</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Processing Statistics:</h6>
                            <ul>
                                <li><strong>Processing Time:</strong> {{ $processing_time }}s</li>
                                <li><strong>Dataset Size:</strong> 88,000+ records</li>
                                <li><strong>Status:</strong> <span class="text-danger">Failed</span></li>
                            </ul>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>What you can do:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-primary">🔄 Try Again</h6>
                                    <p class="small">The server may have been busy. Try the analysis again.</p>
                                    <a href="{{ $retry_url }}" class="btn btn-primary">
                                        <i class="fas fa-redo"></i> Retry Fleet Analysis
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h6 class="text-secondary">📊 Back to Dashboard</h6>
                                    <p class="small">Return to vehicle prediction dashboard.</p>
                                    <a href="{{ $back_url }}" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <h6><i class="fas fa-lightbulb"></i> Tips for Success:</h6>
                        <ul class="mb-0">
                            <li>Try during off-peak hours for better server performance</li>
                            <li>Ensure stable internet connection</li>
                            <li>Allow 2-3 minutes for complete processing</li>
                            <li>Contact system administrator if errors persist</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection