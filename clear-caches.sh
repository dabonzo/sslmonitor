#!/bin/bash

# A script to clear all Laravel caches.
# Run this from the root of your Laravel project.

echo "🧹 Clearing all Laravel caches..."

# Clear Application Cache
./vendor/bin/sail artisan cache:clear
echo "Application cache cleared!"

# Clear Route Cache
./vendor/bin/sail artisan route:clear
echo "Route cache cleared!"

# Clear Configuration Cache
./vendor/bin/sail artisan config:clear
echo "Configuration cache cleared!"

# Clear Compiled Views
./vendor/bin/sail artisan view:clear
echo "View cache cleared!"

# Clear Event Cache
./vendor/bin/sail artisan event:clear
echo "Event cache cleared!"

# Clear Compiled Packages and Services
./vendor/bin/sail artisan compiled:clear
echo "Compiled files cleared!"

# Optimize Composer's class autoloader
./vendor/bin/sail composer dump-autoload -o
echo "Composer autoload optimized!"

echo ""
echo "✅ All caches have been cleared successfully!"
