#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting Server Update..."

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Pull latest code
echo "📥 Pulling latest code..."
git pull origin main

# Backend Update
echo "🔧 Updating Backend..."
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
cd ..

# Frontend Build
echo "🎨 Building Frontend..."
cd frontend
npm ci
npm run build
cd ..

# Copy Frontend to Backend
echo "📦 Deploying Frontend build..."
rm -rf backend/public/assets
rm -f backend/public/index.html
rm -f backend/public/favicon.ico
rm -f backend/public/registerSW.js
rm -f backend/public/sw.js
rm -f backend/public/workbox-*.js
rm -f backend/public/manifest.webmanifest
cp -r frontend/dist/* backend/public/

# Update SPA blade template
mkdir -p backend/resources/views
cp frontend/dist/index.html backend/resources/views/spa.blade.php
rm backend/public/index.html
php backend/artisan view:clear

# Restart queue workers if running (optional)
if command -v supervisorctl &> /dev/null; then
    echo "🔄 Restarting queue workers..."
    sudo supervisorctl restart all 2>/dev/null || true
fi

# Clear opcache if PHP-FPM is running
if command -v php-fpm &> /dev/null || pgrep -x "php-fpm" > /dev/null; then
    echo "🧹 Clearing PHP opcache..."
    php backend/artisan opcache:clear 2>/dev/null || true
fi

echo "✅ Server Update Complete!"
echo "   $(date)"
