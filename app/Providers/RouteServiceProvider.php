<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

/**
 * Class RouteServiceProvider
 * 
 * This service provider is responsible for loading the application's route files
 * and configuring route model bindings, pattern filters, rate limiters, etc.
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication if no specific redirect is set.
     * This constant is often used by Laravel's authentication scaffolding.
     *
     * @var string
     */
    public const HOME = '/home'; // Default home path, can be customized or removed if not used.

    /**
     * Define your route model bindings, pattern filters, rate limiters, and other route-specific configurations.
     *
     * This method is called during the booting phase of the application.
     */
    public function boot(): void
    {
        // Configure the global API rate limiter.
        // This applies to routes within the 'api' middleware group.
        RateLimiter::for('api', function (Request $request) {
            // Limits requests to 60 per minute, identified by the user's IP address.
            // Adjust the limit or identification method (e.g., by user ID if authenticated) as needed.
            return Limit::perMinute(60)->by($request->ip());
        });

        // Define how the application's route files are loaded.
        $this->routes(function () {
            // Load API routes from routes/api.php
            // These routes are typically stateless, prefixed with '/api', and use the 'api' middleware group.
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Load web routes from routes/web.php
            // These routes typically maintain session state and use the 'web' middleware group.
            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
