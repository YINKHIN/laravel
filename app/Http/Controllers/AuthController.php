<?php

namespace App\Http\Controllers;

use App\Mail\PasswordResetCodeMail;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function index()
    {
        return response()->json([
            'list' => User::with('profile')->get()
        ]);
    }

    // Login a user and return a JWT token
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get credentials
        $credentials = $request->only('email', 'password');

        try {
            // Attempt to verify the credentials and create a token for the user
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
        } catch (JWTException $e) {
            // Something went wrong with JWT
            return response()->json([
                'success' => false,
                'message' => 'Could not create token'
            ], 500);
        }

        // Get the authenticated user
        $user = Auth::user();

        // Load the profile relationship
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    /**
     * Register new user
     */
    public function register(Request $request)
    {
        // Only validate image if a new image is being uploaded
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'type' => 'sometimes|in:admin,user,manager,sales,inventory',
            'phone' => 'sometimes|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'address' => 'sometimes|string|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userType = $request->type ?? 'user';

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $userType,
        ]);

        // Create profile if additional data provided
        if ($request->has(['phone', 'address']) || $request->hasFile('image')) {
            $profileData = [
                'user_id' => $user->id,
                'type' => $userType
            ];

            if ($request->phone)
                $profileData['phone'] = $request->phone;
            if ($request->address)
                $profileData['address'] = $request->address;

            // Handle image upload
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('profiles', 'public');
                $profileData['image'] = $imagePath;
            }

            Profile::create($profileData);
        }

        // Generate JWT token
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not create token'
            ], 500);
        }

        // Load the profile relationship
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ], 201);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->load('profile');

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function update(Request $request, string $id)
    {
        // Only validate image if a new image is being uploaded
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password' => 'sometimes|nullable|min:6',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'type' => 'nullable|in:admin,user,manager,sales,inventory'
        ];

        // Only add image validation if a file is being uploaded
        if ($request->hasFile('image')) {
            $rules['image'] = 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);

        // Update user basic info
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
        ];

        $userType = null;
        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        if ($request->filled('type')) {
            $userType = $request->type;
            $userData['user_type'] = $userType;
        }

        $user->update($userData);

        // Update or create profile
        $profileData = [
            'user_id' => $user->id
        ];

        if ($userType) {
            $profileData['type'] = $userType;
        }

        if ($request->filled('phone')) {
            $profileData['phone'] = $request->phone;
        }

        if ($request->filled('address')) {
            $profileData['address'] = $request->address;
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->profile && $user->profile->image) {
                Storage::disk('public')->delete($user->profile->image);
            }

            $imagePath = $request->file('image')->store('profiles', 'public');
            $profileData['image'] = $imagePath;
        }

        if ($user->profile) {
            $user->profile->update($profileData);
        } else {
            Profile::create($profileData);
        }

        // Load the profile relationship
        $user->load('profile');

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }

    /**
     * Upload profile image
     */
    public function uploadImage(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->profile && $user->profile->image) {
                Storage::disk('public')->delete($user->profile->image);
            }

            $imagePath = $request->file('image')->store('profiles', 'public');
            
            // Update or create profile with image
            $profileData = [
                'user_id' => $user->id,
                'image' => $imagePath,
            ];

            if ($user->profile) {
                $user->profile->update($profileData);
            } else {
                Profile::create($profileData);
            }

            // Load the profile relationship
            $user->load('profile');

            return response()->json([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'data' => [
                    'image' => $imagePath
                ],
                'user' => $user
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No image provided'
        ], 400);
    }

    /**
     * Logout user (invalidate token)
     */
    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'success' => true,
                'message' => 'Logout successful'
            ]);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout'
            ], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get authenticated user
            $user = JWTAuth::parseToken()->authenticate();

            // Log for debugging
            \Log::info('Password change attempt', [
                'user_id' => $user->id,
                'current_password_length' => strlen($request->current_password),
                'stored_password_length' => strlen($user->password)
            ]);

            // Check if current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                \Log::warning('Password change failed - incorrect current password', [
                    'user_id' => $user->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to change password: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Request password reset
     */
    public function requestPasswordReset(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user by email
            $user = User::where('email', $request->email)->first();

            // Generate a 6-digit verification code
            $verificationCode = rand(100000, 999999);

            // Store the verification code in the user's profile (or you could create a separate table for this)
            $profile = $user->profile;
            if (!$profile) {
                $profile = new Profile();
                $profile->user_id = $user->id;
            }
            $profile->verification_code = $verificationCode;
            $profile->verification_code_expires_at = now()->addMinutes(10); // Code expires in 10 minutes
            $profile->save();

            // Send email with verification code
            Mail::to($user->email)->send(new PasswordResetCodeMail($verificationCode, $user));

            \Log::info('Password reset verification code sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'verification_code' => $verificationCode
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to your email'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to request password reset: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset password with verification code
     */
    public function resetPassword(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'verification_code' => 'required|numeric|digits:6',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get user by email
            $user = User::where('email', $request->email)->first();

            // Check if user has a profile with verification code
            if (!$user->profile) {
                return response()->json([
                    'success' => false,
                    'message' => 'No verification code found for this user'
                ], 400);
            }

            // Check if verification code matches and hasn't expired
            if ($user->profile->verification_code != $request->verification_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid verification code'
                ], 400);
            }

            if (now()->greaterThan($user->profile->verification_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Verification code has expired'
                ], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            // Clear verification code
            $user->profile->verification_code = null;
            $user->profile->verification_code_expires_at = null;
            $user->profile->save();

            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password: ' . $e->getMessage()
            ], 500);
        }
    }

}
