  #!/bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Easy deploy script for manual deployment
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

COMMAND=$1

# Pull latest from Git
git pull

# Create  env if it doesn't work
php -r "file_exists('.env') || copy('.env.example', '.env');"

# Install dependecies
composer install -q --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist
npm install

# Generate PHP key
php artisan key:generate

# Adjust directory permissions
chmod -R 777 storage bootstrap/cache
chmod -R 777 storage bootstrap/cache

# Artisan magic
php artisan migrate
php artisan cache:clear
php artisan config:clear

# Create front-end assets

if [ "$COMMAND" = "dev" ]; then
    npm run dev
else
    npm run prod
fi