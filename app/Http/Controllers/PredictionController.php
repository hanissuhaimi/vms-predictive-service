<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\VMSPredictionService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PredictionController extends Controller
{
    protected $predictionService;

    public function __construct(VMSPredictionService $predictionService)
    {
        $this->predictionService = $predictionService;
    }

    public function index()
    {
        $samplePlates = [
            'WGW1349' => 693,
            'WUF9184' => 998, 
            'WRT8584' => 958,
            'ABC1234' => 573,
            'XYZ5678' => 750,
            'DEF9999' => 400
        ];

        return view('prediction.index', compact('samplePlates'));
    }

    public function predict(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => 'required|string|min:3',
            'odometer' => 'required|integer|min:1',
            'number_plate' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Prepare request data for ML model (match training data format)
            $requestData = [
                'Description' => $request->description, // Note: Capital D to match training
                'Odometer' => $request->odometer,
                'Priority' => $request->priority ?? 1,
                'service_count' => $request->service_count ?? $this->estimateServiceCount($request->odometer),
                'Building_encoded' => $request->building_encoded ?? 2,
                'Vehicle_encoded' => $this->getVehicleEncoded($request->number_plate),
                'Status_encoded' => 3, // Default completed
                'MrType_encoded' => $this->autoDetectMrType($request->description),
                'request_date' => $request->request_date ?? now()->format('Y-m-d H:i:s'),
                'response_days' => $request->response_days ?? 1,
                'request_hour' => now()->hour,
                'request_day_of_week' => now()->dayOfWeek,
                'request_month' => now()->month,
            ];

            Log::info('Attempting ML prediction with data: ', $requestData);

            // Try real ML prediction first
            $result = $this->predictionService->predict($requestData);
            
            if ($result && !isset($result['error'])) {
                $result['source'] = 'ML Model';
                Log::info('ML prediction successful');
            } else {
                // Fallback to mock prediction if ML fails
                $result = $this->createMockPrediction($request->description);
                $result['source'] = 'Fallback Prediction';
                $result['note'] = 'ML model returned error - using keyword-based prediction';
                Log::warning('ML prediction failed, using fallback');
            }
            
            if ($result) {
                $analysis = $this->generateAnalysis($result['prediction'], $result['confidence']);
                
                return view('prediction.result', [
                    'result' => $result,
                    'analysis' => $analysis,
                    'requestData' => $request->all()
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Controller prediction error: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage())->withInput();
        }

        return back()->with('error', 'Unable to make prediction. Please try again.')->withInput();
    }

    private function estimateServiceCount($odometer)
    {
        return max(2, min(2704, intval($odometer / 15000)));
    }

    private function getVehicleEncoded($numberPlate)
    {
        $samplePlates = [
            'WGW1349' => 693,
            'WUF9184' => 998, 
            'WRT8584' => 958,
            'ABC1234' => 573,
            'XYZ5678' => 750,
            'DEF9999' => 400
        ];

        return $samplePlates[strtoupper($numberPlate)] ?? 573;
    }

    private function autoDetectMrType($description)
    {
        $descLower = strtolower($description);
        
        if (str_contains($descLower, 'service') || 
            str_contains($descLower, 'servis') || 
            str_contains($descLower, 'maintenance') || 
            str_contains($descLower, 'check')) {
            return 0;
        } elseif (str_contains($descLower, 'repair') || 
                  str_contains($descLower, 'fix') || 
                  str_contains($descLower, 'broken') || 
                  str_contains($descLower, 'rosak') || 
                  str_contains($descLower, 'baiki')) {
            return 1;
        }
        
        return 0;
    }

    private function createMockPrediction($description)
    {
        $descLower = strtolower($description);
        
        if (str_contains($descLower, 'brake') || str_contains($descLower, 'brek')) {
            return ['prediction' => 'brake_system', 'confidence' => 0.89];
        } elseif (str_contains($descLower, 'tire') || str_contains($descLower, 'tayar') || str_contains($descLower, 'pancit')) {
            return ['prediction' => 'tire', 'confidence' => 0.92];
        } elseif (str_contains($descLower, 'engine') || str_contains($descLower, 'injin') || str_contains($descLower, 'start')) {
            return ['prediction' => 'engine', 'confidence' => 0.85];
        } elseif (str_contains($descLower, 'clean') || str_contains($descLower, 'cuci') || str_contains($descLower, 'wash')) {
            return ['prediction' => 'cleaning', 'confidence' => 0.95];
        } elseif (str_contains($descLower, 'service') || str_contains($descLower, 'servis') || str_contains($descLower, 'maintenance')) {
            return ['prediction' => 'service', 'confidence' => 0.88];
        } elseif (str_contains($descLower, 'electrical') || str_contains($descLower, 'battery') || str_contains($descLower, 'light')) {
            return ['prediction' => 'electrical', 'confidence' => 0.82];
        } else {
            return ['prediction' => 'other', 'confidence' => 0.75];
        }
    }

    private function generateAnalysis($category, $confidence)
    {
        $analyses = [
            'brake_system' => [
                'solution' => '🛑 Brake System Service',
                'action' => 'URGENT: Brake inspection and repair needed',
                'time_needed' => 'Same day',
                'cost_estimate' => 'RM 200 - RM 800'
            ],
            'tire' => [
                'solution' => '🛞 Tire Service Required',
                'action' => 'Check/replace tires, wheel alignment',
                'time_needed' => 'Same day',
                'cost_estimate' => 'RM 100 - RM 600'
            ],
            'engine' => [
                'solution' => '🚗 Engine Repair',
                'action' => 'Engine diagnosis and repair needed',
                'time_needed' => '1-3 days',
                'cost_estimate' => 'RM 400 - RM 2000'
            ],
            'cleaning' => [
                'solution' => '🧽 Vehicle Cleaning Service',
                'action' => 'Schedule vehicle washing and cleaning',
                'time_needed' => 'Same day',
                'cost_estimate' => 'RM 50 - RM 150'
            ],
            'service' => [
                'solution' => '⚙️ Routine Maintenance',
                'action' => 'Schedule regular service (oil, filters, check-up)',
                'time_needed' => 'Half day',
                'cost_estimate' => 'RM 200 - RM 500'
            ],
            'electrical' => [
                'solution' => '⚡ Electrical System Repair',
                'action' => 'Check wiring, battery, electrical components',
                'time_needed' => 'Half day to 1 day',
                'cost_estimate' => 'RM 150 - RM 600'
            ],
            'mechanical' => [
                'solution' => '🔧 Mechanical Repair',
                'action' => 'Mechanical parts need repair/replacement',
                'time_needed' => '1-2 days',
                'cost_estimate' => 'RM 300 - RM 1200'
            ],
            'air_system' => [
                'solution' => '💨 Air System Service',
                'action' => 'Air brake/suspension system check',
                'time_needed' => '1 day',
                'cost_estimate' => 'RM 250 - RM 700'
            ],
            'hydraulic' => [
                'solution' => '💧 Hydraulic System Repair',
                'action' => 'Check hydraulic fluid and system',
                'time_needed' => '1-2 days',
                'cost_estimate' => 'RM 300 - RM 1000'
            ],
            'body' => [
                'solution' => '🚛 Body Work Required',
                'action' => 'Vehicle body repair or maintenance',
                'time_needed' => '1-3 days',
                'cost_estimate' => 'RM 300 - RM 1500'
            ],
            'other' => [
                'solution' => '🔧 General Repair Needed',
                'action' => 'Take to workshop for diagnosis and repair',
                'time_needed' => '1-2 days',
                'cost_estimate' => 'RM 200 - RM 800'
            ]
        ];

        return $analyses[$category] ?? $analyses['other'];
    }
}