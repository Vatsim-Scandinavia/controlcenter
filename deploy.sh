# ! /bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Easy deploy script for manual deployment
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

COMMAND=$1

# Run the supported PHP versions or work backwards to the one we find. Useful in environments with more versions installed
function run_php () {
    if [ -e "/usr/bin/php8.1" ]; then
        /usr/bin/php8.1 $@
    elif [ -e "/usr/local/bin/php8.1" ]; then
        /usr/local/bin/php8.0 $@
    elif [ -e "/usr/bin/php8.0" ]; then
        /usr/bin/php8.1 $@
    elif [ -e "/usr/local/bin/php8.0" ]; then
        /usr/local/bin/php8.0 $@
    else
        php $@
    fi
}

# Print out the version for reference in console
run_php -v

# Turn maintenance mode on, unless it's the initial run
if [ "$COMMAND" != "init" ]; then 
    run_php artisan down --render="errors.maintenance"
fi

# Pull latest from Git
git pull

# Install dependecies
if [ "$COMMAND" = "dev" ]; then 
    composer install
else
    composer install -q --no-dev --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist
fi

composer dump-autoload

if [ "$COMMAND" = "dev" ]; then 
    # Install all dependecies
    npm install
else
    #Install without dev dependecies
    npm ci --production
fi

# Adjust directory permissions
chmod -R 777 storage bootstrap/cache

# Artisan magic
run_php artisan migrate

# Clear All Cache
run_php artisan optimize:clear

if [ "$COMMAND" = "dev" ]; then

    # Create front-end assets
    npm run dev

elif [ "$COMMAND" = "init" ]; then

    # Generate PHP key
    run_php artisan key:generate

    # Create front-end assets
    npm run dev

else

    # Create front-end assets
    npm run prod

fi

# Turn maintenance mode off
if [ "$COMMAND" != "init" ]; then 
    run_php artisan up
fi

 
