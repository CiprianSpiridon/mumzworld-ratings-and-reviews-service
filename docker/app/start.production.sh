#!/bin/sh

# Function to cleanup processes on exit
cleanup() {
    echo "Stopping processes..."
    kill $(jobs -p)
    exit 0
}

# Trap SIGTERM and SIGINT
trap cleanup SIGTERM SIGINT

# Clear any existing horizon.pid
rm -f storage/logs/horizon.pid

# Run production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Start Horizon in the background with production configuration
php artisan horizon --env=production > storage/logs/horizon.log 2>&1 &

# Start Octane with FrankenPHP in production mode
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=80 --admin-port=2019 --workers=auto --max-requests=500 