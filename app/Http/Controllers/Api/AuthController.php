<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\FileManager;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    public function setProfile(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6',
            'photo' => 'max:2048',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->password);

        if ($request->hasFile('photo')) {
            // Replace #user_id# in path
            $path = str_replace('#user_id#', $user->id, config('constants.s3.base_folder')) . '/profile-photos';

            // Upload using FileManager
            $relativePath = FileManager::upload($request, $path, 'photo');
            \Log::info('Uploaded profile photo', ['path' => $relativePath]);

            // Just save the relative path (no full URL)
            $user->avatar = $relativePath;
        }

        $user->save();

        return response()->json(['message' => 'Profile updated successfully']);
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:2048',
        ]);

        $user = Auth::user();
        $path = str_replace('#user_id#', $user->id, config('constants.s3.base_folder')) . '/profile-photos';
        $relativePath = FileManager::upload($request, $path, 'photo');
        \Log::info('Updated profile photo', ['path' => $relativePath]);

        $user->avatar = $relativePath;
        $user->save();

        return response()->json(['message' => 'Avatar updated successfully', 'user' => $user]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = Auth::user();
        $user->password = bcrypt($request->password);
        $user->save();

        return response()->json(['message' => 'Password updated successfully']);
    }



    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            \Log::error('Google authentication error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to authenticate with Google'], 401);
        }
        // \Log::info('googleUser', ['user' => $googleUser]);
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => User::where('email', $googleUser->getEmail())->exists()
                    ? User::where('email', $googleUser->getEmail())->first()->avatar
                    : $googleUser->getAvatar(),
            ]
        );

        $token = $user->createToken('api-token')->plainTextToken;

        // Redirect to Vue with user info & token
        // $frontendUrl = 'http://localhost:5174/login/callback';
        // Use a dot to join the function result and the string
$frontendUrl = env('FRONTEND_URL', 'http://localhost:5174') . '/login/callback';
        $params = http_build_query([
            'token' => $token,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url,
            'id' => $user->id,
            'password_set' => !empty($user->password)
        ]);
        return redirect("{$frontendUrl}?{$params}");
    }
}
