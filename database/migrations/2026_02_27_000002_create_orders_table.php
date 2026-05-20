<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();       // Pembeli
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete(); // Opsional, dari negosiasi
            $table->bigInteger('total_harga');
            $table->enum('metode_pengiriman', ['cod', 'kirim_paket']);
            $table->enum('status', [
                'pending',      // menunggu pembayaran / konfirmasi
                'dibayar',      // sudah bayar (Midtrans settlement)
                'dikirim',      // penjual sudah kirim
                'selesai',      // pembeli konfirmasi terima
                'dibatalkan',   // dibatalkan / expired
            ])->default('pending');
            $table->text('alamat_pengiriman')->nullable();
            $table->text('catatan')->nullable();

            // Midtrans Payment Fields
            $table->string('midtrans_transaction_id')->nullable();
            $table->string('midtrans_snap_token')->nullable();
            $table->string('metode_pembayaran')->nullable(); // e.g. 'gopay', 'bca_va'

            // Escrow Fields
            $table->boolean('dana_ditahan')->default(false);
            $table->timestamp('dana_dilepas_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
