<?php

namespace App\Http\Controllers;

use App\Models\InvitationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvitationCodeController extends Controller
{
    public function index(Request $request)
    {
        // Ensure user is admin (middleware should handle this, but good to be safe)
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return InvitationCode::with(['creator', 'user'])->latest()->get();
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'code' => 'nullable|string|unique:invitation_codes,code|max:255',
        ]);

        $codeString = $request->code ? $request->code : strtoupper(Str::random(10));

        $code = InvitationCode::create([
            'code' => $codeString,
            'created_by' => $request->user()->id,
            'expires_at' => $request->expires_at, // Optional expiration
        ]);

        return response()->json($code, 201);
    }

    public function update(Request $request, $id)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $invitation = InvitationCode::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:255|unique:invitation_codes,code,'.$id,
            'is_used' => 'boolean',
        ]);

        $invitation->update([
            'code' => $request->code,
            'is_used' => $request->is_used,
        ]);

        // If marked as unused, clear the used_by field
        if (! $request->is_used) {
            $invitation->update(['used_by' => null]);
        }

        return response()->json($invitation);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->user()->role !== 'admin' && $request->user()->role !== 'superadmin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $code = InvitationCode::findOrFail($id);
        $code->delete();

        return response()->noContent();
    }
}
