<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function create() {
        return Inertia::render('RegisterComponent');
    }

    public function register(Request $request) {

        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone'    => 'nullable|size:10',
            'address'  => 'nullable|string',
        ]);
        
        User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => Hash::make($data['password']),
            'phone'    => $data['phone'] ?? null,
            'address'  => $data['address'] ?? null,
            'Role'     => 'vendor',
        ]);

        return redirect()->route('login')->with('message', 'Registration successful!');
    }
}