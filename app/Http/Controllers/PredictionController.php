<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use App\Services\PredictionService;
use App\Services\ReferenceDataService;
use App\Repositories\ServiceRequestRepository;
use App\Http\Requests\PredictionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PredictionController extends Controller
{
    private VehicleService $vehicleService;
    private PredictionService $predictionService;
    private ReferenceDataService $referenceService;

    public function __construct(
        VehicleService $vehicleService,
        PredictionService $predictionService,
        ReferenceDataService $referenceService
    ) {
        $this->vehicleService = $vehicleService;
        $this->predictionService = $predictionService;
        $this->referenceService = $referenceService;
    }

    /**
     * Show prediction form
     */
    public function index()
    {
        return view('prediction.index', [
            'mr_types' => $this->referenceService->getAllMRTypes(),
            'page_title' => 'Vehicle Maintenance Prediction'
        ]);
    }

    /**
     * Handle prediction request
     * Controller stays thin - delegates to services
     */
    public function predict(PredictionRequest $request)
    {
        try {
            Log::info("Prediction request started", $request->validated());

            // Step 1: Get vehicle data (delegated to VehicleService)
            $vehicleData = $this->vehicleService->getVehicleWithHistory(
                $request->validated('vehicle_number')
            );

            // Step 2: Make prediction (delegated to PredictionService)
            $predictionResult = $this->predictionService->makePrediction(
                $vehicleData,
                $request->validated('current_mileage')
            );

            Log::info("Prediction completed successfully", [
                'vehicle' => $request->validated('vehicle_number'),
                'prediction' => $predictionResult['prediction']['prediction'] ?? 'unknown'
            ]);

            // Step 3: Return view with data
            return view('prediction.result', [
                'vehicle_data' => $vehicleData,
                'prediction_result' => $predictionResult,
                'current_mileage' => $request->validated('current_mileage'),
                'page_title' => 'Prediction Results'
            ]);

        } catch (\Exception $e) {
            Log::error("Prediction failed", [
                'error' => $e->getMessage(),
                'vehicle' => $request->validated('vehicle_number')
            ]);

            return back()
                ->withInput()
                ->withErrors(['prediction' => 'Prediction failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show prediction results directly (for navigation from history)
     */
    public function show(string $vehicleNumber, int $currentMileage)
    {
        try {
            // Validate inputs
            $validation = $this->vehicleService->validateVehicleData($vehicleNumber, $currentMileage);
            
            if (!$validation['valid']) {
                return redirect()->route('prediction.index')
                    ->withErrors(['vehicle_number' => $validation['message']]);
            }

            // Get data and make prediction
            $vehicleData = $this->vehicleService->getVehicleWithHistory($vehicleNumber);
            $predictionResult = $this->predictionService->makePrediction($vehicleData, $currentMileage);

            return view('prediction.result', [
                'vehicle_data' => $vehicleData,
                'prediction_result' => $predictionResult,
                'current_mileage' => $currentMileage,
                'page_title' => 'Prediction Results'
            ]);

        } catch (\Exception $e) {
            return redirect()->route('prediction.index')
                ->withErrors(['error' => 'Unable to generate prediction: ' . $e->getMessage()]);
        }
    }

    /**
     * API endpoint for predictions
     */
    public function apiPredict(PredictionRequest $request)
    {
        try {
            $vehicleData = $this->vehicleService->getVehicleWithHistory(
                $request->validated('vehicle_number')
            );

            $predictionResult = $this->predictionService->makePrediction(
                $vehicleData,
                $request->validated('current_mileage')
            );

            return response()->json([
                'success' => true,
                'data' => $predictionResult,
                'meta' => [
                    'vehicle' => $request->validated('vehicle_number'),
                    'mileage' => $request->validated('current_mileage'),
                    'timestamp' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}