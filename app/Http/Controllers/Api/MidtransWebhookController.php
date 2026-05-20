<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MidtransWebhookController extends Controller
{
    /**
     * Handle incoming Midtrans Webhook / HTTP Notification
     */
    public function __invoke(Request $request, OrderService $orderService)
    {
        try {
            $payload = $request->all();
            
            Log::info('Midtrans Webhook Received:', $payload);

            // Robust check: Pastikan semua field kritikal yang dibutuhkan verifikasi ada di payload
            $requiredFields = ['order_id', 'status_code', 'gross_amount', 'signature_key', 'transaction_status'];
            foreach ($requiredFields as $field) {
                if (!isset($payload[$field])) {
                    Log::warning('Midtrans Webhook: Missing required field ' . $field, ['payload' => $payload]);
                    return response()->json(['message' => 'Required field missing: ' . $field], 400);
                }
            }

            $orderIdSent  = $payload['order_id'];
            $statusCode   = $payload['status_code'];
            $grossAmount  = $payload['gross_amount'];
            $signatureKey = $payload['signature_key'];

            // Verifikasi signature key
            $serverKey    = config('midtrans.server_key');
            
            if (!$serverKey) {
                Log::error('Midtrans Webhook Config Error: server_key not configured in config/midtrans.php');
                return response()->json(['message' => 'Server configuration error'], 500);
            }

            $expectedSignature = hash('sha512', $orderIdSent . $statusCode . $grossAmount . $serverKey);

            if ($expectedSignature !== $signatureKey) {
                Log::warning('Midtrans Webhook: Invalid Signature', ['expected' => $expectedSignature, 'actual' => $signatureKey]);
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            // 1. Handle Case: Dashboard Settings "Test" button hits
            // Midtrans sends dynamic test ids like "payment_notif_test_XXXX" which never exist in our DB.
            if (str_contains($orderIdSent, 'payment_notif_test')) {
                Log::info('Midtrans Webhook: Received verification test hit. Responding OK.');
                return response()->json(['message' => 'Connection valid, test accepted'], 200);
            }

            // 2. PRIMARY LOOKUP: Direct match
            $order = Order::where('kode_pesanan', $orderIdSent)->first();
            
            // 3. ROBUST FALLBACK: If there are any prefix/suffixes appended mistakenly by routing
            if (!$order) {
                Log::warning('Midtrans Webhook: Exact ID not found, attempting loose extraction search', ['order_id' => $orderIdSent]);
                // Attempt to match based on pattern if passed aggregated
                $order = Order::where('kode_pesanan', 'LIKE', '%' . $orderIdSent . '%')->first();
            }

            if (!$order) {
                Log::error('Midtrans Webhook: Order Lookup Failed completely', ['order_id' => $orderIdSent]);
                return response()->json(['message' => 'Order object could not be resolved in local dataset'], 404);
            }

            $transactionStatus = $payload['transaction_status'];
            $fraudStatus       = $payload['fraud_status'] ?? null;
            $transactionId     = $payload['transaction_id'] ?? '';
            $paymentType       = $payload['payment_type'] ?? 'midtrans';
 
            if ($transactionStatus == 'capture') {
                if ($fraudStatus == 'accept') {
                    // Leverage encapsulated OrderService flow
                    $orderService->handlePaymentSettlement($order, $transactionId, $paymentType);
                }
            } else if ($transactionStatus == 'settlement') {
                // Successful payment confirmation
                $orderService->handlePaymentSettlement($order, $transactionId, $paymentType);
            } else if ($transactionStatus == 'cancel' || $transactionStatus == 'deny' || $transactionStatus == 'expire') {
                // Triggers automated product restocking & escrow deletion
                $orderService->handlePaymentFailure($order);
            } else if ($transactionStatus == 'pending') {
                $order->update(['status' => 'pending']);
            }

            return response()->json(['message' => 'OK']);
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error: ' . $e->getMessage());
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}
