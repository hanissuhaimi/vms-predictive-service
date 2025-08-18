<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleet Analysis Error</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .error-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="error-container">
                    <div class="error-header">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h2>Fleet Analysis Processing Error</h2>
                        <p class="mb-0">Unable to complete comprehensive fleet analysis</p>
                    </div>
                    
                    <div class="p-4">
                        <div class="alert alert-danger" role="alert">
                            <h5 class="alert-heading">
                                <i class="fas fa-times-circle"></i> Processing Failed
                            </h5>
                            <p class="mb-3">{{ $error_message }}</p>
                            
                            @if(isset($processing_time))
                            <hr>
                            <p class="mb-0">
                                <small><strong>Processing time:</strong> {{ $processing_time }} seconds</small>
                            </p>
                            @endif
                        </div>

                        @if(isset($technical_details) && $technical_details)
                        <div class="card mb-4">
                            <div class="card-header">
                                <small><i class="fas fa-code"></i> Technical Details</small>
                            </div>
                            <div class="card-body">
                                <code class="text-danger">{{ $technical_details }}</code>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-lightbulb text-warning"></i> What happened?</h6>
                                <ul class="list-unstyled">
                                    <li>• Large dataset processing was interrupted</li>
                                    <li>• Server timeout or memory limit reached</li>
                                    <li>• Database connection issue during analysis</li>
                                    <li>• Network interruption during processing</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-tools text-info"></i> Suggested Solutions:</h6>
                                @if(isset($suggestions))
                                <ul class="list-unstyled">
                                    @foreach($suggestions as $suggestion)
                                    <li>• {{ $suggestion }}</li>
                                    @endforeach
                                </ul>
                                @else
                                <ul class="list-unstyled">
                                    <li>• Try again in a few minutes</li>
                                    <li>• Contact system administrator</li>
                                    <li>• Check system status</li>
                                </ul>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <button onclick="retryAnalysis()" class="btn btn-primary btn-custom w-100">
                                        <i class="fas fa-redo"></i> Try Again
                                    </button>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <button onclick="window.close()" class="btn btn-secondary btn-custom w-100">
                                        <i class="fas fa-times"></i> Close Tab
                                    </button>
                                </div>
                            </div>
                        </div>

                        @if(isset($retry_message))
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i> {{ $retry_message }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function retryAnalysis() {
            // Close this tab and let user try again from dashboard
            if (window.opener) {
                // If opened from another window, close this and focus parent
                window.opener.focus();
                window.close();
            } else {
                // If direct access, reload to try again
                window.location.reload();
            }
        }

        // Auto-focus on retry button
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('.btn-primary').focus();
        });

        // Allow Enter key to retry
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                retryAnalysis();
            }
        });
    </script>
</body>
</html>