<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
   public function create()
    {
        
        if (Auth::check()) {

            if (strtolower(Auth::user()->role) == 'administrator') {
                return redirect()->route('dashboard');
            }

            if (strtolower(Auth::user()->role) == 'vendor') {
                return redirect()->route('vendor.dashboard');
            }
        }

        
        return Inertia::render('LoginComponent');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email'   => 'required|email',
            'password'=> 'required',
        ]);

        if (Auth::attempt($credentials)) {
            
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on user role
            if ($user->role === 'vendor') {
                return redirect()->intended(route('vendor.dashboard'));
            }

            if ($user->role === 'administrator') {
                return redirect()->intended(route('dashboard'));
            }
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials are incorrect.',
        ])->onlyInput('email');
    }
   
}