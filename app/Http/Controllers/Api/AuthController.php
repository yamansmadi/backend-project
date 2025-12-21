<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'phone' => 'required|string|max:20|unique:users,phone',
        'email' => 'nullable|email|max:255|unique:users,email',
        'password' => 'required|string|min:8|confirmed',

        'birth_date' => 'nullable|date',
        'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        'id_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    ]);

    $profileImagePath = null;
    $idImagePath = null;

    if ($request->hasFile('profile_image')) {
        $profileImagePath = $request->file('profile_image')
            ->store('users/profile_images', 'public');
    }

    if ($request->hasFile('id_image')) {
        $idImagePath = $request->file('id_image')
            ->store('users/id_images', 'public');
    }

    $user = User::create([
        'name' => $request->name,
        'phone' => $request->phone,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'status' => 'pending',

        'birth_date' => $request->birth_date,
        'profile_image' => $profileImagePath,
        'id_image' => $idImagePath,
    ]);

    return response()->json([
        'message' => 'User registered successfully. Waiting for admin approval.',
        'user' => $user
    ], 201);
}


    public function login(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        // Find user by phone
        $user = User::where('phone', $request->phone)->first();

        // Invalid credentials
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid phone or password'
            ], 401);
        }

        // Check admin approval
        if ($user->status !== 'active') {
            return response()->json([
                'message' => 'Your account is pending admin approval'
            ], 403);
        }

        // Create token (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        // Delete the current access token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

}
