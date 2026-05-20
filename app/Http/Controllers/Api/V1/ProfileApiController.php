<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileApiController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        return $this->successResponse(new UserResource($request->user()), 'Profile retrieved successfully');
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Handle avatar upload
            if ($request->hasFile('avatar')) {
                // Hapus avatar lama jika ada dan bukan URL eksternal
                if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->avatar = $path;
            }

            // Fill validated fields (name, email, bio, kampus)
            $validated = $request->validated();
            unset($validated['avatar']); // sudah ditangani di atas
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $this->successResponse(new UserResource($user), 'Profile updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            $user = $request->user();
            $user->delete();

            return $this->successResponse(null, 'Account deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete account', 500);
        }
    }
}
