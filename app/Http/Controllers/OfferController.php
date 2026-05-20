<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Product;
use App\Services\OfferService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OfferController extends Controller
{
    public function __construct(
        protected OfferService $offerService,
    ) {}

    /**
     * Buyer mengirim tawaran (dari NegoModal).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'  => 'required|exists:products,id',
            'offer_price' => 'required|numeric|min:1000',
            'message'     => 'nullable|string|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        try {
            $this->offerService->createOffer(
                $request->user(),
                $product,
                (int) $validated['offer_price'],
                $validated['message'] ?? null,
            );

            return back()->with('success', 'Tawaran berhasil dikirim!');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['offer' => $e->getMessage()]);
        }
    }

    /**
     * Seller menerima tawaran.
     */
    public function accept(Offer $offer)
    {
        // Pastikan hanya seller pemilik produk yang bisa accept
        if ($offer->product->user_id !== auth()->id()) {
            abort(403);
        }

        $this->offerService->acceptOffer($offer);
        return back()->with('success', 'Tawaran diterima! Buyer memiliki 24 jam untuk checkout.');
    }

    /**
     * Seller menolak tawaran.
     */
    public function reject(Offer $offer)
    {
        if ($offer->product->user_id !== auth()->id()) {
            abort(403);
        }

        $this->offerService->rejectOffer($offer);
        return back()->with('success', 'Tawaran ditolak.');
    }

    /**
     * Buyer: Lihat riwayat penawaran saya.
     */
    public function myOffers(Request $request)
    {
        $offers = Offer::where('user_id', $request->user()->id)
            ->with(['product.images', 'product.user'])
            ->latest()
            ->get()
            ->map(fn ($offer) => [
                'id'          => $offer->id,
                'harga_tawar' => $offer->harga_tawar,
                'harga_deal'  => $offer->harga_deal,
                'pesan'       => $offer->pesan,
                'status'      => $offer->status,
                'expired_at'  => $offer->deal_expired_at?->toISOString(),
                'is_expired'  => $offer->isDealExpired(),
                'created_at'  => $offer->created_at->diffForHumans(),
                'product'     => [
                    'id'    => $offer->product->id,
                    'name'  => $offer->product->nama_barang,
                    'price' => $offer->product->harga,
                    'img'   => $offer->product->foto_barang_url,
                    'seller_name' => $offer->product->user->name,
                ],
            ]);

        return Inertia::render('Offers/Index', [
            'offers' => $offers,
        ]);
    }
}
