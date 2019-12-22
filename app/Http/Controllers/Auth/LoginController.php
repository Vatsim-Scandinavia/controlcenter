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
use App\User;
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
        try{
            $this->sso->login(config('sso.return'), function ($key, $secret, $url) {
                session()->put('key', $key);
                session()->put('secret', $secret);
                session()->save();
                header('Location: ' . $url);
                die();
            });
        } catch (SSOException $e) {
            return redirect()->route('front')->withErrors(['error' => $e->getMessage()]);
        }
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
                User::updateOrCreate(['id' => $sso_data->id]);
                Auth::login(User::find($sso_data->id), true);
            });
        } catch (SSOException $e) {
            return redirect()->route('front')->withErrors(['error' => $e->getMessage()]);
        }
        
        return redirect()->intended(route('dashboard'));
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
        return redirect()->to('/')->withSuccess('You have been successfully logged out.');
    }
}