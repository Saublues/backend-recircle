<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\ChatMessage;

class MigrateChatToMongo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:chat-to-mongo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate chat messages from PostgreSQL to MongoDB chunk by chunk (Zero Downtime)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of Chat Messages from SQL to MongoDB...');

        // Verify if tables exists in SQL
        if (!DB::getSchemaBuilder()->hasTable('messages')) {
            $this->error('The SQL table `messages` does not exist. Nothing to migrate.');
            return;
        }

        $totalRecords = DB::table('messages')->count();
        $this->info("Found {$totalRecords} records to migrate.");

        $migratedCount = 0;

        // Process in chunks of 500 to avoid memory limit
        DB::table('messages')->orderBy('id')->chunk(500, function ($messages) use (&$migratedCount, $totalRecords) {
            $mongoData = [];

            foreach ($messages as $msg) {
                // Determine room ID
                $roomId = ChatMessage::generateRoomId((int)$msg->sender_id, (int)$msg->receiver_id);

                $mongoData[] = [
                    'room_id' => $roomId,
                    'sender_id' => (int)$msg->sender_id,
                    'receiver_id' => (int)$msg->receiver_id,
                    'product_id' => $msg->product_id ? (int)$msg->product_id : null,
                    'message' => $msg->message,
                    'is_read' => (bool)$msg->is_read,
                    'created_at' => $msg->created_at,
                    'updated_at' => $msg->updated_at,
                ];
            }

            // Bulk insert into MongoDB collection for extreme speed
            ChatMessage::insert($mongoData);
            
            $migratedCount += count($messages);
            $this->info("Migrated {$migratedCount} / {$totalRecords} records...");
        });

        $this->info('Migration completed successfully!');
    }
}
