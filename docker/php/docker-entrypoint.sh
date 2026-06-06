#!/bin/sh
set -e

echo "[entrypoint] Running admin seeder..."
php /app/create-admin.php

echo "[entrypoint] Starting php-fpm..."
exec php-fpm