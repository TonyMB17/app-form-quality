<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            $user = User::find($request->user()->id);
            if (!$user || !$user->activo || (int) $request->session()->get('auth_version', 0) !== $user->auth_version) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->route('login')->withErrors(['dni' => 'Tu acceso fue desactivado o tu sesión terminó. Contacta al administrador si no puedes ingresar.']);
            }
        }
        return $next($request);
    }
}
