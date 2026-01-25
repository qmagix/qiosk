<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\PendingUpload;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuestUploadController extends Controller
{
    /**
     * Get playlist info for the upload page.
     */
    public function getPlaylistInfo(Request $request, string $id)
    {
        $playlist = Playlist::findOrFail($id);

        $token = $request->query('token');
        if (! $playlist->allow_uploads || ! $token || $token !== $playlist->upload_token) {
            return response()->json(['message' => 'Uploads not available'], 403);
        }

        return response()->json([
            'name' => $playlist->name,
            'orientation' => $playlist->orientation,
            'upload_mode' => $playlist->upload_mode,
            'item_count' => $playlist->items()->count(),
        ]);
    }

    /**
     * Handle guest file upload.
     */
    public function store(Request $request, string $id)
    {
        $playlist = Playlist::findOrFail($id);

        // Validate upload token
        if (! $playlist->allow_uploads) {
            return response()->json(['message' => 'Uploads are disabled for this playlist'], 403);
        }

        $token = $request->input('upload_token') ?? $request->header('X-Upload-Token');
        if (! $token || $token !== $playlist->upload_token) {
            return response()->json(['message' => 'Invalid upload token'], 403);
        }

        // Validate file
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:51200', // 50MB max
        ]);

        $file = $request->file('file');

        // Determine disk based on env (same logic as AssetController)
        $disk = env('MEDIA_DISK', 'public');
        if ($disk === 'auto') {
            if (env('AWS_ACCESS_KEY_ID') && env('AWS_BUCKET')) {
                $disk = 's3';
            } else {
                $disk = 'public';
            }
        }

        // Store file
        $path = $file->store('assets', $disk);

        if (! $path) {
            return response()->json(['message' => 'Failed to store file'], 500);
        }

        // Get URL
        $url = Storage::disk($disk)->url($path);
        $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';

        // Create Asset owned by playlist owner
        $asset = Asset::create([
            'user_id' => $playlist->user_id,
            'type' => $type,
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
        ]);

        if ($playlist->upload_mode === 'auto_add') {
            // Add directly to playlist
            $maxOrder = $playlist->items()->max('display_order') ?? -1;
            PlaylistItem::create([
                'playlist_id' => $playlist->id,
                'asset_id' => $asset->id,
                'display_order' => $maxOrder + 1,
                'duration_seconds' => $type === 'image' ? 5 : 0,
                'transition_effect' => 'fade',
            ]);

            return response()->json([
                'message' => 'Your photo is now showing!',
                'mode' => 'auto_add',
                'asset' => $asset,
            ], 201);
        } else {
            // Create pending upload for approval
            PendingUpload::create([
                'playlist_id' => $playlist->id,
                'asset_id' => $asset->id,
                'status' => 'pending',
            ]);

            return response()->json([
                'message' => 'Thank you for your contribution! It will appear after review.',
                'mode' => 'require_approval',
                'asset' => $asset,
            ], 201);
        }
    }
}
