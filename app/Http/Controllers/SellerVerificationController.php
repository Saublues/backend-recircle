<?php

namespace App\Http\Controllers;

use App\Services\SellerVerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SellerVerificationController extends Controller
{
    public function __construct(
        protected SellerVerificationService $verificationService,
    ) {}

    /**
     * Form pengajuan verifikasi seller.
     */
    public function create(Request $request)
    {
        $latestVerification = $request->user()->latestVerification;

        return Inertia::render('Seller/Verification', [
            'latestVerification' => $latestVerification ? [
                'status'        => $latestVerification->status,
                'catatan_admin' => $latestVerification->catatan_admin,
                'created_at'    => $latestVerification->created_at->diffForHumans(),
            ] : null,
            'is_seller' => $request->user()->is_seller,
        ]);
    }

    /**
     * Submit verifikasi.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'foto_ktm'     => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'nama_kampus'  => 'required|string|max:255',
            'nim'          => 'required|string|max:20',
        ]);

        try {
            $this->verificationService->submitVerification(
                $request->user(),
                $request->file('foto_ktm'),
                $validated['nama_kampus'],
                $validated['nim'],
            );

            return back()->with('success', 'Pengajuan verifikasi berhasil dikirim! Menunggu review admin.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['verification' => $e->getMessage()]);
        }
    }
}
