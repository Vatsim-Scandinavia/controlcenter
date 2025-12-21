#!/usr/bin/env sh

# Laravel Sail command-line tool for project root
# This script allows using Sail without requiring PHP/Composer to be installed locally

# Check if vendor directory exists
if [ ! -d "./vendor" ]; then
    echo "Vendor directory not found. Running composer install using Docker..."
    
    # Create a temporary docker-compose file for composer install
    cat > docker-compose.tmp.yml << 'EOF'
services:
  composer:
    image: composer:latest
    volumes:
      - ./:/app
    working_dir: /app
    command: install --ignore-platform-reqs
EOF
    
    docker compose -f docker-compose.tmp.yml run --rm composer
    rm docker-compose.tmp.yml
    
    # Check if vendor was created
    if [ ! -d "./vendor" ]; then
        echo "Failed to install dependencies. Please check your composer.json file."
        exit 1
    fi
fi

# Now run the actual sail command
if [ -f "./vendor/bin/sail" ]; then
    exec "./vendor/bin/sail" "$@"
else
    echo "Laravel Sail not found. Please ensure laravel/sail is in your composer.json"
    exit 1
fi
