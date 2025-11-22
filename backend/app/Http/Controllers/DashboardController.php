<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Playlist;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'regular_users' => User::where('role', 'regular')->count(),
            'total_playlists' => Playlist::count(),
            'total_assets' => Asset::count(),
            'recent_signups' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at', 'role']),
            'storage_usage' => 0 // Placeholder if we want to calculate file sizes later
        ];

        return response()->json($stats);
    }
}
