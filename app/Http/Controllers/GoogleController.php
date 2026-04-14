<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver("google")->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver("google")->user();

            // 1. Try to find user by google_id
            $user = User::where("google_id", $googleUser->id)->first();

            if (!$user) {
                // 2. If not found, try to find by email (might have registered with password)
                $user = User::where("email", $googleUser->email)->first();

                if ($user) {
                    // Update existing user with Google ID
                    $user->update(["google_id" => $googleUser->id]);
                } else {
                    // 3. Create a brand new user
                    $user = User::create([
                        "name" => $googleUser->name,
                        "email" => $googleUser->email,
                        "google_id" => $googleUser->id,
                        "password" => Hash::make(Str::random(24)), // Dummy password
                    ]);
                }
            }

            Auth::login($user);
            return redirect()->intended("/dashboard");
        } catch (\Exception $e) {
            return redirect("/login")->with(
                "error",
                "Something went wrong with Google Login.",
            );
        }
    }
}
