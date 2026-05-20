<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    use ApiResponse;

    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name'     => 'required|string|max:255',
                'email'    => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $user = User::create([
                'name'     => $request->name,
                'email'    => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 'Registration successful', 201);

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation Error', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle user login and token generation.
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email'    => 'required|email',
                'password' => 'required',
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse('Invalid credentials', 401);
            }

            // Generate Token
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                ],
                'token' => $token,
            ], 'Login successful');

        } catch (ValidationException $e) {
            return $this->errorResponse('Validation Error', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->errorResponse('Internal Server Error', 500);
        }
    }

    /**
     * Get the authenticated user details cleanly.
     */
    public function user(Request $request): JsonResponse
    {
        try {
            $user = auth('sanctum')->user();
            if (!$user) {
                return $this->successResponse(null, 'User is not logged in');
            }
            return $this->successResponse(new \App\Http\Resources\Api\V1\UserResource($user), 'Authenticated user retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Handle user logout and token revocation.
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return $this->successResponse(null, 'Logged out successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to logout', 500);
        }
    }
}
