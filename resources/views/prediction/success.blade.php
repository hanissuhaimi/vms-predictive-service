@extends('layouts.app')

@section('title', 'Form Test Success')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="alert alert-success">
                <h2>🎉 {{ $message }}</h2>
                <p><strong>Vehicle:</strong> {{ $vehicle }}</p>
                <p><strong>Mileage:</strong> {{ number_format($mileage) }} KM</p>
                <p><strong>Time:</strong> {{ now() }}</p>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>✅ What Worked:</h5>
                </div>
                <div class="card-body">
                    <ul>
                        <li>✓ Form submission successful</li>
                        <li>✓ Route configuration working</li>
                        <li>✓ Controller method reached</li>
                        <li>✓ Data validation passed</li>
                        <li>✓ View rendering successful</li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-3 text-center">
                <a href="{{ route('prediction.index') }}" class="btn btn-primary">
                    ← Test Again
                </a>
            </div>
        </div>
    </div>
</div>
@endsection