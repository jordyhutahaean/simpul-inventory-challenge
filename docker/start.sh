#!/bin/bash
set -e

# Free hosting has no persistent disk, so the SQLite file is rebuilt on every start.
mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
touch database/database.sqlite

# Fresh schema + sample warehouses, stock and the delivery history CSV
php artisan migrate:fresh --seed --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
