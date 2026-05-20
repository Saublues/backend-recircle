<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogisticsApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected RajaOngkirService $rajaOngkir) {}

    public function searchDestination(Request $request): JsonResponse
    {
        $keyword = $request->query('keyword', '');
        if (strlen($keyword) < 3) {
            return $this->successResponse([], 'Keyword too short');
        }
        
        try {
            $results = $this->rajaOngkir->searchDestination($keyword);
            return $this->successResponse($results, 'Destinations retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to search destination', 500);
        }
    }

    public function calculateCost(Request $request): JsonResponse
    {
        $request->validate([
            'origin' => 'required',
            'destination' => 'required',
            'weight' => 'required|numeric',
            'courier' => 'required|string',
        ]);

        try {
            $cost = $this->rajaOngkir->calculateCost(
                $request->origin,
                $request->destination,
                $request->weight,
                $request->courier
            );
            return $this->successResponse($cost, 'Shipping cost calculated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to calculate shipping cost: ' . $e->getMessage(), 500);
        }
    }
}
