<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\TimezoneService;

class TimezoneController extends Controller
{
    /**
     * Update user's timezone preference
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'timezone' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
        ]);

        if (Auth::check()) {
            Auth::user()->update([
                'timezone' => $request->timezone,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Timezone updated successfully',
                'timezone' => $request->timezone,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated',
        ], 401);
    }

    /**
     * Get user's current timezone
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            return response()->json([
                'timezone' => $user->timezone,
                'detected_timezone' => $user->detected_timezone,
                'last_ip' => $user->last_ip,
                'friendly_name' => TimezoneService::getFriendlyTimezoneName($user->timezone),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated',
        ], 401);
    }

    /**
     * Get list of common timezones
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function list()
    {
        return response()->json([
            'timezones' => TimezoneService::getCommonTimezones(),
        ]);
    }
}
