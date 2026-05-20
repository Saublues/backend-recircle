<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();

            // Data Barang (Bahasa Indonesia)
            $table->string('nama_barang');
            $table->bigInteger('harga');
            $table->string('kondisi');         // Baru / Bekas
            $table->text('deskripsi');
            $table->string('lokasi_kampus');

            // Pengecualian (Tetap Inggris)
            $table->string('image');
            $table->enum('status', ['active', 'sold', 'archived'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};