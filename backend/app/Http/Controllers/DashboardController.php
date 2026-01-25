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

        if ($user->isAdmin()) {
            $stats = [
                'is_admin' => true,
                'total_users' => User::count(),
                'regular_users' => User::where('role', 'regular')->count(),
                'total_playlists' => Playlist::count(),
                'total_assets' => Asset::count(),
                'recent_signups' => User::latest()->take(5)->get(['id', 'name', 'email', 'created_at', 'role']),
                'storage_usage' => 0, // Placeholder if we want to calculate file sizes later
            ];
        } else {
            $stats = [
                'is_admin' => false,
                'total_playlists' => Playlist::where('user_id', $user->id)->count(),
                'total_assets' => Asset::where('user_id', $user->id)->count(),
                'recent_playlists' => Playlist::where('user_id', $user->id)->latest()->take(5)->get(),
            ];
        }

        return response()->json($stats);
    }
}
