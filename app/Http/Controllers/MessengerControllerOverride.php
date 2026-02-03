<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use App\Models\User;
use Carbon\Carbon;

class MessengerControllerOverride extends Controller
{
    /**
     * Set user's active status
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function setActiveStatus(Request $request)
    {
        $activeStatus = $request['status'] > 0 ? 1 : 0;
        $updates = ['active_status' => $activeStatus];
        
        // If going offline, update last_seen_at
        if ($activeStatus == 0) {
            $updates['last_seen_at'] = Carbon::now();
        }
        
        $status = User::where('id', Auth::user()->id)->update($updates);
        return Response::json([
            'status' => $status,
        ], 200);
    }
}
