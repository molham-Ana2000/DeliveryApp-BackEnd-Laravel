#!/bin/bash

set -e

echo "Starting Laravel container..."


# =====================================================
# Install Composer dependencies
# =====================================================

if [ ! -d "vendor" ]; then

    echo "Vendor folder not found. Running composer install..."

    composer install \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

else

    echo "Vendor folder exists."

fi



# =====================================================
# Create .env if missing
# =====================================================

if [ ! -f ".env" ]; then

    echo ".env file not found. Creating..."

    cp .env.example .env

fi



# =====================================================
# Generate APP_KEY if missing
# =====================================================

if ! grep -q "APP_KEY=base64" .env; then

    echo "Generating Laravel APP_KEY..."

    php artisan key:generate --force

else

    echo "APP_KEY already exists."

fi



# =====================================================
# Storage symbolic link
# =====================================================

if [ ! -e "public/storage" ]; then

    echo "Creating storage link..."

    php artisan storage:link

else

    echo "Storage link already exists."

fi



# =====================================================
# Clear Laravel cache
# =====================================================

echo "Clearing Laravel cache..."

php artisan config:clear || true



echo "Laravel container is ready."


# =====================================================
# Start container process
# =====================================================

exec "$@"
