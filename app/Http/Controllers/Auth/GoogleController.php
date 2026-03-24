<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
       
       $googleUser = Socialite::driver('google')->stateless()->user();

        $avatar = $googleUser->avatar;

        // 1. Check by google_id first
        $user = User::where('google_id', $googleUser->id)->first();

            if (!$user) {
                // 2. Check by email (existing user)
                $user = User::where('email', $googleUser->email)->first();

                if ($user) {
                    // 3. Attach google_id to existing account
                    $user->update([
                        'google_id' => $googleUser->id,
                        'avatar' => $avatar
                    ]);
                } else {
                    // 4. Create new user
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'google_id' => $googleUser->id,
                        'password' => bcrypt('dummy_password'),
                        'avatar' => $avatar
                    ]);
                }
            } else {
                // ✅ ALSO UPDATE avatar on every login (BEST PRACTICE)
                $user->update([
                    'avatar' => $avatar
                ]);
            }

        Auth::login($user);

        return redirect('/dashboard');
    }
}
