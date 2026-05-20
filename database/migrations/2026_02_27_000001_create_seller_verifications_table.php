<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('seller_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('foto_ktm');               // path to uploaded KTM image
            $table->string('nama_kampus');
            $table->string('nim');                     // student ID number
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_admin')->nullable(); // admin notes on rejection
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_verifications');
    }
};
