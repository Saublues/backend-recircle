<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan kita memiliki minimal 2 user
        $buyer = \App\Models\User::firstOrCreate(
            ['email' => 'buyer@example.com'],
            ['name' => 'Buyer Name', 'password' => bcrypt('password')]
        );
        $seller = \App\Models\User::firstOrCreate(
            ['email' => 'seller@example.com'],
            ['name' => 'Seller Name', 'password' => bcrypt('password'), 'universitas' => 'Institut Pertanian Bogor']
        );

        // Buat 2 produk untuk seller
        $product1 = \App\Models\Product::firstOrCreate(
            ['nama_barang' => 'Magic Com Miyako Ungu Series 1726'],
            ['user_id' => $seller->id, 'harga' => 75000, 'deskripsi' => 'Kondisi bagus', 'kategori_id' => 1]
        );
        $product2 = \App\Models\Product::firstOrCreate(
            ['nama_barang' => 'Kulkas Mini Hisense Silver Series 189x'],
            ['user_id' => $seller->id, 'harga' => 850000, 'deskripsi' => 'Dingin mantap', 'kategori_id' => 1]
        );

        // Item 1: Normal item (no offer)
        \App\Models\Cart::firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product2->id,
            'offer_id' => null,
        ]);

        // Item 2: Item with Offer (negotiated_price, expires_at 24h)
        $offer = \App\Models\Offer::firstOrCreate(
            ['user_id' => $buyer->id, 'product_id' => $product1->id],
            [
                'harga_tawar' => 60000,
                'harga_deal' => 50000,
                'status' => 'accepted',
                'pesan' => 'Bisa kurang ngga kak?',
                'deal_expired_at' => now()->addHours(24),
            ]
        );

        \App\Models\Cart::firstOrCreate([
            'user_id' => $buyer->id,
            'product_id' => $product1->id,
            'offer_id' => $offer->id,
        ]);
    }
}
