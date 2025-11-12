#!/bin/sh
set -e

# set default PORT if not provided
: "${PORT:=80}"

# substitute PORT into nginx config (replace ${PORT} tokens)
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# ensure ownership/permissions
chown -R www-data:www-data /var/www/html || true

# start php-fpm (daemonize) then nginx in foreground
php-fpm -D
nginx -g 'daemon off;'
