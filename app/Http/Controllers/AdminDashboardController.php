<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Offer;
use App\Models\SellerVerification;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'users_trend' => $this->calculateTrend(User::class),
            'total_products' => Product::count(),
            'products_trend' => $this->calculateTrend(Product::class),
            'pending_verifications' => SellerVerification::where('status', 'pending')->count(),
            'verifications_trend' => $this->calculateTrend(SellerVerification::class),
            'total_transactions' => Order::count(),
            'transactions_trend' => $this->calculateTrend(Order::class),
            'total_revenue' => Order::where('status', 'completed')->sum('total_harga'),
            'active_offers' => Offer::where('status', 'pending')->count(),
        ];

        // Monthly stats for chart (last 6 months)
        $monthlyStats = Order::select(
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(total_harga) as revenue'),
            DB::raw("DATE_FORMAT(created_at, '%m') as month")
        )
            ->where('status', 'completed')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(6)
            ->get();

        $recentActivities = $this->getRecentActivities();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'monthlyStats' => $monthlyStats,
            'recentActivities' => $recentActivities,
        ]);
    }

    public function products(Request $request)
    {
        $query = Product::with(['user', 'category']);

        if ($request->search) {
            $query->where('nama_barang', 'like', "%{$request->search}%")
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $products = $query->latest()->paginate(10)->through(fn($p) => [
            'id' => $p->id,
            'name' => $p->nama_barang,
            'seller' => $p->user->name,
            'category' => $p->category->nama_kategori ?? 'Umum',
            'price' => $p->harga,
            'status' => $p->status ?? 'pending',
            'image' => $p->foto_barang_url,
            'created_at' => $p->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Products', [
            'products' => $products,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function updateProductStatus(Request $request, Product $product)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,rejected,inactive,pending',
        ]);

        $product->update(['status' => $validated['status']]);

        return back()->with('success', "Status produk {$product->nama_barang} diperbarui menjadi {$validated['status']}.");
    }

    public function offers(Request $request)
    {
        $query = Offer::with(['user', 'product.user']);

        if ($request->search) {
            $query->whereHas('product', fn($q) => $q->where('nama_barang', 'like', "%{$request->search}%"))
                ->orWhereHas('user', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
        }

        $offers = $query->latest()->paginate(10)->through(fn($o) => [
            'id' => $o->id,
            'product' => $o->product->nama_barang,
            'buyer' => $o->user->name,
            'seller' => $o->product->user->name,
            'original_price' => $o->product->harga,
            'offered_price' => $o->harga_tawaran,
            'status' => $o->status,
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Offers', [
            'offers' => $offers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function transactions(Request $request)
    {
        $query = Order::with(['buyer', 'seller', 'product']);

        if ($request->search) {
            $query->where('id', 'like', "%{$request->search}%")
                ->orWhereHas('buyer', fn($q) => $q->where('name', 'like', "%{$request->search}%"))
                ->orWhereHas('product', fn($q) => $q->where('nama_barang', 'like', "%{$request->search}%"));
        }

        $orders = $query->latest()->paginate(10)->through(fn($o) => [
            'id' => $o->id,
            'product' => $o->product->nama_barang ?? 'Produk Dihapus',
            'buyer' => $o->buyer->name,
            'seller' => $o->seller->name,
            'total_harga' => $o->total_harga,
            'status' => $o->status,
            'payment_status' => $o->payment_status,
            'created_at' => $o->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Transactions', [
            'transactions' => $orders,
            'filters' => $request->only(['search']),
        ]);
    }

    public function users(Request $request)
    {
        $query = User::query();

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->role) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(10)->withQueryString()->through(fn($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'role' => $u->role ?? 'user',
            'is_active' => $u->is_active ?? true,
            'created_at' => $u->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
        ]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,seller,user',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
        ]);

        return back()->with('success', "User {$validated['name']} berhasil ditambahkan.");
    }

    public function updateUser(Request $request, User $user)
    {
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

        return back()->with('success', "User {$user->name} berhasil diperbarui.");
    }

    public function destroyUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "User {$userName} berhasil dihapus.");
    }

    public function toggleUserStatus(User $user)
    {
        $user->is_active = !($user->is_active ?? true);
        $user->save();

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "User {$user->name} berhasil {$status}.");
    }

    public function activities()
    {
        $activities = \App\Services\ActivityService::getRecentActivities(50);

        return Inertia::render('Admin/Activities', [
            'activities' => $activities,
        ]);
    }

    private function getRecentActivities($limit = 5)
    {
        return \App\Services\ActivityService::getRecentActivities($limit);
    }

    private function calculateTrend($modelClass, $days = 30)
    {
        $currentPeriod = $modelClass::where('created_at', '>=', now()->subDays($days))->count();
        $previousPeriod = $modelClass::where('created_at', '>=', now()->subDays($days * 2))
            ->where('created_at', '<', now()->subDays($days))
            ->count();

        if ($previousPeriod == 0) {
            return $currentPeriod > 0 ? 100 : 0;
        }

        return round((($currentPeriod - $previousPeriod) / $previousPeriod) * 100, 1);
    }
}
