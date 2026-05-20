<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SellerProductApiController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        try {
            $products = $request->user()->products()
                ->withTrashed()
                ->with(['category', 'orders' => function($q) {
                    $q->whereIn('status', ['pending', 'dibayar', 'dikirim']);
                }])
                ->latest()
                ->get()
                ->map(fn ($p) => [
                    'id'            => $p->id,
                    'name'          => $p->nama_barang,
                    'price'         => (int) $p->harga,
                    'category'      => $p->category->nama_kategori ?? '-',
                    'status'        => $p->status,
                    'is_archived'   => (bool) $p->is_archived,
                    'deleted_at'    => $p->deleted_at ? $p->deleted_at->toISOString() : null,
                    'img'           => $p->foto_barang_url,
                    'created_at'    => $p->created_at->diffForHumans(),
                    'active_orders' => $p->orders->count(),
                ]);

            return $this->successResponse($products, 'Seller products retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve seller products', 500);
        }
    }

    public function toggleArchive(int $id, Request $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            if ($product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $product->update([
                'is_archived' => !$product->is_archived,
            ]);

            $statusStr = $product->is_archived ? 'diarsipkan' : 'dikembalikan ke etalase';
            return $this->successResponse(null, "Produk berhasil {$statusStr}.");
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to toggle archive status', 500);
        }
    }

    public function destroy(int $id, Request $request): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            if ($product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized', 403);
            }

            $product->delete();
            return $this->successResponse(null, 'Produk berhasil dipindahkan ke Tong Sampah.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product', 500);
        }
    }

    public function restore(int $id, Request $request): JsonResponse
    {
        try {
            $product = Product::onlyTrashed()->where('user_id', $request->user()->id)->find($id);
            if (!$product) {
                return $this->errorResponse('Produk tidak ditemukan di tong sampah', 404);
            }

            $product->restore();
            return $this->successResponse(null, 'Produk berhasil dipulihkan.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to restore product', 500);
        }
    }

    public function forceDestroy(int $id, Request $request): JsonResponse
    {
        try {
            $product = Product::onlyTrashed()->where('user_id', $request->user()->id)->find($id);
            if (!$product) {
                return $this->errorResponse('Produk tidak ditemukan', 404);
            }

            if ($product->orders()->exists()) {
                return $this->errorResponse('Tidak dapat dihapus permanen karena memiliki riwayat transaksi', 422);
            }

            $product->forceDelete();
            return $this->successResponse(null, 'Produk berhasil dihapus permanen.');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to force delete product', 500);
        }
    }
}
