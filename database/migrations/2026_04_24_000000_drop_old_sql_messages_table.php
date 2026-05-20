<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // WARNING: ONLY RUN THIS AFTER VERIFYING MONGODB MIGRATION IS 100% SUCCESSFUL
        Schema::dropIfExists('messages');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // If restoring rollback is requested but the data was already on mongo,
        // it requires an inverse migration script. We just create empty structure back.
        Schema::create('messages', function ($table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
    }
};
