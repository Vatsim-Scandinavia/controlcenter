<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

/**
 * This controller controls the front page view when not logged in
 */
class FrontPageController extends Controller
{
    /**
     * Show the landing page if not logged in, or redirect if logged in.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        if (Auth::check()) {
            return redirect()->intended(route('dashboard'));
        }

        return view('front');
    }
}
