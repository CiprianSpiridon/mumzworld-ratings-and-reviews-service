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

        // Schedule the reviews:translate command to run daily.
        // It attempts to translate up to 500 published reviews that need translation.
        // withoutOverlapping() ensures that a new instance of the command doesn't start if the previous one is still running.
        $schedule->command('reviews:translate --limit=500 --status=published')
            ->daily() // Runs once per day (typically at midnight server time)
            ->withoutOverlapping(); // Prevents job overlap
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
