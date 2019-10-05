<?php
namespace App\Http\Controllers\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Vatsim\OAuth\SSOException;
use Vatsim\OAuth\SSO;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RatingsController;
use App\Http\Controllers\PRatingsController;
use App\Http\Controllers\DivisionsController;
use App\Http\Controllers\TestController;
use App\Models\User;
/**
 * Class AuthController
 * @package App\Http\Controllers\login
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;
    /**
     * @var SSO
     */
    private $sso;
    /**
     * LoginController constructor.
     */
    public function __construct()
    {
        $this->sso = new SSO(
            config('sso.base'),
            config('sso.key'),
            config('sso.secret'),
            config('sso.method'),
            config('sso.cert'),
            config('sso.additionalConfig')
        );
    }
    /**
     * Redirect user to VATSIM SSO for login
     *
     * @throws \Vatsim\OAuth\SSOException
     */
    public function login()
    {
        $this->sso->login(config('sso.return'), function ($key, $secret, $url) {
            session()->put('key', $key);
            session()->put('secret', $secret);
            session()->save();
            header('Location: ' . $url);
            die();
        });
    }
    /**
     * Validate the login and access protected resources, create the user if they don't exist, update them if they do, and log them in
     *
     * @param Request $get
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Vatsim\OAuth\SSOException
     */
    public $newUser;
    public function validateLogin(Request $get)
    {
        try{
            $this->sso->validate(session('key'), session('secret'), $get->input('oauth_verifier'), function ($sso_data, $request) {
                session()->forget('key');
                session()->forget('secret');
                User::updateOrCreate(['id' => $sso_data->id], ['fname' => $sso_data->name_first, 'lname' => $sso_data->name_last]);
                $user = User::find($sso_data->id);
                $subdivision = DivisionsController::getSubdivisionID($sso_data->region, $sso_data->division, $sso_data->subdivision);
                $rating = RatingsController::getRatingID($sso_data->rating);
                $pRatings = PRatingsController::getPRatingsIDs($sso_data->pilot_rating);
                $user->subdivision()->sync($subdivision);
                $user->rating()->sync($rating);
                $user->p_ratings()->sync($pRatings);
                
                Auth::login($user, true);
            });
        } catch (SSOException $e) {
            return redirect()->route('page')->withError($e->getMessage());
        }
        
        return redirect()->intended(route('page'));
    }
    /**
     * Log the user out
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function logout()
    {
        if (! Auth::check()) return redirect()->back();
        Auth::logout();
        return redirect()->route('page')->withSuccess('You have been successfully logged out.');
    }
}