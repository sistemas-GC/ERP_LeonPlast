<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Usuario;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'nombre_usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $this->ensureIsNotRateLimited($request);

        $loginInput = trim($request->input('nombre_usuario'));
        $primaryField = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'nombre_usuario';
        $secondaryField = ($primaryField === 'email') ? 'nombre_usuario' : 'email';

        $remember = $request->filled('remember');

        // Intento 1: campo principal detectado (email o nombre_usuario)
        $attemptSuccess = Auth::guard('web')->attempt([
            $primaryField => $loginInput,
            'password' => $request->input('password'),
            'activo' => 1,
        ], $remember);

        // Intento 2: si no coincidió, probar con el otro campo por compatibilidad
        if (!$attemptSuccess) {
            $attemptSuccess = Auth::guard('web')->attempt([
                $secondaryField => $loginInput,
                'password' => $request->input('password'),
                'activo' => 1,
            ], $remember);
        }

        if ($attemptSuccess) {
            RateLimiter::clear($this->throttleKey($request));

            $request->session()->regenerate();
            
            $user = Auth::user();
            $user->ultimo_login = Carbon::now();
            $user->save();

            return $this->authenticated($request, $user);
        }

        RateLimiter::hit($this->throttleKey($request));

        throw ValidationException::withMessages([
            'nombre_usuario' => ['Las credenciales son incorrectas o la cuenta está inactiva.'],
        ]);
    }

    protected function authenticated(Request $request, $user)
    {
        // Redirección profesional basada en nombres de rutas
        if ($user->rol === 'Administrador') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->rol === 'Supervisor') {
            return redirect()->route('supervisor.dashboard');
        } elseif ($user->rol === 'Especialista') {
            return redirect()->route('especialista.dashboard');
        } elseif ($user->rol === 'ALMACEN') {
            return redirect()->route('almacen.dashboard');
        }

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    protected function ensureIsNotRateLimited(Request $request)
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'nombre_usuario' => ['Demasiados intentos. Intente en ' . ceil($seconds / 60) . ' minutos.'],
        ]);
    }

    protected function throttleKey(Request $request)
    {
        return Str::transliterate(Str::lower($request->input('nombre_usuario')).'|'.$request->ip());
    }
}