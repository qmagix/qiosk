const { contextBridge, ipcRenderer } = require('electron')

contextBridge.exposeInMainWorld('electronAPI', {
    downloadFile: (url, filename) => ipcRenderer.invoke('download-file', url, filename),
    checkFile: (filename) => ipcRenderer.invoke('check-file', filename),
    getMediaDir: () => ipcRenderer.invoke('get-media-dir'),
    savePlaylist: (slug, data) => ipcRenderer.invoke('save-playlist', slug, data),
    loadPlaylist: (slug) => ipcRenderer.invoke('load-playlist', slug)
})
