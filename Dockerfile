FROM docker.io/library/php:8.1.10-apache-bullseye

ENV APACHE_DOCUMENT_ROOT /src/app/public

RUN apt-get update && \
    apt-get install -y git unzip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    a2enmod rewrite

# Replace document root in available site configuration
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
# Replace document root in available configuration
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
# FIXME: remove
# Add the user
#RUN usermod -G www-data,root -u $uid -d /home/$user $user

COPY --from=docker.io/library/composer:latest /usr/bin/composer /usr/bin/composer
COPY ./ /src/app/

WORKDIR /src/app

RUN mkdir -p /src/app && \
    chown -R www-data:www-data /src/app && \
    chmod -R 755 storage bootstrap/cache && \
    composer install

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
