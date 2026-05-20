<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Escrow auto-release: jalan setiap hari, lepas dana untuk order 'dikirim' > 3 hari
Schedule::command('escrow:release')->daily();

// Hapus otomatis produk yang di soft-delete > 30 hari
Schedule::command('products:cleanup-trash')->daily();
