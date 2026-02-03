<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EmailVerification;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view (email input).
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Display the complete registration form (after OTP verification).
     */
    public function showCompleteForm(): View|RedirectResponse
    {
        // Check if email is verified
        if (!session('verified_email') || session('verification_type') !== 'registration') {
            return redirect()->route('register')->withErrors([
                'email' => 'Please verify your email first.'
            ]);
        }

        return view('auth.register-complete', [
            'email' => session('verified_email'),
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Verify that email was verified via OTP
        if (!session('verified_email') || session('verification_type') !== 'registration') {
            return redirect()->route('register')->withErrors([
                'email' => 'Please verify your email first.',
            ]);
        }

        $verifiedEmail = session('verified_email');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Double-check email is still unique
        if (User::where('email', $verifiedEmail)->exists()) {
            return redirect()->route('register')->withErrors([
                'email' => 'This email is already registered.',
            ]);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $verifiedEmail,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        // Clear session data
        session()->forget(['verified_email', 'verification_type', 'verification_id']);

        Auth::login($user);

        return redirect(RouteServiceProvider::HOME);
    }
}

