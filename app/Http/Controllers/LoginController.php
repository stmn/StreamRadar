<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $storedUsername = Setting::get('auth_username');
        $storedPassword = Setting::get('auth_password');

        if (
            $request->input('username') === $storedUsername
            && Hash::check($request->input('password'), $storedPassword)
        ) {
            $request->session()->put('authenticated', true);
            $request->session()->regenerate();

            return redirect('/');
        }

        return back()->with('error', 'Invalid credentials.');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
