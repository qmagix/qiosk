<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
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
        $request->validate(['name' => 'required|string|max:255']);

        $playlist = $request->user()->playlists()->create([
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . Str::random(6),
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
            'items' => 'sometimes|array',
            'items.*.asset_id' => 'required|exists:assets,id',
            'items.*.duration_seconds' => 'integer|min:0',
            'items.*.transition_effect' => 'string',
        ]);

        if ($request->has('name')) {
            $playlist->update(['name' => $request->name]);
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
        $playlist = Playlist::where('slug', $slug)->with(['items' => function($query) {
            $query->orderBy('display_order')->with('asset');
        }])->firstOrFail();

        return response()->json($playlist);
    }
}
