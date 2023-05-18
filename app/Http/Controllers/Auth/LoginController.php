<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OAuthController;
use App\Models\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
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

    /**
     * Login the user
     *
     * @param  \Illuminate\Http\Request  $request request to proccess
     * @return mixed
     */
    public function login(Request $request)
    {
        if (! $request->has('code') || ! $request->has('state')) {
            $authorizationUrl = $this->provider->getAuthorizationUrl(); // Generates state
            $request->session()->put('oauthstate', $this->provider->getState());

            return redirect()->away($authorizationUrl);
        } elseif ($request->input('state') !== session()->pull('oauthstate')) {
            return redirect()->route('front')->withError('Something went wrong, please try again (state mismatch).');
        } else {
            return $this->verifyLogin($request);
        }
    }

    /**
     * Verify the login of the user's request before proceeding
     *
     * @param  \Illuminate\Http\Request  $request request to proccess
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function verifyLogin(Request $request)
    {
        try {
            $accessToken = $this->provider->getAccessToken('authorization_code', [
                'code' => $request->input('code'),
            ]);
        } catch (IdentityProviderException $e) {
            return redirect()->route('front')->withError('Authentication error: ' . $e->getMessage());
        }
        $resourceOwner = json_decode(json_encode($this->provider->getResourceOwner($accessToken)->toArray()));

        if (! isset($resourceOwner->data->id)) {
            return redirect()->route('front')->withError('You did not grant all data which is required to use this service.');
        }

        $account = $this->completeLogin($resourceOwner, $accessToken);

        // Login the user and don't remember the session forever
        auth()->login($account, false);

        $authLevel = 'User';
        if (\Auth::user()->groups->count() > 0) {
            $authLevel = User::find(\Auth::user()->id)->groups->sortBy('id')->first()->name;
            ActivityLogController::warning('ACCESS', 'Logged in with ' . $authLevel . ' access');
        } else {
            ActivityLogController::info('ACCESS', 'Logged in with ' . $authLevel . ' access');
        }

        return redirect()->intended(route('dashboard'))->withSuccess('Login Successful');
    }

    /**
     * Complete the login by creating or updating the existing account and last login timestamp
     *
     * @param  mixed  $resourceOwner
     * @param  mixed  $token
     * @return \App\Models\User User's account data
     */
    protected function completeLogin($resourceOwner, $token)
    {
        $account = User::updateOrCreate(
            ['id' => $resourceOwner->data->id],
            ['last_login' => \Carbon\Carbon::now()]
        );

        $account->save();

        return $account;
    }

    /**
     * Log out he user and redirect to front page
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        ActivityLogController::info('ACCESS', 'Logged out.');
        auth()->logout();

        return redirect(route('front'))->withSuccess('You have been successfully logged out');
    }
}
