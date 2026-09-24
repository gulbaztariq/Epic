# syntax=docker/dockerfile:1
#
# EPIC website — production image.
#
# Apache + PHP 8.3, serving Laravel's public/ directory. Built for Railway but
# works on any container host: the entrypoint binds to $PORT when one is set.

FROM php:8.3-apache

# --- PHP extensions the application needs -----------------------------------
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libonig-dev libxml2-dev \
        unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-freetype --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd zip intl pdo_mysql opcache exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Sensible opcache settings for a long-running web container.
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=16'; \
        echo 'opcache.max_accelerated_files=20000'; \
        echo 'opcache.validate_timestamps=0'; \
    } > /usr/local/etc/php/conf.d/opcache.ini \
    && { \
        echo 'upload_max_filesize=24M'; \
        echo 'post_max_size=32M'; \
        echo 'memory_limit=512M'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/epic.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Exactly one MPM may be loaded or Apache refuses to start ("More than one MPM
# loaded"). a2dismod only manages the symlinks in mods-enabled, so clear those
# outright and put back the single MPM this image needs: mod_php is not
# thread-safe, so prefork. The listing that follows records, in the build log,
# every place the served config pulls an MPM in from.
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf \
    && a2enmod mpm_prefork rewrite headers expires deflate \
    && echo '--- Include directives in apache2.conf ---' \
    && grep -nE '^[[:space:]]*Include' /etc/apache2/apache2.conf \
    && echo '--- MPM LoadModule lines the served config reads ---' \
    && { grep -RnE '^[[:space:]]*LoadModule[[:space:]]+mpm_' \
            /etc/apache2/apache2.conf /etc/apache2/ports.conf \
            /etc/apache2/mods-enabled /etc/apache2/conf-enabled \
            /etc/apache2/sites-enabled || echo '(none)'; }

WORKDIR /var/www/html

# Dependencies first so code changes don't re-resolve the whole tree.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

# Explicit paths: RUN uses /bin/sh, which has no brace expansion.
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p public/uploads \
        storage/framework/cache/data storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public/uploads \
    && chmod -R 775 storage bootstrap/cache public/uploads

COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Fail the build, not the container, if the Apache config is unservable.
RUN apache2ctl -t
COPY docker/entrypoint.sh /usr/local/bin/epic-entrypoint
RUN chmod +x /usr/local/bin/epic-entrypoint

EXPOSE 8080
ENTRYPOINT ["epic-entrypoint"]
