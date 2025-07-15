<?php

namespace App\Http\Controllers\Admin\api\v1\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\Admin\api\v1\auth\LoginRequest;
use App\Http\Requests\Admin\api\v1\auth\RegisterRequest;
use App\Http\Controllers\Admin\api\v1\ApiResponseController;
use Illuminate\Http\Response;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;
use Laravel\Passport\PersonalAccessTokenResult;


class AuthController extends ApiResponseController
{
    public function register(RegisterRequest $request)
    {
        // return $request->all();
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'dial_code' => $request->dial_code,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        return $this->success($user, 'Register successfully',200);

    }

    public function login(LoginRequest $request)
    {
        if (Auth::attempt($request->validated())) {
            $user = Auth::user();
            $user['token'] = $user->createToken('laravel10')->accessToken;
            return $this->success($user, 'Login successfully',200);
        }
        return $this->error('Invalid Credentials.', Response::HTTP_UNAUTHORIZED, 'Wrong email or password!');

    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();
        logger($googleUser);
        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'password' => bcrypt(Str::random(16)),
            ]
        );

        $token = $user->createToken('authToken')->accessToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }

    public function logout()
    {
        $user = Auth::user()->token();
        $user->revoke();
        return $this->success('', 'Logout successfully',200);
    }

    public function googleTokenLogin(Request $request)
    {
        $idToken = $request->input('token');

        $client = new \Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]); // same ID as frontend
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json(['error' => 'Invalid Google ID token'], 401);
        }

        // Find or create user
        $user = User::updateOrCreate(
            ['email' => $payload['email']],
            ['name' => $payload['name'], 'google_id' => $payload['sub']]
        );

        $token = $user->createToken('Google Login Token')->accessToken;

        return response()->json(['token' => $token, 'user' => $user]);
    }
}
