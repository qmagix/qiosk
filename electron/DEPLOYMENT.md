# Deployment Instructions

This document outlines how to build and deploy the EyePub Electron Player.

## Prerequisites

- Node.js (v18 or higher)
- NPM
- Git

## Building the Application

The build process consists of two main steps:
1.  Building the frontend (Vite/Vue)
2.  Packaging the Electron app

### 1. Build Frontend

First, ensure the frontend is built with the correct base path for Electron.

```bash
cd frontend
npm install
npm run build:electron
```

This will create the production assets in `frontend/dist`.

### 2. Package Electron App

Navigate to the electron directory and run the distribution script.

```bash
cd electron
npm install
```

**For macOS:**
```bash
npm run dist:mac
```
Output: `electron/dist/EyePub Player-1.0.0.dmg` (and .zip)

**For Windows:**
(Note: It is best to build Windows apps on Windows, but simple builds can sometimes be done from Mac/Linux using Wine, though electron-builder handles some of this. For best results, run this on a Windows machine or CI/CD).
```bash
npm run dist:win
```
Output: `electron/dist/EyePub Player Setup 1.0.0.exe`

**For Linux:**
```bash
npm run dist:linux
```
Output: `electron/dist/EyePub Player-1.0.0.AppImage`

## Automated Build Script

You can use the `prebuild` script automatically included in the `dist` commands, which copies the `frontend/dist` assets into `electron/renderer` before packaging.

## Installation

### macOS
1.  Open the `.dmg` file.
2.  Drag "EyePub Player" to the Applications folder.
3.  Open the application.
    *   *Note*: Since the app is not code-signed with an Apple Developer Certificate, you may need to right-click and select "Open" the first time, or go to System Settings > Privacy & Security to allow it.

### Windows
1.  Run the `.exe` installer.
2.  The application will install and launch automatically.

### Linux
1.  Make the `.AppImage` executable: `chmod +x EyePub\ Player-1.0.0.AppImage`
2.  Run it: `./EyePub\ Player-1.0.0.AppImage`

## Using the Application

### Basic Usage

Once installed, the EyePub Player works as a standalone desktop application:

1.  **Launch the Application**: Double-click the app icon (or run from terminal on Linux).
2.  **Navigate Within the App**: The app loads the EyePub Player interface where you can:
    - Browse and select playlists
    - View media content
    - Use all web features in a native desktop window

### Current Capabilities

The app supports loading specific URLs or routes in multiple ways:

1.  **Default Mode**: Open the app normally and browse playlists via the built-in UI
2.  **Command Line**: Pass a URL or route as a command line argument
3.  **Config File**: Set a default URL in `config.json` for automatic loading

### Loading Specific URLs

**Option 1: Command Line Argument**

Open the app with a specific URL:

```bash
# macOS
/Applications/EyePub\ Player.app/Contents/MacOS/EyePub\ Player --url https://yourdomain.com/p/abc123

# Windows
"C:\Program Files\EyePub Player\EyePub Player.exe" --url https://yourdomain.com/p/abc123

# Linux
./EyePub\ Player-1.0.0.AppImage --url https://yourdomain.com/p/abc123
```

**Option 2: Config File**

Create a `config.json` file in the application directory (next to the executable):

```json
{
  "defaultUrl": "https://yourdomain.com/p/abc123"
}
```

Then launch the app normally. It will automatically load the configured URL.

**Priority Order:**
- Command line argument (highest priority)
- Config file
- Default homepage (lowest priority)

### Remote vs Local Content

The Electron app can work in two modes:

1.  **Remote Mode**: Load content from a web server
    - Set URL to your production server: `https://yourdomain.com/p/abc123`
    - Requires internet connection
    - Always loads latest content

2.  **Offline Mode**: Use cached local content
    - Content is cached during initial load
    - Works without internet after caching
    - Perfect for kiosks without reliable internet

### Offline Features

The Electron app includes special offline capabilities:

- **Media Caching**: Downloaded media is stored in the app's user data directory
- **Playlist Storage**: Playlists are cached locally for offline access
- **Custom Protocol**: Media files are served via the `media://` protocol internally

### Advanced: Custom Protocol Deep Linking (Optional)

The app currently supports URL loading via command line arguments. If you want to add OS-level URL scheme support (e.g., clicking `eyepub://p/abc123` in a browser opens the app), you can implement custom protocol handling:

#### How to Implement Custom Protocol (`eyepub://`)

1.  **Update `electron/package.json`** build config:
    ```json
    "build": {
      "appId": "com.eyepub.player",
      "productName": "EyePub Player",
      "protocols": [{
        "name": "EyePub",
        "schemes": ["eyepub"]
      }],
      ...
    }
    ```

2.  **Add to `main.js`** (before `app.whenReady()`):
    ```javascript
    // Register protocol handler
    if (process.defaultApp) {
      if (process.argv.length >= 2) {
        app.setAsDefaultProtocolClient('eyepub', process.execPath, [path.resolve(process.argv[1])])
      }
    } else {
      app.setAsDefaultProtocolClient('eyepub')
    }

    // Handle protocol URLs
    app.on('open-url', (event, url) => {
      event.preventDefault()
      const route = url.replace('eyepub://', '')
      if (mainWindow) {
        mainWindow.loadURL(getLoadUrl(!app.isPackaged, '/' + route))
      }
    })
    ```

3.  **Rebuild the app** using `npm run dist:mac` (or appropriate platform)

4.  **Usage**:
    ```bash
    # These will now open the app automatically
    open "eyepub://p/abc123"
    open "eyepub://p/abc123/token123"
    open "eyepub://play/my-playlist"
    ```

**Note**: This is an optional enhancement. The command line argument method (documented above) works without additional setup.

## Code Signing (Production)

To avoid security warnings (especially on macOS and Windows), you need to sign your application.

**macOS:**
1.  Enroll in the Apple Developer Program.
2.  Generate a "Developer ID Application" certificate.
3.  Configure `electron-builder` with your identity.

**Windows:**
1.  Purchase a Code Signing Certificate (EV is recommended).
2.  Configure `electron-builder` with the certificate path and password.

Refer to [electron-builder code signing documentation](https://www.electron.build/code-signing) for details.
