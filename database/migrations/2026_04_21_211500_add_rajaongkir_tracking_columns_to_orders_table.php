<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Jika ada references kolom biteship, hapus
            if (Schema::hasColumn('orders', 'biteship_order_id')) {
                $table->dropColumn('biteship_order_id');
            }
            if (Schema::hasColumn('orders', 'waybill_id')) {
                $table->dropColumn('waybill_id');
            }

            // Tambah Tracking Columns Baru
            $table->string('courier_service')->nullable()->after('metode_pengiriman');
            $table->unsignedBigInteger('shipping_cost')->default(0)->after('courier_service');
            $table->string('rajaongkir_tracking_id')->nullable()->after('shipping_cost');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courier_service', 'shipping_cost', 'rajaongkir_tracking_id']);
        });
    }
};
