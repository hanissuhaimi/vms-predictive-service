<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'VMS Prediction Service')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="{{ route('prediction.index') }}">
                <i class="fas fa-car"></i> VMS Prediction Service
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-detect priority based on description
        function autoDetectPriority() {
            const description = document.getElementById('description').value.toLowerCase();
            const prioritySelect = document.getElementById('priority');
            const autoDetectCheck = document.getElementById('auto_detect_priority');
            
            if (autoDetectCheck.checked) {
                let priority = 1; // Default to Critical
                
                if (description.includes('emergency') || description.includes('urgent') || 
                    description.includes('breakdown') || description.includes('tidak boleh') || 
                    description.includes('rosak teruk') || description.includes('accident')) {
                    priority = 1; // Critical
                } else if (description.includes('noise') || description.includes('problem') || 
                          description.includes('issue') || description.includes('bunyi') || 
                          description.includes('masalah')) {
                    priority = 2; // High
                } else {
                    priority = 1; // Default to Critical
                }
                
                prioritySelect.value = priority;
                updatePriorityDisplay(priority);
            }
        }

        function updatePriorityDisplay(priority) {
            const priorityText = {
                1: "🔴 Critical",
                2: "🟠 High", 
                3: "🟡 Normal",
                0: "⚪ Low"
            };
            
            const display = document.getElementById('priority_display');
            if (display) {
                display.textContent = "Auto-detected: " + (priorityText[priority] || "🔴 Critical");
            }
        }

        function togglePriorityMode() {
            const autoDetect = document.getElementById('auto_detect_priority').checked;
            const prioritySelect = document.getElementById('priority_select');
            const priorityDisplay = document.getElementById('priority_display');
            
            if (autoDetect) {
                prioritySelect.style.display = 'none';
                priorityDisplay.style.display = 'block';
                autoDetectPriority();
            } else {
                prioritySelect.style.display = 'block';
                priorityDisplay.style.display = 'none';
            }
        }

        function estimateServiceCount() {
            const odometer = document.getElementById('odometer').value;
            const serviceCountInput = document.getElementById('service_count');
            
            if (odometer) {
                const estimated = Math.max(2, Math.min(2704, Math.floor(odometer / 15000)));
                serviceCountInput.value = estimated;
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            togglePriorityMode();
            
            // Add event listeners
            document.getElementById('description').addEventListener('input', autoDetectPriority);
            document.getElementById('auto_detect_priority').addEventListener('change', togglePriorityMode);
            document.getElementById('odometer').addEventListener('input', estimateServiceCount);
        });
    </script>
</body>
</html>