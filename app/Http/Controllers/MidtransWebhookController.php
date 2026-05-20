<?php

namespace App\Http\Controllers;

use App\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayInterface $paymentGateway,
    ) {}

    /**
     * Handle Midtrans HTTP notification (webhook).
     * Route: POST /api/midtrans/notification
     * Excluded from CSRF protection.
     */
    public function handle(Request $request)
    {
        try {
            $payload = $request->all();
            $this->paymentGateway->handleNotification($payload);

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}
