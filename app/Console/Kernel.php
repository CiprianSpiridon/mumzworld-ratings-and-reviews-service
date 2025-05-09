<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Class Kernel
 * 
 * The application's console kernel.
 * This class is responsible for registering custom Artisan commands
 * and defining the command schedule for recurring tasks.
 */
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * These commands are automatically registered with Artisan.
     *
     * @var array<int, string>
     */
    protected $commands = [
        \App\Console\Commands\DynamoDbMigrate::class,
        \App\Console\Commands\TranslateReviewsCommand::class,
        \App\Console\Commands\InvalidateCloudFrontCache::class,
        \App\Console\Commands\BackfillReviewStatisticsCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * This method is called by Laravel to define scheduled tasks that should run automatically.
     * 
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();

        // Daily translation of published reviews (max 500)
        // Prevents overlapping executions
        $schedule->command('reviews:translate --limit=500 --status=published')
            ->daily()
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * This method is called to load command files.
     * It loads commands from the app/Console/Commands directory and from routes/console.php.
     *
     * @return void
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
