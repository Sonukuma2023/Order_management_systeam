<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
       
        if (!Auth::check()) {
            return redirect('/login');
        }
        if (Auth::user()->role !== $role) {

            if (Auth::user()->role === 'vendor') {
                return redirect('/vendor/dashboard');
            }

            if (Auth::user()->role === 'administrator') {
                return redirect('/dashboard');
            }
            abort(403);
        }
        return $next($request);
    }
}
