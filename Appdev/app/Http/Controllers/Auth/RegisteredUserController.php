<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\OtpVerification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class RegisteredUserController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        if (auth()->check()) {
            return redirect(auth()->user()->is_admin ? route('dashboard') : route('booking'));
        }

        return Inertia::render('auth/Register');
    }

    private function getPasswordRules()
    {
        return [
            'required',
            'confirmed',
            'min:8',
            'regex:/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        ];
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => $this->getPasswordRules(),
            'terms' => 'required|accepted',
        ], [
            'password.regex' => 'The password must contain at least one letter, one number, and one symbol (@$!%*?&).',
            'password.min' => 'The password must be at least 8 characters long.',
        ]);

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $cacheKey = 'otp_registration_' . $request->email;
        Cache::put($cacheKey, [
            'otp' => $otp,
            'user_data' => $request->only(['name', 'email', 'password']),
            'attempts' => 0,
        ], now()->addMinutes(10));

        try {
            Mail::send('emails.otp-verification', ['otp' => $otp, 'name' => $request->name], function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Your LABERS Registration Verification Code');
            });

            return back()->with('message', 'OTP sent successfully to your email.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Failed to send OTP. Please try again.']);
        }
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $cacheKey = 'otp_registration_' . $request->email;
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            return back()->withErrors(['otp' => 'OTP session expired. Please restart the registration process.']);
        }

        $resendKey = 'otp_resend_' . $request->email;
        if (Cache::has($resendKey)) {
            return back()->withErrors(['otp' => 'Please wait before requesting another OTP.']);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        
        $otpData['otp'] = $otp;
        $otpData['attempts'] = 0; 
        Cache::put($cacheKey, $otpData, now()->addMinutes(10));

        Cache::put($resendKey, true, now()->addMinute());

        try {
            Mail::send('emails.otp-verification', ['otp' => $otp, 'name' => $otpData['user_data']['name']], function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('Your LABERS Registration Verification Code');
            });

            return back()->with('message', 'New OTP sent successfully to your email.');
        } catch (\Exception $e) {
            return back()->withErrors(['otp' => 'Failed to send OTP. Please try again.']);
        }
    }

    public function verifyOtpAndRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => 'required|string|size:6',
            'email' => 'required|email',
            'name' => 'required|string|max:255',
            'password' => $this->getPasswordRules(),
            'password_confirmation' => 'required',
            'terms' => 'required|accepted',
        ], [
            'password.regex' => 'The password must contain at least one letter, one number, and one symbol (@$!%*?&).',
            'password.min' => 'The password must be at least 8 characters long.',
        ]);

        if ($request->password !== $request->password_confirmation) {
            throw ValidationException::withMessages([
                'password_confirmation' => ['The password confirmation does not match.'],
            ]);
        }

        $cacheKey = 'otp_registration_' . $request->email;
        $otpData = Cache::get($cacheKey);

        if (!$otpData) {
            throw ValidationException::withMessages([
                'otp' => ['OTP session expired. Please restart the registration process.'],
            ]);
        }

        if ($otpData['attempts'] >= 3) {
            Cache::forget($cacheKey);
            throw ValidationException::withMessages([
                'otp' => ['Too many failed attempts. Please restart the registration process.'],
            ]);
        }

        if ($request->otp !== $otpData['otp']) {
            $otpData['attempts']++;
            Cache::put($cacheKey, $otpData, now()->addMinutes(10));
            
            $remainingAttempts = 3 - $otpData['attempts'];
            throw ValidationException::withMessages([
                'otp' => ["Invalid OTP code. {$remainingAttempts} attempts remaining."],
            ]);
        }

        try {
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.'],
                ]);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => now(), 
            ]);

            event(new Registered($user));

            Cache::forget($cacheKey);

            Cache::forget('otp_resend_' . $request->email);

            return to_route('login')->with('message', 'Registration successful! Please log in with your credentials.');

        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') { 
                throw ValidationException::withMessages([
                    'email' => ['The email has already been taken.'],
                ]);
            }
            
            throw ValidationException::withMessages([
                'otp' => ['An error occurred during registration. Please try again.']
            ]);
        } catch (\Exception $e) {
            \Log::error('Registration error: ' . $e->getMessage());
            throw ValidationException::withMessages([
                'otp' => ['An error occurred during registration. Please try again.']
            ]);
        }
    }

    /**
     * Handle an incoming registration request (kept for backward compatibility)
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
            'password' => $this->getPasswordRules(),
        ], [
            'password.regex' => 'The password must contain at least one letter, one number, and one symbol (@$!%*?&).',
            'password.min' => 'The password must be at least 8 characters long.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        return to_route('login')->with('message', 'Registration successful! Please log in with your credentials.');
    }
}