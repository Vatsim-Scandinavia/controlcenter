#!/bin/bash
set -e

echo "🚀 Setting up Control Center development environment..."

# Wait for database to be ready
echo "⏳ Waiting for database to be ready..."
until mysqladmin ping -h mysql -u ${DB_USERNAME} -p${DB_PASSWORD} --silent > /dev/null 2>&1; do
    echo "Database not ready yet, waiting 2 seconds..."
    sleep 2
done

# Check if this is the first run by looking for setup marker
SETUP_MARKER="/var/www/html/storage/.container-setup-complete"
FIRST_RUN=false

if [ ! -f "$SETUP_MARKER" ]; then
    FIRST_RUN=true
    echo "� First run detected, performing initial setup..."
fi

# Install Composer dependencies if vendor directory doesn't exist or first run
if [ ! -d "vendor" ] || [ "$FIRST_RUN" = true ]; then
    echo "�📦 Installing Composer dependencies..."
    composer install
fi

# Install NPM dependencies if node_modules doesn't exist or first run
if [ ! -d "node_modules" ] || [ "$FIRST_RUN" = true ]; then
    echo "📦 Installing NPM dependencies..."
    npm install
fi

# Build assets if public/build doesn't exist or first run
if [ ! -d "public/build" ] || [ "$FIRST_RUN" = true ]; then
    echo "🎨 Building assets..."
    npm run build
fi

# Generate application key if .env doesn't have one or first run
if [ "$FIRST_RUN" = true ] || ! grep -q "APP_KEY=base64:" .env 2>/dev/null; then
    echo "🔑 Generating application key..."
    php artisan key:generate
fi

# Always run migrations (they're safe to run multiple times)
echo "🗄️ Running database migrations..."
php artisan migrate

# Only seed on first run
if [ "$FIRST_RUN" = true ]; then
    echo "🌱 Seeding database..."
    php artisan db:seed
    
    # Create setup marker to indicate setup is complete
    touch "$SETUP_MARKER"
    echo "✅ Initial setup complete!"
else
    echo "♻️ Container already set up, skipping initial setup steps"
fi

echo "🚀 Starting Laravel development server..."

# Start the Laravel development server
exec php artisan serve --host=0.0.0.0 --port=80