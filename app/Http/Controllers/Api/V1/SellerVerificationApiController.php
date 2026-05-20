<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SellerVerificationResource;
use App\Models\SellerVerification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerVerificationApiController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        try {
            $verification = SellerVerification::where('user_id', $request->user()->id)->first();
            if (!$verification) return $this->successResponse(null, 'No verification found');

            return $this->successResponse(new SellerVerificationResource($verification), 'Verification details retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve verification details', 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nama_kampus' => 'required|string|max:255',
                'nim'         => 'required|string|max:50',
                'foto_ktm'    => 'required|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            $user = $request->user();

            if (SellerVerification::where('user_id', $user->id)->exists()) {
                return $this->errorResponse('Verification request already exists', 400);
            }

            $path = $request->file('foto_ktm')->store('verifications', 'public');

            $verification = SellerVerification::create([
                'user_id'     => $user->id,
                'nama_kampus' => $validated['nama_kampus'],
                'nim'         => $validated['nim'],
                'foto_ktm'    => $path,
                'status'      => 'pending',
            ]);

            return $this->successResponse(new SellerVerificationResource($verification), 'Verification request submitted successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to submit verification: ' . $e->getMessage(), 500);
        }
    }
}
