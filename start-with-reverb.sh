#!/bin/bash

# Function to handle cleanup on exit
cleanup() {
    echo "Shutting down services..."
    kill $(jobs -p) 2>/dev/null
    exit
}

# Set up signal handlers
trap cleanup SIGTERM SIGINT

echo "Starting SSL Monitor services..."

# Start Reverb WebSocket server in background
echo "Starting Reverb WebSocket server..."
# Use environment-specific host binding
if [ "$APP_ENV" = "production" ]; then
    # Production: Bind to localhost only (behind reverse proxy)
    php artisan reverb:start --host=127.0.0.1 --port=8080 &
else
    # Development: Bind to all interfaces for Docker networking
    php artisan reverb:start --host=0.0.0.0 --port=8080 &
fi

# Start Horizon queue worker in background
echo "Starting Horizon queue worker..."
php artisan horizon &

# Start Laravel scheduler in background
echo "Starting Laravel scheduler..."
php artisan schedule:work &

# Wait a moment for services to initialize
sleep 2

# Start the main Laravel application (this keeps the container running)
echo "Starting Laravel web application..."
exec "$@"