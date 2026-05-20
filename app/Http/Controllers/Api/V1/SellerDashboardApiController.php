<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerBalance;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SellerDashboardApiController extends Controller
{
    use ApiResponse;

    /**
     * Get seller dashboard statistics, chart data, and recent orders.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $productIds = $user->products()->pluck('id');

            // --- Stats ---
            $totalProducts  = $user->products()->count();
            $activeProducts = $user->products()->where('status', 'active')->count();
            $totalSold      = $user->products()->where('status', 'sold')->count();

            $activeOrders   = Order::whereIn('product_id', $productIds)
                ->whereIn('status', ['pending', 'dibayar', 'dikirim'])
                ->count();

            $needsShipping  = Order::whereIn('product_id', $productIds)
                ->where('status', 'dibayar')
                ->count();

            // --- Wallet ---
            $walletActive = SellerBalance::where('user_id', $user->id)
                ->whereIn('status', ['dilepas', 'ditarik'])
                ->sum('jumlah');

            $walletEscrow = SellerBalance::where('user_id', $user->id)
                ->where('status', 'ditahan')
                ->sum('jumlah');

            // --- Chart Data: Pendapatan 7 hari terakhir dari orders selesai ---
            $dayLabels = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

            $chartRaw = Order::whereIn('product_id', $productIds)
                ->where('status', 'selesai')
                ->where('created_at', '>=', now()->subDays(6)->startOfDay())
                ->select(
                    DB::raw('DAYOFWEEK(created_at) as day_num'),
                    DB::raw('SUM(total_harga) as total')
                )
                ->groupBy('day_num')
                ->pluck('total', 'day_num');

            // Build 7-day series (today minus 6 days)
            $chartData = [];
            for ($i = 6; $i >= 0; $i--) {
                $date   = now()->subDays($i);
                $dayNum = (int) $date->format('w') + 1; // PHP: 0=Sun, MySQL DAYOFWEEK: 1=Sun
                $chartData[] = [
                    'name'  => $dayLabels[$date->dayOfWeek], // Carbon: 0=Sun
                    'total' => (int) ($chartRaw[$dayNum] ?? 0),
                ];
            }

            // --- Recent Orders ---
            $recentOrders = Order::whereIn('product_id', $productIds)
                ->with(['user', 'product'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn($o) => [
                    'id'           => $o->id,
                    'buyer_name'   => $o->user->name ?? 'Pembeli',
                    'product_name' => $o->product->nama_barang ?? 'Produk',
                    'created_at'   => $o->created_at->diffForHumans(),
                ]);

            return $this->successResponse([
                'stats' => [
                    'total_products'  => $totalProducts,
                    'active_products' => $activeProducts,
                    'total_sold'      => $totalSold,
                    'active_orders'   => $activeOrders,
                    'needs_shipping'  => $needsShipping,
                    'total_revenue'   => $walletActive,
                    'pending_balance' => $walletEscrow,
                ],
                'chartData'     => $chartData,
                'recent_orders' => $recentOrders,
            ], 'Seller dashboard statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve dashboard statistics: ' . $e->getMessage(), 500);
        }
    }
}
