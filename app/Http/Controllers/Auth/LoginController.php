<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ActivityLogController;
use App\User;
use App\Group;
use Illuminate\Http\Request;
use League\OAuth2\Client\Token;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OAuthController;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * This controller handles authenticating users for the application and
 * redirecting them to your home screen. The controller uses a trait
 * to conveniently provide its functionality to your applications.
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $provider;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->provider = new OAuthController();
    }

    public function login(Request $request)
    {
        if (! $request->has('code') || ! $request->has('state')) {
            $authorizationUrl = $this->provider->getAuthorizationUrl(); // Generates state
            $request->session()->put('oauthstate', $this->provider->getState());
			return redirect()->away($authorizationUrl);
        } else if ($request->input('state') !== session()->pull('oauthstate')) {
            return redirect()->route('front')->withError("Something went wrong, please try again (state mismatch).");
        } else {
            return $this->verifyLogin($request);
        }
    }

    protected function verifyLogin(Request $request)
    {
        try {
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->input('code')
            ]);
            
        } catch (IdentityProviderException $e) {
            return redirect()->route('front')->withError("Authentication error: ".$e->getMessage());
        }
        $resourceOwner = json_decode(json_encode($this->provider->getResourceOwner($accessToken)->toArray()));

        if (!isset($resourceOwner->data->id)) {
            return redirect()->route('front')->withError("You did not grant all data which is required to use this service.");
        }

        $account = $this->completeLogin($resourceOwner, $accessToken);

        // Login the user and don't remember the session forever
        auth()->login($account, false);

        $authLevel = "User";
        if(isset(\Auth::user()->group)) $authLevel = Group::find(\Auth::user()->group)->name;
        ActivityLogController::info("Logged in with ".$authLevel." access");

        return redirect()->intended(route('dashboard'))->withSuccess('Login Successful');
    }

    protected function completeLogin($resourceOwner, $token)
    {
        $account = User::updateOrCreate(
            ['id' => $resourceOwner->data->id],
            ['last_login' => \Carbon\Carbon::now()]
        );

        $account->save();

        return $account;
    }

    public function logout()
    {
        ActivityLogController::info("Logged out.");
        auth()->logout();

        return redirect(route('front'))->withSuccess('You have been successfully logged out');
    }
}