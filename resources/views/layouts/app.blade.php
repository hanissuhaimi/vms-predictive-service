<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VMS Fleet Prediction Service')</title>
    
    <!-- Local Bootstrap CSS -->
    <link href="{{ asset('bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Font Awesome and Bootstrap Icons from CDN -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <!-- Custom Styles -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
        }
        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            border: none;
            border-radius: 10px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .result-card {
            background: linear-gradient(135deg, #e8f4f8 0%, #f0f8ff 100%);
            border: 3px solid #2196F3;
            border-radius: 15px;
            padding: 25px;
            margin: 20px 0;
            text-align: center;
        }
        .info-card {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin: 10px 0;
        }
        .time-card { background-color: #fff3e0; }
        .cost-card { background-color: #e8f5e8; }
        .service-card { background-color: #f3e5f5; }
        .alert-lg {
            padding: 1.5rem;
            font-size: 1.1rem;
        }
        @media print {
            .btn, .alert {
                display: none !important;
            }
            .card {
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('prediction.index') }}">
                <i class="fas fa-truck"></i> VMS Vehicle Prediction Service
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <!-- Local Bootstrap JavaScript -->
    <script src="{{ asset('bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- NO OTHER JAVASCRIPT - Clean and simple -->
</body>
</html>