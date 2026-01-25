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

## Development Workflow

### Running in Development Mode

The Electron app runs in two modes:
- **Development**: Loads from the Vite dev server (`http://localhost:5173`)
- **Production**: Loads from built static files

To run in development mode with hot reload:

**Terminal 1 - Start Frontend Dev Server:**
```bash
cd frontend
npm run dev
```
This starts Vite on `http://localhost:5173`

**Terminal 2 - Start Electron App:**
```bash
cd electron
npm start
```

The Electron window will open and load your frontend from the dev server. Any changes to the frontend will hot-reload automatically.

### Loading Remote URLs in Development

If you want to load content from a remote server (instead of running the local dev server), you have several options:

**Option 1: Using `run.sh` (Simplest)**

Edit or create `run.sh`:
```bash
#!/bin/bash
ELECTRON_START_URL=https://yourdomain.com/p/abc123 npm start
```

Then run:
```bash
./run.sh
```

**Option 2: Environment Variable**
```bash
ELECTRON_START_URL=https://yourdomain.com/p/abc123 npm start
```

**Option 3: Command Line Argument**
```bash
npm start -- --url https://yourdomain.com/p/abc123
```

**Priority Order:**
1. `--url` command line argument (highest)
2. Config file (`config.json`)
3. `ELECTRON_START_URL` environment variable
4. Default `http://localhost:5173` (lowest)

### Testing with Specific URLs

In development mode, the app loads the full Vue router, so you can:

1. **Navigate within the app** using the UI
2. **Test specific routes** by modifying the URL in `main.js` temporarily:
   ```javascript
   // In main.js, line ~79, change:
   win.loadURL(devUrl);
   // To:
   win.loadURL('http://localhost:5173/p/abc123');
   ```

3. **Use the dev tools** (automatically opens in dev mode) to navigate programmatically

## URL Handling (Command Line & Config)

The Electron app supports loading specific URLs or routes via command line arguments or a config file.

### Command Line Arguments

**Development Mode:**
```bash
cd electron

# Load a specific route from dev server
npm start -- --url /p/abc123

# Load a remote URL
npm start -- --url https://yourdomain.com/p/abc123

# Alternative syntax (positional argument)
npm start -- https://yourdomain.com/p/abc123
```

**Production Mode (Packaged App):**
```bash
# macOS
/Applications/EyePub\ Player.app/Contents/MacOS/EyePub\ Player --url https://yourdomain.com/p/abc123

# Windows
"C:\Program Files\EyePub Player\EyePub Player.exe" --url https://yourdomain.com/p/abc123

# Linux
./EyePub\ Player-1.0.0.AppImage --url https://yourdomain.com/p/abc123
```

### Config File

Create a `config.json` file in the `electron/` directory:

```json
{
  "defaultUrl": "https://yourdomain.com/p/abc123"
}
```

**Examples:**
- **Remote Server**: `"defaultUrl": "https://yourdomain.com/p/abc123"`
- **Local Route**: `"defaultUrl": "/p/abc123"` (works in dev mode)
- **Homepage**: `"defaultUrl": "/"`

A sample config file is provided at `config.json.example`.

### URL Priority

The app uses the following priority order:
1. **Command line argument** (`--url` flag)
2. **Config file** (`config.json`)
3. **Default** (homepage)

### Use Cases

**Kiosk Mode**: Set a default playlist URL in `config.json` so the app always opens to the same playlist:
```json
{
  "defaultUrl": "https://yourdomain.com/play/lobby-display"
}
```

**Development Testing**: Use command line to quickly test different routes:
```bash
npm start -- --url /p/test123
```

**Remote Content**: Load content from your production server in the Electron app:
```bash
npm start -- --url https://eyepub.com/p/abc123
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
