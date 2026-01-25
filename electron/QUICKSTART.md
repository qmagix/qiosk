# Quick Start: Running Electron App with URLs

## Development Mode

### 1. Start the Frontend Dev Server
```bash
cd frontend
npm run dev
```
Starts Vite on `http://localhost:5173`

### 2. Start Electron App

**Option A: Default (homepage)**
```bash
cd electron
npm start
```

**Option B: With specific route**
```bash
cd electron
npm start -- --url /p/abc123
```

**Option C: With remote URL (using run.sh - EASIEST)**
```bash
cd electron
# Edit run.sh to set your URL
./run.sh
```

Example `run.sh`:
```bash
#!/bin/bash
ELECTRON_START_URL=https://eyepub.com/p/abc123 npm start
```

**Option D: With remote URL (environment variable)**
```bash
cd electron
ELECTRON_START_URL=https://yourdomain.com/p/abc123 npm start
```

**Option E: With remote URL (command line)**
```bash
cd electron
npm start -- --url https://yourdomain.com/p/abc123
```


## Production Mode (After Building)

### Build the App
```bash
# From project root
cd frontend
npm run build:electron

cd ../electron
npm run dist:mac  # or dist:win, dist:linux
```

### Run with URL

**macOS:**
```bash
# Default
/Applications/EyePub\ Player.app/Contents/MacOS/EyePub\ Player

# With URL
/Applications/EyePub\ Player.app/Contents/MacOS/EyePub\ Player --url https://yourdomain.com/p/abc123
```

**Windows:**
```cmd
"C:\Program Files\EyePub Player\EyePub Player.exe" --url https://yourdomain.com/p/abc123
```

**Linux:**
```bash
./EyePub\ Player-1.0.0.AppImage --url https://yourdomain.com/p/abc123
```

## Using Config File

Create `electron/config.json`:
```json
{
  "defaultUrl": "https://yourdomain.com/p/abc123"
}
```

Then launch normally - it will automatically load the configured URL.

## Examples

### Kiosk Display
```json
{
  "defaultUrl": "https://eyepub.com/play/lobby-display"
}
```

### Testing Private Playlist
```bash
npm start -- --url https://yourdomain.com/p/abc123/secret-token
```

### Local Development
```bash
npm start -- --url /p/test-playlist
```

## Troubleshooting

- **Blank screen in dev mode**: Make sure frontend dev server is running on port 5173
- **URL not loading**: Check console logs - Electron prints what URL it's loading
- **Config not working**: Ensure `config.json` is in the same directory as `main.js`
