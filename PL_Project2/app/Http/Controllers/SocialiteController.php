<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function handleProviderCallback(Request $request)
    {
        $provider = $request->provider;
        $validated = $this->validateProvider($provider);
        if (!is_null($validated))
            return $validated;
        $providerUser = Socialite::driver($provider)->userFromToken($request->token);
        $providerId = Provider::where('provider_id', $providerUser->getId())->first();
        if ($providerId) {
            $userCreated = User::firstOrCreate(
                [
                    'email' => $providerUser->getEmail()
                ],
                [
                    'email_verified_at' => Carbon::now(),
                    'is_email_verified' => 1,
                    'name' => $providerUser->getName(),
                    'prof_img_url' => $providerUser->getAvatar(),
                ]
            );
            $userCreated->providers()->updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_id' => $providerUser->getId(),
                    'user_id' => $userCreated->id,
                ]
            );
            $token = $userCreated->createToken('token-name')->plainTextToken;
            $data["user"] = AuthController::user(User::find($userCreated->id));
            $data["token_type"] = 'Bearer';
            $data["access_token"] = $token;
            return response()->json($data, 200);
        }
        return response()->json(['message' => __('messages.There seems to be a problem')], 400);
    }

    protected function validateProvider($provider)
    {
        if (!in_array($provider, ['google'])) {
            return response()->json(["message" => __("messages.Please login using google only")], 400);
        }
    }
}
