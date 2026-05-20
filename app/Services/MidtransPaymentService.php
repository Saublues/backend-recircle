<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class MidtransPaymentService implements PaymentGatewayInterface
{
    public function __construct()
    {
        $serverKey    = env('MIDTRANS_SERVER_KEY');
        $clientKey    = env('MIDTRANS_CLIENT_KEY');
        $isProduction = env('MIDTRANS_IS_PRODUCTION');
        $isSanitized  = true;
        $is3ds        = true;
    }

    /**
     * Buat transaksi Snap dan dapatkan token.
     */
    public function createTransaction(Order $order): array
    {
        $orderIdUnique = $order->kode_pesanan . '-' . time();

        $params = [
            'transaction_details' => [
                'order_id'     => $orderIdUnique,
                'gross_amount' => $order->total_harga,
            ],
            'customer_details' => [
                'first_name' => $order->user->name,
                'email'      => $order->user->email,
                'phone'      => $order->user->nomor_wa ?? '',
            ],
            'item_details' => [
                [
                    'id'       => $order->product_id,
                    'price'    => $order->total_harga,
                    'quantity' => 1,
                    'name'     => substr($order->product->nama_barang, 0, 50),
                ],
            ],
        ];

        try {
            $snapToken = Snap::getSnapToken($params);
            
            return [
                'token'        => $snapToken,
                'redirect_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$snapToken}",
                'midtrans_order_id' => $orderIdUnique,
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Midtrans Snap Error: ' . $e->getMessage());
            throw new \Exception('Failed to generate payment token.');
        }
    }

    /**
     * Handle notifikasi webhook dari Midtrans.
     */
    public function handleNotification(array $payload): void
    {
        // Verifikasi signature key
        $serverKey    = config('midtrans.server_key');
        $orderId      = $payload['order_id'];
        $statusCode   = $payload['status_code'];
        $grossAmount  = $payload['gross_amount'];
        $signatureKey = $payload['signature_key'];

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        if ($expectedSignature !== $signatureKey) {
            throw new \InvalidArgumentException('Invalid signature key.');
        }

        $order = Order::where('kode_pesanan', $orderId)->firstOrFail();
        $transactionStatus = $payload['transaction_status'];
        $paymentType       = $payload['payment_type'] ?? null;

        /** @var OrderService $orderService */
        $orderService = app(OrderService::class);

        match ($transactionStatus) {
            'capture', 'settlement' => $orderService->handlePaymentSettlement(
                $order,
                $payload['transaction_id'],
                $paymentType
            ),
            'deny', 'cancel', 'expire' => $orderService->handlePaymentFailure($order),
            default => null, // pending, dll — abaikan
        };
    }

    /**
     * Cek status transaksi di Midtrans.
     */
    public function getTransactionStatus(string $orderId): string
    {
        $status = \Midtrans\Transaction::status($orderId);
        return $status->transaction_status ?? 'unknown';
    }
}
