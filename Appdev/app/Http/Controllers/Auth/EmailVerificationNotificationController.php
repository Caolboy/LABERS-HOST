<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse 
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if ($user->is_admin) { 
                return redirect()->intended(route('dashboard', absolute: false));
            }

            return redirect()->intended(route('booking', absolute: false));
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
