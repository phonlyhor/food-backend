<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Update user profile information
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|string',
            'date_of_birth' => 'nullable|date',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Process profile picture file upload if exists
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $profilePicturePath = $request->file('profile_picture')
                ->store('profile_pictures', 'public');
            $user->profile_picture = $profilePicturePath;
        }

        $user->name = $validatedData['name'];
        $user->gender = $validatedData['gender'];
        $user->date_of_birth = $validatedData['date_of_birth'] ?? null;
        $user->phone_number = $validatedData['phone_number'] ?? null;
        $user->address = $validatedData['address'] ?? null;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'user' => $user
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 401);
        }

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check if old password matches current database password
        if (!Hash::check($request->old_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'The old password you entered is incorrect.'
            ], 400);
        }

        // Save new hashed password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully.'
        ]);
     }

    /**
     * Get all customer users (role_id = 2) for admin panel
     */
    public function adminIndex()
    {
        $customers = User::where('role_id', 2)
            ->orWhereHas('role', function ($query) {
                $query->where('name', 'Customer');
            })
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'customers' => $customers
        ]);
    }
}
