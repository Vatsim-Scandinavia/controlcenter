<?php

namespace App\Http\Controllers;

use App\GlobalSetting;
use Illuminate\Http\Request;
use anlutro\LaravelSettings\Facade as Setting;

class GlobalSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.globalsettings');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Group  $group
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $data = $request->validate([
            'trainingEnabled' => '',
            'trainingShowEstimate' => '',
            'trainingSOP' => 'required|url'
        ]);

        isset($data['trainingEnabled']) ? $trainingEnabled = true : $trainingEnabled = false;
        isset($data['trainingShowEstimate']) ? $trainingShowEstimate = true : $trainingShowEstimate = false;
        
        Setting::set('trainingEnabled', $trainingEnabled);
        Setting::set('trainingShowEstimate', $trainingShowEstimate);
        Setting::set('trainingSOP', $data['trainingSOP']);
        Setting::save();

        return redirect()->intended(route('admin.settings'))->withSuccess("Server settings successfully changed");
    }

}
