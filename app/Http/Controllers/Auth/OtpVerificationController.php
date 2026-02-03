<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerification;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class OtpVerificationController extends Controller
{
    /**
     * Generate and send OTP to email
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'type' => 'required|in:registration,login',
        ]);

        // For registration, check if email already exists
        if ($request->type === 'registration') {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'This email is already registered. Please login instead.',
                ], 422);
            }
        }

        // For login, check if user exists
        if ($request->type === 'login') {
            $existingUser = \App\Models\User::where('email', $request->email)->first();
            if (!$existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address.',
                ], 422);
            }
        }

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Delete any existing non-verified OTPs for this email and type
        EmailVerification::where('email', $request->email)
            ->where('type', $request->type)
            ->where('verified', false)
            ->delete();

        // Create new OTP record
        $verification = EmailVerification::create([
            'email' => $request->email,
            'otp' => $otp,
            'type' => $request->type,
            'expires_at' => Carbon::now()->addMinutes(10), // OTP valid for 10 minutes
            'verified' => false,
        ]);

        // Send OTP via email
        Notification::route('mail', $request->email)
            ->notify(new OtpNotification($otp, $request->type));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email address.',
        ]);
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'type' => 'required|in:registration,login',
        ]);

        // Find the latest OTP for this email and type
        $verification = EmailVerification::where('email', $request->email)
            ->where('type', $request->type)
            ->where('verified', false)
            ->latest()
            ->first();

        if (!$verification) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP.',
            ], 422);
        }

        if ($verification->isExpired()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 422);
        }

        if (!$verification->isValid($request->otp)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP. Please try again.',
            ], 422);
        }

        // Mark as verified
        $verification->update(['verified' => true]);

        // Store verified email in session for next step
        session([
            'verified_email' => $request->email,
            'verification_type' => $request->type,
            'verification_id' => $verification->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully.',
            'redirect' => $request->type === 'registration' 
                ? route('register.complete')
                : route('login.complete'),
        ]);
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request)
    {
        return $this->sendOtp($request);
    }
}
