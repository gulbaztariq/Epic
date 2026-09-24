#!/usr/bin/env bash
#
# Binds Apache to the port the platform asks for, makes sure the writable
# directories exist (a mounted volume starts empty), warms Laravel's caches,
# then hands over.

set -e

PORT="${PORT:-8080}"

sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# public/uploads may be a freshly mounted volume, so create and claim it here
# rather than relying on the image layer.
mkdir -p /var/www/html/public/uploads /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions /var/www/html/storage/framework/views \
         /var/www/html/storage/logs

# A pre-deploy or release step usually runs in a throwaway container, so the
# caches it builds never reach this one. Warm them here instead. Serving
# uncached is slower but correct, so a failure must not stop the site booting.
cd /var/www/html
for cache in config route view; do
    if ! php artisan "${cache}:cache" >/dev/null 2>&1; then
        echo "EPIC: could not cache ${cache}; serving it uncached" >&2
        php artisan "${cache}:clear" >/dev/null 2>&1 || true
    fi
done

chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage \
                           /var/www/html/bootstrap/cache || true

# Apache loads exactly one MPM or refuses to start ("More than one MPM loaded",
# which names no file). The image enables prefork alone, because mod_php is not
# thread-safe, but a module the image deleted can reappear in the container the
# host actually runs, so settle it here against the filesystem Apache will read.
for module in /etc/apache2/mods-enabled/mpm_*.load; do
    case "${module}" in
        */mpm_prefork.load) continue ;;
        *'*.load') continue ;;   # nothing matched the glob
    esac
    echo "EPIC: disabling a second MPM: ${module##*/}" >&2
    rm -f "${module}" "${module%.load}.conf"
done

echo "EPIC: starting Apache on port ${PORT}"
exec apache2-foreground
