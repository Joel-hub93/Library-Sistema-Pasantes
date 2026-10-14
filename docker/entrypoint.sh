#!/bin/sh
set -e

cd /var/www/html

# Permisos por si el volumen de storage quedo propiedad de root
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

# Migra (reintenta una vez por si la base de datos tarda en aceptar conexiones)
if ! php artisan migrate --force; then
    echo ">> 'migrate' fallo; reintentando en 5s..."
    sleep 5
    php artisan migrate --force
fi

# Cachea configuracion, rutas y vistas
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
