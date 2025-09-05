<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Auth simple hardcodeado desde .env
        $adminEmail = env('ADMIN_EMAIL', 'admin@test.com');
        $adminPassword = env('ADMIN_PASSWORD', 'password123');

        // Verificar credenciales admin hardcodeadas
        if ($credentials['email'] === $adminEmail && $credentials['password'] === $adminPassword) {
            session(['authenticated' => true, 'user' => [
                'name' => 'Admin User',
                'email' => $adminEmail
            ]]);
            
            return redirect()->route('dashboard');
        }

        // Verificar usuario en base de datos
        $user = User::where('email', $credentials['email'])->first();
        
        if ($user && Hash::check($credentials['password'], $user->password)) {
            session(['authenticated' => true, 'user' => [
                'name' => $user->name,
                'email' => $user->email
            ]]);
            
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden.',
        ])->withInput($request->except('password'));
    }

    public function logout(Request $request)
    {
        session()->forget(['authenticated', 'user']);
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showRegister()
    {
        if (session('authenticated')) {
            return redirect()->route('dashboard');
        }
        
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Crear sesión manual similar al login
        session(['authenticated' => true, 'user' => [
            'name' => $user->name,
            'email' => $user->email
        ]]);

        return redirect()->route('dashboard');
    }
}
