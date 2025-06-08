<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse 
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            if ($user->is_admin) {
                return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
            }

            return redirect()->intended(route('booking', absolute: false) . '?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        } 

        if ($user->is_admin) {
            return redirect()->intended(route('dashboard', absolute: false) . '?verified=1');
        }

        return redirect()->intended(route('booking', absolute: false) . '?verified=1');
    }
}
