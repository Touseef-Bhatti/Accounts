#!/bin/bash
set -e
cd /var/www/html

if [ ! -f vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist
fi

chown -R www-data:www-data uploads storage 2>/dev/null || true
chmod -R 775 uploads storage 2>/dev/null || true

exec apache2-foreground
