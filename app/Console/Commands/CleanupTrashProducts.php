<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Carbon\Carbon;

class CleanupTrashProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:cleanup-trash';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete soft-deleted products older than 30 days that have no transactions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $threshold = Carbon::now()->subDays(30);

        $products = Product::onlyTrashed()
            ->where('deleted_at', '<', $threshold)
            ->get();

        $deletedCount = 0;
        $skippedCount = 0;

        foreach ($products as $product) {
            if ($product->orders()->exists()) {
                // Biarkan sebagai historis
                $skippedCount++;
            } else {
                $product->forceDelete();
                $deletedCount++;
            }
        }

        $this->info("Cleanup completed. Deleted: {$deletedCount}, Skipped (has orders): {$skippedCount}");
    }
}
