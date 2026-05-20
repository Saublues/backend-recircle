<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSeller
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->isSeller()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Akses hanya untuk penjual terverifikasi.'], 403);
            }
            return redirect()->route('seller.verification.create')
                ->with('error', 'Kamu harus terverifikasi sebagai penjual untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
