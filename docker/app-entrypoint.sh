#!/bin/sh
set -e

cd /var/www/html

composer install

if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate --force --no-interaction
fi

php artisan migrate:refresh --seed
