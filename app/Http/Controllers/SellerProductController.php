<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Traits\ApiResponse;

class SellerProductController extends Controller
{
    use ApiResponse;

    /**
     * Seller: Kelola Produk (List Produk Sendiri).
     */
    public function index(Request $request)
    {
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
                'price'         => $p->harga,
                'category'      => $p->category->nama_kategori ?? '-',
                'status'        => $p->status,
                'is_archived'   => (bool) $p->is_archived,
                'deleted_at'    => $p->deleted_at ? $p->deleted_at->toISOString() : null,
                'img'           => $p->foto_barang_url,
                'created_at'    => $p->created_at->diffForHumans(),
                'active_orders' => $p->orders->count(),
            ]);

        return $this->successResponse($products, 'Products retrieved successfully');
    }

    /**
     * Mengubah status is_archived (Aktif <-> Arsip).
     */
    public function toggleArchive(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $product->update([
            'is_archived' => !$product->is_archived,
        ]);

        $statusStr = $product->is_archived ? 'diarsipkan' : 'dikembalikan ke etalase';
        return $this->successResponse(null, "Produk berhasil {$statusStr}.");
    }

    /**
     * Melakukan Soft Delete (barang masuk ke "Tong Sampah").
     */
    public function destroy(Request $request, Product $product)
    {
        if ($product->user_id !== $request->user()->id) {
            return $this->errorResponse('Unauthorized access', 403);
        }

        $product->delete();

        return $this->successResponse(null, 'Produk berhasil dipindahkan ke Tong Sampah.');
    }

    /**
     * Mengembalikan barang dari "Tong Sampah" ke etalase utama.
     */
    public function restore(Request $request, $id)
    {
        $product = Product::onlyTrashed()->where('user_id', $request->user()->id)->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        $product->restore();

        return $this->successResponse(null, 'Produk berhasil dipulihkan.');
    }

    /**
     * Penjual menghapus permanen manual dari "Tong Sampah".
     */
    public function forceDestroy(Request $request, $id)
    {
        $product = Product::onlyTrashed()->where('user_id', $request->user()->id)->find($id);

        if (!$product) {
            return $this->errorResponse('Product not found', 404);
        }

        // Cek apakah produk punya relasi ke tabel orders
        if ($product->orders()->exists()) {
            return $this->errorResponse('Tidak dapat dihapus permanen karena memiliki riwayat transaksi', 422);
        }

        $product->forceDelete();

        return $this->successResponse(null, 'Produk berhasil dihapus permanen.');
    }
}
