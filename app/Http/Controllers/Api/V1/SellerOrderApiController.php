<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerOrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerOrderApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $productIds = $request->user()->products()->pluck('id');
            $orders = Order::whereIn('product_id', $productIds)
                ->with(['user', 'product'])
                ->latest()
                ->paginate(10);

            return $this->successResponse(
                SellerOrderResource::collection($orders)->response()->getData(true),
                'Seller orders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve seller orders', 500);
        }
    }

    public function ship(int $id, Request $request): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);
            $productIds = $request->user()->products()->pluck('id');
            
            if (!$productIds->contains($order->product_id)) {
                return $this->errorResponse('Unauthorized', 403);
            }

            if ($order->status !== 'dibayar') {
                return $this->errorResponse('Order must be in paid status to ship', 400);
            }

            $this->orderService->markAsShipped($order);
            return $this->successResponse(new SellerOrderResource($order), 'Order marked as shipped');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark order as shipped', 500);
        }
    }
}
