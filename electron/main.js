const { app, BrowserWindow, ipcMain } = require('electron')
const path = require('path')
const fs = require('fs')
const { pipeline } = require('stream/promises');

// IPC Handlers for Offline Support
ipcMain.handle('get-media-dir', () => {
    const mediaDir = path.join(app.getPath('userData'), 'media');
    if (!fs.existsSync(mediaDir)) {
        fs.mkdirSync(mediaDir, { recursive: true });
    }
    return mediaDir;
});

ipcMain.handle('check-file', async (event, filename) => {
    const mediaDir = path.join(app.getPath('userData'), 'media');
    const filePath = path.join(mediaDir, filename);
    return fs.existsSync(filePath);
});

ipcMain.handle('download-file', async (event, url, filename) => {
    const mediaDir = path.join(app.getPath('userData'), 'media');
    if (!fs.existsSync(mediaDir)) {
        fs.mkdirSync(mediaDir, { recursive: true });
    }
    const filePath = path.join(mediaDir, filename);

    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error(`unexpected response ${response.statusText}`);
        await pipeline(response.body, fs.createWriteStream(filePath));
        return filePath;
    } catch (error) {
        console.error('Download failed:', error);
        throw error;
    }
});

ipcMain.handle('save-playlist', async (event, slug, data) => {
    const playlistDir = path.join(app.getPath('userData'), 'playlists');
    if (!fs.existsSync(playlistDir)) {
        fs.mkdirSync(playlistDir, { recursive: true });
    }
    const filePath = path.join(playlistDir, `${slug}.json`);
    await fs.promises.writeFile(filePath, JSON.stringify(data));
    return true;
});

ipcMain.handle('load-playlist', async (event, slug) => {
    const playlistDir = path.join(app.getPath('userData'), 'playlists');
    const filePath = path.join(playlistDir, `${slug}.json`);
    if (fs.existsSync(filePath)) {
        const data = await fs.promises.readFile(filePath, 'utf-8');
        return JSON.parse(data);
    }
    return null;
});

// Helper function to load config
function loadConfig() {
    const configPath = path.join(__dirname, 'config.json');
    if (fs.existsSync(configPath)) {
        try {
            const configData = fs.readFileSync(configPath, 'utf-8');
            return JSON.parse(configData);
        } catch (error) {
            console.error('Error loading config:', error);
            return {};
        }
    }
    return {};
}

// Helper function to parse command line arguments
function getCommandLineUrl() {
    // Check for --url argument
    const urlArgIndex = process.argv.indexOf('--url');
    if (urlArgIndex !== -1 && process.argv[urlArgIndex + 1]) {
        return process.argv[urlArgIndex + 1];
    }

    // Check for positional URL argument (after all flags)
    // This allows: electron . https://example.com/p/abc123
    const positionalArgs = process.argv.slice(2).filter(arg => !arg.startsWith('--'));
    if (positionalArgs.length > 0 && positionalArgs[0].startsWith('http')) {
        return positionalArgs[0];
    }

    return null;
}

// Helper function to determine the full URL to load
function getLoadUrl(isDev, targetRoute = null) {
    const devBaseUrl = process.env.ELECTRON_START_URL || 'http://localhost:5173';
    const config = loadConfig();

    // Priority: Command line > Config file > Default
    let targetUrl = getCommandLineUrl() || config.defaultUrl || null;

    // If targetRoute is provided (for navigation after load), use it
    if (targetRoute) {
        targetUrl = targetRoute;
    }

    if (isDev) {
        // In development mode
        if (targetUrl) {
            // If it's a full URL (http/https), use it directly
            if (targetUrl.startsWith('http://') || targetUrl.startsWith('https://')) {
                return targetUrl;
            }
            // If it's a route (starts with /), append to dev server
            if (targetUrl.startsWith('/')) {
                return devBaseUrl + targetUrl;
            }
            // Otherwise, treat as a route and prepend /
            return devBaseUrl + '/' + targetUrl;
        }
        return devBaseUrl;
    } else {
        // In production mode
        return targetUrl; // Will be used with loadURL if it exists
    }
}

let mainWindow = null;

function createWindow() {
    const win = new BrowserWindow({
        width: 800,
        height: 600,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true
        }
    })

    mainWindow = win;

    const isDev = !app.isPackaged;
    const loadUrl = getLoadUrl(isDev);

    if (isDev) {
        // Development mode: load from dev server
        console.log('Loading URL (dev):', loadUrl);
        win.loadURL(loadUrl);
        win.webContents.openDevTools();
    } else {
        // Production mode: load built files first, then navigate if needed
        const packagedPath = path.join(__dirname, 'renderer/index.html');
        const localPath = path.join(__dirname, '../frontend/dist/index.html');

        if (fs.existsSync(packagedPath)) {
            win.loadFile(packagedPath).then(() => {
                if (loadUrl && (loadUrl.startsWith('http://') || loadUrl.startsWith('https://'))) {
                    // If a remote URL is specified, load it
                    console.log('Loading remote URL:', loadUrl);
                    win.loadURL(loadUrl);
                } else if (loadUrl && loadUrl.startsWith('/')) {
                    // If a route is specified, navigate to it
                    console.log('Navigating to route:', loadUrl);
                    win.webContents.executeJavaScript(`window.location.hash = '${loadUrl}'`);
                }
            });
        } else if (fs.existsSync(localPath)) {
            win.loadFile(localPath).then(() => {
                if (loadUrl && (loadUrl.startsWith('http://') || loadUrl.startsWith('https://'))) {
                    console.log('Loading remote URL:', loadUrl);
                    win.loadURL(loadUrl);
                } else if (loadUrl && loadUrl.startsWith('/')) {
                    console.log('Navigating to route:', loadUrl);
                    win.webContents.executeJavaScript(`window.location.hash = '${loadUrl}'`);
                }
            });
        } else {
            console.error('No built frontend found!');
        }
    }

    return win;
}

app.whenReady().then(() => {
    // Register a custom protocol to serve media files
    // Usage: media://<filename>
    const { protocol } = require('electron');
    protocol.registerFileProtocol('media', (request, callback) => {
        const url = request.url.replace('media://', '');
        const filename = decodeURIComponent(url);
        const mediaDir = path.join(app.getPath('userData'), 'media');
        const filePath = path.join(mediaDir, filename);
        callback({ path: filePath });
    });

    createWindow()

    app.on('activate', () => {
        if (BrowserWindow.getAllWindows().length === 0) {
            createWindow()
        }
    })
})

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit()
    }
})
