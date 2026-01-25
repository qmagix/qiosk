<?php

namespace App\Http\Controllers;

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
        if ($request->has('external_url')) {
            $request->validate([
                'external_url' => 'required|url',
                'type' => 'required|in:image,video',
                'filename' => 'nullable|string|max:255',
            ]);

            $url = $request->external_url;
            $type = $request->type;
            $filename = $request->filename ?? basename($url);

            // Basic cleanup of filename if it's just a domain or empty
            if (empty($filename) || $filename === $url) {
                $filename = 'External Asset';
            }

            $asset = $request->user()->assets()->create([
                'type' => $type,
                'url' => $url,
                'filename' => $filename,
                'mime_type' => null, // We don't know the mime type for sure without fetching headers
            ]);

            return response()->json($asset, 201);
        }

        $request->validate([
            'file' => 'required|file|mimes:jpeg,png,jpg,webp,mp4,mov|max:51200', // 50MB max
        ]);

        $file = $request->file('file');

        // Determine disk based on env
        $disk = env('MEDIA_DISK', 'public');
        if ($disk === 'auto') {
            // If S3 credentials are set, use s3, otherwise public
            if (env('AWS_ACCESS_KEY_ID') && env('AWS_BUCKET')) {
                $disk = 's3';
            } else {
                $disk = 'public';
            }
        }

        // Store file
        // Use store() instead of storePublicly() because S3 buckets often have ACLs disabled (Bucket Owner Enforced).
        // We rely on Bucket Policy for public access.
        $path = $file->store('assets', $disk);

        if (! $path) {
            return response()->json(['message' => 'Failed to store file'], 500);
        }

        // Get URL
        $url = Storage::disk($disk)->url($path);

        $type = str_starts_with($file->getMimeType(), 'video') ? 'video' : 'image';

        $asset = $request->user()->assets()->create([
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
