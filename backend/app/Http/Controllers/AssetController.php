<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return $request->user()->assets()->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,mp4,mov|max:51200', // 50MB max
        ]);

        $file = $request->file('file');
        // Store in storage/app/public/assets
        $path = $file->store('assets', 'public');
        $url = asset(Storage::url($path));

        $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';        $asset = $request->user()->assets()->create([
            'type' => $type,
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json($asset, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $asset = $request->user()->assets()->findOrFail($id);
        $asset->delete();
        return response()->noContent();
    }
}
