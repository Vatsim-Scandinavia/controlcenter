<?php

namespace App\Helpers;

use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OAuthHelper
{
    protected $client;

    protected $baseUrl;

    public function __construct()
    {
        $this->client = $this->client();
        $this->baseUrl = config('oauth.base');
    }

    protected function client()
    {
        return new Client([
            'base_uri' => $this->baseUrl,
            'headers' => [
                'Accept' => 'application/json',
                'Content-type' => 'application/json',
            ],
        ]);
    }

    public function fetchUser(User $user)
    {
        try {
            $response = $this->client->get($this->baseUrl . '/api/user', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $user->access_token,
                ],
            ]);

            return json_decode($response->getBody());
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            return false;
        } catch (\Exception $exception) {
            Log::critical($exception->getMessage());
        }

        return false;

    }

    public function refreshToken(User $user)
    {
        try {
            $response = $this->client->post($this->baseUrl . '/oauth/token', [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $user->refresh_token,
                    'client_id' => config('oauth.id'),
                    'client_secret' => config('oauth.secret'),
                    'scope' => implode(' ', config('oauth.scopes')),
                ],
            ]);

            if ($response->getStatusCode() != 200) {
                return false;
            }

            return json_decode($response->getBody());
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            return false;
        } catch (\Exception $exception) {
            Log::critical($exception->getMessage());
        }

    }
}
