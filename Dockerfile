# Intermediate build container for front-end resources
FROM docker.io/library/node:22.9.0-alpine as frontend
# Easy to prune intermediary containers
LABEL stage=build

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npm run build

####################################################################################################
# Primary container
FROM docker.io/library/php:8.3.11-apache-bookworm

# Default container port for the apache configuration
EXPOSE 80 443

# Install various dependencies
# - git and unzip for composer
# - vim and nano for our egos
# - ca-certificates for OAuth2
RUN apt-get update && \
    apt-get install -y git unzip vim nano ca-certificates && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite ssl


# Custom Apache2 configuration based on defaults; fairly straightforward
COPY ./container/configs/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ./container/configs/apache.conf /etc/apache2/apache2.conf
# Custom PHP configuration based on $PHP_INI_DIR/php.ini-production
COPY ./container/configs/php.ini /usr/local/etc/php/php.ini


# Install PHP extension(s)
COPY --from=mlocati/php-extension-installer:2.5.0 /usr/bin/install-php-extensions /usr/local/bin/
# These are the extensions we depend on:
# $ composer check -f json 2>/dev/null | jq '.[] | select(.name | startswith("ext-")) | .name | sub("ext-"; "")' -r
# Currently, this seems to only be pdo_mysql.
RUN install-php-extensions pdo_mysql

# Install composer
COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer
# Copy over the application, static files, plus the ones built/transpiled by Mix in the frontend stage further up
COPY --chown=www-data:www-data ./ /app/
COPY --from=frontend --chown=www-data:www-data /app/public/ /app/public/

WORKDIR /app

RUN chmod -R 755 storage bootstrap/cache && \
        composer install --no-dev --no-interaction --prefer-dist && \
        mkdir -p /app/storage/app/public/files

# Wrap around the default PHP entrypoint with a custom entrypoint
COPY ./container/entrypoint.sh /usr/local/bin/controlcenter-entrypoint
ENTRYPOINT [ "controlcenter-entrypoint" ]
CMD ["apache2-foreground"]
