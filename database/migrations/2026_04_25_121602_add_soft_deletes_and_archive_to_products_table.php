<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->softDeletes();
            $table->boolean('is_archived')->default(false);

            // Compound index for performance
            $table->index(['user_id', 'deleted_at', 'is_archived']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'deleted_at', 'is_archived']);
            $table->dropColumn('is_archived');
            $table->dropSoftDeletes();
        });
    }
};
