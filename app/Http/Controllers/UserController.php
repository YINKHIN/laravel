<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('profile');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('type')) {
            $query->where('user_type', $request->input('type'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'type' => 'required|in:admin,user,manager,sales,inventory',
            'phone' => 'sometimes|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'address' => 'sometimes|string|max:255',
        ]);
 if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->type,
        ]);

        // Create profile with image handling
        $profileData = ['user_id' => $user->id];
        
        if ($request->phone)
            $profileData['phone'] = $request->phone;
        if ($request->address)
            $profileData['address'] = $request->address;

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('profiles', 'public');
            $profileData['image'] = $imagePath;
        }

        if (!empty($profileData) && count($profileData) > 1) {
            Profile::create($profileData);
        }

        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    public function show(string $id)
    {
        $user = User::with('profile')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|string|min:6',
            'type' => 'sometimes|in:admin,user,manager,sales,inventory',
            'phone' => 'sometimes|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'address' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update user data
        $userData = $request->only(['name', 'email']);
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }
        
        // Handle user type update
        if ($request->filled('type')) {
            $userData['user_type'] = $request->type;
        }

        $user->update($userData);

        // Update or create profile
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($profile->image && Storage::disk('public')->exists($profile->image)) {
                Storage::disk('public')->delete($profile->image);
            }

            // Store new image
            $imagePath = $request->file('image')->store('profiles', 'public');
            $profile->image = $imagePath;
        }

        // Update profile data
        $profileData = $request->only(['phone', 'address']);
        
        // Update profile type to match user type if user type was changed
        if ($request->filled('type')) {
            $profileData['type'] = $request->type;
        }
        
        if (!empty($profileData)) {
            $profile->update($profileData);
        }

        // Save profile if image was updated
        if ($request->hasFile('image')) {
            $profile->save();
        }

        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy(string $id)
    {
        $user = User::with('profile')->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Delete profile image if exists
        if ($user->profile && $user->profile->image) {
            if (Storage::disk('public')->exists($user->profile->image)) {
                Storage::disk('public')->delete($user->profile->image);
            }
        }

        // Delete profile if exists (will cascade delete)
        if ($user->profile) {
            $user->profile->delete();
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}