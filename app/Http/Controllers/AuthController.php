<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function redirectGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Redirect to Google OAuth page.
     *
     * Uses Laravel Socialite to start the OAuth flow.
     *
     * @return \Illuminate\Http\RedirectResponse
     */

    public function callbackGoogle()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Student'),
                'google_id' => $googleUser->getId(),
                'avatar_url' => $googleUser->getAvatar(),
                'password' => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
            ]
        );

        if (!$this->userHasAnyRole($user, ['Admin','LabStaff','Student'])) {
            $user->assignRole('Student');
        }

        Auth::login($user, true);

        return redirect()->route('dashboard');
    }

    /**
     * Handle Google OAuth callback.
     *
     * Finds or creates a `User` by email, assigns a default `Student` role if none
     * of the expected roles are present, logs the user in and redirects to the dashboard.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
}
