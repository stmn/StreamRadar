#!/bin/sh
set -e

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    if grep -q "^APP_KEY=$" /app/.env 2>/dev/null || ! grep -q "^APP_KEY=" /app/.env 2>/dev/null; then
        php /app/artisan key:generate --force
    fi
fi

# Create SQLite database if missing
if [ ! -f /app/database/database.sqlite ]; then
    touch /app/database/database.sqlite
fi
chown www-data:www-data /app/database/database.sqlite

# Run migrations
php /app/artisan migrate --force --no-interaction

# Cache for production
php /app/artisan config:cache
php /app/artisan route:cache
php /app/artisan view:cache

echo ""
echo "  StreamRadar is ready!"
echo "  Open http://localhost:${APP_PORT:-8080} in your browser"
echo ""

exec "$@"
