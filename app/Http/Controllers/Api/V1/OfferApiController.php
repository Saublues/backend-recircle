<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Models\Product;
use App\Services\OfferService;
use App\Traits\ApiResponse;
use App\Http\Resources\Api\V1\OfferResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class OfferApiController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected OfferService $offerService,
    ) {}

    /**
     * Buyer: Lihat riwayat penawaran saya.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $offers = Offer::where('user_id', $request->user()->id)
                ->with(['product.images', 'product.user'])
                ->latest()
                ->get();

            return $this->successResponse(
                OfferResource::collection($offers),
                'Offers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve offers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Seller: Lihat daftar penawaran masuk untuk produk seller.
     */
    public function incomingOffers(Request $request): JsonResponse
    {
        try {
            $productIds = $request->user()->products()->pluck('id');

            $offers = Offer::whereIn('product_id', $productIds)
                ->with(['user', 'product'])
                ->latest()
                ->get();

            return $this->successResponse(
                OfferResource::collection($offers),
                'Incoming offers retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve incoming offers: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Buyer mengirim tawaran (dari NegoModal).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'product_id'  => 'required|exists:products,id',
                'offer_price' => 'required|numeric|min:1000',
                'message'     => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors(), 422);
            }

            $product = Product::find($request->product_id);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $offer = $this->offerService->createOffer(
                $request->user(),
                $product,
                (int) $request->offer_price,
                $request->message,
            );

            return $this->successResponse(
                new OfferResource($offer),
                'Tawaran berhasil dikirim!',
                201
            );
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create offer: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Seller menerima tawaran.
     */
    public function accept(Request $request, $id): JsonResponse
    {
        try {
            $offer = Offer::with('product')->find($id);

            if (!$offer) {
                return $this->errorResponse('Offer not found', 404);
            }

            // Pastikan hanya seller pemilik produk yang bisa accept
            if ($offer->product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized to accept this offer', 403);
            }

            $this->offerService->acceptOffer($offer);

            return $this->successResponse(
                new OfferResource($offer),
                'Tawaran diterima! Buyer memiliki 24 jam untuk checkout.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to accept offer: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Seller menolak tawaran.
     */
    public function reject(Request $request, $id): JsonResponse
    {
        try {
            $offer = Offer::with('product')->find($id);

            if (!$offer) {
                return $this->errorResponse('Offer not found', 404);
            }

            // Pastikan hanya seller pemilik produk yang bisa reject
            if ($offer->product->user_id !== $request->user()->id) {
                return $this->errorResponse('Unauthorized to reject this offer', 403);
            }

            $this->offerService->rejectOffer($offer);

            return $this->successResponse(
                new OfferResource($offer),
                'Tawaran ditolak.'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to reject offer: ' . $e->getMessage(), 500);
        }
    }
}
