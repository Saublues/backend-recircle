<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerSettingsApiController extends Controller
{
    use ApiResponse;

    /**
     * Get current seller settings (profile fields).
     */
    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()), 'Settings retrieved successfully');
    }

    /**
     * Update seller profile settings.
     * Skips bank info for now — only name, kampus, bio.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name'    => 'sometimes|string|max:255',
                'kampus'  => 'sometimes|nullable|string|max:255',
                'bio'     => 'sometimes|nullable|string|max:1000',
                'nomor_wa'=> 'sometimes|nullable|string|max:20',
                'komerceDestinationId'    => 'sometimes|nullable|string',
                'komerceDestinationLabel' => 'sometimes|nullable|string',
            ]);

            $user = $request->user();
            
            // Manual Map from CamelCase request to snake_case model
            if (isset($validated['komerceDestinationId'])) {
                $user->komerce_destination_id = $validated['komerceDestinationId'];
            }
            if (isset($validated['komerceDestinationLabel'])) {
                $user->komerce_destination_label = $validated['komerceDestinationLabel'];
            }

            $user->fill(collect($validated)->except(['komerceDestinationId', 'komerceDestinationLabel'])->toArray());
            $user->save();

            return $this->successResponse(new UserResource($user), 'Pengaturan berhasil disimpan');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse($e->errors(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan pengaturan: ' . $e->getMessage(), 500);
        }
    }
}
