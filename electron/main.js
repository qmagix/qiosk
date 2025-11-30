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

    // In development, load the local dev server.
    // In production, load the built index.html.
    // We assume the frontend is running on port 5173 (Vite default)
    const isDev = !app.isPackaged; // Or use an env var

    // You might want to use an environment variable to control this more explicitly
    const devUrl = process.env.ELECTRON_START_URL || 'http://localhost:5173';

    if (isDev) {
        win.loadURL(devUrl);
        // Open the DevTools.
        win.webContents.openDevTools();
    } else {
        // In production, we expect the frontend to be built.
        // If packaged, we expect the frontend to be in a 'renderer' subdirectory.
        const packagedPath = path.join(__dirname, 'renderer/index.html');
        const localPath = path.join(__dirname, '../frontend/dist/index.html');

        if (fs.existsSync(packagedPath)) {
            win.loadFile(packagedPath);
        } else {
            win.loadFile(localPath);
        }
    }
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
