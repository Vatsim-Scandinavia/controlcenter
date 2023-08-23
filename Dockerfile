# Intermediate build container for front-end resources
FROM docker.io/library/node:20.5.1-alpine@sha256:f62abc08fe1004555c4f28b6793af8345a76230b21d2d249976f329079e2fef2 as frontend
# Easy to prune intermediary containers
LABEL stage=build

WORKDIR /app
COPY ./ /app/

RUN npm ci --omit dev && \
    npm run prod

####################################################################################################
# Primary container
FROM docker.io/library/php:8.1.10-apache-bullseye@sha256:37f1f4545f9f8845f5d6dbc53d3f36a72d745d14ea3c96be8936c575b0f720e8

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
COPY --from=mlocati/php-extension-installer:2.1.38@sha256:c3a2b786a0ed48919fea7af99bbc2732e50a787ea1eb31efe20df08f33c33220 /usr/bin/install-php-extensions /usr/local/bin/
# These are the extensions we depend on:
# $ composer check -f json 2>/dev/null | jq '.[] | select(.name | startswith("ext-")) | .name | sub("ext-"; "")' -r
# Currently, this seems to only be pdo_mysql.
# TODO: Support additional PDOs on demand; consider SQLite & PgSQL
RUN install-php-extensions pdo_mysql

# Install composer
COPY --from=docker.io/library/composer:latest@sha256:7c03aa544494973299998db9e51f3c4ca880f2d51475b78a4bca4214a8a4fe82 /usr/bin/composer /usr/bin/composer
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
