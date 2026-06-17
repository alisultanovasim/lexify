<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if ($user && is_null($user->password)) {
            return back()->withErrors([
                'email' => 'Bu hesab Google ilə qeydiyyatdan keçib. Zəhmət olmasa Google ilə daxil olun.',
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended('/dashboard');
        }

        // For AJAX / axios requests (Accept: application/json), return JSON 422
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'E-poçt və ya şifrə səhvdir.',
                'errors'  => [
                    'email' => ['E-poçt və ya şifrə səhvdir.'],
                ],
            ], 422);
        }

        return back()->withErrors([
            'email' => 'E-poçt və ya şifrə səhvdir.',
        ])->onlyInput('email');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
