<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerVerificationResource;
use App\Models\SellerVerification;
use App\Services\SellerVerificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminVerificationApiController extends Controller
{
    use ApiResponse;

    public function __construct(protected SellerVerificationService $verificationService) {}

    public function index(): JsonResponse
    {
        try {
            $verifications = $this->verificationService->getPendingVerifications();
            return $this->successResponse(
                SellerVerificationResource::collection($verifications),
                'Pending verifications retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve verifications', 500);
        }
    }

    public function approve(int $id): JsonResponse
    {
        try {
            $verification = SellerVerification::findOrFail($id);
            $this->verificationService->approveVerification($verification);
            return $this->successResponse(null, 'Seller approved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to approve verification', 500);
        }
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        try {
            $verification = SellerVerification::findOrFail($id);
            $validated = $request->validate(['catatan_admin' => 'required|string|max:500']);
            $this->verificationService->rejectVerification($verification, $validated['catatan_admin']);
            return $this->successResponse(null, 'Seller rejected');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reject verification', 500);
        }
    }
}
