<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the user's notifications filtered by role.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $role = $request->query('role', 'buyer');

            // Query strictly by user_id and target_role in MongoDB
            $notifications = Notification::where('user_id', $user->id)
                ->where('target_role', $role)
                ->latest()
                ->get();

            return $this->successResponse($notifications, 'Notifications retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve notifications', 500);
        }
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $notification = Notification::where('user_id', $user->id)
                ->findOrFail($id);

            $notification->update(['is_read' => true]);

            return $this->successResponse($notification, 'Notification marked as read');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark notification as read', 500);
        }
    }

    /**
     * Mark all notifications of a specific role as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $role = $request->query('role', 'buyer');

            Notification::where('user_id', $user->id)
                ->where('target_role', $role)
                ->update(['is_read' => true]);

            return $this->successResponse(null, 'All notifications marked as read');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to mark all notifications as read', 500);
        }
    }
}
