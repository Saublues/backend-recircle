<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function __construct(
        protected RecommendationService $recommendationService,
    ) {}

    /**
     * Halaman Explore (Index).
     */
    public function index(Request $request)
    {
        $categories = Category::all();

        $products = Product::active()
            ->with(['user', 'category', 'images'])
            ->latest()
            ->get()
            ->map(fn ($product) => $this->mapProductToProps($product));

        return Inertia::render('Product/Index', [
            'initialProducts' => $products,
            'categories'      => $categories->map(fn ($c) => [
                'id'   => $c->id,
                'name' => $c->nama_kategori,
                'slug' => $c->slug,
            ]),
        ]);
    }

    /**
     * Halaman Detail Produk (Show).
     */
    public function show(Request $request, $id)
    {
        $product = Product::with(['user', 'category', 'images', 'offers'])->findOrFail($id);

        // Rekam view untuk rekomendasi jika user login
        if ($request->user()) {
            $this->recommendationService->recordView($request->user(), $product);
        }

        return Inertia::render('Product/Show', [
            'product' => [
                'id'             => $product->id,
                'name'           => $product->nama_barang,
                'price'          => $product->harga,
                'category'       => $product->category->nama_kategori,
                'category_id'    => $product->category_id,
                'condition'      => $product->kondisi,
                'desc'           => $product->deskripsi,
                'loc'            => $product->lokasi_kampus,
                'img'            => $product->foto_barang_urls[0] ?? $product->foto_barang_url,
                'images'         => $product->foto_barang_urls,
                'status'         => $product->status,
                'posted_at'      => $product->created_at->diffForHumans(),
                'seller' => [
                    'id'          => $product->user->id,
                    'name'        => $product->user->name,
                    'phone'       => $product->user->nomor_wa ?? '',
                    'kampus'      => $product->user->kampus ?? '',
                    'is_verified' => $product->user->is_seller,
                    'avatar'      => $product->user->avatar
                        ?? 'https://ui-avatars.com/api/?name=' . urlencode($product->user->name) . '&background=43552c&color=fff',
                ],
            ],
        ]);
    }

    /**
     * Form Create Produk.
     */
    public function create()
    {
        $categories = Category::all()->map(fn ($c) => [
            'id'   => $c->id,
            'name' => $c->nama_kategori,
        ]);

        return Inertia::render('Product/Create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store Produk Baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_barang'   => 'required|string|max:255',
            'harga'         => 'required|numeric|min:1000',
            'category_id'   => 'required|exists:categories,id',
            'kondisi'       => 'required|string',
            'deskripsi'     => 'required|string',
            'lokasi_kampus' => 'required|string',
            'images'        => 'required|array|min:1|max:5',
            'images.*'      => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $product = $request->user()->products()->create([
            'nama_barang'   => $validated['nama_barang'],
            'harga'         => $validated['harga'],
            'category_id'   => $validated['category_id'],
            'kondisi'       => $validated['kondisi'],
            'deskripsi'     => $validated['deskripsi'],
            'lokasi_kampus' => $validated['lokasi_kampus'],
            'image'         => '',
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                $fullPath = '/storage/' . $path;
                $product->images()->create(['path' => $fullPath]);
                if ($index === 0) {
                    $product->update(['image' => $fullPath]);
                }
            }
        }

        return redirect()->route('seller.products.index')->with('message', 'Barang berhasil tayang!');
    }

    /**
     * Map produk model ke props frontend.
     */
    protected function mapProductToProps(Product $product): array
    {
        return [
            'id'            => $product->id,
            'name'          => $product->nama_barang,
            'price'         => $product->harga,
            'category'      => $product->category->nama_kategori ?? '',
            'category_id'   => $product->category_id,
            'location_uni'  => $product->lokasi_kampus,
            'img'           => $product->foto_barang_url,
            'seller_name'   => $product->user->name,
            'seller_avatar' => $product->user->avatar
                ?? 'https://ui-avatars.com/api/?name=' . urlencode($product->user->name) . '&background=43552c&color=fff',
            'posted_at'     => $product->created_at->diffForHumans(),
        ];
    }
}