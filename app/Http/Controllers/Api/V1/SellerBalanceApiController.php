<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerBalanceResource;
use App\Models\SellerBalance;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerBalanceApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $balances = SellerBalance::where('user_id', $request->user()->id)
                ->with('order.product')
                ->latest()
                ->paginate(10);

            $totalDilepas = SellerBalance::where('user_id', $request->user()->id)
                ->whereIn('status', ['dilepas', 'ditarik'])
                ->sum('jumlah');

            $totalDitahan = SellerBalance::where('user_id', $request->user()->id)
                ->where('status', 'ditahan')
                ->sum('jumlah');

            $totalDitarik = SellerBalance::where('user_id', $request->user()->id)
                ->where('status', 'ditarik')
                ->sum('jumlah');

            return $this->successResponse([
                'totalDilepas' => $totalDilepas,
                'totalDitahan' => $totalDitahan,
                'totalDitarik' => $totalDitarik,
                'history' => SellerBalanceResource::collection($balances)->response()->getData(true)
            ], 'Balance history retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve balance history', 500);
        }
    }
}
