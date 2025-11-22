<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

Route::post('/register', function (Request $request) {
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json(['token' => $token], 201);
});

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $token = $request->user()->createToken('api-token')->plainTextToken;
        return response()->json(['token' => $token]);
    }

    return response()->json(['message' => 'Unauthorized'], 401);
});

Route::get('/playlists/{slug}/play', [PlaylistController::class, 'play']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::apiResource('assets', AssetController::class);
    Route::apiResource('playlists', PlaylistController::class);
    Route::apiResource('users', UserController::class);
});
