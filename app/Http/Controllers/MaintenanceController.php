<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\Log;

class MaintenanceController extends Controller
{
    /**
     * Show the maintenance request form.
     */
    public function create(Request $request)
    {
        return view('maintenance.create', [
            'prediction' => $request->input('prediction'),
            'odometer' => $request->input('odometer'),
            'description' => $request->input('description'),
            'confidence' => $request->input('confidence'),
            'cost_estimate' => $request->input('cost_estimate'),
            'time_needed' => $request->input('time_needed'),
            'number_plate' => $request->input('number_plate'),
            'priority' => $request->input('priority'),
            'prediction_confidence' => $request->input('prediction_confidence'),
        ]);
    }

    /**
     * Store the submitted maintenance request to existing ServiceRequest table.
     */
    public function store(Request $request)
    {
        // Updated validation - make optional fields nullable
        $validated = $request->validate([
            'issue_type' => 'required|string|max:255',
            'odometer' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'preferred_date' => 'required|date|after_or_equal:today',
            'confidence' => 'nullable|string|max:50',
            'estimated_cost' => 'nullable|string|max:100',
            'time_needed' => 'nullable|string|max:100',
            'number_plate' => 'nullable|string|max:20',
            'priority' => 'nullable|integer|between:1,4',
            'prediction_confidence' => 'nullable|numeric|between:0,1',
            // Add other potential fields
            'prediction_category' => 'nullable|string|max:100',
            'ml_source' => 'nullable|string|max:50',
        ]);

        try {
            // Map Laravel form data to existing ServiceRequest table columns
            // Use null coalescing operator (??) to handle missing keys safely
            $serviceRequestData = [
                // Basic request info
                'SR' => $this->generateSRNumber(),
                'Datereceived' => now(),
                'timereceived' => now()->format('g:i:s A'),
                'Requestor' => auth()->user()->name ?? 'Web User',
                
                // Issue details - safely access array keys
                'Description' => $validated['description'] ?? $validated['issue_type'] ?? 'No description provided',
                'CMType' => $this->mapIssueTypeToCMType($validated['issue_type'] ?? 'general'),
                'MrType' => $this->mapIssueTypeToMrType($validated['issue_type'] ?? 'general'),
                
                // Vehicle info - safely access keys
                'Vehicle' => $validated['number_plate'] ?? '',
                'Odometer' => isset($validated['odometer']) ? (string) $validated['odometer'] : '0',
                
                // Priority and status - safely access keys
                'Priority' => isset($validated['priority']) ? (string) $validated['priority'] : '1',
                'Status' => '1', // 1 = Pending (based on sample data pattern)
                
                // Location (you may want to customize these)
                'Building' => '40200', // Default building code (from sample data)
                'department' => 'Maintenance',
                'location' => '442021030', // Default location (from sample data)
                
                // Staff assignment
                'Staff' => auth()->user()->name ?? 'system',
                
                // Additional info for ML predictions (store in available fields)
                'Response' => $this->formatPredictionInfo([
                    'prediction' => $validated['issue_type'] ?? 'Unknown',
                    'confidence' => $validated['confidence'] ?? null,
                    'cost_estimate' => $validated['estimated_cost'] ?? null,
                    'time_needed' => $validated['time_needed'] ?? null,
                    'prediction_confidence' => $validated['prediction_confidence'] ?? null,
                    'prediction_category' => $validated['prediction_category'] ?? null,
                    'ml_source' => $validated['ml_source'] ?? 'manual',
                ]),
                
                // Dates - safely handle preferred_date
                'responseDate' => isset($validated['preferred_date']) ? 
                    \Carbon\Carbon::parse($validated['preferred_date']) : 
                    now()->addDay(),
                'DateModify' => now(),
                'TimeModify' => now(),
                'ModifyBy' => auth()->user()->name ?? 'system',
                
                // Trailer info (if applicable)
                'ForTrailer' => false,
            ];

            Log::info('Attempting to insert into existing ServiceRequest table:', $serviceRequestData);

            // Insert into existing ServiceRequest table
            $newRequest = ServiceRequest::create($serviceRequestData);

            Log::info('Successfully inserted ServiceRequest with ID: ' . $newRequest->ID);

            return redirect()->route('maintenance.show', $newRequest->ID)
                ->with('success', 'Service request submitted successfully!');

        } catch (\Exception $e) {
            Log::error('Error inserting into ServiceRequest table: ' . $e->getMessage());
            Log::error('Request data received: ' . json_encode($request->all()));
            Log::error('Validated data: ' . json_encode($validated ?? []));
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to submit service request: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified service request.
     */
    public function show($id)
    {
        try {
            $request = ServiceRequest::findOrFail($id);
            return view('maintenance.show', compact('request'));
        } catch (\Exception $e) {
            Log::error('Error retrieving ServiceRequest: ' . $e->getMessage());
            return redirect()->route('prediction.index')
                ->withErrors(['error' => 'Service request not found.']);
        }
    }

    /**
     * Get all service requests (for admin view)
     */
    public function index()
    {
        try {
            $requests = ServiceRequest::orderBy('Datereceived', 'desc')->take(50)->get();
            return view('maintenance.index', compact('requests'));
        } catch (\Exception $e) {
            Log::error('Error retrieving ServiceRequests: ' . $e->getMessage());
            return view('maintenance.index', ['requests' => collect()]);
        }
    }

    /**
     * Generate Service Request number in your existing format
     */
    private function generateSRNumber()
    {
        try {
            $year = date('y');
            $building = '40200'; // Default building code
            $sequence = ServiceRequest::whereYear('Datereceived', date('Y'))
                ->count() + 1;
                
            return sprintf('MR/%s/%s/30/%05d', $year, $building, $sequence);
        } catch (\Exception $e) {
            // Fallback if database query fails
            return 'MR/' . date('y') . '/40200/30/' . sprintf('%05d', rand(1, 99999));
        }
    }

    /**
     * Map issue type to CMType (based on your existing data patterns)
     */
    private function mapIssueTypeToCMType($issueType)
    {
        $mapping = [
            'brake_system' => 'BRAKE',
            'tire' => 'TIRE',
            'engine' => 'ENGINE',
            'cleaning' => 'CLEANING',
            'service' => 'SERVICE',
            'electrical' => 'ELECTRICAL',
            'mechanical' => 'MECHANICAL',
            'air_system' => 'AIR',
            'hydraulic' => 'HYDRAULIC',
            'body' => 'BODY',
            'other' => 'GENERAL'
        ];

        return $mapping[$issueType] ?? 'GENERAL';
    }

    /**
     * Map issue type to MrType (based on sample data: 1=repair, 2=cleaning, etc.)
     */
    private function mapIssueTypeToMrType($issueType)
    {
        if (in_array($issueType, ['cleaning', 'wash'])) {
            return '2'; // Cleaning/washing
        } elseif (in_array($issueType, ['service', 'maintenance'])) {
            return '3'; // Maintenance
        } else {
            return '1'; // Repair (default)
        }
    }

    /**
     * Format prediction information for storage in Response field
     * Safely handle missing or null values
     */
    private function formatPredictionInfo($predictionData)
    {
        $info = "ML Prediction Results:\n";
        $info .= "Predicted Issue: " . ($predictionData['prediction'] ?? 'Unknown') . "\n";
        $info .= "Confidence: " . ($predictionData['confidence'] ?? 'N/A') . "\n";
        $info .= "Estimated Cost: " . ($predictionData['cost_estimate'] ?? 'N/A') . "\n";
        $info .= "Time Needed: " . ($predictionData['time_needed'] ?? 'N/A') . "\n";
        $info .= "ML Confidence Score: " . ($predictionData['prediction_confidence'] ?? 'N/A') . "\n";
        $info .= "Prediction Category: " . ($predictionData['prediction_category'] ?? 'N/A') . "\n";
        $info .= "ML Source: " . ($predictionData['ml_source'] ?? 'manual') . "\n";
        $info .= "Generated: " . now()->format('Y-m-d H:i:s');
        
        return $info;
    }
}