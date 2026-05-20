<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdminOfferResource;
use App\Http\Resources\Api\V1\AdminProductResource;
use App\Http\Resources\Api\V1\AdminUserResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerVerification;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardApiController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $stats = [
            'totalUsers' => User::count(),
            'totalProducts' => Product::count(),
            'pendingVerifications' => SellerVerification::where('status', 'pending')->count(),
            'totalTransactions' => Order::count(),
            'totalRevenue' => Order::where('status', 'selesai')->sum('total_harga'),
            'activeOffers' => Offer::where('status', 'pending')->count(),
            // Pre-populated mocked trends for cosmetic view parity if exact calculations are not available
            'usersTrend' => 12,
            'productsTrend' => 5,
            'transactionsTrend' => 8,
            'verificationsTrend' => 3,
        ];

        // Generate last 6 months statistics
        $monthlyStats = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyStats[] = [
                'month' => $date->format('n'), // Numerical month representation
                'revenue' => Order::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_harga') ?? 0
            ];
        }

        $recentActivities = \App\Services\ActivityService::getRecentActivities(5);

        return $this->successResponse([
            'stats' => $stats,
            'monthlyStats' => $monthlyStats,
            'recentActivities' => $recentActivities
        ], 'Admin dashboard summary aggregated successfully');
    }

    public function users(Request $request): JsonResponse
    {
        $query = User::query();
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")->orWhere('email', 'like', "%{$request->search}%");
        }
        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(10);
        return $this->successResponse(AdminUserResource::collection($users)->response()->getData(true), 'Users retrieved successfully');
    }

    public function storeUser(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,seller,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return $this->successResponse(new AdminUserResource($user), 'User created successfully', 201);
    }

    public function updateUser(Request $request, int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
                'role' => 'required|string|in:admin,seller,user',
                'password' => 'nullable|string|min:8',
            ]);

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->role = $validated['role'];
            if (!empty($validated['password'])) {
                $user->password = bcrypt($validated['password']);
            }
            $user->save();

            return $this->successResponse(new AdminUserResource($user), 'User updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update user', 500);
        }
    }

    public function destroyUser(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            if ($user->id === auth()->id()) {
                return $this->errorResponse('You cannot delete yourself', 403);
            }
            $user->delete();
            return $this->successResponse(null, 'User deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete user', 500);
        }
    }

    public function toggleUserStatus(int $id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->is_active = !($user->is_active ?? true);
            $user->save();
            return $this->successResponse(new AdminUserResource($user), 'User status toggled successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle user status', 500);
        }
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::with(['user', 'category']);
        if ($request->search) {
            $query->where('nama_barang', 'like', "%{$request->search}%");
        }
        if ($request->status) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(10);
        return $this->successResponse(AdminProductResource::collection($products)->response()->getData(true), 'Products retrieved successfully');
    }

    public function updateProductStatus(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $validated = $request->validate(['status' => 'required|in:active,rejected,inactive,pending']);
            $product->update(['status' => $validated['status']]);
            return $this->successResponse(new AdminProductResource($product), 'Product status updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product status', 500);
        }
    }

    public function offers(Request $request): JsonResponse
    {
        $query = Offer::with(['user', 'product.user']);
        $offers = $query->latest()->paginate(10);
        return $this->successResponse(AdminOfferResource::collection($offers)->response()->getData(true), 'Offers retrieved successfully');
    }

    public function transactions(Request $request): JsonResponse
    {
        $query = Order::with(['user', 'product.user']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('kode_pesanan', 'like', "%{$request->search}%")
                  ->orWhere('id', 'like', "%{$request->search}%")
                  ->orWhereHas('user', function ($uq) use ($request) {
                      $uq->where('name', 'like', "%{$request->search}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($request) {
                      $pq->where('nama_barang', 'like', "%{$request->search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10);
        return $this->successResponse(OrderResource::collection($orders)->response()->getData(true), 'Transactions retrieved successfully');
    }

    public function activities(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $activities = \App\Services\ActivityService::getRecentActivities(50, $type);
        return $this->successResponse($activities, 'Activities retrieved successfully');
    }
}
