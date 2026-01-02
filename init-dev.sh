#!/bin/bash

# Development initialization script

echo "Generating Laravel application key..."
php artisan key:generate

echo "Installing npm dependencies..."
npm install

echo "Running database migrations and seeding..."
php artisan migrate:fresh --seed

echo "Development setup complete!"