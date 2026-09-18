<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $key = 'login:' . $request->ip();
        $isApi = $request->wantsJson() || $request->isJson() || $request->expectsJson();

        // Max 5 attempts per minute
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            if ($isApi) {
                return response()->json([
                    'success' => false,
                    'message' => "Too many login attempts. Please try again in {$seconds} seconds.",
                ], 429);
            }
            throw ValidationException::withMessages([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ]);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            RateLimiter::clear($key);

            if (!$isApi) {
                $request->session()->regenerate();
                return redirect()->intended('/');
            }

            $user = Auth::user();
            $user->load('roles.permissions:id,name', 'tenant');
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'user'    => $user,
                'token'   => $token,
                'message' => 'Login successful.',
            ]);
        }

        RateLimiter::hit($key, 60);

        if ($isApi) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
            'business_name' => 'required|string|max:255',
            'phone'         => 'nullable|string|max:20',
            'address'       => 'nullable|string',
        ]);

        // Create a new tenant for the user
        $tenant = \App\Models\Tenant::create([
            'name' => $request->business_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
        ]);

        // Assign the default 'owner' role
        $ownerRole = \App\Models\Role::where('name', 'owner')->whereNull('tenant_id')->first();
        if ($ownerRole) {
            $user->roles()->attach($ownerRole->id);
        }
        
        $user->load('roles', 'tenant');
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'message' => 'Registration successful.',
        ], 201);
    }

    public function logout(Request $request)
    {
        if ($request->wantsJson() || $request->isJson() || $request->expectsJson()) {
            $request->user('sanctum')?->currentAccessToken()->delete();
            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
