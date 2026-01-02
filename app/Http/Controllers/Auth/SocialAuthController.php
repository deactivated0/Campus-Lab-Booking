<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    protected $providers = ['github','facebook','google','twitter','apple'];

    public function redirect($provider)
    {
        if (!in_array($provider, $this->providers)) {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    public function callback(Request $request, $provider)
    {
        if (!in_array($provider, $this->providers)) {
            abort(404);
        }

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'Social login failed');
        }

        // Need email to match/create users
        $email = $socialUser->getEmail();
        if (!$email) {
            return redirect()->route('login')->with('error', 'No email returned from provider');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $userData = [
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? $email,
                'email' => $email,
                'password' => bcrypt(bin2hex(random_bytes(16))),
            ];

            // provider-specific fields (store provider ID in column like github_id)
            if (method_exists($socialUser, 'getId') && $socialUser->getId()) {
                $userData["{$provider}_id"] = $socialUser->getId();
            }

            if (method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                $userData['avatar_url'] = $socialUser->getAvatar();
            }

            $user = User::create($userData);
            try { $user->assignRole('Student'); } catch (\Throwable $e) {}
        } else {
            // update avatar/provider id if missing
            $needsUpdate = false;
            if (empty($user->avatar_url) && method_exists($socialUser, 'getAvatar') && $socialUser->getAvatar()) {
                $user->avatar_url = $socialUser->getAvatar();
                $needsUpdate = true;
            }
            if (method_exists($socialUser, 'getId') && $socialUser->getId()) {
                $col = "{$provider}_id";
                if (empty($user->{$col})) {
                    $user->{$col} = $socialUser->getId();
                    $needsUpdate = true;
                }
            }
            if ($needsUpdate) $user->save();
        }
        Auth::login($user);

        return redirect()->route('dashboard');
    }
}
