# Electron Player App

This is the Electron-based player for the Media Kiosk application.

## Purpose
This application wraps the frontend player to provide:
1.  **Offline Support**: Can cache media and playlists locally to play without an internet connection.
2.  **Kiosk Mode**: Can run in full screen, disable system keys, and start on boot.
3.  **Hardware Access**: Can access local hardware if needed (serial ports, etc.).

## Setup

1.  Install dependencies:
    ```bash
    npm install
    ```

2.  Run in development (requires frontend running on port 5173):
    ```bash
    npm start
    ```

## Building for Production

To build the electron app, you typically need to:
1.  Build the frontend (`npm run build` in `../frontend`).
2.  Package the electron app.

## Offline Strategy (Implemented)

To fully support offline mode, we have implemented:
1.  **Download Media**: The Electron main process downloads playlist media to the local file system (`userData/media`).
2.  **Custom Protocol**: We use a `media://` protocol to serve local files safely to the renderer.
3.  **Local Storage**: Playlist schedules and metadata are stored in local JSON files (`userData/playlists`).
4.  **Sync Logic**: The frontend `OfflineManager` service handles syncing assets and falling back to local content when offline.
