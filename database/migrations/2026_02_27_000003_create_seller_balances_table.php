<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seller_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();  // Penjual
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('jumlah');              // Nominal yang ditahan/dilepas
            $table->enum('status', [
                'ditahan',   // dana masih di-escrow
                'dilepas',   // buyer konfirmasi terima, dana dilepas
                'ditarik',   // penjual sudah withdraw/cairkan
            ])->default('ditahan');
            $table->string('catatan')->nullable();
            $table->timestamp('dilepas_at')->nullable();
            $table->timestamp('ditarik_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_balances');
    }
};
