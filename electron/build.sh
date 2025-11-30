#!/bin/bash

# Exit on error
set -e

echo "🚀 Starting Build Process..."

# 1. Build Frontend
echo "📦 Building Frontend..."
cd ../frontend
npm install
npm run build:electron

# Check if build was successful
if [ ! -d "dist" ]; then
    echo "❌ Frontend build failed!"
    exit 1
fi

echo "✅ Frontend Build Complete"

# 2. Package Electron
echo "📦 Packaging Electron App..."
cd ../electron
npm install

# Determine platform
if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "🍎 Building for macOS..."
    npm run dist:mac
elif [[ "$OSTYPE" == "linux-gnu"* ]]; then
    echo "jq Building for Linux..."
    npm run dist:linux
elif [[ "$OSTYPE" == "msys" ]]; then
    echo "🪟 Building for Windows..."
    npm run dist:win
else
    echo "⚠️ Unknown OS, building default..."
    npm run dist
fi

echo "🎉 Build Complete! Artifacts are in electron/dist"
