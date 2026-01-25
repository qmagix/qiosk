# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Media Kiosk (EyePub) is a digital signage solution with three components:
- **backend/**: Laravel 12 PHP API with Sanctum authentication, SQLite database
- **frontend/**: Vue 3 SPA with Pinia state, Vue Router, Tailwind CSS, and PWA support
- **electron/**: Electron wrapper for offline kiosk playback with local media caching

## Common Commands

### Development (run from respective directories)

```bash
# Backend - starts server, queue, logs, and vite concurrently
cd backend && composer dev

# Frontend only
cd frontend && npm run dev

# Electron (requires frontend dev server running first)
cd electron && npm start

# Electron with specific URL
cd electron && npm start -- --url https://example.com/p/abc123
```

### Build & Deploy

```bash
# Full deployment (backend setup + frontend build + copy to backend/public)
./deploy.sh

# Frontend build only
cd frontend && npm run build

# Frontend build for Electron (relative paths)
cd frontend && npm run build:electron

# Electron packaging
cd electron && npm run dist        # All platforms
cd electron && npm run dist:mac    # macOS only
```

### Testing

```bash
cd backend && composer test
```

### Linting

```bash
cd backend && ./vendor/bin/pint   # PHP code style
```

## Architecture

### Data Flow
1. Laravel API serves playlist/asset data via `/api/*` routes (Sanctum-protected for admin, public for playback)
2. Frontend fetches playlists and renders the MediaPlayer component
3. Electron wraps the frontend, adds offline caching via IPC handlers, uses `media://` protocol for local files

### Key Models (backend/app/Models/)
- **User**: Has role (regular/superadmin), owns playlists and assets
- **Playlist**: Has items, slug for public URL, visibility (public/private), orientation
- **PlaylistItem**: Belongs to playlist and asset, stores duration, order, crop_data
- **Asset**: Media files (images/videos) stored via Laravel filesystem
- **InvitationCode**: Required for registration

### Key Routes
- `/api/playlists/{slug}/play` - Public playlist playback endpoint
- `/api/playlists/{id}/play-by-id` - Direct ID-based playback (with optional token for private)
- `/p/:id/:token?` - Frontend player route
- `/play/:slug?` - Frontend player route (public slug)
- `/admin/*` - Protected admin routes (dashboard, assets, playlists, users, invitations)

### Frontend Structure (frontend/src/)
- **views/**: Page components (AdminLayout, PlaylistEditor, AssetManager, etc.)
- **components/**: Reusable components (MediaPlayer, ImageCropperModal)
- **services/OfflineManager.js**: Electron offline sync logic
- **router/**: Uses hash history in Electron, web history in browser

### Electron Architecture
- **main.js**: IPC handlers for file operations, custom `media://` protocol for cached files
- **preload.js**: Exposes electronAPI to renderer (downloadFile, checkFile, savePlaylist, loadPlaylist)
- URL priority: CLI arg > config.json > ELECTRON_START_URL env > localhost:5173

## Configuration

### Frontend Environment
- `frontend/.env`: Set `VITE_API_BASE_URL` for production API endpoint

### Backend Environment
- `backend/.env`: Standard Laravel config, uses SQLite by default
- `ADMIN_NOTIFICATION_EMAIL`: Optional email for new user registration alerts

### Electron Config
- `electron/config.json`: Set `defaultUrl` for kiosk startup URL
