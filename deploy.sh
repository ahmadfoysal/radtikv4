#!/bin/bash

cd /www/wwwroot/app.radtik.com || exit


git config --global --add safe.directory /www/wwwroot/app.radtik.com

echo "Starting deployment at $(date)" >> storage/logs/deploy.log

git pull origin main >> storage/logs/deploy.log 2>&1

# composer install --no-interaction --prefer-dist --optimize-autoloader >> storage/logs/deploy.log 2>&1

# php artisan down || true

# php artisan migrate --force >> storage/logs/deploy.log 2>&1

#php artisan optimize:clear >> storage/logs/deploy.log 2>&1
# php artisan optimize >> storage/logs/deploy.log 2>&1

# php artisan queue:restart >> storage/logs/deploy.log 2>&1

# php artisan up

echo "Deployment finished at $(date)" >> storage/logs/deploy.log
