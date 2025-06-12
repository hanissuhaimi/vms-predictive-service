<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MaintenanceRequest; 

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
        ]);
    }

    /**
     * Store the submitted maintenance request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'issue_type' => 'required|string',
            'odometer' => 'required|numeric',
            'description' => 'nullable|string',
            'preferred_date' => 'required|date',
            'confidence' => 'nullable|string',
            'estimated_cost' => 'nullable|string',
            'time_needed' => 'nullable|string',
        ]);

        // Save to database, send email, or call an external API
        $newRequest = MaintenanceRequest::create($validated);

        return redirect()->route('maintenance.show', $newRequest->id);
    }

    public function show($id)
    {
        $request = MaintenanceRequest::findOrFail($id);
        return view('maintenance.show', compact('request'));
    }

}
