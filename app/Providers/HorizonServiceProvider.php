<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

/**
 * Class HorizonServiceProvider
 * 
 * Configures Laravel Horizon, the queue monitoring dashboard.
 * This provider handles authorization for accessing the Horizon dashboard.
 */
class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     * 
     * This method is called during the booting phase of the application.
     * It can be used to configure Horizon features like custom metrics or notification channels.
     */
    public function boot(): void
    {
        parent::boot(); // Call parent boot method for Horizon's default bootstrapping

        // Horizon::routeSmsNotificationsTo('15556667777');
        // Horizon::routeMailNotificationsTo('example@example.com');
        // Horizon::routeSlackNotificationsTo('slack-webhook-url', '#channel');
        //
        // Examples of how to route Horizon event notifications.
        // Uncomment and configure these if you want Horizon to send notifications
        // for events like long waits, job failures, etc.
    }

    /**
     * Register the Horizon gate.
     *
     * This gate determines who can access the Horizon dashboard in non-local environments.
     * By default, Horizon is accessible by anyone in local environments.
     * In other environments, this gate must return true for authorized users.
     */
    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user = null) { // Allow nullable $user for unauthenticated access if Gate is hit before auth middleware
            // Define users who are authorized to view the Horizon dashboard.
            // Example: return in_array($user->email, ['admin@example.com']);
            // Currently, no specific users are defined, meaning access might be restricted
            // in non-local environments unless this logic is updated.
            // For local environment, access is typically open by default.
            return in_array($user->email ?? '', [
                // 'your-authorized-email@example.com',
            ]);
        });
    }
}
