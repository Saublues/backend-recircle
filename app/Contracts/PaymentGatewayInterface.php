<?php

namespace App\Contracts;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /**
     * Buat transaksi pembayaran dan dapatkan token/URL.
     *
     * @return array{token: string, redirect_url: string}
     */
    public function createTransaction(Order $order): array;

    /**
     * Handle notifikasi webhook dari payment gateway.
     */
    public function handleNotification(array $payload): void;

    /**
     * Cek status transaksi.
     */
    public function getTransactionStatus(string $orderId): string;
}
