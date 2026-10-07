<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => __('auth.failed')], 401);
        }

        $user  = Auth::user();
        $token = $user->createToken('pwa-token', ['*'], now()->addDays(30))->plainTextToken;

        // Set user locale for response
        app()->setLocale($user->locale ?? 'en');

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'locale' => $user->locale,
                'role'   => $user->getRoleNames()->first(),
                'avatar' => $user->avatar,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('foreman.currentProject');
        return response()->json([
            'id'              => $user->id,
            'name'            => $user->name,
            'email'           => $user->email,
            'locale'          => $user->locale,
            'role'            => $user->getRoleNames()->first(),
            'avatar'          => $user->avatar,
            'foreman'         => $user->foreman ? [
                'id'              => $user->foreman->id,
                'specialty'       => $user->foreman->specialty,
                'current_project' => $user->foreman->currentProject?->only(['id', 'name', 'code']),
            ] : null,
        ]);
    }

    public function updateLocale(Request $request)
    {
        $request->validate(['locale' => 'required|in:en,am,so']);
        $request->user()->update(['locale' => $request->locale]);
        return response()->json(['locale' => $request->locale]);
    }
}
