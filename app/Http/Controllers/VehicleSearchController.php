<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use App\Services\PredictionService;
use App\Services\ReferenceDataService;
use App\Repositories\ServiceRequestRepository;
use App\Http\Requests\PredictionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleSearchController extends Controller
{
    private VehicleService $vehicleService;
    private VehicleRepository $vehicleRepository;

    public function __construct(
        VehicleService $vehicleService,
        VehicleRepository $vehicleRepository
    ) {
        $this->vehicleService = $vehicleService;
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * Search vehicles
     */
    public function search(Request $request)
    {
        try {
            $request->validate([
                'search' => 'nullable|string|max:50',
                'depot' => 'nullable|string',
                'status' => 'nullable|string|in:active,maintenance,all',
                'model' => 'nullable|string'
            ]);

            $searchResults = $this->vehicleService->searchVehicles(
                $request->get('search', ''),
                $request->only(['depot', 'status', 'model'])
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $searchResults
                ]);
            }

            return view('vehicles.search', [
                'search_results' => $searchResults,
                'page_title' => 'Vehicle Search'
            ]);

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 400);
            }

            return view('vehicles.search', [
                'error' => 'Search failed: ' . $e->getMessage(),
                'page_title' => 'Vehicle Search'
            ]);
        }
    }

    /**
     * Get vehicles needing maintenance
     */
    public function needingMaintenance()
    {
        try {
            $vehiclesNeedingMaintenance = $this->vehicleRepository->getVehiclesNeedingMaintenance();

            return view('vehicles.maintenance-needed', [
                'vehicles' => $vehiclesNeedingMaintenance,
                'page_title' => 'Vehicles Needing Maintenance'
            ]);

        } catch (\Exception $e) {
            return view('vehicles.maintenance-needed', [
                'vehicles' => collect(),
                'error' => 'Unable to load maintenance data',
                'page_title' => 'Vehicles Needing Maintenance'
            ]);
        }
    }
}