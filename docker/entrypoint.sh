#!/usr/bin/env bash
#
# Binds Apache to the port the platform asks for, makes sure the writable
# directories exist (a mounted volume starts empty), then hands over.

set -e

PORT="${PORT:-8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# public/uploads may be a freshly mounted volume, so create and claim it here
# rather than relying on the image layer.
mkdir -p /var/www/html/public/uploads /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views \
         /var/www/html/storage/logs
chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage /var/www/html/bootstrap/cache || true

echo "EPIC: starting Apache on port ${PORT}"
exec apache2-foreground
