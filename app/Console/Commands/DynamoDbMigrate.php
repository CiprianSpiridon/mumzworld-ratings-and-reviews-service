<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DynamoDbMigrate extends Command
{
    protected $signature = 'dynamodb:migrate {--fresh : Drop all tables before migrating}';
    protected $description = 'Run DynamoDB migrations';

    public function handle()
    {
        $path = database_path('migrations');
        $files = File::glob($path . '/*_*.php');
        sort($files);

        foreach ($files as $file) {
            $migration = require $file;

            if (method_exists($migration, 'up')) {
                if ($this->option('fresh')) {
                    $this->info("Rolling back: " . basename($file));
                    $migration->down();
                }

                $this->info("Migrating: " . basename($file));
                $migration->up();
            }
        }

        $this->info('DynamoDB migration completed successfully.');
    }
}
