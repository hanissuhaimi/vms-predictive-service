<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use App\Services\PredictionService;
use App\Services\ReferenceDataService;
use App\Repositories\ServiceRequestRepository;
use App\Http\Requests\PredictionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VehicleHistoryController extends Controller
{
    private VehicleService $vehicleService;
    private ServiceRequestRepository $serviceRepository;
    private ReferenceDataService $referenceService;

    public function __construct(
        VehicleService $vehicleService,
        ServiceRequestRepository $serviceRepository,
        ReferenceDataService $referenceService
    ) {
        $this->vehicleService = $vehicleService;
        $this->serviceRepository = $serviceRepository;
        $this->referenceService = $referenceService;
    }

    /**
     * Show vehicle maintenance history
     */
    public function show(Request $request, string $vehicleNumber)
    {
        try {
            // Validate vehicle exists
            $validation = $this->vehicleService->validateVehicleData($vehicleNumber, 100000);
            
            if (!$validation['valid'] && $validation['code'] !== 'INVALID_MILEAGE') {
                return redirect()->route('prediction.index')
                    ->withErrors(['vehicle_number' => $validation['message']]);
            }

            // Get vehicle data
            $vehicleData = $this->vehicleService->getVehicleWithHistory($vehicleNumber);

            // Get service history with filters
            $filters = $this->getHistoryFilters($request);
            $serviceHistory = $this->serviceRepository->findByVehicle($vehicleNumber, $filters);

            // Get statistics
            $serviceStats = $this->serviceRepository->getServiceStatistics($vehicleNumber);

            // Get service trends
            $serviceTrends = $this->serviceRepository->getServiceTrends(
                $vehicleNumber, 
                $request->get('trend_period', 'monthly')
            );

            return view('maintenance.history', [
                'vehicle_data' => $vehicleData,
                'service_history' => $serviceHistory,
                'service_stats' => $serviceStats,
                'service_trends' => $serviceTrends,
                'filters' => $filters,
                'mr_types' => $this->referenceService->getAllMRTypes(),
                'page_title' => "Maintenance History - {$vehicleNumber}"
            ]);

        } catch (\Exception $e) {
            Log::error("History view failed", [
                'vehicle' => $vehicleNumber,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('prediction.index')
                ->withErrors(['error' => 'Unable to load vehicle history: ' . $e->getMessage()]);
        }
    }

    /**
     * Export vehicle history
     */
    public function export(Request $request, string $vehicleNumber)
    {
        try {
            $filters = $this->getHistoryFilters($request);
            $serviceHistory = $this->serviceRepository->findByVehicle($vehicleNumber, $filters);
            $vehicleData = $this->vehicleService->getVehicleWithHistory($vehicleNumber);

            // This would implement Excel/PDF export
            // For now, return JSON for API consumption
            return response()->json([
                'vehicle' => $vehicleData['vehicle']->formatted_registration,
                'export_date' => now()->toDateString(),
                'total_records' => $serviceHistory->count(),
                'history' => $serviceHistory->map(function ($service) {
                    return [
                        'date' => $service->formatted_date_received,
                        'sr_number' => $service->SR,
                        'description' => $service->formatted_description,
                        'type' => $service->mr_type_text,
                        'status' => $service->status_text,
                        'priority' => $service->priority_text
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get history filters from request
     */
    private function getHistoryFilters(Request $request): array
    {
        $filters = [];

        if ($request->filled('mr_type')) {
            $filters['mr_type'] = $request->get('mr_type');
        }

        if ($request->filled('status')) {
            $filters['status'] = $request->get('status');
        }

        if ($request->filled('priority')) {
            $filters['priority'] = $request->get('priority');
        }

        if ($request->filled('date_from')) {
            $filters['date_from'] = $request->get('date_from');
        }

        if ($request->filled('date_to')) {
            $filters['date_to'] = $request->get('date_to');
        }

        if ($request->filled('limit')) {
            $filters['limit'] = min(1000, (int) $request->get('limit'));
        }

        return $filters;
    }
}