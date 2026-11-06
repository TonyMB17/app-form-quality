<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function create()
    {
        return Inertia::render('Login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'dni' => ['required', 'regex:/^[0-9]{8}$/'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'dni.required' => 'Ingresa tu DNI.',
            'dni.regex' => 'El DNI debe tener ocho dígitos.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);
        $key = 'login:'.hash('sha256', $credentials['dni'].'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['dni' => 'Demasiados intentos. Intenta nuevamente en '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['dni' => 'DNI o contraseña incorrectos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();

        return redirect()->intended($request->user()->role === 'administrador' ? '/tablero' : '/mi-establecimiento');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role === 'digitador' && $user->establecimiento, 403, 'No tienes un establecimiento asignado para digitar.');

        return Inertia::render('MiEstablecimiento', [
            'perfil' => [
                'nombre' => $user->name,
                'dni' => $user->dni,
                'establecimiento' => [
                    'codigo' => $user->establecimiento->codigo_renipress,
                    'nombre' => $user->establecimiento->nombre,
                ],
            ],
        ]);
    }
}
