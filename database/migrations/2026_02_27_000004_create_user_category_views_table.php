<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_category_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('jumlah_view')->default(1);
            $table->timestamps();

            // Satu baris per user per kategori, increment jumlah_view
            $table->unique(['user_id', 'category_id']);
            $table->index('jumlah_view');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_views');
    }
};
