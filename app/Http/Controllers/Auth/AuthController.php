<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // find the user by email, loading their type and status
        $user = User::with(['userType', 'status'])->where('email', $request->email)->first();

        // check if user exists and password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'auth' => 'Invalid credentials',
            ]);
        }

        // credentials are valid, now check your custom status logic
        $userTypeName = $user->userType->type_name;
        $statusName = $user->status->status_name;

        // custom rule
        if (($userTypeName === 'employee' && $statusName !== 'active') || 
            ($userTypeName === 'intern' && $statusName !== 'ongoing')) {
            throw ValidationException::withMessages([
                'auth' => 'Your account has been disabled',
                'custom' => "Your account has been marked as {$statusName}. If you think this was a mistake, please contact support.",
            ]);
        }

        // log the user in
        Auth::login($user);

        // regenerate session and redirect
        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showLogin()
    {
        return Inertia::render('Auth/LoginView');
    }
}
