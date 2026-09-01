#!/bin/sh

echo "Running database migrations..."

php artisan migrate:fresh --seed --force

echo "Starting Apache..."

apache2-foreground