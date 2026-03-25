<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;

class SimpleAuth
{
    public function handle(Request $request, Closure $next)
    {
        // No auth configured — let everyone in
        $username = Setting::get('auth_username');
        $password = Setting::get('auth_password');

        if (! $username || ! $password) {
            return $next($request);
        }

        // Already authenticated this session
        if ($request->session()->get('authenticated')) {
            return $next($request);
        }

        // Allow login page and login POST
        if ($request->is('login') || $request->is('login/*')) {
            return $next($request);
        }

        // Redirect to login
        return redirect('/login');
    }
}
