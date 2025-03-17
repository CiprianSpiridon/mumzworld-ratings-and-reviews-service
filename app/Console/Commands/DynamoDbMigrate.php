<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DynamoDbMigrate extends Command
{
    protected $signature = 'dynamodb:migrate 
                            {--fresh : Drop all tables before migrating}
                            {--file= : Run a specific migration file}';
    protected $description = 'Run DynamoDB migrations';

    public function handle()
    {
        $path = database_path('migrations');
        $specificFile = $this->option('file');

        if ($specificFile) {
            // Run only the specified migration file
            $filePath = $path . '/' . $specificFile;

            if (!File::exists($filePath)) {
                $this->error("Migration file not found: {$specificFile}");
                return 1;
            }

            $this->runMigration($filePath);
            $this->info("Migration of {$specificFile} completed successfully.");
            return 0;
        }

        // Run all migrations
        $files = File::glob($path . '/*_*.php');
        sort($files);

        foreach ($files as $file) {
            $this->runMigration($file);
        }

        $this->info('DynamoDB migration completed successfully.');
        return 0;
    }

    /**
     * Run a single migration file
     *
     * @param string $file
     * @return void
     */
    protected function runMigration($file)
    {
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
}
