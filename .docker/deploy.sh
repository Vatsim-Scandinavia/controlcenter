# ! /bin/sh
# deploy.sh
#
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
# Easy deploy script for deploying inside docker container
# = = = = = = = = = = = = = = = = = = = = = = = = = = = =
#

ENV=$1

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
if [ "$ENV" != "init" ]; then 
    run_php artisan down --render="errors.maintenance"
fi

# Pull latest updates from Git
git pull origin master

# Install dependecies
if [ "$ENV" = "dev" ]; then 
    run_php /usr/local/bin/composer install
else
    run_php /usr/local/bin/composer install -q --no-dev --no-ansi --no-interaction --no-scripts --no-suggest --no-progress --prefer-dist
fi

run_php /usr/local/bin/composer dump-autoload

if [ "$ENV" = "dev" ]; then 
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

if [ "$ENV" = "dev" ]; then

    # Create front-end assets
    npm run dev

elif [ "$ENV" = "init" ]; then

    # Generate PHP key
    run_php artisan key:generate

    # Create front-end assets
    npm run dev

else

    # Create front-end assets
    npm run prod

fi

# Turn maintenance mode off
if [ "$ENV" != "init" ]; then 
    run_php artisan up
fi

 
