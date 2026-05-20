<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userId = 76;
$latestMessages = App\Models\ChatMessage::raw(function ($collection) use ($userId) {
    return $collection->aggregate([
        ['$match' => ['$or' => [['sender_id' => $userId], ['receiver_id' => $userId]]]],
        ['$sort' => ['created_at' => -1]],
        ['$group' => [
            '_id' => '$room_id',
            'original' => ['$first' => '$$ROOT']
        ]],
        ['$replaceRoot' => ['newRoot' => '$original']],
        ['$sort' => ['created_at' => -1]]
    ]);
});

$messages = iterator_to_array($latestMessages);
echo "Original Class: " . get_class($messages[0]) . "\n";
echo "Sender ID: " . $messages[0]->sender_id . "\n";

$hydrated = App\Models\ChatMessage::hydrate($messages);
echo "Hydrated Sender ID: " . var_export($hydrated[0]->sender_id, true) . "\n";
