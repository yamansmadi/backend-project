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

    // 1. جلب بيانات الملف الشخصي للمستخدم الحالي
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    }

    // 2. تحديث بيانات الملف الشخصي
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20|unique:users,phone,' . $user->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'birth_date' => 'nullable|date',
            'password' => 'sometimes|string|min:8|confirmed',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // تحديث النصوص
        if ($request->has('name')) $user->name = $validated['name'];
        if ($request->has('phone')) $user->phone = $validated['phone'];
        if ($request->has('email')) $user->email = $validated['email'];
        if ($request->has('birth_date')) $user->birth_date = $validated['birth_date'];

        // تحديث كلمة المرور إذا وجدت
        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        // تحديث الصورة الشخصية إذا تم رفع واحدة جديدة
        if ($request->hasFile('profile_image')) {
            $profileImagePath = $request->file('profile_image')
                ->store('users/profile_images', 'public');
            $user->profile_image = $profileImagePath;
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }
}
