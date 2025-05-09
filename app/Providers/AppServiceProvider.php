<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 * 
 * The main service provider for the application.
 * Used for registering services and bootstrapping application-level functionality.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * This method is used to bind services into the service container.
     * It is called before the 'boot' method.
     *
     * @return void
     */
    public function register(): void
    {
        // Register the RouteServiceProvider to handle application routes.
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * This method is called after all other service providers have been registered,
     * meaning you have access to all other services that have been registered by the framework.
     * Used for event listeners, observers, route model binding, etc.
     *
     * @return void
     */
    public function boot(): void
    {
        // No application-specific bootstrapping logic is currently defined here.
        // This is a common place to register observers, event listeners, or other boot-time configurations.
    }
}
