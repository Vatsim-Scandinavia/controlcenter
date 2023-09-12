<?php

namespace App\Console\Commands;

use App\Helpers\OAuthHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OAuthController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateMemberData extends Command
{
    protected $oauthHelper;

    protected $controller;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:member:data {user?*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command updates users data, so we keep our user information up to date.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(OAuthHelper $oauthHelper, Controller $controller)
    {
        parent::__construct();
        $this->oauthHelper = $oauthHelper;
        $this->controller = $controller;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $optionalUserIdFilter = $this->argument('user');

        if (! $optionalUserIdFilter) {
            $users = User::query()->where('refresh_token', '!=', null)->get();
        } else {
            $users = User::findOrFail($optionalUserIdFilter);
        }

        foreach ($users as $user) {

            if (Carbon::parse($user->token_expires)->isPast()) {

                $refresh = $this->oauthHelper->refreshToken($user);

                if (! $refresh) {

                    $user->access_token = null;
                    $user->refresh_token = null;
                    $user->token_expires = null;
                    $user->save();

                    continue;

                }

                $user->access_token = $refresh->access_token;
                $user->refresh_token = $refresh->refresh_token;
                $user->token_expires = now()->addSeconds($refresh->expires_in)->timestamp;
                $user->save();

            }

            $response = $this->oauthHelper->fetchUser($user);

            if ($response && collect($response)->isNotEmpty()) {

                $user->email = OAuthController::getOAuthProperty(config('oauth.mapping_mail'), $response);
                $user->first_name = OAuthController::getOAuthProperty(config('oauth.mapping_first_name'), $response);
                $user->last_name = OAuthController::getOAuthProperty(config('oauth.mapping_last_name'), $response);
                $user->rating = OAuthController::getOAuthProperty(config('oauth.mapping_rating'), $response);
                $user->rating_short = OAuthController::getOAuthProperty(config('oauth.mapping_rating_short'), $response);
                $user->rating_long = OAuthController::getOAuthProperty(config('oauth.mapping_rating_long'), $response);
                $user->region = OAuthController::getOAuthProperty(config('oauth.mapping_region'), $response);
                $user->division = OAuthController::getOAuthProperty(config('oauth.mapping_division'), $response);
                $user->subdivision = OAuthController::getOAuthProperty(config('oauth.mapping_subdivision'), $response);
                $user->save();

            }
        }

    }
}
