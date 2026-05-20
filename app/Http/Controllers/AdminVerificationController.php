<?php

namespace App\Http\Controllers;

use App\Models\SellerVerification;
use App\Services\SellerVerificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminVerificationController extends Controller
{
    public function __construct(
        protected SellerVerificationService $verificationService,
    ) {
    }

    /**
     * Halaman admin: daftar verifikasi pending.
     */
    public function index()
    {
        $verifications = $this->verificationService->getPendingVerifications()
            ->map(fn($v) => [
                'id' => $v->id,
                'nama_kampus' => $v->nama_kampus,
                'nim' => $v->nim,
                'foto_ktm' => asset('storage/' . $v->foto_ktm),
                'status' => $v->status,
                'created_at' => $v->created_at->diffForHumans(),
                'user' => [
                    'name' => $v->user->name,
                    'email' => $v->user->email,
                ],
            ]);

        return Inertia::render('Admin/Verifications', [
            'verifications' => $verifications,
        ]);
    }

    /**
     * Admin approve verifikasi.
     */
    public function approve(SellerVerification $verification)
    {
        $this->verificationService->approveVerification($verification);
        return back()->with('success', "User {$verification->user->name} disetujui sebagai penjual.");
    }

    /**
     * Admin reject verifikasi.
     */
    public function reject(Request $request, SellerVerification $verification)
    {
        $validated = $request->validate([
            'catatan_admin' => 'required|string|max:500',
        ]);

        $this->verificationService->rejectVerification($verification, $validated['catatan_admin']);
        return back()->with('success', 'Verifikasi ditolak.');
    }
}
