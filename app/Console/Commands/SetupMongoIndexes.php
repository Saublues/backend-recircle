<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ChatMessage;

class SetupMongoIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mongo:setup-indexes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create performance indexes on MongoDB collections (Compound Index)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up MongoDB indexes...');

        try {
            $collection = ChatMessage::raw(function($collection) {
                // Create Compound Index for ChatMessage: room_id (1) and created_at (-1) for quick history access
                return $collection->createIndex(
                    ['room_id' => 1, 'created_at' => -1],
                    ['background' => true, 'name' => 'room_created_at_index']
                );
            });
            $this->info('ChatMessage compound index (room_id, created_at) created successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to create index: ' . $e->getMessage());
        }

        $this->info('Done!');
    }
}
