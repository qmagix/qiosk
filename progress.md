✅ Completed Work
1. Backend Implementation

- Database: Created and ran the migration add_orientation_to_playlists_table to add the orientation column (defaulting to 'landscape').
- Model: Updated Playlist.php to allow mass assignment for the orientation field.
- API: Updated PlaylistController.php to validate and save the orientation during playlist creation and updates.

2. Frontend Implementation

- Playlist Manager (PlaylistManager.vue):
    - Added an Orientation Selector (Landscape/Portrait) to the "Create Playlist" modal.
    - Updated the playlist list to display a badge showing the current orientation of each playlist.
    - Wired up the API call to send the selected orientation when creating a new playlist.
- Playlist Editor (PlaylistEditor.vue):
    - Added an Orientation Dropdown in the editor header.
    - Updated the savePlaylist function to persist orientation changes.

3. Media Player Support (MediaPlayer.vue)
- Updated the player to respect the orientation setting.
- Added logic to rotate the player container if the playlist is portrait but the screen is landscape.

4. Editor Enhancements (PlaylistEditor.vue)
- Added "Save & Preview" button for quick testing.
- Implemented Visual Preview: Thumbnails and asset library items now adjust their aspect ratio (16:9 vs 9:16) based on the playlist orientation.
- Added visual warning text to ensure assets match the selected aspect ratio.

🚧 Pending / Future Improvements
1. Advanced Asset Filtering
Currently, the warning is just text. Future versions could analyze video metadata (width/height) on upload to programmatically warn users or filter incompatible assets.

2. TV Testing
Validating the UI on actual large screens.


✅ Playlist Visibility & Public/Private Sharing
Added visibility (public/private) and access_token to playlists
Public playlists: /p/{id}
Private playlists: /p/{id}/{token}
Backend validates tokens for private playlists
Frontend displays shareable URLs with copy button
✅ Media Download Optimization
Only downloads new media files (checks if file exists first)
Re-uses local copies for existing files
Polls every 30s for playlist updates
Downloads only changed/new media
✅ Offline Support (Electron)
Full offline playback after initial sync
Survives reboots and network outages
Local storage in userData directory
Recovery via cached playlist data
✅ Bug Fixes
Fixed Vue Router useRoute() warnings (moved to top level)
Fixed /p/:id route not loading (added to onMounted check)
Fixed cropperjs CORS issues (disabled cross-origin checks)
Fixed login issues (password hashing)
