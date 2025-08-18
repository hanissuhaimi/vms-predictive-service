<?php

namespace App\Http\Controllers;

use App\Services\VehicleService;
use App\Services\PredictionService;
use App\Services\ReferenceDataService;
use App\Repositories\ServiceRequestRepository;
use App\Http\Requests\PredictionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DepotController extends Controller
{
    private DepotRepository $depotRepository;
    private VehicleRepository $vehicleRepository;
    private ReferenceDataService $referenceService;

    public function __construct(
        DepotRepository $depotRepository,
        VehicleRepository $vehicleRepository,
        ReferenceDataService $referenceService
    ) {
        $this->depotRepository = $depotRepository;
        $this->vehicleRepository = $vehicleRepository;
        $this->referenceService = $referenceService;
    }

    /**
     * Show all depots with statistics
     */
    public function index()
    {
        try {
            $depotsWithStats = $this->depotRepository->getAllDepotsWithStats();

            return view('depots.index', [
                'depots' => $depotsWithStats,
                'page_title' => 'Depot Management'
            ]);

        } catch (\Exception $e) {
            Log::error("Depot index failed", ['error' => $e->getMessage()]);

            return view('depots.index', [
                'depots' => collect(),
                'error' => 'Unable to load depot data',
                'page_title' => 'Depot Management'
            ]);
        }
    }

    /**
     * Show specific depot details
     */
    public function show(string $depotCode)
    {
        try {
            $depot = $this->depotRepository->findDepotWithInfo($depotCode);
            
            if (!$depot) {
                return redirect()->route('depots.index')
                    ->withErrors(['error' => "Depot {$depotCode} not found"]);
            }

            $vehicleData = $this->vehicleRepository->getVehiclesByDepot($depotCode);
            $performanceMetrics = $this->depotRepository->getDepotPerformanceMetrics($depotCode);

            return view('depots.show', [
                'depot' => $depot,
                'vehicle_data' => $vehicleData,
                'performance_metrics' => $performanceMetrics,
                'page_title' => "Depot Details - {$depot->depot_nama}"
            ]);

        } catch (\Exception $e) {
            Log::error("Depot show failed", [
                'depot_code' => $depotCode,
                'error' => $e->getMessage()
            ]);

            return redirect()->route('depots.index')
                ->withErrors(['error' => 'Unable to load depot details']);
        }
    }
}