<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use App\Models\InvitationCode;

use App\Mail\NewUserRegistered;
use Illuminate\Support\Facades\Mail;

Route::post('/register', function (Request $request) {
    try {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'invitation_code' => 'required|string|exists:invitation_codes,code',
        ]);

        // Check if invitation code is valid and unused
        $invitation = InvitationCode::where('code', $request->invitation_code)
            ->where('is_used', false)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$invitation) {
            return response()->json(['message' => 'Invalid or expired invitation code.'], 422);
        }

        $role = 'regular';
        if ($request->invitation_code === '86AngelsAdmin') {
            $role = 'superadmin';
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role,
        ]);

        // Mark invitation as used
        $invitation->update([
            'is_used' => true,
            'used_by' => $user->id,
        ]);

        // Send notification email
        if (env('ADMIN_NOTIFICATION_EMAIL')) {
            try {
                Mail::to(env('ADMIN_NOTIFICATION_EMAIL'))->send(new NewUserRegistered($user));
            } catch (\Exception $e) {
                // Log email error but don't fail registration
                \Illuminate\Support\Facades\Log::error('Failed to send registration email: ' . $e->getMessage());
            }
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token], 201);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['message' => $e->getMessage(), 'errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Registration error: ' . $e->getMessage());
        return response()->json(['message' => 'Registration failed: ' . $e->getMessage()], 500);
    }
});

Route::post('/login', function (Request $request) {
    try {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $token = $request->user()->createToken('api-token')->plainTextToken;
            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Unauthorized'], 401);
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Login error: ' . $e->getMessage());
        return response()->json(['message' => 'Login failed: ' . $e->getMessage()], 500);
    }
});

Route::get('/playlists/{slug}/play', [PlaylistController::class, 'play']);

use App\Http\Controllers\InvitationCodeController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('assets', AssetController::class);
    Route::apiResource('playlists', PlaylistController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('invitations', InvitationCodeController::class)->only(['index', 'store', 'update', 'destroy']);
});
