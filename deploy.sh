#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting Deployment..."

# Backend Setup
echo "🔧 Setting up Backend..."
cd backend
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi
composer install --optimize-autoloader
php artisan migrate --force
php artisan storage:link || true
cd ..

# Frontend Setup
echo "🎨 Setting up Frontend..."
cd frontend

# Check for PWA icons
if [ ! -f public/pwa-192x192.png ] || [ ! -f public/pwa-512x512.png ]; then
    echo "⚠️  WARNING: PWA icons (pwa-192x192.png, pwa-512x512.png) are missing in frontend/public."
    echo "    The app may not be fully installable on devices without them."
fi

if [ ! -f .env ]; then
    echo "VITE_API_BASE_URL=" > .env
fi
npm install
npm run build
cd ..

# Copy Frontend to Backend
echo "📦 Moving Frontend build to Backend..."
# Ensure public directory exists
mkdir -p backend/public
# Remove old assets if they exist (be careful not to remove storage link)
rm -rf backend/public/assets
rm -f backend/public/index.html
rm -f backend/public/favicon.ico
# Copy build files
cp -r frontend/dist/* backend/public/

# Move index.html to views and remove it from public
# This ensures all requests go through Laravel's index.php
mkdir -p backend/resources/views
cp frontend/dist/index.html backend/resources/views/spa.blade.php
rm backend/public/index.html

echo "✅ Deployment Complete!"
