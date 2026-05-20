<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Console\Command;

class ReleaseEscrow extends Command
{
    protected $signature = 'escrow:release';
    protected $description = 'Auto-release escrow untuk order yang sudah dikirim lebih dari 3 hari (anti-ghosting)';

    public function handle(OrderService $orderService): int
    {
        $orders = Order::where('status', 'dikirim')
            ->where('updated_at', '<', now()->subDays(3))
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $orderService->confirmReceipt($order);
            $count++;
        }

        $this->info("Released escrow for {$count} orders.");

        return Command::SUCCESS;
    }
}
