@extends('layouts.app')

@section('title', 'Service Requests History')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3><i class="fas fa-list"></i> Recent Service Requests</h3>
                    <p class="mb-0">Latest maintenance requests from the system</p>
                </div>
                
                <div class="card-body">
                    @if($requests->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>SR Number</th>
                                        <th>Vehicle</th>
                                        <th>Issue</th>
                                        <th>Date</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Odometer</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($requests as $request)
                                        <tr>
                                            <td><strong>{{ $request->ID }}</strong></td>
                                            <td>
                                                @if($request->SR)
                                                    <code>{{ $request->SR }}</code>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($request->Vehicle)
                                                    <span class="badge bg-dark">{{ $request->Vehicle }}</span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div style="max-width: 200px;">
                                                    {{ Str::limit($request->Description ?? 'No description', 50) }}
                                                </div>
                                                @if($request->CMType)
                                                    <small class="badge bg-secondary">{{ $request->CMType }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($request->Datereceived)
                                                    <small>
                                                        {{ $request->Datereceived->format('M d, Y') }}<br>
                                                        {{ $request->timereceived ?? '' }}
                                                    </small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $request->Priority == '1' ? 'danger' : ($request->Priority == '2' ? 'warning' : 'info') }}">
                                                    {{ $request->priority_text }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $request->Status == '3' ? 'success' : ($request->Status == '2' ? 'warning' : 'secondary') }}">
                                                    {{ $request->status_text }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($request->Odometer)
                                                    <small>{{ number_format($request->Odometer) }} KM</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('maintenance.show', $request->ID) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="mt-3 text-muted">
                            <small>Showing latest {{ $requests->count() }} service requests</small>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Service Requests Found</h5>
                            <p class="text-muted">No service requests have been submitted yet.</p>
                            <a href="{{ route('prediction.index') }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Create First Request
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styling for better readability */
.table th {
    font-size: 0.9rem;
    border-top: none;
}

.table td {
    font-size: 0.85rem;
    vertical-align: middle;
}

code {
    font-size: 0.8rem;
}

.badge {
    font-size: 0.7rem;
}
</style>
@endsection