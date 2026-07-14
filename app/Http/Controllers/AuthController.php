<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid email or password'
            ], 401);
        }

        $user = Auth::user();

        // Decide role + token name/abilities based on role_id
        $isAdmin = $user->role_id == 1;
        $role = $isAdmin ? 'Admin' : 'Customer';
        $tokenName = $isAdmin ? 'admin_token' : 'user_token';
        $abilities = $isAdmin ? ['admin'] : ['user'];

        // Create Sanctum Token with role-specific name + abilities
        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'message' => 'Login successfully',
            'token'   => $token,
            'role'    => $role,
            'user'    => $user,
        ], 200);
    }

    // Register Customer
   public function register_user(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|min:8|confirmed',
        'gender' => 'required|string',
        'date_of_birth' => 'nullable|date',
        'phone_number' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
        'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
    ]);

    $profilePicturePath = null;

    if ($request->hasFile('profile_picture')) {
        $profilePicturePath = $request->file('profile_picture')
            ->store('profile_pictures', 'public');
    }

    $user = User::create([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
        'password' => Hash::make($validatedData['password']),
        'gender' => $validatedData['gender'],
        'date_of_birth' => $validatedData['date_of_birth'] ?? null,
        'phone_number' => $validatedData['phone_number'] ?? null,
        'address' => $validatedData['address'] ?? null,
        'profile_picture' => $profilePicturePath,
        'role_id' => 2, // Customer
    ]);

    return response()->json([
        'message' => 'Register successfully. Please login to continue.',
        'user' => $user,
    ], 201);
}

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successfully'
        ], 200);
    }
}