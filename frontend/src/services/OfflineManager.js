
/**
 * Offline Manager Service
 * Handles downloading and syncing media assets for offline playback in Electron.
 */
export const offlineManager = {
    isElectron: () => !!window.electronAPI,

    /**
     * Syncs a playlist's media assets to local storage.
     * Returns a new playlist object with local file paths.
     * @param {Object} playlist - The playlist object
     * @returns {Promise<Object>} - The updated playlist
     */
    async syncPlaylist(playlist) {
        if (!this.isElectron()) {
            console.log('Not in Electron, skipping offline sync.');
            return playlist;
        }

        console.log('Starting offline sync for playlist:', playlist.name);
        const updatedItems = await Promise.all(playlist.items.map(async (item) => {
            const newItem = { ...item };

            // Handle Image/Video assets
            if (item.asset && item.asset.path) {
                try {
                    // Construct filename from ID and original name to ensure uniqueness
                    const extension = item.asset.path.split('.').pop();
                    const filename = `${item.asset.id}_${item.id}.${extension}`;

                    // Check if file exists
                    const exists = await window.electronAPI.checkFile(filename);

                    if (!exists) {
                        console.log(`Downloading ${filename}...`);
                        // Assuming item.asset.url is the full URL. If not, prepend API base.
                        // In this app, we usually have full URLs or need to construct them.
                        // Let's assume we need to handle both cases.
                        let url = item.asset.url;
                        if (!url && item.asset.path) {
                            // Fallback if url is missing but path exists (Laravel storage)
                            // This part depends on how the backend sends data.
                            // For now, we assume the 'url' property is populated by the backend resource.
                            console.warn('No URL found for asset', item.asset);
                        }

                        if (url) {
                            await window.electronAPI.downloadFile(url, filename);
                            console.log(`Downloaded ${filename}`);
                        }
                    } else {
                        console.log(`File ${filename} already exists.`);
                    }

                    // Update the item to point to the local file
                    // We use the file:// protocol. 
                    // Note: The main process saves to userData/media.
                    // We need to know the full path or use a custom protocol.
                    // Our main process returns the full path from downloadFile, but checkFile returns boolean.
                    // Use custom media:// protocol
                    newItem.localPath = `media://${filename}`;

                } catch (error) {
                    console.error(`Failed to sync item ${item.id}:`, error);
                }
            }

            return newItem;
        }));

        return {
            ...playlist,
            items: updatedItems
        };
    },

    /**
     * Get the local source for an item if available, otherwise return original source.
     * @param {Object} item 
     */
    getMediaSource(item) {
        if (this.isElectron() && item.localPath) {
            return item.localPath;
        }
        return item.asset?.url || '';
    },

    async savePlaylist(slug, playlist) {
        if (!this.isElectron()) return;
        try {
            await window.electronAPI.savePlaylist(slug, playlist);
            console.log('Playlist saved offline:', slug);
        } catch (e) {
            console.error('Failed to save playlist offline:', e);
        }
    },

    async loadPlaylist(slug) {
        if (!this.isElectron()) return null;
        try {
            const playlist = await window.electronAPI.loadPlaylist(slug);
            if (playlist) {
                console.log('Loaded offline playlist:', slug);
                return playlist;
            }
        } catch (e) {
            console.error('Failed to load offline playlist:', e);
        }
        return null;
    }
};
