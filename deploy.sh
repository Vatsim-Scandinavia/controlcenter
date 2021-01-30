# ! /bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Easy deploy script for manual deployment
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

COMMAND=$1

# Turn maintenance mode on
php artisan down

# Pull latest from Git
git pull

# Create  env if it doesn't work
php -r "file_exists('.env') || copy('.env.example', '.env');"

# Install dependecies
composer install -q --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist
npm install

# Adjust directory permissions
chmod -R 775 storage bootstrap/cache

# Artisan magic
php artisan migrate
php artisan cache:clear
php artisan config:clear
php artisan view:cache

if [ "$COMMAND" = "dev" ]; then

    # Create front-end assets
    npm run dev

elif [ "$COMMAND" = "init" ]; then

    # Generate PHP key
    php artisan key:generate

else

    # Create front-end assets
    npm run prod

fi

# Turn maintenance mode off
php artisan up

 