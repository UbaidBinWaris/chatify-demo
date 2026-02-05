<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\TimezoneService;
use Illuminate\Support\Facades\Auth;

class DetectUserTimezone
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();
            $clientIp = TimezoneService::getClientIp();

            // Update last IP if changed
            if ($user->last_ip !== $clientIp) {
                // Detect timezone from IP
                $detectedTimezone = TimezoneService::detectTimezoneFromIp($clientIp);

                // Update user's detected timezone and IP
                $user->update([
                    'detected_timezone' => $detectedTimezone,
                    'last_ip' => $clientIp,
                ]);

                // If user hasn't set a timezone preference, use detected one
                if ($user->timezone === 'UTC' || empty($user->timezone)) {
                    $user->update(['timezone' => $detectedTimezone]);
                }
            }

            // Set the application timezone for this request to user's timezone
            config(['app.display_timezone' => $user->timezone ?? 'UTC']);
        }

        return $next($request);
    }
}
