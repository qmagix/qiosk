# Media Kiosk Development Plan

## Phase 1: Backend Core (Laravel)
- [x] **Models & Relationships**: Define `Asset`, `Playlist`, `PlaylistItem` models with proper relationships.
- [x] **API Routes**: Create routes for CRUD operations on Assets and Playlists.
- [x] **Controllers**: Implement logic for:
    - Uploading files (Local/S3).
    - Managing playlists (add/remove/reorder items).
- [x] **Authentication**: Configure Laravel Sanctum for API token authentication.

## Phase 2: Admin Dashboard (Vue.js)
- [x] **Auth Views**: Login/Register pages.
- [x] **Layout**: Admin layout with sidebar navigation.
- [x] **Asset Manager**:
    - [x] File upload UI (drag & drop).
    - [x] Gallery view of uploaded assets.
- [x] **Playlist Manager**:
    - [x] Create/Edit playlists.
    - [x] Drag-and-drop interface to reorder items (using something like `vuedraggable`).
    - [x] Specific settings per item (duration, transition).

## Phase 3: Player Integration
- [x] **API Integration**: Fetch real playlist data from the backend instead of dummy data.
- [x] **Routing**: Set up Vue Router.
    - `/admin/*` for management.
    - `/play/:slug` for the public player view.
- [x] **Polling/Updates**: Mechanism to check for playlist updates without refreshing the page.

## Phase 4: Polish & Advanced Features
- [x] **Transitions**: Add more transition types (slide, zoom).
- [x] **Offline Support**: Service Worker for caching assets (PWA).
- [x] **TV Optimization**: Ensure UI scales correctly on 4K/1080p screens.

## Phase 5: Deployment
- [x] **Environment Config**: Setup `.env` for production.
- [x] **Build Process**: Compile Vue assets to Laravel public folder or serve separately.

