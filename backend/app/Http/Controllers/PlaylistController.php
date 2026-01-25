<?php

namespace App\Http\Controllers;

use App\Models\PendingUpload;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlaylistController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request->user()->playlists()->withCount('items')->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'orientation' => 'in:landscape,portrait',
            'visibility' => 'in:public,private',
            'allow_uploads' => 'boolean',
            'upload_mode' => 'in:auto_add,require_approval',
            'qr_frequency' => 'integer|min:1|max:100',
        ]);

        $allowUploads = $request->allow_uploads ?? false;

        $playlist = $request->user()->playlists()->create([
            'name' => $request->name,
            'slug' => Str::slug($request->name).'-'.Str::random(6),
            'orientation' => $request->orientation ?? 'landscape',
            'visibility' => $request->visibility ?? 'private',
            'access_token' => ($request->visibility === 'private') ? Str::random(32) : null,
            'allow_uploads' => $allowUploads,
            'upload_token' => $allowUploads ? Str::random(32) : null,
            'upload_mode' => $request->upload_mode ?? 'auto_add',
            'qr_frequency' => $request->qr_frequency ?? 5,
        ]);

        return response()->json($playlist, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $playlist = $request->user()->playlists()->with('items.asset')->findOrFail($id);

        return response()->json($playlist);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'orientation' => 'sometimes|in:landscape,portrait',
            'visibility' => 'sometimes|in:public,private',
            'allow_uploads' => 'sometimes|boolean',
            'upload_mode' => 'sometimes|in:auto_add,require_approval',
            'qr_frequency' => 'sometimes|integer|min:1|max:100',
            'items' => 'sometimes|array',
            'items.*.asset_id' => 'required|exists:assets,id',
            'items.*.duration_seconds' => 'integer|min:0',
            'items.*.transition_effect' => 'string',
            'items.*.crop_data' => 'nullable|array',
        ]);

        if ($request->has('name')) {
            $playlist->update(['name' => $request->name]);
        }

        if ($request->has('orientation')) {
            $playlist->update(['orientation' => $request->orientation]);
        }

        if ($request->has('visibility')) {
            $updates = ['visibility' => $request->visibility];
            if ($request->visibility === 'private' && ! $playlist->access_token) {
                $updates['access_token'] = Str::random(32);
            }
            $playlist->update($updates);
        }

        // Handle upload settings
        if ($request->has('allow_uploads')) {
            $updates = ['allow_uploads' => $request->allow_uploads];
            if ($request->allow_uploads && ! $playlist->upload_token) {
                $updates['upload_token'] = Str::random(32);
            }
            $playlist->update($updates);
        }

        if ($request->has('upload_mode')) {
            $playlist->update(['upload_mode' => $request->upload_mode]);
        }

        if ($request->has('qr_frequency')) {
            $playlist->update(['qr_frequency' => $request->qr_frequency]);
        }

        if ($request->has('items')) {
            // Simple re-sync: delete all and re-create
            $playlist->items()->delete();

            foreach ($request->items as $index => $itemData) {
                $playlist->items()->create([
                    'asset_id' => $itemData['asset_id'],
                    'display_order' => $index,
                    'duration_seconds' => $itemData['duration_seconds'] ?? 10,
                    'transition_effect' => $itemData['transition_effect'] ?? 'fade',
                    'crop_data' => $itemData['crop_data'] ?? null,
                ]);
            }
        }

        return response()->json($playlist->load('items.asset'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);
        $playlist->delete();

        return response()->noContent();
    }

    /**
     * Get playlist by slug for public playback.
     */
    public function play(string $slug)
    {
        // This method might be deprecated or used for legacy slug access
        // For now, let's allow it but maybe enforce public visibility?
        // The user didn't explicitly say to remove slug access, but "unobscured url should be fine".
        // Let's keep it as is for backward compatibility or update it to check visibility.

        $playlist = Playlist::where('slug', $slug)->with([
            'items' => function ($query) {
                $query->orderBy('display_order')->with('asset');
            },
        ])->firstOrFail();

        // If private, we shouldn't allow access via just slug unless authenticated?
        // But this is the "public player" endpoint.
        // If it's private, we should probably deny unless it's the owner (but this is public endpoint).
        // Let's leave slug access open for now or restrict it.
        // Given the new requirement is ID + Token, let's assume slug is for public only?
        // For safety, let's allow it if public.
        if ($playlist->visibility === 'private') {
            return response()->json(['message' => 'Private playlist'], 403);
        }

        return response()->json($playlist);
    }

    /**
     * Get playlist by ID with optional token check.
     */
    public function playById(Request $request, string $id)
    {
        $playlist = Playlist::with([
            'items' => function ($query) {
                $query->orderBy('display_order')->with('asset');
            },
        ])->findOrFail($id);

        if ($playlist->visibility === 'private') {
            $token = $request->query('token');
            if (! $token || $token !== $playlist->access_token) {
                return response()->json(['message' => 'Unauthorized access to private playlist'], 403);
            }
        }

        // Include upload settings in response for the player
        $response = $playlist->toArray();
        if ($playlist->allow_uploads && $playlist->upload_token) {
            $response['allow_uploads'] = true;
            $response['upload_token'] = $playlist->upload_token;
            $response['upload_mode'] = $playlist->upload_mode;
            $response['qr_frequency'] = $playlist->qr_frequency;
        }

        return response()->json($response);
    }

    /**
     * Get pending uploads for a playlist.
     */
    public function pendingUploads(Request $request, string $id)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);

        $pending = $playlist->pendingUploads()
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pending);
    }

    /**
     * Approve a pending upload.
     */
    public function approveUpload(Request $request, string $id, string $uploadId)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);
        $pendingUpload = PendingUpload::where('playlist_id', $playlist->id)
            ->where('id', $uploadId)
            ->where('status', 'pending')
            ->firstOrFail();

        // Add to playlist
        $maxOrder = $playlist->items()->max('display_order') ?? -1;
        PlaylistItem::create([
            'playlist_id' => $playlist->id,
            'asset_id' => $pendingUpload->asset_id,
            'display_order' => $maxOrder + 1,
            'duration_seconds' => $pendingUpload->asset->type === 'image' ? 5 : 0,
            'transition_effect' => 'fade',
        ]);

        // Update status
        $pendingUpload->update(['status' => 'approved']);

        return response()->json(['message' => 'Upload approved and added to playlist']);
    }

    /**
     * Reject a pending upload.
     */
    public function rejectUpload(Request $request, string $id, string $uploadId)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);
        $pendingUpload = PendingUpload::where('playlist_id', $playlist->id)
            ->where('id', $uploadId)
            ->where('status', 'pending')
            ->firstOrFail();

        // Update status (keep the record for audit)
        $pendingUpload->update(['status' => 'rejected']);

        return response()->json(['message' => 'Upload rejected']);
    }

    /**
     * Regenerate upload token.
     */
    public function regenerateUploadToken(Request $request, string $id)
    {
        $playlist = $request->user()->playlists()->findOrFail($id);
        $playlist->update(['upload_token' => Str::random(32)]);

        return response()->json(['upload_token' => $playlist->upload_token]);
    }
}
