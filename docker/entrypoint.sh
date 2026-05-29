#!/bin/sh
set -e

# Create SQLite database file if it doesn't exist
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
fi

# Run migrations
php /var/www/html/artisan migrate --force --no-interaction 2>&1 || true

# Warm up config/route/view cache
php /var/www/html/artisan config:cache  2>&1 || true
php /var/www/html/artisan route:cache   2>&1 || true
php /var/www/html/artisan view:cache    2>&1 || true

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
