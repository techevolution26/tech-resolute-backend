#!/bin/sh
set -e

# defaults: Railway usually assigns $PORT (HTTP). If not set, assume 9000.
: "${PORT:=9000}"
# internal php-fpm port (avoid Railway's HTTP port)
: "${PHP_FPM_PORT:=9001}"

# Updating php-fpm listen address in www.conf to 127.0.0.1:PHP_FPM_PORT
# This updates the default pool config to listen on the chosen internal port.
if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
  sed -ri "s#^listen\s*=.*#listen = 127.0.0.1:${PHP_FPM_PORT}#g" /usr/local/etc/php-fpm.d/www.conf
fi

# Substitute PORT and PHP_FPM_PORT into nginx config template
envsubst '\$PORT \$PHP_FPM_PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# ensure ownership/permissions
chown -R www-data:www-data /var/www/html || true

# Start php-fpm (daemonize) so nginx can connect to it
php-fpm -D

# Start nginx in foreground
nginx -g 'daemon off;'
