# Media Kiosk System

A digital signage solution built with Laravel (Backend) and Vue.js (Frontend).

## Features
- **Asset Management**: Upload photos and videos.
- **Playlist Editor**: Drag-and-drop interface to organize media.
- **Media Player**: Auto-playing kiosk mode with transitions (Fade, Slide, Zoom).
- **Offline Support**: PWA capabilities for offline playback.
- **TV Optimization**: Fullscreen support and hidden cursor.

## Requirements
- PHP 8.2+
- Node.js 20+
- SQLite (default) or MySQL

## Installation & Deployment

1. **Run the deployment script**:
   ```bash
   ./deploy.sh
   ```
   This will install dependencies, build the frontend, and set up the backend.

2. **PWA Icons**:
   For the app to be installable on devices, ensure you place the following icons in `frontend/public/`:
   - `pwa-192x192.png`
   - `pwa-512x512.png`

3. **Serve the Application**:
   Point your web server (Nginx/Apache) to the `backend/public` directory.

## Development

- **Backend**: `cd backend && php artisan serve`
- **Frontend**: `cd frontend && npm run dev`