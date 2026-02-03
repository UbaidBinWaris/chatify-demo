<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view (email input).
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Display the complete login form (after OTP verification).
     */
    public function showCompleteForm(): View|RedirectResponse
    {
        // Check if email is verified
        if (!session('verified_email') || session('verification_type') !== 'login') {
            return redirect()->route('login')->withErrors([
                'email' => 'Please verify your email first.'
            ]);
        }

        return view('auth.login-complete', [
            'email' => session('verified_email'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // Verify that email was verified via OTP
        if (!session('verified_email') || session('verification_type') !== 'login') {
            return redirect()->route('login')->withErrors([
                'email' => 'Please verify your email first.',
            ]);
        }

        $request->authenticate();

        // Clear session data
        session()->forget(['verified_email', 'verification_type', 'verification_id']);

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            $user->update([
                'active_status' => 0,
                'last_seen_at' => now(),
            ]);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
