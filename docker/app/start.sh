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

# Start Horizon in the background
php artisan horizon > storage/logs/horizon.log 2>&1 &

# Start Octane with FrankenPHP
exec php artisan octane:start --server=frankenphp --host=0.0.0.0 --port=80 --admin-port=2019 