#!/bin/sh
set -e

# Railway usually injects PORT (HTTP). Use 9000 default to match Railway UI if missing.
: "${PORT:=9000}"
# internal php-fpm port (avoid Railway's HTTP port)
: "${PHP_FPM_PORT:=9001}"

echo ">>> STARTUP: PORT=${PORT}, PHP_FPM_PORT=${PHP_FPM_PORT}"

# Update php-fpm listen address in www.conf to 127.0.0.1:PHP_FPM_PORT
if [ -f /usr/local/etc/php-fpm.d/www.conf ]; then
  sed -ri "s#^listen\s*=.*#listen = 127.0.0.1:${PHP_FPM_PORT}#g" /usr/local/etc/php-fpm.d/www.conf || true
  echo ">>> patched /usr/local/etc/php-fpm.d/www.conf listen to 127.0.0.1:${PHP_FPM_PORT}"
fi

# Substitute PORT and PHP_FPM_PORT into nginx config
envsubst '\$PORT \$PHP_FPM_PORT' < /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf
echo ">>> generated /etc/nginx/conf.d/default.conf:"
cat /etc/nginx/conf.d/default.conf

# Show listeners and processes BEFORE starting (useful in logs)
echo ">>> current listeners (before start):"
ss -ltn || true
echo ">>> current processes (before start):"
ps aux | head -n 30

# ensure ownership/permissions
chown -R www-data:www-data /var/www/html || true

# Start php-fpm in background (daemonize)
php-fpm -D
sleep 0.5

# Show listeners AFTER php-fpm started (we should see php-fpm bound)
echo ">>> listeners after php-fpm start:"
ss -ltnp | head -n 50 || ss -ltn | head -n 50

# Start nginx in foreground
echo ">>> starting nginx..."
nginx -g 'daemon off;'
