<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductApiController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of active and non-archived products.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::active()->with(['user', 'category']);

            if ($request->has('search') && $request->search != '') {
                $query->where('nama_barang', 'like', '%' . $request->search . '%');
            }

            if ($request->has('category') && $request->category != '') {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category)
                      ->orWhere('id', $request->category);
                });
            }

            if ($request->has('kampus') && $request->kampus != '') {
                $query->where('lokasi_kampus', $request->kampus);
            }

            if ($request->has('sort')) {
                if ($request->sort === 'price_asc') {
                    $query->orderBy('harga', 'asc');
                } elseif ($request->sort === 'price_desc') {
                    $query->orderBy('harga', 'desc');
                } else {
                    $query->latest();
                }
            } else {
                $query->latest();
            }

            $products = $query->paginate(10)->appends($request->query());

            $resource = ProductResource::collection($products);

            return $this->successResponse(
                $resource->response()->getData(true),
                'Products retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve products: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified product.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::with(['user', 'category'])->findOrFail($id);

            // If product is not active, ONLY the seller (owner) can view it
            if (!$product->isActive()) {
                $currentUser = \Illuminate\Support\Facades\Auth::guard('sanctum')->user();
                if (!$currentUser || $product->user_id !== $currentUser->id) {
                    return $this->errorResponse('Product not found or unavailable', 404);
                }
            }

            return $this->successResponse(
                new ProductResource($product),
                'Product retrieved successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve product', 500);
        }
    }

    /**
     * Store a newly created product in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'categoryId'     => 'required|exists:categories,id',
                'name'           => 'required|string|max:255',
                'description'    => 'required|string',
                'price'          => 'required|numeric|min:0',
                'stock'          => 'required|integer|min:0',
                'condition'      => 'required|string',
                'campusLocation' => 'required|string|max:255',
                'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation Error', 422, $validator->errors());
            }

            $validated = $validator->validated();
            
            $data = [
                'category_id'   => $validated['categoryId'],
                'nama_barang'   => $validated['name'],
                'deskripsi'     => $validated['description'],
                'harga'         => $validated['price'],
                'stok'          => $validated['stock'],
                'kondisi'       => $validated['condition'],
                'lokasi_kampus' => $validated['campusLocation'],
                'status'        => 'active',
                'image'         => json_encode([]), // Ensure default value to avoid 1364 SQL error
            ];

            // Handle image uploads
            if ($request->hasFile('images')) {
                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = $image->store('products', 'public');
                }
                $data['image'] = json_encode($imagePaths);
            }

            $product = $request->user()->products()->create($data);

            return $this->successResponse(
                new ProductResource($product->load(['user', 'category'])),
                'Product created successfully',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            if ($product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized to update this product', 403);
            }

            $validator = Validator::make($request->all(), [
                'categoryId'     => 'sometimes|exists:categories,id',
                'name'           => 'sometimes|string|max:255',
                'description'    => 'sometimes|string',
                'price'          => 'sometimes|numeric|min:0',
                'stock'          => 'sometimes|integer|min:0',
                'condition'      => 'sometimes|string',
                'campusLocation' => 'sometimes|string|max:255',
                'status'         => 'sometimes|string|in:active,archived,sold',
                'images.*'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Validation Error', 422, $validator->errors());
            }

            $validated = $validator->validated();
            $data = [];
            
            if (isset($validated['categoryId'])) $data['category_id'] = $validated['categoryId'];
            if (isset($validated['name'])) $data['nama_barang'] = $validated['name'];
            if (isset($validated['description'])) $data['deskripsi'] = $validated['description'];
            if (isset($validated['price'])) $data['harga'] = $validated['price'];
            if (isset($validated['stock'])) $data['stok'] = $validated['stock'];
            if (isset($validated['condition'])) $data['kondisi'] = $validated['condition'];
            if (isset($validated['campusLocation'])) $data['lokasi_kampus'] = $validated['campusLocation'];
            if (isset($validated['status'])) $data['status'] = $validated['status'];

            // Handle image uploads
            if ($request->hasFile('image')) {
                 $path = $request->file('image')->store('products', 'public');
                 $data['image'] = $path;
            } elseif ($request->hasFile('images')) {
                $imagePaths = [];
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = $image->store('products', 'public');
                }
                $data['image'] = json_encode($imagePaths);
            }

            $product->update($data);

            return $this->successResponse(
                new ProductResource($product->load(['user', 'category'])),
                'Product updated successfully'
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update product: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified product from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);

            if ($product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized to delete this product', 403);
            }

            $product->delete();

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete product: ' . $e->getMessage(), 500);
        }
    }
}