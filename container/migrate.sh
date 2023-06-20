#!/bin/sh
# This is a manual script for entering maintenance mode and
# migrating the database during an upgrade of Control Center.
#
# WARNING:
# This script does not perform any validation steps or
# otherwise ensures that it is doing the right thing at the
# right time.

# Turn maintenance mode on, unless it's the initial run
php artisan down --render="errors.maintenance"

# Artisan magic
php artisan migrate

# Clear all of the cache
php artisan optimize:clear

# Turn maintenance mode off
php artisan up
