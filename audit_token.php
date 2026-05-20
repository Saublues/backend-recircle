<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::first();
$t = $u->createToken('audit')->plainTextToken;
echo "TOKEN:" . $t . "\n";
echo "CATEGORY_ID:" . \App\Models\Category::first()?->id . "\n";
