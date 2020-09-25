<?php

namespace App\Http\Controllers;

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class OAuthController extends GenericProvider
{
    /**
     * @var GenericProvider
     */
    private $provider;

    /**
     * Initializes the provider variable.
     */
    public function __construct()
    {
        parent::__construct([
            'clientId'                => config('oauth.id'),    // The client ID assigned to you by the provider
            'clientSecret'            => config('oauth.secret'),   // The client password assigned to you by the provider
            'redirectUri'             => route('login'),
            'urlAuthorize'            => config('oauth.base').'/oauth/authorize',
            'urlAccessToken'          => config('oauth.base').'/oauth/token',
            'urlResourceOwnerDetails' => config('oauth.base').'/api/user',
        ]);
    }

    /**
     * Gets an (updated) user token
     * @param Token $token
     * @return Token
     * @return null
     */
    public static function updateToken($token)
    {
        $controller = new OAuthController;
        try {
            return $controller->getAccessToken('refresh_token', [
                'refresh_token' => $token->getRefreshToken()
            ]);
        } catch (IdentityProviderException $e) {
            return null;
        }
    }
}
