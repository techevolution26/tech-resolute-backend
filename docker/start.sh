#!/bin/sh
set -e

# default PORT if not provided
: "${PORT:=80}"

# substitute PORT into nginx config
envsubst '$PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# ensure ownership/permissions
chown -R www-data:www-data /var/www/html || true

# Start php-fpm as a daemon (so we can start nginx in foreground)
# php-fpm -D will daemonize. If your php-fpm version requires different flags, adjust.
php-fpm -D

# Start nginx in foreground
nginx -g 'daemon off;'
