<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ActivityResource;
use App\Services\ActivityService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerActivityApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $type = $request->query('type');
            $activities = ActivityService::getSellerActivities($request->user()->id, 20, $type);
            // Assuming ActivityService returns raw arrays, we can still use the resource if we want but 
            // since it's already formatted, we can just return it or wrap it.
            return $this->successResponse($activities, 'Seller activities retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve activities', 500);
        }
    }
}
