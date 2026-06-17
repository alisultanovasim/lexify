<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Google\Client as GoogleClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function handle(Request $request): RedirectResponse
    {
        $request->validate(['token' => 'required|string']);

        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));

        try {
            $payload = $client->verifyIdToken($request->input('token'));
        } catch (\Exception $e) {
            Log::error('Google token verification failed: ' . $e->getMessage());
            return back()->withErrors(['email' => 'Google ilə giriş alınmadı. Yenidən cəhd edin.']);
        }

        if (!$payload) {
            return back()->withErrors(['email' => 'Google token doğrulanmadı.']);
        }

        $name = trim(($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? ''))
            ?: $payload['name']
            ?? $payload['email'];

        $user = User::firstOrCreate(
            ['email' => $payload['email']],
            [
                'name'              => $name,
                'google_id'         => $payload['sub'],
                'email_verified_at' => now(),
                'password'          => null,
            ]
        );

        if (!$user->google_id) {
            $user->update(['google_id' => $payload['sub']]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
